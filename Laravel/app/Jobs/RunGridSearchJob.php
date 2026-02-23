<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\SalesOrderItem;
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

class RunGridSearchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productId;

    // Timeout 1 jam per produk
    public $timeout = 3600; 
    public $tries = 1;

    public function __construct($productId)
    {
        $this->productId = $productId;
    }

    public function handle()
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        try {
        
            $rawSales = SalesOrderItem::where('product_id', $this->productId)
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
                Log::warning("Grid Search Skipped: No sales data for product ID {$this->productId}");
                return;
            }

            // 2. Gap Filling (Sampai Bulan Ini)
            $firstDate = Carbon::parse(array_key_first($rawSales));
            $lastDate = Carbon::now()->startOfMonth(); // Sampai Februari 2026

            $periodRange = CarbonPeriod::create($firstDate, '1 month', $lastDate);
            $formattedSalesData = [];

            foreach ($periodRange as $date) {
                $key = $date->format('Y-m-01');
                $formattedSalesData[] = [
                    'date' => $key,
                    'qty'  => isset($rawSales[$key]) ? (int)$rawSales[$key] : 0 
                ];
            }

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

            // 4. Simpan Hasil Terbaik ke Tabel Products
            $bestParams = $output['best_params']; // [p, d, q, P, D, Q, s]
            $bestPreprocessing = $output['preprocessing']; // 'raw', 'ma', 'bc', etc.

            $product = Product::find($this->productId);
            if ($product) {
                $product->update([
                    'order_p' => $bestParams[0],
                    'order_d' => $bestParams[1],
                    'order_q' => $bestParams[2],
                    'seasonal_P' => $bestParams[3],
                    'seasonal_D' => $bestParams[4],
                    'seasonal_Q' => $bestParams[5],
                    'seasonal_s' => $bestParams[6],
                    'pre_processing' => $bestPreprocessing, // Simpan metode terbaik
                    'rmse' => $output['metrics']['rmse'] ?? 0,
                    'mape' => $output['metrics']['mape'] ?? 0,
                    'last_trained_at' => now(),
                ]);
            }

            Log::info("Grid Search Success for Product ID {$this->productId}. Method: {$bestPreprocessing}");

        } catch (\Exception $e) {
            Log::error("Grid Search Failed for Product ID {$this->productId}: " . $e->getMessage());
            throw $e;
        }
    }
}