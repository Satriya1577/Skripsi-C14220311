<?php

namespace App\Http\Controllers;

use App\Models\forecast;
use App\Http\Controllers\Controller;
use App\Jobs\RunSarimaJob;
use App\Models\Forecast as ModelsForecast;
use App\Models\ForecastingJob;
use App\Models\Product;
use App\Models\ProductionPlan;
use App\Models\Sales;
use App\Models\SarimaConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForecastController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::orderBy('id', 'asc')->paginate(10);
        return view('forecast.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function show(Product $product)
    {
        $productionPlans = ProductionPlan::where('product_id', $product->id)
            ->orderBy('period', 'desc') // Opsional: Urutkan tanggal terbaru
            ->paginate(10);
        return view('forecast.show', compact('product','productionPlans'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    public function showChart(ProductionPlan $productionPlan)
    {
        // 1. Ambil Product
        $product = $productionPlan->product;

        // 2. Ambil Validation Logs (Data Grafik)
        // Pastikan di model ProductionPlan ada relasi: public function validationLogs() { return $this->hasMany(ValidationLog::class); }
        $logs = $productionPlan->validationLogs()
            ->orderBy('period', 'asc')
            ->get();

        // 3. Format Data untuk Chart.js
        $labels = [];
        $actuals = [];
        $forecasts = [];

        foreach ($logs as $log) {
            // Format Label (Bulan Tahun)
            $labels[] = Carbon::parse($log->period)->format('M Y');

            // Data Actual (Bisa null jika ini adalah periode masa depan murni)
            $actuals[] = $log->actual_qty;

            // Data Predicted
            $forecasts[] = $log->predicted_qty;
        }

        // Struktur Data untuk JavaScript
        $chartData = [
            'labels' => $labels,
            'actual' => $actuals,
            'forecast' => $forecasts,
        ];

        // 4. Ambil Metrics dari Production Plan
        $metrics = [
            'rmse' => $productionPlan->rmse,
            'mape' => $productionPlan->mape,
        ];

        // 5. Query Material Recommendations (BOM & Stock Status)
        // Tentukan jumlah produksi yang akan jadi patokan
        // Gunakan 'approved_production_qty' jika sudah di-approve, 
        // jika belum gunakan 'recommended_production_qty'
        $targetQty = $productionPlan->status === 'approved' 
                        ? $productionPlan->approved_production_qty 
                        : $productionPlan->recommended_production_qty;

        // Query untuk mengambil list material yang dibutuhkan produk ini
        $materialRecommendations = DB::table('product_materials')
            ->join('materials', 'product_materials.material_id', '=', 'materials.id')
            ->where('product_materials.product_id', $product->id)
            ->select('materials.code','materials.name',
            // Gunakan purchase_unit jika ada, kalau kosong gunakan base unit
            DB::raw('COALESCE(materials.purchase_unit, materials.unit) as unit'),
            
            // KEBUTUHAN (Qty Need)
            // Rumus: (Target Produksi * Amount Needed) / Conversion Factor
            // (Agar satuannya menjadi Purchase Unit)
            DB::raw("({$targetQty} * product_materials.amount_needed) / materials.conversion_factor as qty_need"),
            
            // STOK SAAT INI
            // Rumus: Current Stock / Conversion Factor
            DB::raw("materials.current_stock / materials.conversion_factor as current_stock"),
            
            // SEDANG DALAM PERJALANAN (OTW)
            // Menggunakan kolom ordered_stock (Sudah dipesan ke Supplier)
            DB::raw("materials.ordered_stock / materials.conversion_factor as purchase_otw")
        )->get()->map(function ($item) {
            return (object) [
                'material'      => (object) [
                    'code' => $item->code, 
                    'name' => $item->name, 
                    'unit' => $item->unit
                ],
                'qty_need'      => $item->qty_need,
                'current_stock' => $item->current_stock,
                'purchase_otw'  => $item->purchase_otw
            ];
        });

    return view('forecast.chart', compact('product', 'productionPlan', 'metrics', 'chartData', 'materialRecommendations'));
}

    /**
    * Approve a production plan with user-specified quantity
    */
    public function approvePlan(Request $request, ProductionPlan $productionPlan)
    {
        // --- 0. CEK HAK AKSES ---
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'production'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk menyetujui plan produksi.')->withInput();
        }

        $validated = $request->validate([
            'approved_production_qty' => 'required|numeric|min:0',
        ]);

        $productionPlan->update([
            'approved_production_qty' => $validated['approved_production_qty'],
            'status' => 'approved',
        ]);

        return back()->with('success', 'Production plan approved successfully!');
    }

    public function generate(Request $request, Product $product)
    {
        // --- 0. CEK HAK AKSES ---
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'production'])) {
            $msg = 'Terjadi kesalahan: Anda tidak memiliki akses.';
            
            if ($request->wantsJson()) {
                return response()->json(['error' => $msg], 403);
            }
            return redirect()->back()->with('error', $msg)->withInput();
        }
        // Pastikan produk ada
        $product = Product::findOrFail($product->id);

        // 1. Tentukan Tanggal Target (Selalu 1 Bulan ke Depan dari Sekarang)
        // Contoh: Sekarang Feb 2026 -> Target Mar 2026
        $targetDate = Carbon::now()->addMonth()->startOfMonth();

        // 2. CEK STATUS PRODUCTION PLAN
        // Jangan izinkan generate ulang jika plan bulan tersebut sudah disetujui/selesai
        $isPlanLocked = ProductionPlan::where('product_id', $product->id)
            ->where('period', $targetDate->format('Y-m-d'))
            ->whereIn('status', ['approved', 'completed'])
            ->exists();

        if ($isPlanLocked) {
            $msg = 'Cannot regenerate. Plan for ' . $targetDate->format('M Y') . ' is already locked.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $msg], 422); 
            }
            return back()->with('error', $msg);     
        }

        // 3. Cek Antrian Job (Agar tidak double klik)
        $isBusy = ForecastingJob::where('product_id', $product->id)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($isBusy) {
            $msg = 'Analysis is currently running. Please wait.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        // 4. Buat Job Baru
        $job = ForecastingJob::create([
            'product_id' => $product->id,
            'target_period' => $targetDate,
            'status' => 'pending',
            'message' => 'Queued via Web UI'
        ]);

        // 5. Dispatch ke Queue Worker
        // Parameter cutoffLastMonth dihapus karena logika backtest sudah tidak ada
        RunSarimaJob::dispatch($job->id);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Job started', 'job_id' => $job->id]);
        }

        return redirect()->back()->with('success', 'Forecasting sedang berjalan untuk periode ' . $targetDate->format('F Y'));
    }

    public function checkStatus(Product $product)
    {
        // Ambil job terakhir untuk produk ini
        $job = ForecastingJob::where('product_id', $product->id)
            ->latest()
            ->first();

        if (!$job) {
            return response()->json(['status' => 'idle']);
        }

        return response()->json([
            'status' => $job->status, // pending, processing, completed, atau failed
            'message' => $job->message
        ]);
    }
}
