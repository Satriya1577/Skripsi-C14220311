<?php

namespace App\Http\Controllers;

use App\Models\validation_log;
use App\Http\Controllers\Controller;
use App\Models\MaterialTransaction;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionPlan;
use App\Models\ProductionRealization;
use App\Models\ProductTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use function Symfony\Component\Clock\now;

class ProductionController extends Controller
{
    public function index() {
        $products = Product::orderBy('id', 'asc')->paginate(10);
        return view('production.index', compact('products'));
    }

    public function showPlan(Product $product) {
        $productionPlans = ProductionPlan::where('product_id', $product->id)
            ->orderBy('period', 'desc') // Opsional: Urutkan tanggal terbaru
            ->paginate(10);
        return view('production.plan', compact('product', 'productionPlans'));
    }

    public function createCurrentMonthProductionPlan(Request $request, Product $product)
    {
        // --- 0. CEK HAK AKSES ---
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'purchase', 'production'])) {
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
        $targetDate = Carbon::now()->startOfMonth();

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

        $params = [
            $product->order_p ?? 1, $product->order_d ?? 1, $product->order_q ?? 1,
            $product->seasonal_P ?? 1, $product->seasonal_D ?? 1, $product->seasonal_Q ?? 1, 
            $product->seasonal_s ?? 12
        ];

        // Nilai Default jika API Python gagal atau data sales kosong
        $targetDateStr  = Carbon::parse($targetDate)->format('Y-m-d');

        $forecastVal    = 0;
        $rmse           = 0;
        $mape           = 0;
        $newSafetyStock = $product->safety_stock ?? 0;
        $recQty         = 0;

        ProductionPlan::updateOrCreate(
            [
                'product_id' => $product->id, 
                'period'     => $targetDateStr
            ],
            [
                'forecast_qty'               => $forecastVal,
                'current_stock_snapshot'     => $product->current_stock,
                'safety_stock_snapshot'      => $newSafetyStock,
                'recommended_production_qty' => $recQty,
                'rmse'                       => $rmse,
                'mape'                       => $mape,
                'order_p'                    => $params[0],
                'order_d'                    => $params[1],
                'order_q'                    => $params[2],
                'seasonal_P'                 => $params[3],
                'seasonal_D'                 => $params[4],
                'seasonal_Q'                 => $params[5],
                'seasonal_s'                 => $params[6],
                'status'                     => 'draft',
            ]
        );
        return redirect()->back()->with('success', "Plan produksi untuk bulan " . $targetDate->format('M Y') . " berhasil dibuat.");
    }

    public function showPlanDetails(ProductionPlan $productionPlan) 
    {
        $product = $productionPlan->product;
        
        // Ambil list batch yang terkait dengan plan ini
        $batches = ProductionBatch::with('productionRealizations')
            ->where('production_plan_id', $productionPlan->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Menghitung total qty_produced khusus untuk Plan ini

        $batchIds = ProductionBatch::where('production_plan_id', $productionPlan->id)->pluck('id');
        $totalProduced = ProductionRealization::whereIn('production_batch_id', $batchIds)->sum('qty_produced');        
        $targetQty = $productionPlan->approved_production_qty ?? $productionPlan->recommended_production_qty;
        $remainingQty = max(0, $targetQty - $totalProduced);


        // 5. Query Material Recommendations (BOM & Stock Status)
        // Tentukan jumlah produksi yang akan jadi patokan
        // Gunakan 'approved_production_qty' jika sudah di-approve, 
        // jika belum gunakan 'recommended_production_qty'
        $targetQty = $productionPlan->status === 'approved' || $productionPlan->status === 'completed'
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

        return view('production.details', compact('productionPlan', 'product', 'batches', 'totalProduced', 'targetQty', 'remainingQty', 'materialRecommendations'));
    }

    public function storeBatch(Request $request) 
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'production'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk membuat batch produksi yang baru.')->withInput();
        }

        // Validasi input (hanya butuh ID Plan karena data lain sudah otomatis)
        $request->validate([
            'production_plan_id' => 'required|exists:production_plans,id',
        ]);

        // Ambil data Production Plan beserta relasi Product-nya
        $plan = ProductionPlan::with('product')->findOrFail($request->production_plan_id);

        // 1. Pengecekan: Apakah batch dibuat di periode yang benar sesuai dengan plan?
        // Kenapa kok masih bisa bikin batch walaupun sudah mencapai target plan produksi?
        // karena bisa aja demand nya meleset dan tidak sesuai dengan plan yang sudah disetujui diawal

        $currentPeriod = now()->format('Y-m');
        //$currentPeriod = '2026-08'; // Example value, replace with actual current period
        $planPeriod    = Carbon::parse($plan->period)->format('Y-m');

        if ($currentPeriod !== $planPeriod) {
            $currentMonthName = now()->format('F Y');
            //$currentMonthName = 'August 2026';
            $planMonthName    = \Carbon\Carbon::parse($plan->period)->format('F Y');

            return redirect()->back()->with('error', "GAGAL: Tidak dapat membuat batch baru. Plan ini diperuntukkan untuk periode {$planMonthName}, sedangkan saat ini Anda berada di bulan {$currentMonthName}.");
        }


        // 2. Pengecekan: Apakah masih ada batch yang belum selesai (end_date NULL)?
        $unfinishedBatchExists = ProductionBatch::where('production_plan_id', $plan->id)
            ->whereNull('end_date')
            ->exists();

        if ($unfinishedBatchExists) {
            return redirect()->back()
                ->with('error', 'GAGAL: Batch Produksi yang sedang berjalan (In Progress). Selesaikan batch sebelumnya terlebih dahulu.');
        }

        // 3. GENERATE BATCH NUMBER: Format B-ProdukCode-<random>
        $productCode = $plan->product->code;
        $randomStr = strtoupper(Str::random(5)); // 5 Karakter acak (Huruf & Angka)
        $batchNumber = "B-{$productCode}-{$randomStr}";

        // 4. BUAT BATCH BARU 
        ProductionBatch::create([
            'production_plan_id' => $plan->id,
            'product_id'         => $plan->product_id,
            'batch_number'       => $batchNumber,
            'qty_produced'       => $plan->product->batch_size, // Mengambil langsung dari master product
            'start_date'         => now()->format('Y-m-d'),     // Otomatis tanggal hari ini
            'end_date'           => null,                       // Null menandakan status "In Progress"
        ]);

        return redirect()->back()->with('success', "Batch produksi [{$batchNumber}] berhasil dimulai dengan target {$plan->product->batch_size} Pcs.");
    }

    public function showRealization(ProductionBatch $productionBatch) 
    {
        $productionPlan = $productionBatch->productionPlan;
        $product = $productionBatch->product;
        
        // Ambil data historis dari tabel realization
        $realizations = $productionBatch->productionRealizations()->orderBy('production_date', 'desc')->get();
        
        // Kalkulasi
        $totalRealized = $realizations->sum('qty_produced');
        $remainingBatchQty = $productionBatch->qty_produced - $totalRealized;
        $batch = $productionBatch;
        
        return view('production.realization', compact(
            'batch', 'productionPlan', 'product', 'realizations', 'totalRealized', 'remainingBatchQty'
        ));
    }

    public function storeRealization(Request $request) 
    {
        // Cek apakah input realisasi produksi lebih besar dari plan dan jumlah material mencukupi untuk produksi sejumlah qty_produced yang diinput
        // Kurangi qty material sejumlah qty * qty_need_bom untuk setiap material yang terlibat di produk ini (back-flushing)
        // Masukkan data material transaction untuk setiap material yang terlibat di produk ini
        // Tambahkan stok produk jadi
        // Masukkan data product transaction untuk produksi ini
        // Update harga hpp produk menggunakan moving average
        // Cek apakah batch sudah selesai (qty_produced >= target), jika ya update end_date di batch menjadi hari ini, jika belum biarkan end_date tetap null (In Progress)
        // Cek apakah total realisasi produksi untuk plan ini sudah mencapai target, jika ya update status plan menjadi completed, jika belum biarkan status tetap (draft/approved)
       
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'production'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk membuat riwayat realisasi produksi baru.')->withInput();
        }

        $request->validate([
            'production_batch_id' => 'required|exists:production_batches,id',
            'qty_produced'        => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {

            $batch = ProductionBatch::with('product.productMaterials.material')->findOrFail($request->production_batch_id);
            $product = $batch->product;
            $qtyProduced = $request->qty_produced;
            $today = now()->format('Y-m-d');

            // 0. Pengecekan Apakah Input Realisasi < sisa per Batch & Cek Ketersediaan Material

            $realizations = $batch->productionRealizations()->orderBy('production_date', 'desc')->get();
            $totalRealized = $realizations->sum('qty_produced');
            $remainingBatchQty = $batch->qty_produced - $totalRealized;

            if ($qtyProduced > $remainingBatchQty) {
                DB::rollBack(); // transaksi dibatalkan 
                return redirect()->back()->with('error', "Gagal: Qty Produksi ({$qtyProduced} pcs) melebihi sisa batch ({$remainingBatchQty} pcs).")->withInput();
            }

            // Cek Ketersediaan Material
            $shortageMaterials = [];
            foreach ($product->productMaterials as $pm) {
                $material = $pm->material;
                $totalNeeded = $qtyProduced * $pm->amount_needed;

                // testing 
                // $totalNeeded = 9999999999999;
                // Konversi stok ke format riilnya jika menggunakan conversion factor di tampilan
                if ($material->current_stock < $totalNeeded) {
                    $shortage = $totalNeeded - $material->current_stock;
                    
                    // Format angka yang kurang agar lebih mudah dibaca user
                    $materialConversionFactor = $material->conversion_factor > 0 ? $material->conversion_factor : 1;
                    $formattedShortage = number_format($shortage / $materialConversionFactor, 2, ',', '.');
                    $totalNeededFormatted = number_format($totalNeeded / $materialConversionFactor, 2, ',', '.');
                    $shortageMaterials[] = "- {$material->name}: Kurang {$formattedShortage} {$material->purchase_unit} (Dibutuhkan: " . $totalNeededFormatted . " {$material->purchase_unit}) \n";
                }
            }

            // Jika ada satu saja material yang kurang, gagalkan proses
            if (!empty($shortageMaterials)) {
                DB::rollBack(); // transaksi dibatalkan 
                $errorMessage = "Stok material tidak mencukupi untuk memproduksi {$qtyProduced} pcs. Berikut rinciannya:\n" . implode("\n", $shortageMaterials) . "\n\nSilahkan buat PO terlebih dahulu.";
                return redirect()->back()->with('error', $errorMessage)->withInput();
            }

            // 1. Buat Data Realisasi Produksi
            $realization = ProductionRealization::create([
                'production_batch_id' => $batch->id,
                'qty_produced'        => $qtyProduced,
                'production_date'     => $today,
            ]);

            $totalProductionCost = 0;

            foreach ($product->productMaterials as  $pm) {
                $material = $pm->material;

                // 2. Hitung kebutuhan material: (Qty Produksi * Kebutuhan BOM per produk)
                $totalNeeded = $qtyProduced * $pm->amount_needed;
                
                // Kurangi stok material (Back-flushing)
                $material->current_stock -= $totalNeeded;
                $material->save();

                // Kalkulasi biaya untuk material ini
                $totalCostForThisMaterial = $totalNeeded * $material->price_per_unit;
                $totalProductionCost += $totalCostForThisMaterial;

                // 3. Catat history pemakaian material (Tipe: OUT)
                MaterialTransaction::create([
                    'material_id'               => $material->id,
                    'transaction_date'          => $today,
                    'type'                      => 'out',
                    'qty'                       => -$totalNeeded, // Negatif karena barang keluar
                    'price_per_unit'            => $material->price_per_unit,
                    'total_price'               => $totalCostForThisMaterial,
                    
                    // Snapshot Data Material
                    'material_name_snapshot'              => $material->name,
                    'material_packaging_size_snapshot'    => $material->packaging_size ?? 0,
                    'material_packaging_unit_snapshot'    => $material->packaging_unit ?? '-',
                    'material_conversion_factor_snapshot' => $material->conversion_factor,
                    'purchase_unit_snapshot'              => $material->purchase_unit ?? '-',
                    'material_unit_snapshot'              => $material->unit,
                    
                    'current_stock_balance'     => $material->current_stock,
                    'production_realization_id' => $realization->id,
                    'description'               => "Pemakaian produksi untuk Batch: {$batch->batch_number}",
                ]);
            }

            // 4. Update HPP Produk Menggunakan Moving Average
            // Rumus: ((Stok Lama * HPP Lama) + (Stok Baru * Biaya Pembuatan Baru)) / Total Stok
            $costPerUnitProduced = $qtyProduced > 0 ? ($totalProductionCost / $qtyProduced) : 0;
            
            $oldStock = $product->current_stock;
            $oldCostPrice = $product->cost_price;
            $newTotalStock = $oldStock + $qtyProduced;

            $newMovingAverageHPP = $oldCostPrice; // Default
            if ($newTotalStock > 0) {
                $oldInventoryValue = $oldStock * $oldCostPrice;
                $newInventoryValue = $qtyProduced * $costPerUnitProduced;
                $newMovingAverageHPP = ($oldInventoryValue + $newInventoryValue) / $newTotalStock;
            }
            
            // 5. Tambahkan Stok Produk Jadi & Update Harga
            $product->current_stock += $qtyProduced;
            $product->cost_price = $newMovingAverageHPP;
            $product->save();

            // 6. Masukkan Data Product Transaction
            ProductTransaction::create([
                'product_id'                 => $product->id,
                'transaction_date'           => $today,
                'type'                       => 'production_in',
                'qty'                        => $qtyProduced, // Positif karena barang masuk
                'cost_price'                 => $costPerUnitProduced, // Nilai HPP SAAT INI (bukan average)
                'current_stock_balance'      => $product->current_stock,
                
                // Snapshot
                'product_name_snapshot'      => $product->name,
                'product_packaging_snapshot' => $product->packaging,
                
                'production_realization_id'  => $realization->id,
                'description'                => "Realisasi Produksi Batch: {$batch->batch_number}",
            ]);

            // 7. Cek Apakah Batch Sudah Selesai (Target Terpenuhi)
            $totalRealizedForBatch = ProductionRealization::where('production_batch_id', $batch->id)->sum('qty_produced');

            if ($totalRealizedForBatch >= $batch->qty_produced) {
                $batch->end_date = $today;
                $batch->save();
            }

            // 8. Cek apakah plan sudah selesai
            $plan = ProductionPlan::find($batch->production_plan_id);
            if ($plan) {
                // Ambil semua ID batch yang berada di bawah plan ini
                $allBatchIds = ProductionBatch::where('production_plan_id', $plan->id)->pluck('id');
                
                // Hitung total akumulasi realisasi dari semua batch tersebut
                $totalRealizedForPlan = ProductionRealization::whereIn('production_batch_id', $allBatchIds)->sum('qty_produced');

                // Jika total realisasi mencapai atau melampaui plan yang disetujui, update status
                if ($totalRealizedForPlan >= $plan->approved_production_qty) {
                    $plan->status = 'completed';
                    $plan->save();
                }
            }

            // Jika semua berjalan lancar, commit (simpan permanen) ke database
            DB::commit();

            return redirect()->back()->with('success', "Berhasil mencatat realisasi produksi sebanyak {$qtyProduced} pcs. Stok material dan produk telah diperbarui.");

        } catch (\Exception $e) {
            // Jika ada error di baris manapun, batalkan semua perubahan DB
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mencatat realisasi: ' . $e->getMessage());
        }
    }
    
}
