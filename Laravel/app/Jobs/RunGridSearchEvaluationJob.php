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
            Log::info("[RunGridSearchEvaluationJob] RunGridSearchEvaluationJob class started for Product ID {$this->product->id}");

            // =====================================================================
            // BLOK 1: CEK DATA DI CSV MASTER TERLEBIH DAHULU (RESUME FEATURE)
            // =====================================================================
            // Menggunakan nama file statis
            $csvFileName = 'backup_sarima_evaluation_result.csv';
            $csvPath = storage_path('logs/' . $csvFileName);
            $foundInCsv = false;

            if (file_exists($csvPath)) {
                if (($handle = fopen($csvPath, "r")) !== FALSE) {
                    $headers = fgetcsv($handle, 1000, ","); // Lewati baris header pertama
                    
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        // Index 1 adalah Product Code
                        if (isset($data[1]) && $data[1] === $this->product->code) {
                            $foundInCsv = true;
                            
                            Log::info("[RunGridSearchEvaluationJob] Product ID {$this->product->id} (Code: {$this->product->code}) di-skip dari Python karena data sudah ada di master CSV. Melakukan restore ke Database...");

                            // Restore data dari CSV langsung ke Database
                            SarimaProductEvaluation::updateOrCreate(
                                ['product_code' => $this->product->code], 
                                [
                                    'product_name'   => $this->product->name,
                                    'raw_order_p'    => $data[3] !== '' ? $data[3] : null,
                                    'raw_order_d'    => $data[4] !== '' ? $data[4] : null,
                                    'raw_order_q'    => $data[5] !== '' ? $data[5] : null,
                                    'raw_seasonal_P' => $data[6] !== '' ? $data[6] : null,
                                    'raw_seasonal_D' => $data[7] !== '' ? $data[7] : null,
                                    'raw_seasonal_Q' => $data[8] !== '' ? $data[8] : null,
                                    'raw_seasonal_s' => $data[9] !== '' ? $data[9] : null,
                                    'last_trained_at'=> now(),
                                    'raw_rmse' => $data[10] !== '' ? $data[10] : null,
                                    'raw_mape' => $data[11] !== '' ? $data[11] : null,
                                    'ma_rmse'  => $data[12] !== '' ? $data[12] : null,
                                    'ma_mape'  => $data[13] !== '' ? $data[13] : null,
                                    'sg_rmse'  => $data[14] !== '' ? $data[14] : null,
                                    'sg_mape'  => $data[15] !== '' ? $data[15] : null,
                                    'bc_rmse'  => $data[16] !== '' ? $data[16] : null,
                                    'bc_mape'  => $data[17] !== '' ? $data[17] : null,
                                    'yj_rmse'  => $data[18] !== '' ? $data[18] : null,
                                    'yj_mape'  => $data[19] !== '' ? $data[19] : null,
                                ]
                            );
                            break; 
                        }
                    }
                    fclose($handle);
                }
            }

            // Jika produk sudah ada di master CSV, hentikan eksekusi script ini
            if ($foundInCsv) {
                return;
            }
            // =====================================================================


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
                Log::warning("[RunGridSearchEvaluationJob] Grid Search Skipped: No sales data for product ID {$this->product->id}");
                return;
            }

            // Gap Filling
            $firstDate = Carbon::parse(array_key_first($rawSales));
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

            Log::info("[RunGridSearchEvaluationJob] Data dikirim ke Python untuk Product ID {$this->product->id}");

            // Kirim ke Python API
            // $response = Http::timeout(3500)->post('http://127.0.0.1:5000/grid-search', [
            //     'sales_data' => $formattedSalesData,
            // ]);

            $response = Http::withOptions([
                'curl' => [
                    CURLOPT_TCP_KEEPALIVE => 1, // Aktifkan Keep-Alive
                    CURLOPT_TCP_KEEPIDLE => 120, // Kirim ping setiap 2 menit jika diam
                    CURLOPT_TCP_KEEPINTVL => 60, // Interval antar ping
                ]
            ])
            ->timeout(3600) // Timeout 1 Jam
            ->post('http://127.0.0.1:5000/grid-search', [
                'sales_data' => $formattedSalesData,
            ]);

            if ($response->failed()) {
                throw new \Exception("Python Grid Search Error: " . $response->body());
            }

            $output = $response->json();

            if(isset($output['error'])) {
                 throw new \Exception($output['error']);
            }

            $bestParams = $output['best_params']; 
            $metrics = $output['all_preprocessing_metrics']; 
            
            // =====================================================================
            // BLOK 2: SIMPAN HASIL BARU KE MASTER CSV
            // =====================================================================
            try {
                // Menggunakan nama file statis yang sama
                $csvFileName = 'backup_sarima_evaluation_result.csv';
                $csvPath = storage_path('logs/' . $csvFileName);
                
                $fileExists = file_exists($csvPath);
                $file = fopen($csvPath, 'a'); // Mode append

                if (!$fileExists) {
                    // Tulis Header hanya jika file benar-benar baru
                    fputcsv($file, [
                        'Timestamp', 'Product Code', 'Product Name', 
                        'p', 'd', 'q', 'P', 'D', 'Q', 's',
                        'Raw RMSE', 'Raw MAPE',
                        'MA RMSE', 'MA MAPE',
                        'SG RMSE', 'SG MAPE',
                        'BC RMSE', 'BC MAPE',
                        'YJ RMSE', 'YJ MAPE'
                    ]);
                }

                // Tulis data baru (akan masuk ke baris paling bawah)
                fputcsv($file, [
                    now()->toDateTimeString(),
                    $this->product->code,
                    $this->product->name,
                    $bestParams[0], $bestParams[1], $bestParams[2], $bestParams[3], $bestParams[4], $bestParams[5], $bestParams[6],
                    $metrics['raw']['rmse'] ?? '', $metrics['raw']['mape'] ?? '',
                    $metrics['ma']['rmse']  ?? '', $metrics['ma']['mape']  ?? '',
                    $metrics['sg']['rmse']  ?? '', $metrics['sg']['mape']  ?? '',
                    $metrics['bc']['rmse']  ?? '', $metrics['bc']['mape']  ?? '',
                    $metrics['yj']['rmse']  ?? '', $metrics['yj']['mape']  ?? ''
                ]);

                fclose($file);
                Log::info("[RunGridSearchEvaluationJob] Berhasil menambahkan hasil Product ID {$this->product->id} ke master CSV.");
            } catch (\Exception $csvEx) {
                Log::error("[RunGridSearchEvaluationJob] Gagal menyimpan ke master CSV: " . $csvEx->getMessage());
            }

            // =====================================================================
            // BLOK 3: SIMPAN HASIL KE DATABASE
            // =====================================================================
            SarimaProductEvaluation::updateOrCreate(
                ['product_code' => $this->product->code], 
                [
                    'product_name'   => $this->product->name,
                    'raw_order_p'    => $bestParams[0],
                    'raw_order_d'    => $bestParams[1],
                    'raw_order_q'    => $bestParams[2],
                    'raw_seasonal_P' => $bestParams[3],
                    'raw_seasonal_D' => $bestParams[4],
                    'raw_seasonal_Q' => $bestParams[5],
                    'raw_seasonal_s' => $bestParams[6],
                    'last_trained_at'=> now(),
                    'raw_rmse' => $metrics['raw']['rmse'] ?? null,
                    'raw_mape' => $metrics['raw']['mape'] ?? null,
                    'ma_rmse'  => $metrics['ma']['rmse'] ?? null,
                    'ma_mape'  => $metrics['ma']['mape'] ?? null,
                    'sg_rmse'  => $metrics['sg']['rmse'] ?? null,
                    'sg_mape'  => $metrics['sg']['mape'] ?? null,
                    'bc_rmse'  => $metrics['bc']['rmse'] ?? null,
                    'bc_mape'  => $metrics['bc']['mape'] ?? null,
                    'yj_rmse'  => $metrics['yj']['rmse'] ?? null,
                    'yj_mape'  => $metrics['yj']['mape'] ?? null,
                ]
            );

            Log::info("[RunGridSearchEvaluationJob] Grid Search Evaluation Success for Product ID {$this->product->id}.");

        } catch (\Exception $e) {
            Log::error("[RunGridSearchEvaluationJob] Grid Search Evaluation Failed for Product ID {$this->product->id}: " . $e->getMessage());
            
            return; 
            //throw $e;
        }
    }
}