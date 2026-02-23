<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MaterialTransaction;
use App\Models\ProductTransaction;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    
    public function showProductReports()
    {
        $transactions = ProductTransaction::with(['product', 'salesOrder', 'productionRealization'])
                        // ->orderBy('transaction_date', 'desc')
                        ->orderBy('created_at', 'desc')
                        ->paginate(20); // <--- Perubahan di sini

        return view('reports.products', compact('transactions'));
    }

    public function showMaterialReports(Request $request)
    {
        // 1. Inisialisasi Query dengan Eager Loading Relasi
        $query = MaterialTransaction::with([
            'material',              // Untuk nama & kode material
            'purchaseOrder',         // Untuk referensi PO (jika ada)
            'productionRealization'  // Untuk referensi Produksi (jika ada)
        ]);

        // 2. Logika Filter Tanggal (Dari input form di View)
        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }

        // 3. Sorting & Pagination
        $transactions = $query->orderBy('transaction_date', 'desc') // Urutkan tanggal transaksi terbaru
                              ->orderBy('created_at', 'desc')       // Jika tanggal sama, urutkan jam input terbaru
                              ->paginate(20);                       // Batasi 20 per halaman

        // Agar parameter filter tetap ada saat klik halaman 2, 3, dst.
        $transactions->appends($request->all());

        // 4. Return View
        // Pastikan nama file blade Anda sesuai, misal: resources/views/reports/materials.blade.php
        return view('reports.materials', compact('transactions'));
    }
}
