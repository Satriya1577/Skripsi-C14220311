<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\SalesOrderItem;
use App\Models\SarimaProductEvaluation;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RunGridSearchEvaluationJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productId;

    protected Product $product;

    // Timeout 1 jam per produk
    public $timeout = 3600; 
    public $tries = 1;

    public function __construct($product)
    {
        $this->product = $product;
    }

    public function handle()
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        try {
        
            Log::info("RunGridSearchEvaluationJob class");
            $rawSales = SalesOrderItem::where('product_id', $this->product->id)
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
                Log::warning("Grid Search Skipped: No sales data for product ID {$this->product->id}");
                return;
            }

            // 2. Gap Filling (HANYA antara transaksi pertama hingga transaksi terakhir)
            $firstDate = Carbon::parse(array_key_first($rawSales));
            
            // PERUBAHAN: Berhenti tepat di bulan transaksi terakhir yang terekam
            $lastDate = Carbon::parse(array_key_last($rawSales)); 

            $periodRange = CarbonPeriod::create($firstDate, '1 month', $lastDate);
            $formattedSalesData = [];

            foreach ($periodRange as $date) {
                $key = $date->format('Y-m-01');
                $formattedSalesData[] = [
                    'date' => $key,
                    'qty'  => isset($rawSales[$key]) ? (int)$rawSales[$key] : 0 
                ];
            }

            // PERUBAHAN: Menampilkan output array ke Log sebelum dikirim ke Python
            Log::info("Data dikirim ke Python untuk Product ID {$this->product->id}: \n" . json_encode($formattedSalesData, JSON_PRETTY_PRINT));

            // 3. Kirim ke Python API
            $response = Http::timeout(3500)->post('http://127.0.0.1:5000/grid-search', [
                'sales_data' => $formattedSalesData,
            ]);

            if ($response->failed()) {
                throw new \Exception("Python Grid Search Error: " . $response->body());
            }

            $output = $response->json();

            if(isset($output['error'])) {
                 throw new \Exception($output['error']);
            }

            // 4. Simpan Hasil ke Tabel SarimaProductEvaluation
            $bestParams = $output['best_params']; // [p, d, q, P, D, Q, s]
            $metrics = $output['all_preprocessing_metrics']; // Akses data performa setiap teknik

            // Update jika product_code sudah ada, Create jika belum ada
            SarimaProductEvaluation::updateOrCreate(
                ['product_code' => $this->product->code], // Parameter Pencarian
                [
                    'product_name'   => $this->product->name,
                    
                    // Parameter Model (Berdasarkan tuning raw data)
                    'raw_order_p'    => $bestParams[0],
                    'raw_order_d'    => $bestParams[1],
                    'raw_order_q'    => $bestParams[2],
                    'raw_seasonal_P' => $bestParams[3],
                    'raw_seasonal_D' => $bestParams[4],
                    'raw_seasonal_Q' => $bestParams[5],
                    'raw_seasonal_s' => $bestParams[6],
                    'last_trained_at'=> now(),

                    // Metrics Raw
                    'raw_rmse' => $metrics['raw']['rmse'] ?? null,
                    'raw_mape' => $metrics['raw']['mape'] ?? null,

                    // Metrics Moving Average (MA)
                    'ma_rmse'  => $metrics['ma']['rmse'] ?? null,
                    'ma_mape'  => $metrics['ma']['mape'] ?? null,

                    // Metrics Savitzky-Golay (SG)
                    'sg_rmse'  => $metrics['sg']['rmse'] ?? null,
                    'sg_mape'  => $metrics['sg']['mape'] ?? null,

                    // Metrics Box-Cox (BC)
                    'bc_rmse'  => $metrics['bc']['rmse'] ?? null,
                    'bc_mape'  => $metrics['bc']['mape'] ?? null,

                    // Metrics Yeo-Johnson (YJ)
                    'yj_rmse'  => $metrics['yj']['rmse'] ?? null,
                    'yj_mape'  => $metrics['yj']['mape'] ?? null,
                ]
            );

            Log::info("Grid Search Evaluation Success for Product ID {$this->product->id}.");

        } catch (\Exception $e) {
            Log::error("Grid Search Evaluation Failed for Product ID {$this->product->id}: " . $e->getMessage());
            throw $e;
        }
    }
}