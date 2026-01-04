<?php

namespace App\Imports;

use App\Models\Sales;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class SalesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // 1. Handling Tanggal
        $transactionDate = now();
        if (isset($row['tanggal_nota'])) {
            try {
                if (is_numeric($row['tanggal_nota'])) {
                    $transactionDate = Date::excelToDateTimeObject($row['tanggal_nota']);
                } else {
                    $transactionDate = Carbon::createFromFormat('d/m/Y', $row['tanggal_nota']);
                }
            } catch (\Exception $e) {
                // silence is golden
            }
        }

        // 2. Cari Produk
        $excelKode = $row['kode'] ?? $row['kode_barang'] ?? null;
        if (!$excelKode) return null;

        $product = Product::where('code', $excelKode)->first(); // Sesuaikan 'code' dengan kolom di DB Anda
        if (!$product) return null;

        // --- HITUNGAN ---
        $qty = $row['quantity'];
        $pricePerUnit = $product->price ?? 0; 
        $totalPrice = $qty * $pricePerUnit;

        // ==========================================================
        // TAMBAHAN LOGIC: UPDATE STOK (DECREMENT)
        // ==========================================================
        
        // Pastikan nama kolom 'stock' ini sesuai dengan tabel products Anda
        // Bisa jadi 'stock', 'quantity', 'current_stock', atau 'qty'
        
        $product->decrement('current_stock', $qty); 

        // ==========================================================

        // 3. Return Data Sales untuk di-insert
        return new Sales([
            'product_id'       => $product->id,
            'transaction_date' => $transactionDate,
            'quantity_sold'    => $qty,
            'price_per_unit'   => $pricePerUnit,
            'total_price'      => $totalPrice,
        ]);
    }
}