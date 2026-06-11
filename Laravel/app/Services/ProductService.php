<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function calculateDemandStats(Product $product) 
    {
        // Ambil data 30 hari ke belakang dari hari ini
        $thirtyDaysAgo = now()->subDays(30)->format('Y-m-d');
        
        // Cari transaksi penjualan barang keluar (sales_out)
        $transactions = ProductTransaction::where('product_id', $product->id)
            ->where('type', 'sales_out')
            ->where('transaction_date', '>=', $thirtyDaysAgo)
            ->get();

        if ($transactions->isEmpty()) {
            return ['average' => 0, 'max' => 0];
        }

        // Kelompokkan kuantitas (absolut) berdasarkan tanggal (Daily Demand)
        $dailyDemand = $transactions->groupBy('transaction_date')->map(function ($dayTransactions) {
            return abs($dayTransactions->sum('qty')); // abs() karena barang keluar mungkin minus di DB
        });

        // Rata-rata harus dibagi 30 hari kalender penuh, BUKAN dibagi jumlah hari yg ada transaksinya.
        return [
            'average' => $dailyDemand->sum() / 30,
            'max'     => $dailyDemand->max()
        ];
    }

    public function calculateLeadTimeStats(Product $product) 
    {
        // Jika mode manual, gunakan data yang sudah ada di database
        if ($product->is_manual_lead_time === 'manual') {
            return [
                'min'     => $product->min_lead_time_days,
                'max'     => $product->max_lead_time_days,
                'average' => ($product->min_lead_time_days + $product->max_lead_time_days) / 2,
            ];
        }

        // Jika mode automatic, tarik 30 batch produksi terakhir yang SUDAH SELESAI
        $recentBatches = ProductionBatch::where('product_id', $product->id)
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->orderBy('end_date', 'desc')
            ->take(30)
            ->get();

        // Fallback jika produk ini belum pernah diproduksi sama sekali
        if ($recentBatches->isEmpty()) {
            return [
                'min'     => $product->min_lead_time_days,
                'max'     => $product->max_lead_time_days,
                'average' => ($product->min_lead_time_days + $product->max_lead_time_days) / 2,
            ];
        }

        $leadTimes = [];
        foreach ($recentBatches as $batch) {
            $start = Carbon::parse($batch->start_date);
            $end = Carbon::parse($batch->end_date);
            
            // Hitung selisih hari. Kita gunakan max(1, ...) agar jika produksi 
            // selesai di hari yang sama (selisih 0), tetap dihitung butuh waktu minimal 1 hari (proses pabrik)
            $days = max(1, $start->diffInDays($end));
            $leadTimes[] = $days;
        }

        return [
            'min'     => min($leadTimes),
            'max'     => max($leadTimes),
            'average' => array_sum($leadTimes) / count($leadTimes),
        ];
    }
}