<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // 1. Create atau Update Produk
        $product = Product::updateOrCreate(
            ['code' => $row['product_code']], 
            [
                'name'          => $row['name'],
                'current_stock' => $row['current_stock'] ?? 0,
                'safety_stock'  => $row['safety_stock'] ?? 0,
                'price'         => $row['price'] ?? 0,
            ]
        );

        // 2. Tambahkan Default Sarima Config (Jika belum ada)
        // Kita gunakan 'firstOrCreate' untuk mencegah duplikasi jika produknya hanya di-update
        $product->sarimaConfig()->firstOrCreate(
            ['product_id' => $product->id], // Cek apakah config untuk produk ini sudah ada?
            [
                // Jika belum ada, buat dengan nilai default ini:
                'order_p'         => 1, 
                'order_d'         => 1,
                'order_q'         => 1,
                'seasonal_P'      => 0,
                'seasonal_D'      => 0,
                'seasonal_Q'      => 0,
                'seasonal_s'      => 12,
                'rmse'            => 0,
                'mape'            => 0,
                'last_trained_at' => now(),
            ]
        );

        return $product;
    }

    public function rules(): array
    {
        return [
            'product_code'  => 'required|string', 
            'name'          => 'required|string',
            'current_stock' => 'nullable|numeric|min:0',
            'safety_stock'  => 'nullable|numeric|min:0',
            'price'         => 'nullable|numeric|min:0',
        ];
    }
}