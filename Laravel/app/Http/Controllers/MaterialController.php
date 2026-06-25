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
use App\Services\MaterialService;

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
    public function update(Request $request, Material $material, MaterialService $materialService)
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
        $material->is_manual_lead_time = $request->is_manual_lead_time;
        $material->min_lead_time_days  = $request->min_lead_time_days ?? 1;
        $material->max_lead_time_days  = $request->max_lead_time_days ?? 7;

        // Panggil MaterialService untuk menghitung
        $materialService = new MaterialService();
        $leadTimeStats = $materialService->calculateLeadTimeStats($material);
        
        $minLead = $leadTimeStats['min'];
        $maxLead = $leadTimeStats['max'];
        $avgLead = $leadTimeStats['average'];

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

    public function stockAdjustment(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'inventory'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk mengatur data stok opname.')->withInput();
        }

        $request->validate([
            'material_id'  => 'required|exists:materials,id',
            'actual_qty'   => 'required|numeric|min:0', 
            'notes'        => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $material = Material::findOrFail($request->material_id);
            $faktor = $material->conversion_factor > 0 ? $material->conversion_factor : 1;

            // Input User (Satuan Beli) -> Konversi ke Base Unit
            $inputQtyPurchaseUnit = $request->actual_qty;
            $actualQtyInBase      = $inputQtyPurchaseUnit * $faktor;
            $systemQty            = $material->current_stock; 
            
            $diffQty = $actualQtyInBase - $systemQty;

            if (abs($diffQty) < 0.0001) { 
                return back()->with('info', "Stok fisik sudah sesuai dengan sistem.");
            }

            // FILTER PERLINDUNGAN: Cek harga dasar jika terjadi surplus persediaan
            $transactionPrice = $material->price_per_unit;
            if ($diffQty > 0 && $transactionPrice == 0) {
                throw new \Exception("Harga dasar material saat ini Rp 0. Silakan lakukan 'Cost Adjustment' (Update Harga) terlebih dahulu sebelum menambah stok baru.");
            }

            // Eksekusi Update Stok Fisik
            $material->update(['current_stock' => $actualQtyInBase]);

            $typeLabel = $diffQty > 0 ? "Surplus (Found)" : "Loss (Usage)";
            $desc      = "Opname: {$typeLabel}. Fisik: {$inputQtyPurchaseUnit} {$material->purchase_unit}. " . $request->notes;

            MaterialTransaction::create([
                'material_id'           => $material->id,
                'type'                  => 'adjustment', 
                'qty'                   => $diffQty, 
                'price_per_unit'        => $transactionPrice,
                'total_price'           => abs($diffQty) * $transactionPrice,
                'transaction_date'      => now(),
                'description'           => $desc,

                // Snapshots
                'material_name_snapshot' => $material->name,
                'material_packaging_size_snapshot' => $material->packaging_size,
                'material_packaging_unit_snapshot' => $material->packaging_unit,
                'material_conversion_factor_snapshot' => $material->conversion_factor,
                'purchase_unit_snapshot' => $material->purchase_unit,
                'material_unit_snapshot' => $material->unit,
                'current_stock_balance'  => $actualQtyInBase,
            ]);

            DB::commit();
            
            $diffInPurchUnit = $diffQty / $faktor;
            return back()->with('success', "Stock Adjustment berhasil. Selisih: " . ($diffInPurchUnit > 0 ? '+' : '') . number_format($diffInPurchUnit, 2) . " " . $material->purchase_unit);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function costAdjustment(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'purchase'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk mengubah harga dasar material.')->withInput();
        }

        $request->validate([
            'material_id' => 'required|exists:materials,id',
            'new_price'   => 'required|numeric|min:0.01', // Harga Baru per Purchase Unit (misal per Sak/Box)
            'reason'      => 'required|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            $material = Material::findOrFail($request->material_id);
            $faktor = $material->conversion_factor > 0 ? $material->conversion_factor : 1;

            $oldPriceInPurchaseUnit = $material->price_per_unit * $faktor;
            $newPriceInPurchaseUnit = $request->new_price;

            // Konversi Harga per Purchase Unit menjadi Harga per Base Unit untuk Database
            $newPriceInBaseUnit = $newPriceInPurchaseUnit / $faktor;

            // Update Master Harga
            $material->update(['price_per_unit' => $newPriceInBaseUnit]);

            // Catat transaksi Revaluasi Nilai (Qty = 0 karena kuantitas fisik tidak berubah)
            $desc = "Cost Adjustment: Penyesuaian harga dari Rp " . number_format($oldPriceInPurchaseUnit, 2, ',', '.') . " menjadi Rp " . number_format($newPriceInPurchaseUnit, 2, ',', '.') . " per " . $material->purchase_unit . ". Alasan: " . $request->reason;

            MaterialTransaction::create([
                'material_id'           => $material->id,
                'type'                  => 'cost_adjustment', 
                'qty'                   => 0, // Kuantitas fisik tidak bergeser
                'price_per_unit'        => $newPriceInBaseUnit,
                'total_price'           => 0,
                'transaction_date'      => now(),
                'description'           => $desc,

                // Snapshots
                'material_name_snapshot' => $material->name,
                'material_packaging_size_snapshot' => $material->packaging_size,
                'material_packaging_unit_snapshot' => $material->packaging_unit,
                'material_conversion_factor_snapshot' => $material->conversion_factor,
                'purchase_unit_snapshot' => $material->purchase_unit,
                'material_unit_snapshot' => $material->unit,
                'current_stock_balance'  => $material->current_stock, // Saldo stok tetap sama
            ]);

            DB::commit();
            return back()->with('success', "Harga dasar material berhasil diperbarui.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update harga: ' . $e->getMessage())->withInput();
        }
    }

    // function ini dijalankan secara manual oleh user untuk mengupdate lead time, safety stock dan ROP
    // untuk semua bahan baku
    // tombol update ini ada di halaman material.index
    // REVISI NOMOR 2: HAPUS ROP
    public function updateMaterialLeadTimeSafetyStock(MaterialService $materialService) 
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'inventory', 'production'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk update data lead time dan safety stok.')->withInput();
        }
        try {
            // Panggil proses utama yang ada di service
            $materialService->updateAllMaterialLeadTimeSafetyStock();
            // REVISI NOMOR 2: HAPUS ROP
            return redirect()->back()->with('success', 'Lead Time dan Safety Stock seluruh material berhasil diperbarui.');

            // return redirect()->back()->with('success', 'Lead Time, Safety Stock, dan ROP seluruh material berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }
}