<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Http\Controllers\Controller;
use App\Imports\MaterialsImport;
use App\Models\MaterialTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class MaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $materials = Material::orderBy('id', 'asc')->paginate(10);
        return view('materials.index',compact('materials'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    private function calculateConversionFactor($size, $packagingUnit, $baseUnit)
    {
        // Jika satuan beli == satuan dasar (misal beli Gram, pakai Gram), faktor = 1
        // if ($packagingUnit == $baseUnit) {
        //     return 1;
        // }

        // Logic Konversi Berat (Target: Gram)
        if ($baseUnit == 'gram') {
            switch ($packagingUnit) {
                case 'kg': return $size * 1000;
                case 'ons': return $size * 100;
                case 'gram': return $size;
                default: return $size; // Asumsi user input manual dalam gram
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
                case 'pcs': return $size; // <--- PERBAIKAN: Return size, bukan 1
                default: return $size; 
            }
        }

        return $size;
    }

    private function handleStore(Request $request)
    {
        // 1. VALIDASI INPUT
        $request->validate([
            // Identitas
            'code' => 'required|string|unique:materials,code',
            'name' => 'required|string|max:255',
            'category_type' => 'required|in:mass,volume,unit',
            'lead_time_days' => 'required|integer|min:0',

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

        // SEBELUM CREATE / UPDATE DATA:
        // Hitung Conversion Factor secara otomatis
        $calculatedFactor = $this->calculateConversionFactor(
            $request->packaging_size, 
            $request->packaging_unit, 
            $request->unit // Base Unit (gram/ml/pcs)
        );

        try {
            DB::beginTransaction();

            // 2. HITUNG STOK & HARGA BASE (Jika ada saldo awal)
            $initialStockBase = 0;
            $initialPriceBase = 0;
            $hasInitialStock = false;

            // Jika user mengisi saldo awal > 0
            if ($request->filled('initial_qty_purchase_unit') && $request->initial_qty_purchase_unit > 0) {
                $hasInitialStock = true;
                $faktor = $calculatedFactor;

                // Konversi Qty ke Base Unit
                // Contoh: 10 Karung * 25.000 = 250.000 Gram
                $initialStockBase = $request->initial_qty_purchase_unit * $faktor;

                // Konversi Harga ke Base Unit
                // Contoh: Rp 200.000 (per karung) / 25.000 = Rp 8 (per gram)
                // Jika harga kosong/0, set 0
                $priceInput = $request->initial_price_purchase_unit ?? 0;
                $initialPriceBase = ($faktor > 0) ? ($priceInput / $faktor) : 0;
            }

            // 3. SIMPAN KE TABEL MATERIALS (Master Data)
            $material = Material::create([
                'code' => $request->code,
                'name' => $request->name,
                'category_type' => $request->category_type,
                'lead_time_days' => $request->lead_time_days,
                
                // Simpan Konfigurasi Satuan
                'unit' => $request->unit,
                'purchase_unit' => $request->purchase_unit,

                // SIMPAN INPUT MENTAH USER AGAR BISA DI-EDIT
                'packaging_size' => $request->packaging_size, // 25
                'packaging_unit' => $request->packaging_unit, // kg

                'conversion_factor' => $calculatedFactor, // <--- Hasil hitungan controller

                // Simpan Saldo Awal (Dalam Base Unit)
                'current_stock' => $initialStockBase,
                'price_per_unit' => $initialPriceBase, // Moving Average Awal
                'is_active' => $request->is_active,
            ]);

            // 4. CATAT TRANSAKSI SALDO AWAL (Jika ada)
            // Ini penting agar kartu stok balance dari awal
            if ($hasInitialStock) {
                MaterialTransaction::create([
                    'material_id' => $material->id,
                    'type' => 'adjustment', // Gunakan tipe 'adjustment' atau 'in' untuk saldo awal
                    'qty' => $initialStockBase, // Simpan dalam Base Unit
                    'price_per_unit' => $initialPriceBase,
                    'total_price' => $initialStockBase * $initialPriceBase,
                    'transaction_date' => now(),
                    'description' => "Initial Stock: {$request->initial_qty_purchase_unit} {$request->purchase_unit} @ " . number_format($request->initial_price_purchase_unit),
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
 
    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        if ($request->filled('material_id')) {
            $material = Material::findOrFail($request->material_id);
            return $this->handleUpdate($request, $material);
        }
        return $this->handleStore($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(material $material)
    {
         $transactions = $material->transactions()
        ->orderBy('transaction_date', 'desc')
        ->paginate(5); // 5 data per page
        return view('materials.show', compact('material', 'transactions'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(material $material)
    {
        //
    }

    public function handleUpdate(Request $request, Material $material)
    {
        // 1. CEK RIWAYAT TRANSAKSI
        $hasTransaction = MaterialTransaction::where('material_id', $material->id)->exists();

        // 2. VALIDASI DASAR
        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:materials,code,'.$material->id,
            'lead_time_days' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
            // Tambahkan validasi untuk input baru
            'packaging_size' => 'required|numeric|min:0.0001',
            'packaging_unit' => 'required|string',
        ];

        // Hitung Faktor Konversi dari Input User saat ini
        // Kita butuh ini untuk dibandingkan dengan data lama (Logic Guard)
        // Gunakan 'unit' dari request jika ada, jika tidak pakai dari material lama
        $baseUnitForCalc = $request->input('unit', $material->unit);
        
        $calculatedNewFactor = $this->calculateConversionFactor(
            $request->packaging_size,
            $request->packaging_unit,
            $baseUnitForCalc
        );

        // 3. LOGIC PENGUNCIAN (GUARD)
        if ($hasTransaction) {
            // JIKA SUDAH ADA TRANSAKSI:
            
            // Cek Unit Dasar
            if ($request->unit != $material->unit) {
                return back()->with('error', 'GAGAL: Satuan Dasar (Unit) tidak boleh diubah karena material ini sudah memiliki riwayat transaksi.');
            }

            // Cek Faktor Konversi (Logic Diubah sedikit untuk akomodasi input baru)
            // Kita bandingkan hasil hitungan input user vs data di database
            // Gunakan abs() > 0.001 untuk mengatasi masalah presisi koma float
            if (abs($calculatedNewFactor - $material->conversion_factor) > 0.001) {
                return back()->with('error', 'GAGAL: Ukuran Kemasan (Isi per Satuan Beli) tidak boleh diubah karena material ini sudah memiliki riwayat transaksi. Data lama: ' . number_format($material->conversion_factor) . ', Data baru: ' . number_format($calculatedNewFactor));
            }

            // Cek Satuan Beli
            if ($request->purchase_unit != $material->purchase_unit) {
                return back()->with('error', 'GAGAL: Satuan Beli tidak boleh diubah karena sudah ada riwayat transaksi.');
            }

        } else {
            // JIKA BELUM ADA TRANSAKSI (Material Baru/Masih Bersih):
            $rules['unit'] = 'required|string';
            $rules['purchase_unit'] = 'required|string';
            // conversion_factor tidak divalidasi manual lagi, karena dihitung otomatis
        }

        $request->validate($rules);

        // 4. PROSES UPDATE
        $dataToUpdate = [
            'name' => $request->name,
            'code' => $request->code,
            'lead_time_days' => $request->lead_time_days,
            'category_type' => $request->category_type, 
            'is_active' => $request->is_active,
        ];

        // Hanya masukkan data satuan jika BELUM ada transaksi
        if (!$hasTransaction) {
            $dataToUpdate['unit'] = $request->unit;
            $dataToUpdate['purchase_unit'] = $request->purchase_unit;
            
            // Simpan hasil hitungan baru
            $dataToUpdate['conversion_factor'] = $calculatedNewFactor;
            
            // Saldo Awal tetap tidak bisa diubah di sini

            $dataToUpdate['packaging_size'] = $request->packaging_size;
            $dataToUpdate['packaging_unit'] = $request->packaging_unit;
        }

        $material->update($dataToUpdate);

        return redirect()->route('materials.index')->with('success', 'Data material berhasil diperbarui.');
    }
    


    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, material $material)
    {
        return $this->handleUpdate($request, $material);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Material $material)
    {
        $material = Material::findOrFail($material->id);

        // 1. CEK RIWAYAT TRANSAKSI (Kartu Stok)
        // Jika sudah pernah ada transaksi In/Out, DILARANG HAPUS.
        if ($material->transactions()->exists()) {
            return back()->with('error', 'GAGAL: Material tidak bisa dihapus karena sudah memiliki riwayat transaksi (Kartu Stok). Silakan non-aktifkan saja.');
        }

        // 2. CEK PENGGUNAAN DI RESEP (BOM)
        // Jika material ini sedang dipakai di resep produk tertentu, DILARANG HAPUS.
        if ($material->productMaterials()->exists()) {
            // Opsional: Kasih tau user produk mana yang pakai material ini
            $productName = $material->productMaterials->first()->product->name;
            return back()->with('error', "GAGAL: Material sedang digunakan dalam resep produk '$productName'. Hapus dulu dari resep jika ingin menghapus material ini.");
        }

        // 3. JIKA LOLOS PENGECEKAN (AMAN)
        // Berarti ini material baru yang belum pernah diapa-apain (misal salah ketik)
        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Data material berhasil dihapus permanen.');
    }
}







