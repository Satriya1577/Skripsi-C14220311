<?php

namespace App\Http\Controllers;

use App\Models\ProductMaterial;
use App\Http\Controllers\Controller;
use App\Imports\ProductMaterialsImport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ProductMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validasi (Hapus Rule::unique agar bisa di-update)
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'material_id' => 'required|exists:materials,id',
            'amount_needed' => 'required|numeric',
        ]);

        // 2. Eksekusi Database
        // Gunakan $validated agar lebih aman & bersih
        ProductMaterial::updateOrCreate(
            // Kondisi pencarian (WHERE product_id = X AND material_id = Y)
            [
                'product_id'  => $validated['product_id'],
                'material_id' => $validated['material_id']
            ],
            // Data yang di-update/insert
            [
                'amount_needed' => $validated['amount_needed']
            ]
        );

        return redirect()->back()->with('success', 'Material berhasil disimpan/diperbarui.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductMaterial $product_material)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductMaterial $product_material)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductMaterial $product_material)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductMaterial $product_material)
    {
        //
    }

    public function import() {
        return view('products.import.product_materials');
    }



}
