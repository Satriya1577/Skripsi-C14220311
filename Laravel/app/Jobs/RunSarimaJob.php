<?php

namespace App\Jobs;

use App\Models\ForecastingJob;
use App\Models\Product;
use App\Models\ProductionPlan;
use App\Models\SalesOrderItem;
use App\Models\ValidationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RunSarimaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobId;

    public function __construct($id)
    {
        $this->jobId = $id;
    }

    public function handle()
    {
        $jobLog = ForecastingJob::with('product')->find($this->jobId);
        if (!$jobLog) return;

        $jobLog->update(['status' => 'processing', 'message' => 'Preparing sales data...']);
        $product = $jobLog->product;

        try {
            // ==========================================================
            // 1. SIAPKAN PARAMETER MODEL & PREPROCESSING
            // ==========================================================
            $params = [
                $product->order_p ?? 1, 
                $product->order_d ?? 1, 
                $product->order_q ?? 1,
                $product->seasonal_P ?? 1, 
                $product->seasonal_D ?? 1, 
                $product->seasonal_Q ?? 1, 
                $product->seasonal_s ?? 12
            ];

            $preProcessing = $product->pre_processing ?? 'raw';

            // ==========================================================
            // 2. AMBIL & OLAH DATA SALES (GAP FILLING)
            // ==========================================================
            $rawSales = SalesOrderItem::where('product_id', $product->id)
                ->whereHas('salesOrder', function($query) {
                    $query->whereIn('status', ['confirmed', 'shipped']);
                })
                ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
                ->selectRaw('DATE_FORMAT(sales_orders.transaction_date, "%Y-%m-01") as period, SUM(sales_order_items.quantity) as total_qty')
                ->groupBy('period')
                ->orderBy('period', 'asc')
                ->pluck('total_qty', 'period')
                ->toArray();

            if (empty($rawSales)) {
                throw new \Exception("Tidak ada data penjualan valid (Confirmed/Shipped) untuk produk ini.");
            }

            // Gap Filling logic: Dari transaksi pertama sampai Bulan Ini (Feb 2026)
            $firstDate = Carbon::parse(array_key_first($rawSales));
            $lastDate = Carbon::now()->startOfMonth(); 

            $periodRange = CarbonPeriod::create($firstDate, '1 month', $lastDate);
            $formattedSalesData = [];

            foreach ($periodRange as $date) {
                $key = $date->format('Y-m-01');
                $formattedSalesData[] = [
                    'date' => $key,
                    'qty'  => isset($rawSales[$key]) ? (int)$rawSales[$key] : 0 
                ];
            }

            // ==========================================================
            // 3. KIRIM KE PYTHON API
            // ==========================================================
            $jobLog->update(['message' => 'Running SARIMA analysis (' . strtoupper($preProcessing) . ')...']);
            $targetDateStr = Carbon::parse($jobLog->target_period)->format('Y-m-d');

            $response = Http::post('http://127.0.0.1:5000/forecast', [
                'sales_data'     => $formattedSalesData,
                'target_date'    => $targetDateStr, 
                'params'         => $params,
                'cutoff'         => 0,
                'pre_processing' => $preProcessing
            ]);

            if ($response->failed()) {
                throw new \Exception("Python API Error: " . $response->body());
            }

            $output = $response->json();
            
            if (isset($output['error'])) {
                throw new \Exception($output['error']);
            }

            // ==========================================================
            // 4. HITUNG SAFETY STOCK (FIX LOGIC LEAD TIME)
            // ==========================================================
            
            // A. Hitung AvgLeadTimeDemand (Hari)
            $avgLeadTimeDays = 0;
            $maxLeadTimeDays = 0;

            if ($product->is_manual_lead_time === 'manual') {
                // Manual: Rata-rata Min & Max
                $min = $product->min_lead_time_days ?? 0;
                $max = $product->max_lead_time_days ?? 0;
                $avgLeadTimeDays = ($min + $max) / 2;
                $maxLeadTimeDays = $max;
            } else {
                // Automatic: Hitung selisih start_date & end_date dari 30 batch terakhir
                $batches = DB::table('production_batches')
                    ->where('product_id', $product->id)
                    ->whereNotNull('start_date') // Pastikan sudah mulai
                    ->whereNotNull('end_date')   // Pastikan sudah selesai
                    ->orderBy('end_date', 'desc')
                    ->limit(30)
                    ->get();
                
                if ($batches->isEmpty()) {
                    // Fallback ke setting manual jika belum ada history produksi
                    $avgLeadTimeDays = ($product->min_lead_time_days + $product->max_lead_time_days) / 2;
                    $maxLeadTimeDays = $product->max_lead_time_days;
                } else {
                    // Hitung rata-rata durasi hari (end - start)
                    $totalDays = 0;
                    $highestDuration = 0;

                    foreach ($batches as $batch) {
                        $start = Carbon::parse($batch->start_date);
                        $end = Carbon::parse($batch->end_date);
                        // Hitung durasi batch ini
                        $duration = $end->diffInDays($start);
                        
                        // Tambahkan ke total untuk rata-rata
                        $totalDays += $duration;

                        if ($duration > $highestDuration) {
                            $highestDuration = $duration;
                        }
                    }
                    $avgLeadTimeDays = $totalDays / $batches->count();
                    $maxLeadTimeDays = $highestDuration;
                }
            }

            // B. Hitung Avg6MonthSales (Rata-rata per Bulan -> Konversi ke Hari)
            $sixMonthsAgo = Carbon::now()->subMonths(6);
            
            $totalSales6Months = SalesOrderItem::where('product_id', $product->id)
                ->whereHas('salesOrder', function($query) use ($sixMonthsAgo) {
                    $query->where('status', 'shipped') 
                          ->where('transaction_date', '>=', $sixMonthsAgo);
                })
                ->sum('quantity');

            // Rata-rata per bulan (Total / 6)
            $avgMonthlySales = $totalSales6Months > 0 ? $totalSales6Months / 6 : 0;
            
            // Konversi ke Rata-rata per Hari (Asumsi 30 hari/bulan)
            $avgDailySales = $avgMonthlySales / 30;

            // C. Hitung LeadTimeDemand (Qty)
            // Rumus: Avg Lead Time (Hari) x Avg Sales (Qty/Hari)
            $leadTimeDemandQty = $avgLeadTimeDays * $avgDailySales;

            // D. Hitung Safety Stock
            // Rumus: (Max Lead Time (Hari) * Daily Sales) - (Lead Time Demand Rata-rata)
            // Penjelasan: Kita membandingkan "Konsumsi Maksimum saat Lead Time Terburuk" dikurangi "Konsumsi Normal saat Lead Time Normal"
            $maxLeadTimeDemandQty = $maxLeadTimeDays * $avgDailySales;
            $newSafetyStock = ceil($maxLeadTimeDemandQty - $leadTimeDemandQty);
            
            // Pastikan SS tidak negatif
            $newSafetyStock = max(0, $newSafetyStock);

            // Update Master Product
            $product->update(['safety_stock' => $newSafetyStock]);

            // ==========================================================
            // 5. SIMPAN PRODUCTION PLAN
            // ==========================================================
            $forecastVal = (int) $output['forecast']['value'];
            $recQty = max(0, ($forecastVal + $newSafetyStock) - $product->current_stock);

            $productionPlan = ProductionPlan::updateOrCreate(
                [
                    'product_id' => $product->id, 
                    'period'     => $targetDateStr
                ],
                [
                    'forecast_qty'               => $forecastVal,
                    'current_stock_snapshot'     => $product->current_stock,
                    'safety_stock_snapshot'      => $newSafetyStock,
                    'recommended_production_qty' => $recQty,
                    'rmse'                       => $output['metrics']['rmse'] ?? 0,
                    'mape'                       => $output['metrics']['mape'] ?? 0,
                    'order_p'      => $params[0],
                    'order_d'      => $params[1],
                    'order_q'      => $params[2],
                    'seasonal_P'   => $params[3],
                    'seasonal_D'   => $params[4],
                    'seasonal_Q'   => $params[5],
                    'seasonal_s'   => $params[6],
                    'status'       => 'draft',
                ]
            );

            // ==========================================================
            // 6. SIMPAN VALIDATION LOGS
            // ==========================================================
            ValidationLog::where('production_plan_id', $productionPlan->id)->delete();

            $allLogs = collect($output['validation_data']);
            // Ambil 13 data terakhir agar grafik tidak kepanjangan
            $recentLogs = $allLogs->sortBy('date')->values()->take(-13);

            foreach ($recentLogs as $log) {
                ValidationLog::create([
                    'production_plan_id' => $productionPlan->id,
                    'period'             => $log['date'],
                    'actual_qty'         => $log['actual'],
                    'predicted_qty'      => $log['predicted']
                ]);
            }

            $jobLog->update(['status' => 'completed', 'message' => 'Forecast generated successfully with ' . strtoupper($preProcessing) . ' preprocessing.']);

        } catch (\Exception $e) {
            $jobLog->update(['status' => 'failed', 'message' => substr($e->getMessage(), 0, 1000)]);
        }
    }
}