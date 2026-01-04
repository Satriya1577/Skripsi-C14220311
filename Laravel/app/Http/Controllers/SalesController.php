<?php

namespace App\Http\Controllers;

use App\Models\Sales;
use App\Http\Controllers\Controller;
use App\Imports\SalesImport;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SalesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::all();
        $sales = Sales::orderBy('id', 'asc')->paginate(10);
        return view('sales.index', compact('sales', 'products'));
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
        // 1. Validasi Input
        $request->validate([
            'sale_id'          => 'nullable|exists:sales,id', 
            'product_id'       => 'required|exists:products,id',
            'quantity_sold'    => 'required|integer|min:1',
            'price_per_unit'   => 'nullable|numeric|min:0',
            'transaction_date' => 'required|date',
            'nama_distributor' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction(); 

            if ($request->filled('sale_id')) {
                
                // === BAGIAN UPDATE ===
                $sale = Sales::findOrFail($request->sale_id);

                // A. KEMBALIKAN STOK LAMA DULU (Revert Stock)
                // Kita kembalikan stok produk yang lama sejumlah qty lama
                // Ini penting jika user mengganti produk atau mengubah jumlah qty
                $sale->product->increment('current_stock', $sale->quantity_sold);

                // B. Siapkan Data Baru
                $product = Product::findOrFail($request->product_id);
                
                // Logic Harga (Sesuai request: Custom atau Master)
                $finalPricePerUnit = $request->price_per_unit ? $request->price_per_unit : $product->price;
                $finalTotalPrice   = $request->quantity_sold * $finalPricePerUnit;

                // C. Update Data Penjualan
                $sale->update([
                    'product_id'       => $product->id,
                    'transaction_date' => $request->transaction_date,
                    'quantity_sold'    => $request->quantity_sold,
                    'price_per_unit'   => $finalPricePerUnit,
                    'total_price'      => $finalTotalPrice,
                    'nama_distributor' => $request->nama_distributor,
                ]);

                // D. POTONG STOK BARU
                // Kurangi stok produk (bisa jadi produk yang sama atau beda) dengan qty baru
                $product->decrement('current_stock', $request->quantity_sold);

                $message = 'Penjualan berhasil diperbarui!';

            } else {

                // === BAGIAN CREATE (INSERT BARU) ===
                // Logic ini SAMA PERSIS dengan yang Anda punya sebelumnya

                $product = Product::findOrFail($request->product_id);

                // Logic Harga
                $finalPricePerUnit = $request->price_per_unit ? $request->price_per_unit : $product->price;
                $finalTotalPrice   = $request->quantity_sold * $finalPricePerUnit;

                // Simpan Database
                Sales::create([
                    'product_id'       => $product->id,
                    'transaction_date' => $request->transaction_date,
                    'quantity_sold'    => $request->quantity_sold,
                    'price_per_unit'   => $finalPricePerUnit,
                    'total_price'      => $finalTotalPrice,
                    'nama_distributor' => $request->nama_distributor,
                ]);

                // Kurangi Stok
                $product->decrement('current_stock', $request->quantity_sold);

                $message = 'Penjualan berhasil disimpan!';
            }

            DB::commit();
            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

  
    /**
     * Display the specified resource.
     */
    public function show(sales $sales)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(sales $sales)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, sales $sales) 
    {
        //
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sales $sales)
    {
        $sales = Sales::findOrFail($sales->id);
        
        // 1. KEMBALIKAN STOK KE GUDANG
        // Karena penjualan batal, barang balik ke stok
        $sales->product->increment('current_stock', $sales->quantity_sold);

        // 2. HAPUS DATA
        $sales->delete();

        return redirect()->back()->with('success', 'Data dihapus dan stok telah dikembalikan.');
    }

}
