<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Http\Controllers\Controller;
use App\Models\MaterialTransaction;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaterialController extends Controller
{
    
    // menampilkan list bahan baku yang tersedia di database
    public function index()
    {
        $materials = Material::orderBy('id', 'asc')->paginate(10);
        return view('materials.index',compact('materials'));
    }

    // menampilkan form create bahan baku baru
    public function create()
    {
        return view('materials.form');
    }

    // menampilkan form edit bahan baku yang sudah ada
    public function edit(Material $material)
    {
        return view('materials.form', compact('material'));
    }

    
    // mengubah input menjadi konversi satuan dasar (gram, ml, pcs) untuk memudahkan perhitungan stok dan harga di sistem
    // size: isi per kemasan beli (NETTO)
    // packaging_unit: kg, liter, dozen, dll
    // base_unit: satuan pemakaian (gram, ml, pcs)

    // Contoh : 
    // Beli 1 karung Tepung Terigu @20KG
    // Wujud (category type): mass (padatan) -> category_type
    // Satuan pemakaian: gram (karena padatan) -> unit or base_unit
    // Satuan pembelian:  Karung @20KG -> purchase_unit
    // Isi per kemasan beli NETTO: 20 -> packaging_size
    // Packaging unit: kg (karena kita input 20) -> packaging_unit

    // jadi input parameter calculateConversionFactor adalah:
    // size = 20
    // packagingUnit = kg
    // baseUnit = gram
    // maka hasilnya conversion factor nya adalah 
    // 20 * 1000 (karena gram dan kg) = 20.000 (gram) 

    private function calculateConversionFactor($size, $packagingUnit, $baseUnit)
    {
        $size = (float) $size;

        // Logic Konversi Berat (Target: Gram)
        if ($baseUnit == 'gram') {
            switch ($packagingUnit) {
                case 'kg': return $size * 1000;
                case 'ons': return $size * 100;
                case 'gram': return $size;
                default: return $size; 
            }
        }

        // Logic Konversi Volume (Target: ML)
        if ($baseUnit == 'ml') {
            switch ($packagingUnit) {
                case 'liter': return $size * 1000;
                case 'ml': return $size;
                default: return $size;
            }
        }

        // Logic Satuan Unit (Target: Pcs)
        if ($baseUnit == 'pcs') {
            switch ($packagingUnit) {
                case 'dozen': return $size * 12; 
                case 'pcs': return $size; 
                default: return $size; 
            }
        }

        return $size;
    }

    
    // simpan data dari form create bahan baku ke database
    // menghitung conversionFactor menggunakan function calculateConversionFactor()
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'purchase'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk insert data bahan baku baru.')->withInput();
        }
        // 1. VALIDASI INPUT (TIDAK BERUBAH)
        $request->validate([
            // Identitas
            'code' => 'required|string|unique:materials,code',
            'name' => 'required|string|max:255',
            'category_type' => 'required|in:mass,volume,unit',
            
            // Lead Time (NEW SCHEMA)
            'is_manual_lead_time' => 'required|in:manual,automatic',
            'min_lead_time_days'  => 'nullable|integer|min:1', 
            'max_lead_time_days'  => 'nullable|integer|min:1|gte:min_lead_time_days',

            // Konfigurasi Satuan (VITAL)
            'unit' => 'required|string',             // Base Unit (gram, ml, pcs)
            'purchase_unit' => 'required|string',    // Satuan Beli (kg, karung, box)
            'packaging_size' => 'required|numeric|min:0.0001',
            'packaging_unit' => 'required|string',

            // Saldo Awal (Opsional - Input dalam Satuan Beli)
            'initial_qty_purchase_unit' => 'nullable|numeric|min:0',
            'initial_price_purchase_unit' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        // Hitung Conversion Factor
        $calculatedFactor = $this->calculateConversionFactor(
            $request->packaging_size, 
            $request->packaging_unit, 
            $request->unit
        );

        // --- LOGIKA LEAD TIME ---
        $minLead = $request->min_lead_time_days ?? 1;
        $maxLead = $request->max_lead_time_days ?? 7;
        
        if ($request->is_manual_lead_time === 'manual') {
            $avgLead = ($minLead + $maxLead) / 2;
        } else {
            // Jika Auto tapi baru create, set default placeholder
            $avgLead = 0; 
        }

        try {
            DB::beginTransaction();

            // 2. HITUNG STOK & HARGA BASE (Jika ada saldo awal)
            $initialStockBase = 0;
            $initialPriceBase = 0;
            $hasInitialStock = false;

            if ($request->filled('initial_qty_purchase_unit') && $request->initial_qty_purchase_unit > 0) {
                $hasInitialStock = true;
                $faktor = $calculatedFactor;

                // Konversi Qty ke Base Unit
                $initialStockBase = $request->initial_qty_purchase_unit * $faktor;

                // Konversi Harga ke Base Unit
                $priceInput = $request->initial_price_purchase_unit ?? 0;
                $initialPriceBase = ($faktor > 0) ? ($priceInput / $faktor) : 0;
            }

            // 3. SIMPAN KE TABEL MATERIALS
            $material = Material::create([
                'code' => $request->code,
                'name' => $request->name,
                'category_type' => $request->category_type,
                
                // Lead Time Fields (UPDATED)
                'is_manual_lead_time' => $request->is_manual_lead_time,
                'min_lead_time_days' => $minLead,
                'max_lead_time_days' => $maxLead,
                'lead_time_average' => $avgLead,
                
                // System Calculated Defaults
                'safety_stock' => 0,
                'reorder_point' => 0,

                // Satuan Config
                'unit' => $request->unit,
                'purchase_unit' => $request->purchase_unit,
                'packaging_size' => $request->packaging_size,
                'packaging_unit' => $request->packaging_unit,
                'conversion_factor' => $calculatedFactor,

                // Stok & Harga
                'current_stock' => $initialStockBase,
                'price_per_unit' => $initialPriceBase,
                'ordered_stock' => 0,
                'is_active' => $request->is_active,
            ]);

            // 4. CATAT TRANSAKSI SALDO AWAL (UPDATED SKEMA BARU)
            if ($hasInitialStock) {
                MaterialTransaction::create([
                    'material_id'           => $material->id,
                    'type'                  => 'adjustment', // Gunakan 'adjustment' atau 'in' untuk saldo awal
                    'qty'                   => $initialStockBase,
                    
                    // Harga
                    'price_per_unit'        => $initialPriceBase,
                    'total_price'           => $initialStockBase * $initialPriceBase,
                    
                    'transaction_date'      => now(),
                    'description'           => "Initial Stock: {$request->initial_qty_purchase_unit} {$request->purchase_unit} @ " . number_format($request->initial_price_purchase_unit),

                    // Snapshot Wajib
                    'material_name_snapshot' => $material->name,
                    'material_packaging_size_snapshot' => $material->packaging_size,
                    'material_packaging_unit_snapshot' => $material->packaging_unit,
                    'material_conversion_factor_snapshot' => $material->conversion_factor,
                    'purchase_unit_snapshot' => $material->purchase_unit,
                    'material_unit_snapshot' => $material->unit,
                    'current_stock_balance'  => $initialStockBase, // Karena saldo awal, balance = qty awal
                ]);
            }

            DB::commit();

            return redirect()->route('materials.index')
                             ->with('success', 'Material berhasil ditambahkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                             ->withInput()
                             ->with('error', 'Gagal menyimpan material: ' . $e->getMessage());
        }
    }

    
    // update data bahan baku yang sudah ada di database
    public function update(Request $request, Material $material)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'purchase'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk mengupdate data bahan baku.')->withInput();
        }
        // 1. CEK RIWAYAT TRANSAKSI
        $hasTransaction = MaterialTransaction::where('material_id', $material->id)->exists();

        // 2. VALIDASI DASAR
        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:materials,code,'.$material->id,
            'category_type' => 'required|in:mass,volume,unit',
            'is_active' => 'required|boolean',
            
            // Lead Time (NEW SCHEMA)
            'is_manual_lead_time' => 'required|in:manual,automatic',
            'min_lead_time_days'  => 'nullable|integer|min:1', 
            'max_lead_time_days'  => 'nullable|integer|min:1|gte:min_lead_time_days',

            'packaging_size' => 'required|numeric|min:0.0001',
            'packaging_unit' => 'required|string',
        ];

        // Hitung Faktor Konversi Baru
        $baseUnitForCalc = $request->input('unit', $material->unit);
        $calculatedNewFactor = $this->calculateConversionFactor(
            $request->packaging_size,
            $request->packaging_unit,
            $baseUnitForCalc
        );

        // 3. LOGIC PENGUNCIAN (GUARD)
        // cek apakah material ini sudah ada riwayat transaksi (IN OUT) atau belum

        if ($hasTransaction) {
            // Cek Unit Dasar
            if ($request->unit != $material->unit) {
                return back()->with('error', 'GAGAL: Satuan Dasar tidak boleh diubah karena material ini sudah memiliki riwayat transaksi.');
            }

            // Cek Faktor Konversi (Toleransi float)
            if (abs($calculatedNewFactor - $material->conversion_factor) > 0.001) {
                return back()->with('error', 'GAGAL: Ukuran Kemasan tidak boleh diubah drastis karena material ini sudah memiliki riwayat transaksi.');
            }

            // Cek Satuan Beli
            if ($request->purchase_unit != $material->purchase_unit) {
                return back()->with('error', 'GAGAL: Satuan Beli tidak boleh diubah karena sudah ada riwayat transaksi.');
            }

        } else {
            // JIKA BELUM ADA TRANSAKSI:
            $rules['unit'] = 'required|string';
            $rules['purchase_unit'] = 'required|string';
        }

        $request->validate($rules);

        // --- LOGIKA LEAD TIME UPDATE ---
        $minLead = $request->min_lead_time_days ?? 1;
        $maxLead = $request->max_lead_time_days ?? 7;
        $avgLead = $material->lead_time_average; // Default pakai nilai lama

        if ($request->is_manual_lead_time === 'manual') {
            // Jika manual, hitung ulang rata-rata dari input
            $avgLead = ($minLead + $maxLead) / 2;
        } else {
            // Jika automatic, jangan ubah average lead time (biarkan system/job yang update)
            // Atau logic lain: biarkan nilai lama
        }

        // 4. PROSES UPDATE
        $dataToUpdate = [
            'name' => $request->name,
            'code' => $request->code,
            'category_type' => $request->category_type, 
            'is_active' => $request->is_active,
            
            // Lead Time Update
            'is_manual_lead_time' => $request->is_manual_lead_time,
            'min_lead_time_days' => $minLead,
            'max_lead_time_days' => $maxLead,
            'lead_time_average' => $avgLead,
        ];

        // Hanya update detail satuan jika BELUM ada transaksi
        if (!$hasTransaction) {
            $dataToUpdate['unit'] = $request->unit;
            $dataToUpdate['purchase_unit'] = $request->purchase_unit;
            $dataToUpdate['conversion_factor'] = $calculatedNewFactor;
            $dataToUpdate['packaging_size'] = $request->packaging_size;
            $dataToUpdate['packaging_unit'] = $request->packaging_unit;
        }

        $material->update($dataToUpdate);

        return redirect()->route('materials.index')->with('success', 'Data material berhasil diperbarui.');
    }

    // tampilkan detail bahan baku beserta riwayat mutasi/transaki (IN/OUT/ADJUSTMENT) nya
    public function show(Material $material)
    {
        $transactions = $material->transactions()
            ->orderBy('transaction_date', 'asc')
            ->paginate(5);
        return view('materials.show', compact('material', 'transactions'));
    }

    // hapus data bahan baku dari database, 
    // tapi hanya bisa dihapus jika belum pernah ada transaksi sama sekali 
    // dan material ini tidak dipakai di resep produk manapun
    public function destroy(Material $material)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'purchase'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk menghapus data bahan baku.')->withInput();
        }

        $material = Material::findOrFail($material->id);
        // 1. Cek Riwayat Transaksi
        if ($material->transactions()->exists()) {
            return back()->with('error', 'GAGAL: Material tidak bisa dihapus karena sudah memiliki riwayat transaksi.');
        }

        // 2. Cek Penggunaan di Resep
        if ($material->productMaterials()->exists()) {
            $productName = $material->productMaterials->first()->product->name;
            return back()->with('error', "GAGAL: Material sedang digunakan dalam resep produk '$productName'.");
        }

        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Data material berhasil dihapus permanen.');
    }

    
    // mengupdate stok opname bahan baku setelah dilakukan pengecekan fisik di gudang
    // function ini juga bisa mengupdate harga menggunakan input estimasi harga awal barang
    public function storeAdjustment(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'inventory'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk mengatur data stok opname.')->withInput();
        }
        $request->validate([
            'material_id'  => 'required|exists:materials,id',
            'actual_qty'   => 'required|numeric|min:0', 
            'notes'        => 'nullable|string',
            'manual_price' => 'nullable|numeric|min:0.01' 
        ]);

        try {
            DB::beginTransaction();

            $material = Material::findOrFail($request->material_id);
            
            // 1. SIAPKAN KONVERSI
            $faktor = $material->conversion_factor > 0 ? $material->conversion_factor : 1;

            // 2. HITUNG SELISIH (DELTA)
            // Input User (Satuan Beli) -> Konversi ke Base Unit
            $inputQtyPurchaseUnit = $request->actual_qty;
            $actualQtyInBase      = $inputQtyPurchaseUnit * $faktor;

            // Stok Sistem saat ini (Base Unit)
            $systemQty = $material->current_stock; 
            
            // Selisih (+ berarti Surplus/Masuk, - berarti Loss/Keluar)
            $diffQty = $actualQtyInBase - $systemQty;

            if (abs($diffQty) < 0.0001) { 
                return back()->with('info', "Stok fisik sudah sesuai dengan sistem.");
            }

            // 3. LOGIC HARGA & HPP
            $transactionPrice = $material->price_per_unit;

            if ($diffQty > 0) {
                // SURPLUS
                if ($transactionPrice == 0) {
                    if ($request->filled('manual_price')) {
                        $transactionPrice = $request->manual_price / $faktor;
                        $material->update(['price_per_unit' => $transactionPrice]);
                    } else {
                        throw new \Exception("Harga sistem saat ini Rp 0. Wajib mengisi 'Harga Estimasi'.");
                    }
                }
            } else {
                // LOSS: Wajib pakai harga sistem
                $transactionPrice = $material->price_per_unit;
            }

            // 4. EKSEKUSI UPDATE DATABASE
            $material->update(['current_stock' => $actualQtyInBase]);

            $typeLabel = $diffQty > 0 ? "Surplus (Found)" : "Loss (Usage)";
            $desc      = "Opname: {$typeLabel}. Fisik: {$inputQtyPurchaseUnit} {$material->purchase_unit}. " . $request->notes;

            // --- PERBAIKAN DI SINI: SESUAI SKEMA BARU ---
            MaterialTransaction::create([
                'material_id'           => $material->id,
                'type'                  => 'adjustment', // Enum: 'in', 'out', 'adjustment'
                'qty'                   => $diffQty, 
                
                // Harga
                'price_per_unit'        => $transactionPrice,
                'total_price'           => abs($diffQty) * $transactionPrice,
                
                'transaction_date'      => now(),
                'description'           => $desc,

                // Snapshot Wajib
                'material_name_snapshot' => $material->name,
                'material_packaging_size_snapshot' => $material->packaging_size,
                'material_packaging_unit_snapshot' => $material->packaging_unit,
                'material_conversion_factor_snapshot' => $material->conversion_factor,
                'purchase_unit_snapshot' => $material->purchase_unit,
                'material_unit_snapshot' => $material->unit,
                'current_stock_balance'  => $actualQtyInBase, // Saldo setelah update
            ]);

            DB::commit();
            
            $diffInPurchUnit = $diffQty / $faktor;
            return back()->with('success', "Stock Adjustment berhasil. Selisih: " . number_format($diffInPurchUnit, 2) . " " . $material->purchase_unit);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    // function ini dijalankan secara manual oleh user untuk mengupdate lead time, safety stock dan ROP
    // untuk semua bahan baku
    // tombol update ini ada di halaman material.index
    public function updateMaterialLeadTimeSafetyStockROP() 
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'inventory', 'production'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk update data lead time dan safety stok.')->withInput();
        }
        
        // hanya menghitung material yang statusnya aktif saja
        $materials = Material::where('is_active', true)->get();
        DB::beginTransaction();
        try {
            foreach ($materials as $material) {
                
                // 1. Hitung Statistik Hari Tunggu (Lead Time Stats)
                $leadTimeStats = $this->calculateLeadTimeStats($material);
                $averageLeadTimeDays = $leadTimeStats['average']; // rata-rata hari lama waktu tungu
                $minLeadTimeDays     = $leadTimeStats['min']; // waktu hari tunggu tercepat
                $maxLeadTimeDays     = $leadTimeStats['max']; // waktu hari tunggu terlama

                // 2. Hitung Penggunaan Harian (Daily Usage Qty) selama 30 Hari Terakhir
                $usageStats = $this->calculateUsageStats($material);
                $averageDailyUsage = $usageStats['average']; // rata-rata pemakaian selama 30 hari terakhir
                $maxDailyUsage     = $usageStats['max']; // pemakaian harian tertinggi selama 30 hari terakhir

                // 3. Hitung Safety Stock (Kuantitas)
                // maxDemand - averageLeadTimeDemand
                // (Max Lead Time Days * Max Daily Usage Qty) - (Average Lead Time Days * Average Daily Usage Qty)
                $maxDemand = $maxLeadTimeDays * $maxDailyUsage;
                $averageLeadTimeDemand = $averageLeadTimeDays * $averageDailyUsage; // Lead Time Demand

                $safetyStock = max(0, $maxDemand - $averageLeadTimeDemand);

                // 4. Hitung ROP (Kuantitas)
                // averageLeadTimeDemand + safetyStock
                $rop = $averageLeadTimeDemand + $safetyStock;

                // 5. Update data material termasuk Min dan Max Lead Time
                $material->update([
                    'lead_time_average'  => $averageLeadTimeDays,
                    'min_lead_time_days' => $minLeadTimeDays,
                    'max_lead_time_days' => $maxLeadTimeDays,
                    'safety_stock'       => $safetyStock,
                    'reorder_point'      => $rop
                ]);
            }
            DB::commit();
            return redirect()->back()->with('success', 'Lead Time, Safety Stock, dan ROP seluruh material berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    // menghitung rata-rata hari tunggu barang tiba di gudang
    // dihitung sejak PO dibuat sampai barang diterima di gudang (sesuai expected_arrival_date di tabel purchase_orders)
    private function calculateLeadTimeStats(Material $material) 
    {
        // Jika manual, langsung gunakan nilai dari database
        if ($material->is_manual_lead_time === 'manual') {
            return [
                'average' => ($material->min_lead_time_days + $material->max_lead_time_days) / 2,
                'min'     => $material->min_lead_time_days,
                'max'     => $material->max_lead_time_days,
            ];
        }

        // --- Logika Automatic ---
        // ambil id PO dari tabel MaterialTransaction dengan tipe IN 
        // ambil 30 PO terakhir yang sudah selesai diterima (status 'received') dan expected_arrival_date tidak kosong
        $recentPoIds = MaterialTransaction::where('material_id', $material->id)
            ->where('type', 'in')
            ->whereNotNull('purchase_order_id')
            ->orderBy('transaction_date', 'desc')
            ->limit(100) // Tarik 100 data transaksi terakhir untuk disaring
            ->pluck('purchase_order_id') // Ambil kolom ID PO saja
            ->unique() // Saring ID duplikat menggunakan Collection Laravel (bukan SQL)
            ->take(30); // Ambil 30 ID PO unik terbaru

        // Fallback jika belum ada transaksi sama sekali
        if ($recentPoIds->isEmpty()) {
            return [
                'average' => ($material->min_lead_time_days + $material->max_lead_time_days) / 2,
                'min'     => $material->min_lead_time_days,
                'max'     => $material->max_lead_time_days,
            ];
        }

        // ambil list PO berdasarkan ID yang sudah difiltter diatas
        $purchaseOrders = PurchaseOrder::whereIn('id', $recentPoIds)
            ->where('status', 'received')
            ->whereNotNull('expected_arrival_date')
            ->get();

        // array berapa hari selisih antara order_date dan expected_arrival_date untuk setiap PO    
        $leadTimes = [];

        foreach ($purchaseOrders as $po) {
            $orderDate = Carbon::parse($po->order_date);
            $arrivalDate = Carbon::parse($po->expected_arrival_date);
            
            // Simpan selisih hari ke dalam array
            $leadTimes[] = $orderDate->diffInDays($arrivalDate);
        }

        // Fallback jika array kosong (misal expected_arrival_date kosong semua)
        if (empty($leadTimes)) {
            return [
                'average' => ($material->min_lead_time_days + $material->max_lead_time_days) / 2,
                'min'     => $material->min_lead_time_days,
                'max'     => $material->max_lead_time_days,
            ];
        }

        // return rata-rata lama lead time, nilai lead time tercepat, dan nilai lead time terlama dari sebuah PO
        return [
            'average' => array_sum($leadTimes) / count($leadTimes),
            'min'     => min($leadTimes),
            'max'     => max($leadTimes),
        ];
    }


    // menghitung rata-rata pemakaian bahan baku harian selama 30 hari terakhir
    // tipe bahan baku yang terpakai di tabel MaterialTransactionadalah OUT 
    private function calculateUsageStats(Material $material)
    {
        // Ambil data 30 hari ke belakang
        $thirtyDaysAgo = now()->subDays(30)->format('Y-m-d');
        
        // ambil data transaksi dengan tipe OUT untuk material ini selama 30 hari terakhir
        $usages = MaterialTransaction::where('material_id', $material->id)
            ->where('type', 'out')
            ->where('transaction_date', '>=', $thirtyDaysAgo)
            ->get();

        if ($usages->isEmpty()) {
            return ['average' => 0, 'max' => 0];
        }

        // grouping data berdasarkan tanggal transaksi atau harian nya lalu jumlahkan qty untuk setiap hari
        $dailyUsages = $usages->groupBy('transaction_date')->map(function ($dayTransactions) {
            return abs($dayTransactions->sum('qty')); 
        });

        return [
            'average' => $dailyUsages->sum() / 30, // Rata-rata dari 30 hari kalender
            'max'     => $dailyUsages->max()
        ];
    }
}