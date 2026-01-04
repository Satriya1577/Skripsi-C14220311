<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;


class MaterialTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Material $material)
    {
        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

   public function storeAdjustment(Request $request)
    {
        $request->validate([
            'material_id'  => 'required|exists:materials,id',
            // Sesuai input name="actual_qty" di view
            'actual_qty'   => 'required|numeric|min:0', 
            // Sesuai input name="notes"
            'notes'        => 'nullable|string',
            // Sesuai input name="manual_price" (Hanya muncul jika price 0)
            'manual_price' => 'nullable|numeric|min:0.01' 
        ]);

        try {
            DB::beginTransaction();

            $material = Material::findOrFail($request->material_id);
            
            // 1. SIAPKAN KONVERSI
            // Faktor konversi: 1 Satuan Beli = X Satuan Dasar
            $faktor = $material->conversion_factor > 0 ? $material->conversion_factor : 1;

            // 2. HITUNG SELISIH (DELTA)
            // Input User (Satuan Beli) -> Konversi ke Base Unit (Gram/Pcs Internal)
            $inputQtyPurchaseUnit = $request->actual_qty;
            $actualQtyInBase      = $inputQtyPurchaseUnit * $faktor;

            // Stok Sistem saat ini (Base Unit)
            $systemQty = $material->current_stock; 
            
            // Selisih (Positif = Surplus, Negatif = Loss)
            $diffQty = $actualQtyInBase - $systemQty;

            if ($diffQty == 0) {
                return back()->with('info', "Stok fisik ($inputQtyPurchaseUnit {$material->purchase_unit}) sudah sesuai dengan sistem.");
            }

            // 3. LOGIC HARGA & HPP
            $adjustmentPricePerBase = $material->price_per_unit;

            // KASUS KHUSUS: Jika Harga Sistem 0 DAN terjadi Surplus (Barang Nambah)
            // Kita butuh harga acuan agar nilai aset tidak Rp 0.
            if ($adjustmentPricePerBase == 0 && $diffQty > 0) {
                
                if ($request->filled('manual_price')) {
                    // Input View: Harga per Satuan Beli (misal: Rp 100.000 / Karung)
                    // Konversi: Harga per Base Unit (misal: Rp 100 / Gram)
                    $adjustmentPricePerBase = $request->manual_price / $faktor;

                    // Update Master Price agar transaksi berikutnya punya acuan harga
                    $material->update(['price_per_unit' => $adjustmentPricePerBase]);
                } else {
                    // Guard jika user mem-bypass HTML
                    throw new Exception("Harga sistem saat ini Rp 0. Wajib mengisi 'Harga Estimasi' untuk mencatat surplus stok.");
                }
            }

            // 4. EKSEKUSI UPDATE DATABASE

            // A. Update Stok Master (Simpan dalam Base Unit)
            $material->update(['current_stock' => $actualQtyInBase]);

            // B. Catat Transaksi
            // Description mencatat apa yang diinput user (Satuan Beli) agar mudah diverifikasi fisik
            $typeStr = $diffQty > 0 ? "Surplus" : "Loss";
            $desc    = "Opname ($typeStr). Fisik: {$inputQtyPurchaseUnit} {$material->purchase_unit}. " . $request->notes;

            MaterialTransaction::create([
                'material_id'    => $material->id,
                'type'           => 'adjustment',
                'qty'            => abs($diffQty), // Selisih absolut dalam Base Unit
                'price_per_unit' => $adjustmentPricePerBase,
                'total_price'    => abs($diffQty) * $adjustmentPricePerBase,
                'transaction_date' => now(), // Default saat input opname
                'description'    => $desc,
            ]);

            DB::commit();
            return back()->with('success', "Stock Adjustment berhasil disimpan. Selisih tercatat: " . ($diffQty/$faktor) . " " . $material->purchase_unit);

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan adjustment: ' . $e->getMessage())->withInput();
        }
    }
    
    public function storeIn(Request $request)
    {
        $request->validate([
            'material_id'      => 'required|exists:materials,id',
            // Input View: name="qty" (Satuan Beli)
            'qty'              => 'required|numeric|min:0.01',
            // Input View: name="price_per_unit" (Harga Total per Satuan Beli)
            'price_per_unit'   => 'required|numeric|min:0',  
            // Input View: name="transaction_date"
            'transaction_date' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $material = Material::findOrFail($request->material_id);
            
            // 1. SIAPKAN KONVERSI
            $faktor = $material->conversion_factor > 0 ? $material->conversion_factor : 1;

            // 2. NORMALISASI INPUT
            // Qty Masuk (Base Unit) = Input Qty * Faktor
            $qtyInBase = $request->qty * $faktor; 

            // Harga per Base Unit = Input Harga Beli / Faktor
            // Contoh: Beli Rp 10.000/kg. Faktor 1000g. Harga Base = Rp 10/g.
            $priceInBase = $request->price_per_unit / $faktor;

            // 3. HITUNG AVERAGE PRICE (MOVING AVERAGE)
            // Valuasi Lama
            $oldStockValue = $material->current_stock * $material->price_per_unit;
            // Valuasi Transaksi Baru
            $newTransactionValue = $qtyInBase * $priceInBase;
            
            // Stok Total Baru
            $totalStockBaru = $material->current_stock + $qtyInBase;

            // Harga Rata-rata Baru (Weighted Average)
            $newAveragePrice = $totalStockBaru > 0 
                ? ($oldStockValue + $newTransactionValue) / $totalStockBaru 
                : $priceInBase;

            // 4. UPDATE MASTER MATERIAL
            $material->update([
                'current_stock'  => $totalStockBaru,
                'price_per_unit' => $newAveragePrice 
            ]);

            // 5. CATAT TRANSAKSI (IN)
            MaterialTransaction::create([
                'material_id'      => $material->id,
                'type'             => 'in',
                'qty'              => $qtyInBase,        // Masuk DB dalam Base Unit
                'price_per_unit'   => $priceInBase,      // Masuk DB dalam Harga per Base Unit
                'total_price'      => $newTransactionValue,
                'transaction_date' => $request->transaction_date, // Sesuai input date picker
                'description'      => "Purchase: {$request->qty} {$material->purchase_unit} @ " . number_format($request->price_per_unit),
            ]);

            DB::commit();
            return back()->with('success', 'Stok masuk berhasil dicatat & Harga rata-rata diperbarui.');

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
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
    public function show(MaterialTransaction $material_transaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MaterialTransaction $material_transaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MaterialTransaction $material_transaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MaterialTransaction $material_transaction)
    {
        //
    }
}
