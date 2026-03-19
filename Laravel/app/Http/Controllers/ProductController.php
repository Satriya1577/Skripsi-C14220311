<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductsImport;
use App\Models\Material;
use App\Models\ProductTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\ProductionBatch; 
use Carbon\Carbon; 

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::orderBy('id', 'desc')->paginate(10);
        return view('products.index',compact('products'));
    }

    public function create()
    {
        return view('products.form');
    }

    public function edit(Product $product)
    {
        return view('products.form', compact('product'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. VALIDASI INPUT (Khusus Create, pastikan code unik)
        $request->validate([
            'code'                => 'required|string|max:50|unique:products,code', 
            'name'                => 'required|string|max:255',
            'packaging'           => 'nullable|string|max:255',
            'is_manual_lead_time' => 'required|in:manual,automatic',
            'min_lead_time_days'  => 'nullable|integer|min:1', 
            'max_lead_time_days'  => 'nullable|integer|min:1|gte:min_lead_time_days', 
            'batch_size'          => 'required|integer|min:1',
            'price'               => 'nullable|numeric|min:0', 
            'current_stock'       => 'nullable|integer|min:0',
            'cost_price'          => 'nullable|numeric|min:0', 
        ]);

        // 2. HITUNG LEAD TIME
        // Passing null karena produk belum memiliki ID / belum ada di database
        $leadTime = $this->calculateLeadTime($request, null);

        // 3. PROSES SIMPAN KE DATABASE
        try {
            DB::beginTransaction();

            $product = Product::create([
                'code'                => $request->code,
                'name'                => $request->name,
                'packaging'           => $request->packaging,
                'is_manual_lead_time' => $request->is_manual_lead_time,
                'min_lead_time_days'  => $leadTime['min'],
                'max_lead_time_days'  => $leadTime['max'],
                'lead_time_average'   => $leadTime['avg'],
                'batch_size'          => $request->batch_size,
                'price'               => $request->price ?? 0,
                'current_stock'       => $request->current_stock ?? 0,
                'cost_price'          => $request->cost_price ?? 0,
                'committed_stock'     => 0,
                'safety_stock'        => 0,
            ]);

            // Catat Transaksi Saldo Awal jika ada
            if ($request->filled('current_stock') && $request->current_stock > 0) {
                ProductTransaction::create([
                    'product_id'                 => $product->id,
                    'transaction_date'           => now(),
                    'type'                       => 'adjustment',
                    'qty'                        => $request->current_stock,
                    'cost_price'                 => $request->cost_price ?? 0,
                    'current_stock_balance'      => $request->current_stock,
                    'product_name_snapshot'      => $product->name,
                    'product_packaging_snapshot' => $product->packaging,
                    'description'                => 'Initial Stock Opname (Saldo Awal)',
                ]);
            }

            DB::commit();
            return redirect()->route('products.index')->with('success', 'Product created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create product: ' . $e->getMessage());
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        // 1. VALIDASI INPUT (Abaikan unique code untuk ID produk ini sendiri)
        $request->validate([
            'code'                => 'required|string|max:50|unique:products,code,' . $product->id, 
            'name'                => 'required|string|max:255',
            'packaging'           => 'nullable|string|max:255',
            'is_manual_lead_time' => 'required|in:manual,automatic',
            'min_lead_time_days'  => 'nullable|integer|min:1', 
            'max_lead_time_days'  => 'nullable|integer|min:1|gte:min_lead_time_days', 
            'batch_size'          => 'required|integer|min:1',
            'price'               => 'nullable|numeric|min:0', 
            // Note: current_stock & cost_price dihilangkan karena Stock Opname biasanya tidak diizinkan diubah via update produk.
        ]);

        // 2. HITUNG LEAD TIME (Sertakan object product untuk mengecek history batch)
        $leadTime = $this->calculateLeadTime($request, $product);

        // 3. PROSES UPDATE
        $product->update([
            'code'                => $request->code,
            'name'                => $request->name,
            'packaging'           => $request->packaging,
            'is_manual_lead_time' => $request->is_manual_lead_time,
            'min_lead_time_days'  => $leadTime['min'],
            'max_lead_time_days'  => $leadTime['max'],
            'lead_time_average'   => $leadTime['avg'],
            'batch_size'          => $request->batch_size,
            'price'               => $request->price ?? 0,
        ]);

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }


    /**
     * Helper Method untuk Menghitung Lead Time
     */
    private function calculateLeadTime(Request $request, ?Product $product)
    {
        // Jika Mode MANUAL
        if ($request->is_manual_lead_time === 'manual') {
            $min = $request->min_lead_time_days ?? 1;
            $max = $request->max_lead_time_days ?? 3;
            
            return [
                'min' => $min,
                'max' => $max,
                'avg' => ($min + $max) / 2
            ];
        } 
        
        // Jika Mode AUTOMATIC
        else {
            // Jika product dikirim (Kasus Update)
            if ($product) {
                $batches = ProductionBatch::where('product_id', $product->id)
                    ->whereNotNull('start_date')
                    ->whereNotNull('end_date')
                    ->orderBy('end_date', 'desc')
                    ->take(30)
                    ->get();

                if ($batches->count() > 0) {
                    $totalDays = 0;
                    foreach ($batches as $batch) {
                        $start = Carbon::parse($batch->start_date);
                        $end   = Carbon::parse($batch->end_date);
                        $days  = $start->diffInDays($end); 
                        $totalDays += ($days == 0 ? 1 : $days);
                    }
                    
                    $avgDays = (float) ($totalDays / $batches->count());
                    
                    return [
                        'min' => (int) round($avgDays),
                        'max' => (int) round($avgDays),
                        'avg' => $avgDays
                    ];
                }
            }

            // Fallback: Jika insert produk baru (belum ada histori) ATAU histori 0
            return [
                'min' => 1,
                'max' => 1,
                'avg' => 1
            ];
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(product $product)
    {
        //$materials = Material::orderBy('name')->get();

        $transactions = $product->transactions()
                            // ->orderBy('transaction_date', 'desc')
                            // ->orderBy('created_at', 'desc')
                            ->paginate(5); // 10 data per halaman

        $materials = Material::where('is_active', true)->orderBy('name')->get();
        return view('products.show', compact('product', 'materials', 'transactions'));
    }

    /**
    * Remove the specified resource from storage.
    */
    public function destroy(product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }

    public function storeAdjustment(Request $request)
    {
        $request->validate([
            'product_id'   => 'required|exists:products,id',
            'actual_qty'   => 'required|integer|min:0', // Stok fisik (Satuan Unit)
            'notes'        => 'nullable|string|max:255',
            'manual_price' => 'nullable|numeric|min:0.01' // Hanya dipakai jika HPP sistem 0
        ]);

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($request->product_id);

            // 1. HITUNG SELISIH (DELTA)
            // Product tidak punya satuan beli, jadi langsung pakai input user
            $systemQty = $product->current_stock;
            $actualQty = $request->actual_qty;
            
            // Selisih (+ berarti Surplus, - berarti Loss)
            $deltaQty = $actualQty - $systemQty;

            if ($deltaQty == 0) {
                return back()->with('info', "Stok fisik sudah sesuai dengan sistem.");
            }

            // 2. LOGIC HARGA & HPP (COST PRICE)
            // Gunakan Cost Price (HPP), BUKAN Selling Price
            $transactionCost = $product->cost_price;

            if ($deltaQty > 0) {
                // --- KASUS SURPLUS (+) ---
                // Jika HPP sistem 0, cek input manual
                if ($transactionCost == 0) {
                    if ($request->filled('manual_price')) {
                        $transactionCost = $request->manual_price;
                        
                        // Update Master Product (Initial HPP)
                        $product->update(['cost_price' => $transactionCost]);
                    } else {
                        throw new \Exception("HPP sistem saat ini Rp 0. Wajib mengisi 'Estimasi HPP' untuk mencatat surplus stok.");
                    }
                }
                // Jika HPP ada, pakai HPP sistem (Standard Opname tidak mengubah HPP)
            
            } else {
                // --- KASUS LOSS (-) ---
                // WAJIB pakai HPP sistem saat ini.
                $transactionCost = $product->cost_price;
            }

            // 3. EKSEKUSI UPDATE DATABASE

            // A. Update Stok Master
            $product->update(['current_stock' => $actualQty]);

            // B. Catat Transaksi
            $typeLabel = $deltaQty > 0 ? "Surplus (Found)" : "Loss (Usage)";
            $desc      = "Opname: {$typeLabel}. Fisik: {$actualQty} Unit. " . $request->notes;

            ProductTransaction::create([
                'product_id'            => $product->id,
                'type'                  => 'adjustment',
                'qty'                   => $deltaQty, // Simpan +/-
                'cost_price'            => $transactionCost, // HPP saat transaksi
                'current_stock_balance' => $actualQty, // Saldo akhir setelah adjustment
                'product_name_snapshot'   => $product->name,
                'product_packaging_snapshot' => $product->packaging,
                'transaction_date'      => now(),
                'description'           => $desc,
            ]);

            DB::commit();
            
            return back()->with('success', "Stock Adjustment berhasil. Selisih: " . ($deltaQty > 0 ? '+' : '') . $deltaQty . " Unit");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function updateProductLeadTimeSafetyStock() 
    {
        // Ambil semua produk (Bisa juga difilter hanya yang aktif jika ada status aktif)
        $products = Product::all();

        DB::beginTransaction();
        try {
            foreach ($products as $product) {
                
                // 1. Hitung Statistik Lead Time Produksi (Min, Max, Average)
                $leadTimeStats = $this->calculateLeadTimeStats($product);
                $minLeadTime = $leadTimeStats['min'];
                $maxLeadTime = $leadTimeStats['max'];
                $avgLeadTime = $leadTimeStats['average'];

                // 2. Hitung Statistik Demand/Penjualan (Daily Average & Max)
                $demandStats = $this->calculateDemandStats($product);
                $avgDailyDemand = $demandStats['average'];
                $maxDailyDemand = $demandStats['max'];

                // 3. Hitung Safety Stock (Kuantitas)
                // Rumus: (Max Lead Time * Max Daily Demand) - (Average Lead Time * Average Daily Demand)
                $maxExpectedDemand = $maxLeadTime * $maxDailyDemand;
                $averageExpectedDemand = $avgLeadTime * $avgDailyDemand;

                $safetyStock = max(0, $maxExpectedDemand - $averageExpectedDemand);

                // 4. Update data produk
                $product->update([
                    'min_lead_time_days' => $minLeadTime,
                    'max_lead_time_days' => $maxLeadTime,
                    'lead_time_average'  => $avgLeadTime,
                    'safety_stock'       => ceil($safetyStock), // Dibulatkan ke atas agar aman
                ]);
            }
            
            DB::commit();
            return redirect()->back()->with('success', 'Lead Time dan Safety Stock seluruh produk berhasil diperbarui.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    private function calculateLeadTimeStats(Product $product) 
    {
        // Jika mode manual, gunakan data yang sudah ada di database
        if ($product->is_manual_lead_time === 'manual') {
            return [
                'min'     => $product->min_lead_time_days,
                'max'     => $product->max_lead_time_days,
                'average' => ($product->min_lead_time_days + $product->max_lead_time_days) / 2,
            ];
        }

        // Jika mode automatic, tarik 30 batch produksi terakhir yang SUDAH SELESAI
        $recentBatches = ProductionBatch::where('product_id', $product->id)
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->orderBy('end_date', 'desc')
            ->take(30)
            ->get();

        // Fallback jika produk ini belum pernah diproduksi sama sekali
        if ($recentBatches->isEmpty()) {
            return [
                'min'     => $product->min_lead_time_days,
                'max'     => $product->max_lead_time_days,
                'average' => ($product->min_lead_time_days + $product->max_lead_time_days) / 2,
            ];
        }

        $leadTimes = [];
        foreach ($recentBatches as $batch) {
            $start = Carbon::parse($batch->start_date);
            $end = Carbon::parse($batch->end_date);
            
            // Hitung selisih hari. Kita gunakan max(1, ...) agar jika produksi 
            // selesai di hari yang sama (selisih 0), tetap dihitung butuh waktu minimal 1 hari (proses pabrik)
            $days = max(1, $start->diffInDays($end));
            $leadTimes[] = $days;
        }

        return [
            'min'     => min($leadTimes),
            'max'     => max($leadTimes),
            'average' => array_sum($leadTimes) / count($leadTimes),
        ];
    }

    private function calculateDemandStats(Product $product) 
    {
        // Ambil data 30 hari ke belakang dari hari ini
        $thirtyDaysAgo = now()->subDays(30)->format('Y-m-d');
        
        // Cari transaksi penjualan barang keluar (sales_out)
        $transactions = ProductTransaction::where('product_id', $product->id)
            ->where('type', 'sales_out')
            ->where('transaction_date', '>=', $thirtyDaysAgo)
            ->get();

        if ($transactions->isEmpty()) {
            return ['average' => 0, 'max' => 0];
        }

        // Kelompokkan kuantitas (absolut) berdasarkan tanggal (Daily Demand)
        $dailyDemand = $transactions->groupBy('transaction_date')->map(function ($dayTransactions) {
            return abs($dayTransactions->sum('qty')); // abs() karena barang keluar mungkin minus di DB
        });

        // Rata-rata harus dibagi 30 hari kalender penuh, BUKAN dibagi jumlah hari yg ada transaksinya.
        return [
            'average' => $dailyDemand->sum() / 30,
            'max'     => $dailyDemand->max()
        ];
    }
}
