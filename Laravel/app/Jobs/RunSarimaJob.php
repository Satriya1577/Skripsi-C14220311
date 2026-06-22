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
use App\Services\ProductService;

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

        // ==========================================================
        // 1. DEKLARASI VARIABLE, SIAPKAN PARAMETER MODEL & PREPROCESSING
        // ==========================================================

        $targetDateStr  = Carbon::parse($jobLog->target_period)->format('Y-m-d');
        $preProcessing  = $product->pre_processing ?? 'raw';
        $params         = [
            $product->order_p ?? 1, $product->order_d ?? 1, $product->order_q ?? 1,
            $product->seasonal_P ?? 1, $product->seasonal_D ?? 1, $product->seasonal_Q ?? 1, 
            $product->seasonal_s ?? 12
        ];

        // Nilai Default jika API Python gagal atau data sales kosong
        $forecastVal    = 0;
        $rmse           = 0;
        $mape           = 0;
        $newSafetyStock = $product->safety_stock ?? 0;
        $recQty         = 0;

        try {
     
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
                print("[RunSarimaJob] No valid sales data (Confirmed/Shipped) found for this product.");
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

            // update variable dari python
            $forecastVal = (int) $output['forecast']['value'];
            $rmse = $output['metrics']['rmse'] ?? 0;
            $mape = $output['metrics']['mape'] ?? 0;

            // ==========================================================
            // 4. HITUNG SAFETY STOCK 
            // ==========================================================
            
            $productService = new ProductService();

            // A. Hitung AvgLeadTimeDemand (Hari)
            $leadTimeStats = $productService->calculateLeadTimeStatsFromHistory($product);
            $minLeadTime = $leadTimeStats['min'];
            $maxLeadTime = $leadTimeStats['max'];
            $avgLeadTime = $leadTimeStats['average'];

            // B. Hitung Statistik Demand/Penjualan (Daily Average & Max)
            $demandStats = $productService->calculateDemandStatsFromHistory($product);
            $avgDailyDemand = $demandStats['average'];
            $maxDailyDemand = $demandStats['max'];

            // C. Hitung Safety Stock (Kuantitas)
            $maxExpectedDemand = $maxLeadTime * $maxDailyDemand;
            $averageExpectedDemand = $avgLeadTime * $avgDailyDemand;

            $safetyStock = max(0, $maxExpectedDemand - $averageExpectedDemand);
            $newSafetyStock = ceil($safetyStock); // Dibulatkan ke atas agar aman

            // Update data produk
            $product->update([
                'min_lead_time_days' => $minLeadTime,
                'max_lead_time_days' => $maxLeadTime,
                'lead_time_average'  => $avgLeadTime,
                'safety_stock'       => $newSafetyStock,
            ]);

            // ==========================================================
            // 5. SIMPAN VALIDATION LOG 
            // ==========================================================

            $tempPlan = ProductionPlan::firstOrCreate(
                ['product_id' => $product->id, 'period' => $targetDateStr],
                [
                    'status' => 'draft',
                    'forecast_qty'               => $forecastVal,
                    'current_stock_snapshot'     => $product->current_stock,
                    'safety_stock_snapshot'      => $newSafetyStock,
                    'recommended_production_qty' => 0,
                    'rmse'                       => $rmse,
                    'mape'                       => $mape,
                    'order_p'                    => $params[0],
                    'order_d'                    => $params[1],
                    'order_q'                    => $params[2],
                    'seasonal_P'                 => $params[3],
                    'seasonal_D'                 => $params[4],
                    'seasonal_Q'                 => $params[5],
                    'seasonal_s'                 => $params[6]
                ]
            );

            ValidationLog::where('production_plan_id', $tempPlan->id)->delete();

            $allLogs = collect($output['validation_data']);
            $recentLogs = $allLogs->sortBy('date')->values()->take(-13);

            foreach ($recentLogs as $log) {
                ValidationLog::create([
                    'production_plan_id' => $tempPlan->id,
                    'period'             => $log['date'],
                    'actual_qty'         => $log['actual'],
                    'predicted_qty'      => $log['predicted']
                ]);
            }

            $jobLog->update(['status' => 'completed', 'message' => 'Forecast generated successfully with ' . strtoupper($preProcessing) . ' preprocessing.']);

        } catch (\Exception $e) {
            $jobLog->update(['status' => 'failed', 'message' => substr($e->getMessage(), 0, 1000)]);
        } finally {
            $recQty = max(0, ($forecastVal + $newSafetyStock) - $product->current_stock);
            ProductionPlan::updateOrCreate(
                [
                    'product_id' => $product->id, 
                    'period'     => $targetDateStr
                ],
                [
                    'forecast_qty'               => $forecastVal,
                    'current_stock_snapshot'     => $product->current_stock,
                    'safety_stock_snapshot'      => $newSafetyStock,
                    'recommended_production_qty' => $recQty,
                    'rmse'                       => $rmse,
                    'mape'                       => $mape,
                    'order_p'                    => $params[0],
                    'order_d'                    => $params[1],
                    'order_q'                    => $params[2],
                    'seasonal_P'                 => $params[3],
                    'seasonal_D'                 => $params[4],
                    'seasonal_Q'                 => $params[5],
                    'seasonal_s'                 => $params[6],
                    'status'                     => 'draft',
                ]
            );
        }
    }
}