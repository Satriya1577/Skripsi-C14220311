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
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    // public function show(forecast $forecast)
    // {
    //     //
    // }

    public function show(Product $product)
    {
        // 1. Load Product Data
        $product = Product::where('code', $product->code)->firstOrFail();

        // 2. Tentukan Posisi Waktu (Based on Sales Data)
        // Ambil transaksi terakhir untuk menentukan "Bulan Ini" dan "Bulan Depan"
        $lastSaleDate = Sales::where('product_id', $product->id)->max('transaction_date');
        
        // Jika belum ada sales, default ke bulan ini
        $currentMonthDate = $lastSaleDate ? Carbon::parse($lastSaleDate)->startOfMonth() : Carbon::now()->startOfMonth();
        $nextMonthDate = $currentMonthDate->copy()->addMonth();

        // 3. Cek Status Job (Untuk Loading State tombol Generate)
        $latestJob = ForecastingJob::where('product_id', $product->id)->latest()->first();
        $jobStatus = $latestJob ? $latestJob->status : 'idle';

        // 4. Ambil Data Analitik (RMSE, MAPE, Charts)
        $config = SarimaConfig::where('product_id', $product->id)->latest('last_trained_at')->first();

        // Default values jika belum pernah forecast
        $metrics = [
            'rmse' => $config ? number_format($config->rmse, 2) : '-',
            'mape' => $config ? number_format($config->mape, 2) . '%' : '-',
        ];

        // 5. Siapkan Data Chart & Tabel Log
        // Gabungkan Validation Log (Masa Lalu) dan Forecast (Masa Depan)
        $chartData = [
            'labels' => [],
            'actual' => [],
            'forecast' => []
        ];
        
        $logTable = collect([]);

        if ($config) {
            // Ambil log validasi (Test Data)
            $validationLogs = $config->validationLogs()->orderBy('period', 'asc')->get();

            $startDate = $validationLogs->min('period') ?? Carbon::now()->startOfMonth();
            
            // Ambil hasil forecast
            $forecasts = Forecast::where('product_id', $product->id)
                            // ->where('forecast_period', '>=', $validationLogs->min('period')) // Sync timeline
                            ->where('forecast_period', '>=',$startDate)
                            ->orderBy('forecast_period', 'asc')
                            ->get();

            // Mapping untuk Chart
            $labels = $validationLogs->pluck('period')->merge($forecasts->pluck('forecast_period'))
                        ->unique()->sort()->values();
            
            foreach($labels as $date) {
                $chartData['labels'][] = Carbon::parse($date)->format('M Y');
                
                // Cari data actual di log
                $log = $validationLogs->firstWhere('period', $date);
                $chartData['actual'][] = $log ? $log->actual_qty : null;

                // Cari data forecast (bisa dari validation log atau forecast table)
                // Prioritas: Forecast table (future) > Log (past prediction)
                $fc = $forecasts->firstWhere('forecast_period', $date);
                $val = $fc ? $fc->predicted_amount : ($log ? $log->predicted_qty : null);
                $chartData['forecast'][] = $val;

                // Siapkan data untuk Tabel Kiri Bawah
                $logTable->push([
                    'period' => Carbon::parse($date)->format('M Y'),
                    'actual' => $log ? number_format($log->actual_qty) : '-',
                    'predicted' => number_format($val),
                    'type' => $fc ? 'Forecast' : 'Validation', // Jika ada di tabel forecast berarti Future/Result
                    'is_forecast' => (bool)$fc // Helper untuk styling CSS
                ]);
            }
        }

        // 6. Ambil Production Plan (Tabel Kanan Bawah)
        $productionPlans = ProductionPlan::where('product_id', $product->id)
                            ->orderBy('period', 'desc')
                            ->take(6) // Ambil 6 bulan terakhir/kedepan saja agar rapi
                            ->get();

        $sarimaConfig = SarimaConfig::where('product_id', $product->id)->first();

        return view('forecast.show', compact(
            'product', 'jobStatus', 'metrics', 'sarimaConfig',
            'currentMonthDate', 'nextMonthDate', 
            'chartData', 'logTable', 'productionPlans'
        ));
    }

    public function generate(Request $request, Product $product)
    {
        $product = Product::where('code', $product->code)->firstOrFail();
        
        $request->validate([
            'forecastPeriod' => 'required|in:thisPeriod,nextPeriod'
        ]);

        // 1. Tentukan Tanggal Target & Mode Cutoff
        $lastSaleDate = Sales::where('product_id', $product->id)->max('transaction_date');
        $baseDate = $lastSaleDate ? Carbon::parse($lastSaleDate)->startOfMonth() : Carbon::now()->startOfMonth();

        if ($request->forecastPeriod == 'thisPeriod') {
            // Mode Backtesting: Target = Bulan Terakhir Data Sales
            $targetDate = $baseDate->copy(); 
            $cutoffLast = true; 
        } else {
            // Mode Forecasting: Target = Bulan Depan
            $targetDate = $baseDate->copy()->addMonth();
            $cutoffLast = false; 
        }

        // --- [LOGIC BARU] CEK STATUS PRODUCTION PLAN ---
        // Cek apakah Production Plan untuk periode target tersebut sudah 'approved' atau 'completed'
        $isPlanLocked = ProductionPlan::where('product_id', $product->id)
            ->where('period', $targetDate->format('Y-m-d')) // Pastikan format tanggal sama dengan DB
            ->whereIn('status', ['approved', 'completed'])   // Status yang "mengunci" forecast
            ->exists();

        if ($isPlanLocked) {
            return back()->with('error', 'Cannot regenerate forecast. Production Plan for ' . $targetDate->format('M Y') . ' has already been Approved or Completed.');
        }
        // -----------------------------------------------

        // 2. Cek Antrian Job
        $isBusy = ForecastingJob::where('product_id', $product->id)
                    ->whereIn('status', ['pending', 'processing'])
                    ->exists();

        if ($isBusy) {
            return back()->with('error', 'Analysis is currently running. Please wait.');
        }

        // 3. Buat Job Baru
        $job = ForecastingJob::create([
            'product_id' => $product->id,
            'target_period' => $targetDate,
            'status' => 'pending',
            'message' => 'Queued via Web UI'
        ]);

        // 4. Dispatch ke Queue Worker
        RunSarimaJob::dispatch($job->id, $cutoffLast);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Job started', 'job_id' => $job->id]);
        }

        return redirect()->back()->with('success', 'Forecasting sedang berjalan...');
        //return back()->with('success', 'Forecast generation started for ' . $targetDate->format('M Y'));
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


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(forecast $forecast)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, forecast $forecast)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(forecast $forecast)
    {
        //
    }
}
