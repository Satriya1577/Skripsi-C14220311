<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Material;
use App\Models\ProductMaterial;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ProductMaterialsImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // 1. Cari Produk berdasarkan kolom 'kode' (Contoh: A247)
        $product = Product::where('code', $row['kode'])->first();

        // 2. Cari Material berdasarkan kolom 'material' (Contoh: Tepung Terigu)
        // Note: Pencarian ini Case-Insensitive di sebagian besar database (MySQL default)
        $material = Material::where('name', $row['material'])->first();

        // Safety check: Jika entah kenapa lolos validasi tapi data tidak ketemu
        if (!$product || !$material) {
            return null;
        }

        // 3. Simpan ke tabel pivot product_materials
        // Gunakan updateOrCreate agar jika resep sudah ada, qty-nya diupdate
        return ProductMaterial::updateOrCreate(
            [
                // Kunci Pencarian (Unik)
                'product_id'  => $product->id,
                'material_id' => $material->id,
            ],
            [
                // Data yang diupdate/insert
                // Mapping: Kolom Excel 'qty' --> Kolom DB 'amount_needed'
                'amount_needed' => $row['qty'], 
            ]
        );
    }

    /**
     * Rules Validasi
     * Baris Excel akan ditolak jika melanggar aturan ini
     */
    public function rules(): array
    {
        return [
            // Kolom 'Kode' di Excel wajib ada di tabel products kolom code
            'kode' => 'required|exists:products,code',

            // Kolom 'Material' di Excel wajib ada di tabel materials kolom name
            'material' => 'required|exists:materials,name',

            // Kolom 'Qty' wajib angka dan minimal 0
            'qty' => 'required|numeric|min:0',
        ];
    }

    /**
     * Pesan Error Custom (Opsional, agar lebih mudah dibaca user)
     */
    public function customValidationMessages()
    {
        return [
            'kode.exists' => 'Kode Produk ":input" tidak ditemukan di database Master Produk.',
            'material.exists' => 'Material bernama ":input" tidak ditemukan di database Master Material.',
            'qty.numeric' => 'Kolom Qty harus berupa angka.',
        ];
    }
}