<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductTransaction;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Validation\ValidationException;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function model(array $row)
    {
        // 1. Bersihkan nilai numeric (cegah error string kosong "")
        $stockInput     = isset($row['current_stock']) && $row['current_stock'] !== '' ? $row['current_stock'] : 0;
        $costPriceInput = isset($row['cost_price']) && $row['cost_price'] !== '' ? $row['cost_price'] : 0;
        $priceInput     = isset($row['price']) && $row['price'] !== '' ? $row['price'] : 0;
        
        // 2. Mapping Kolom Baru Sesuai Skema Database
        $minLeadTime = isset($row['min_lead_time']) && $row['min_lead_time'] !== '' ? $row['min_lead_time'] : 1;
        $maxLeadTime = isset($row['max_lead_time']) && $row['max_lead_time'] !== '' ? $row['max_lead_time'] : 3;
        $batchSize   = isset($row['batch_size']) && $row['batch_size'] !== '' ? $row['batch_size'] : 50;

        // --- HITUNG AVERAGE LEAD TIME (Logika Manual) ---
        // Karena input dari Excel dianggap manual, average = (min + max) / 2
        $avgLeadTime = ($minLeadTime + $maxLeadTime) / 2;

        // --- VALIDASI MANUAL (LOGIKA PENTING) ---
        // Jika Stok > 0 TAPI Harga Kosong/0 -> STOP IMPORT & LEMPAR ERROR
        if ($stockInput > 0 && ($costPriceInput <= 0)) {
            
            $productName = $row['name'] ?? 'Unknown';
            
            throw ValidationException::withMessages([
                'cost_price' => "Error pada Produk '{$productName}': Cost Price (HPP) wajib diisi jika Current Stock lebih dari 0."
            ]);
        }
        // ------------------------------------------------

        // 3. Create atau Update Produk
        $product = Product::updateOrCreate(
            ['code' => $row['product_code']], // Kunci pencarian (Unique)
            [
                'name'          => $row['name'],
                'packaging'     => $row['packaging'] ?? null,
                'current_stock' => $stockInput,
                
                // --- UPDATE SESUAI SKEMA BARU ---
                'min_lead_time_days'  => $minLeadTime,
                'max_lead_time_days'  => $maxLeadTime,
                'lead_time_average'   => $avgLeadTime,   // Simpan hasil kalkulasi rata-rata
                // 'is_manual_lead_time' => 'manual',       // Set manual karena input Excel
                'batch_size'          => $batchSize,
                // --------------------------------
                
                'price'         => $priceInput,
                'cost_price'    => $costPriceInput,
            ]
        );

        // 5. Catat Transaksi
        // LOGIKA BARU: Hanya catat jika ada stok atau harga (tidak nol semua)
        if ($stockInput > 0 || $costPriceInput > 0) {
            ProductTransaction::create([
                'product_id'            => $product->id,
                'transaction_date'      => now(),
                'type'                  => 'adjustment',
                'qty'                   => $stockInput,
                'cost_price'            => $costPriceInput,
                'current_stock_balance' => $stockInput,
                'product_name_snapshot'   => $product->name,
                'product_packaging_snapshot' => $product->packaging,
                'description'           => 'Import Master Data (Excel Adjustment)',
            ]);
        }

        return $product;
    }

    public function rules(): array
    {
        return [
            'product_code'  => 'required|string',
            'name'          => 'required|string',
            'packaging'     => 'nullable|string',
            'current_stock' => 'nullable|numeric|min:0',
            'price'         => 'nullable|numeric|min:0',
            'cost_price'    => 'nullable|numeric|min:0',
            
            // Validasi kolom baru
            'min_lead_time' => 'nullable|integer|min:1',
            'max_lead_time' => 'nullable|integer|min:1',
            'batch_size'    => 'nullable|integer|min:1',
        ];
    }
}