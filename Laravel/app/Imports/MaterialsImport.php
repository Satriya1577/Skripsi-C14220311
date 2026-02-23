<?php

namespace App\Imports;

use App\Models\Material;
use App\Models\MaterialTransaction;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\ValidationException; 

class MaterialsImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function model(array $row)
    {
        // 0. VALIDASI MANUAL (STOK vs HARGA)
        // Cek ini paling awal sebelum proses logic berat dimulai
        $stockInput = isset($row['current_stock']) ? (float)$row['current_stock'] : 0;
        $priceInput = isset($row['price_per_unit']) ? (float)$row['price_per_unit'] : 0;

        if ($stockInput > 0 && ($priceInput <= 0)) {
            $materialName = $row['name'] ?? 'Unknown Material';
            
            throw ValidationException::withMessages([
                'price_per_unit' => "Error pada Material '{$materialName}': Price Per Unit (Harga Beli) wajib diisi jika Current Stock lebih dari 0."
            ]);
        }

        // 1. Cari Material berdasarkan Kode
        $existingMaterial = Material::where('code', $row['material_code'])->first();
        
        // 2. Cek riwayat transaksi (Safety Check)
        $hasTransaction = false;
        if ($existingMaterial) {
            $hasTransaction = MaterialTransaction::where('material_id', $existingMaterial->id)->exists();
        }

        // 3. Siapkan Data Dasar (Safe Fields)
        // Mapping kolom baru sesuai skema database terbaru
        $minLeadTime = isset($row['min_lead_time']) && $row['min_lead_time'] !== '' ? $row['min_lead_time'] : 1;
        $maxLeadTime = isset($row['max_lead_time']) && $row['max_lead_time'] !== '' ? $row['max_lead_time'] : 7;
        
        // Hitung rata-rata lead time untuk nilai awal
        $avgLeadTime = ($minLeadTime + $maxLeadTime) / 2;

        $dataToUpdate = [
            'name'                => $row['name'],
            'category_type'       => strtolower($row['category_type']),
            
            // --- UPDATE SKEMA BARU ---
            'min_lead_time_days'  => $minLeadTime,
            'max_lead_time_days'  => $maxLeadTime,
            'lead_time_average'   => $avgLeadTime,
            //'is_manual_lead_time' => 'manual', // Default manual import excel
            
            // Tambahan opsional jika user punya data safety stock di excel
            'safety_stock'        => isset($row['safety_stock']) ? (float)$row['safety_stock'] : 0,
            'reorder_point'       => isset($row['reorder_point']) ? (int)$row['reorder_point'] : 0,
            // -------------------------
            
            'is_active'           => true,
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
            $dataToUpdate['purchase_unit']     = $row['purchase_unit'];     // String Label
            
            // --- TAMBAHAN KOLOM BARU DI SINI ---
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
                'material_id'           => $material->id,
                'type'                  => 'adjustment', // Tipe transaksi saldo awal
                'qty'                   => $initialStockBase, // Base Unit
                
                // Harga
                'price_per_unit'        => $initialPriceBase, // Base Unit
                'total_price'           => $initialStockBase * $initialPriceBase,
                
                'transaction_date'      => now(),
                
                // Description ambil dari Excel agar informatif
                'description'           => "Import Excel: Initial Stock " . 
                                            number_format($row['current_stock'] ?? 0) . " " . 
                                            ($row['purchase_unit'] ?? '-'),

                // --- SNAPSHOT WAJIB (SESUAI SKEMA BARU) ---
                'material_name_snapshot' => $material->name,
                'material_packaging_size_snapshot' => $material->packaging_size,
                'material_packaging_unit_snapshot' => $material->packaging_unit,
                'material_conversion_factor_snapshot' => $material->conversion_factor,
                'purchase_unit_snapshot' => $material->purchase_unit,
                'material_unit_snapshot' => $material->unit,
                'current_stock_balance'  => $initialStockBase, // Saldo awal = Qty Masuk awal
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
            
            'packaging_size'    => 'required|numeric|min:0',
            'packaging_unit'    => 'required|string',
            
            'conversion_factor' => 'nullable|numeric|min:0.0001', 
            'current_stock'     => 'nullable|numeric|min:0',
            'price_per_unit'    => 'nullable|numeric|min:0',
            
            // Validasi kolom baru
            'min_lead_time'     => 'nullable|integer|min:1',
            'max_lead_time'     => 'nullable|integer|min:1',
            'safety_stock'      => 'nullable|numeric|min:0',
            'reorder_point'     => 'nullable|integer|min:0',
        ];
    }
}