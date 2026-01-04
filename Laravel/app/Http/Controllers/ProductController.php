<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductsImport;
use App\Models\Material;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::orderBy('id', 'desc')->paginate(10);
        return view('products.index',compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'current_stock' => 'nullable|integer',
            'safety_stock' => 'nullable|integer',
            'price' => 'nullable|numeric',
        ]);

        if ($request->filled('product_id')) {
            Product::findOrFail($request->product_id)->update($data);
        } else {
            $product = Product::create($data);
            $product->sarimaConfig()->create([
                'order_p' => 1, 
                'order_d' => 1,
                'order_q' => 1,
                'seasonal_P' => 0,
                'seasonal_D' => 0,
                'seasonal_Q' => 0,
                'seasonal_s' => 12,
                'rmse' => 0,
                'mape' => 0,
                'last_trained_at' => now(),
            ]);
        }
        return redirect()->route('products.index')->with('success', 'Product saved successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(product $product)
    {
        //$materials = Material::orderBy('name')->get();
        $materials = Material::where('is_active', true)->orderBy('name')->get();
        return view('products.show', compact('product', 'materials'));
    }

    /**
    * Remove the specified resource from storage.
    */
    public function destroy(product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }

    /**
    * Show the form for creating a new resource.
    */
    public function create()
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, product $product)
    {
        //
    }
}
