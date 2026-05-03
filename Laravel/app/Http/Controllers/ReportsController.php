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

    public function showIncomeStatement(Request $request)
    {
       // Ambil filter bulan dan tahun dari request, default ke bulan ini
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Query untuk mengambil rincian per produk yang terjual di bulan tersebut
        // Hanya menghitung sales_order dengan status confirmed atau shipped
        $salesDetails = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->whereIn('sales_orders.status', ['confirmed', 'shipped'])
            ->whereBetween('sales_orders.transaction_date', [$startDate, $endDate])
            ->select(
                'sales_order_items.product_name_snapshot as product_name',
                DB::raw('SUM(sales_order_items.quantity) as total_qty'),
                DB::raw('SUM(sales_order_items.subtotal) as total_revenue'),
                // Mengkalikan cogs_snapshot (HPP per unit) dengan quantity
                DB::raw('SUM(IFNULL(sales_order_items.cogs_snapshot, 0) * sales_order_items.quantity) as total_cogs')
            )
            ->groupBy('sales_order_items.product_name_snapshot')
            ->orderByDesc('total_revenue')
            ->get();

        // Hitung Grand Total
        $totalRevenue = $salesDetails->sum('total_revenue');
        $totalCogs = $salesDetails->sum('total_cogs');
        $grossProfit = $totalRevenue - $totalCogs;
        $profitMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;

        // Data untuk dropdown filter bulan dan tahun
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        $years = range(Carbon::now()->year - 3, Carbon::now()->year + 1);

        return view('reports.incomestatement', compact(
            'salesDetails', 'totalRevenue', 'totalCogs', 'grossProfit', 
            'profitMargin', 'month', 'year', 'months', 'years'
        ));
    }
}
