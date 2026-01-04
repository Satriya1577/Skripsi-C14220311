<?php

namespace App\Imports;

use App\Models\Material;
use App\Models\MaterialTransaction;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class MaterialsImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function model(array $row)
    {
        // 1. Cari Material berdasarkan Kode (Cek apakah update atau create)
        $existingMaterial = Material::where('code', $row['material_code'])->first();
        
        // 2. Cek riwayat transaksi (Safety Check)
        $hasTransaction = false;
        if ($existingMaterial) {
            $hasTransaction = MaterialTransaction::where('material_id', $existingMaterial->id)->exists();
        }

        // 3. Siapkan Data Dasar (Safe Fields)
        // Data ini aman diupdate meskipun transaksi sudah ada
        $dataToUpdate = [
            'name'           => $row['name'],
            'category_type'  => strtolower($row['category_type']),
            'lead_time_days' => $row['lead_time_days'] ?? 1,
            'is_active'      => true,
        ];

        // Variabel penampung untuk logika transaksi nanti
        $initialStockBase = 0;
        $initialPriceBase = 0;
        $shouldCreateTransaction = false; // Flag penanda

        // 4. LOGIC PENGAMANAN & KALKULASI
        if ($hasTransaction) {
            // --- KASUS A: SUDAH ADA TRANSAKSI (Update Ringan) ---
            // Jangan ubah satuan, konversi, harga, stok, atau detail kemasan vital.
            // Biarkan array dataToUpdate apa adanya (hanya nama, kategori, lead time).

        } else {
            // --- KASUS B: MATERIAL BARU / BELUM ADA TRANSAKSI ---
            
            // Ambil Faktor Konversi
            $conversionFactor = $row['conversion_factor'] ? (float)$row['conversion_factor'] : 1;
            
            // Update data konfigurasi satuan & KEMASAN BARU
            $dataToUpdate['unit']              = strtolower($row['unit']); // Base unit (gram/ml)
            $dataToUpdate['purchase_unit']     = $row['purchase_unit'];     // String Label (e.g., Sak @25kg)
            
            // --- TAMBAHAN KOLOM BARU DI SINI ---
            // Masuk ke blok ini agar aman & konsisten dengan conversion_factor
            $dataToUpdate['packaging_size']    = isset($row['packaging_size']) ? (float)$row['packaging_size'] : 0;
            $dataToUpdate['packaging_unit']    = isset($row['packaging_unit']) ? $row['packaging_unit'] : null;
            // -----------------------------------

            $dataToUpdate['conversion_factor'] = $conversionFactor;
            
            // Cek apakah ini Material BARU (Insert)?
            if (!$existingMaterial) {
                // Input Excel (Satuan Beli)
                $inputStockPurchaseUnit = isset($row['current_stock']) ? (float)$row['current_stock'] : 0;
                $inputPricePurchaseUnit = isset($row['price_per_unit']) ? (float)$row['price_per_unit'] : 0;

                // Hitung Konversi ke Base Unit
                // Rumus Stok: Qty Beli * Faktor
                $initialStockBase = $inputStockPurchaseUnit * $conversionFactor;
                
                // Rumus Harga: Harga Beli / Faktor
                $initialPriceBase = ($conversionFactor > 0) ? ($inputPricePurchaseUnit / $conversionFactor) : 0;

                // Masukkan ke array update Material
                $dataToUpdate['current_stock'] = $initialStockBase;
                $dataToUpdate['price_per_unit'] = $initialPriceBase;

                // Nyalakan Flag untuk trigger pembuatan transaksi di langkah bawah
                $shouldCreateTransaction = true; 
            }
        }

        // 5. EKSEKUSI SIMPAN MATERIAL (Master Data)
        // Kita simpan dulu materialnya untuk mendapatkan ID
        $material = Material::updateOrCreate(
            ['code' => $row['material_code']], 
            $dataToUpdate
        );

        // 6. CATAT TRANSAKSI SALDO AWAL (Logic Tambahan)
        // Hanya dijalankan jika ini material baru DAN punya stok awal > 0
        if ($shouldCreateTransaction && $initialStockBase > 0) {
            
            MaterialTransaction::create([
                'material_id'      => $material->id,
                'type'             => 'adjustment', // Tipe transaksi saldo awal
                'qty'              => $initialStockBase, // Base Unit
                'price_per_unit'   => $initialPriceBase, // Base Unit
                'total_price'      => $initialStockBase * $initialPriceBase,
                'transaction_date' => now(),
                // Description ambil dari Excel agar informatif
                'description'      => "Import Excel: Initial Stock " . 
                                      number_format($row['current_stock'] ?? 0) . " " . 
                                      ($row['purchase_unit'] ?? '-')
            ]);
        }

        return $material;
    }

    public function rules(): array
    {
        return [
            'material_code'     => 'required|string',
            'name'              => 'required|string',
            'category_type'     => 'required|in:mass,volume,unit',
            'unit'              => 'required|string', 
            'purchase_unit'     => 'required|string',
            // Validasi kolom baru
            'packaging_size'    => 'required|numeric|min:0',
            'packaging_unit'    => 'required|string',
            
            'conversion_factor' => 'nullable|numeric|min:0.0001', 
            'current_stock'     => 'nullable|numeric|min:0',
            'price_per_unit'    => 'nullable|numeric|min:0',
        ];
    }
}