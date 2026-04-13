<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MaterialTransaction;
use App\Models\ProductTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }
    
    public function showProductReports(Request $request)
    {
        // Set default filter ke 30 hari yang lalu
        $defaultStartDate = Carbon::now()->subDays(30)->format('Y-m-d');
        $startDate = $request->input('start_date', $defaultStartDate);

        $reports = ProductTransaction::with('product') // Eager load untuk mengambil kode produk
            ->select(
                'product_id',
                DB::raw('MAX(product_name_snapshot) as product_name'),
                DB::raw('SUM(CASE WHEN qty > 0 THEN qty ELSE 0 END) as Masuk'),
                DB::raw('SUM(CASE WHEN qty < 0 THEN qty ELSE 0 END) as Keluar')
            )
            ->whereDate('transaction_date', '>=', $startDate)
            ->groupBy('product_id')
            ->paginate(20);

        return view('reports.products', compact('reports', 'startDate'));
    }

    public function showMaterialReports(Request $request)
    {
        // Set default filter ke 30 hari yang lalu
        $defaultStartDate = Carbon::now()->subDays(30)->format('Y-m-d');
        $startDate = $request->input('start_date', $defaultStartDate);

        $reports = MaterialTransaction::with('material') 
            ->select(
                'material_id',
                DB::raw('MAX(material_name_snapshot) as material_name'),
                DB::raw('SUM(CASE WHEN qty > 0 THEN qty/material_conversion_factor_snapshot ELSE 0 END) as Masuk'),
                DB::raw('SUM(CASE WHEN qty < 0 THEN qty/material_conversion_factor_snapshot ELSE 0 END) as Keluar'),
                DB::raw('MAX(purchase_unit_snapshot) as purchase_unit')
            )
            ->whereDate('transaction_date', '>=', $startDate)
            ->groupBy('material_id') // Filter whereDate dihapus
            ->paginate(20);

        return view('reports.materials', compact('reports'));
    }
}
