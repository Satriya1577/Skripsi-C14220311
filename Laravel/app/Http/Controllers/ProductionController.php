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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        return view('production.details', compact('productionPlan', 'product', 'batches', 'totalProduced', 'targetQty', 'remainingQty', 'materialRecommendations'));
    }

    public function storeBatch(Request $request) 
    {
        // 1. Validasi input (hanya butuh ID Plan karena data lain sudah otomatis)
        $request->validate([
            'production_plan_id' => 'required|exists:production_plans,id',
        ]);

        // Ambil data Production Plan beserta relasi Product-nya
        $plan = ProductionPlan::with('product')->findOrFail($request->production_plan_id);

        // 2. PENGECEKAN: Apakah masih ada batch yang belum selesai (end_date NULL)?
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

    public function storeRealization(Request $request) {
        // Kurangi qty material sejumlah qty * qty_need_bom untuk setiap material yang terlibat di produk ini (back-flushing)
        // Masukkan data material transaction untuk setiap material yang terlibat di produk ini
        // Tambahkan stok produk jadi
        // Masukkan data product transaction untuk produksi ini
        // Update harga hpp produk menggunakan moving average
        // Cek apakah batch sudah selesai (qty_produced >= target), jika ya update end_date di batch menjadi hari ini, jika belum biarkan end_date tetap null (In Progress)

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

            // 1. Buat Data Realisasi Produksi
            $realization = ProductionRealization::create([
                'production_batch_id' => $batch->id,
                'qty_produced'        => $qtyProduced,
                'production_date'     => $today,
            ]);

            $totalProductionCost = 0;

            foreach ($product->productMaterials as  $pm) {
                $material = $pm->material;

                // Hitung kebutuhan material: (Qty Produksi * Kebutuhan BOM per produk)
                $totalNeeded = $qtyProduced * $pm->amount_needed;
                
                // Kurangi stok material (Back-flushing)
                $material->current_stock -= $totalNeeded;
                $material->save();

                // Kalkulasi biaya untuk material ini
                $totalCostForThisMaterial = $totalNeeded * $material->price_per_unit;
                $totalProductionCost += $totalCostForThisMaterial;

                // Catat history pemakaian material (Tipe: OUT)
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
