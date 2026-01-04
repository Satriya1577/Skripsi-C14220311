<?php

namespace App\Jobs;

use App\Models\ForecastingJob;
use App\Models\SarimaConfig;
use App\Models\Forecast;
use App\Models\ProductionPlan;
use App\Models\Sales;
use App\Models\ValidationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http; // <--- PAKAI INI SEKARANG
use Carbon\Carbon;

class RunSarimaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobId;
    protected $cutoffLastMonth;

    public function __construct($id, $cutoffLastMonth = false)
    {
        $this->jobId = $id;
        $this->cutoffLastMonth = $cutoffLastMonth;
    }

    public function handle()
    {
        $jobLog = ForecastingJob::with('product')->find($this->jobId);
        if (!$jobLog) return;

        $jobLog->update(['status' => 'processing', 'message' => 'Sending data to Python API...']);
        $product = $jobLog->product;

        try {
            // 1. Siapkan Config
            $config = SarimaConfig::where('product_id', $product->id)->latest()->first();
            $params = $config ? [
                $config->order_p, $config->order_d, $config->order_q,
                $config->seasonal_P, $config->seasonal_D, $config->seasonal_Q, $config->seasonal_s
            ] : [1, 1, 1, 1, 1, 1, 12];

            // 2. AMBIL DATA SALES DARI LARAVEL (Agar Python tidak perlu konek DB)
            $salesData = Sales::where('product_id', $product->id)
                ->orderBy('transaction_date', 'asc')
                ->get()
                ->map(function($sale) {
                    return [
                        'date' => $sale->transaction_date, // Pastikan format Y-m-d
                        'qty' => $sale->quantity_sold
                    ];
                });

            if($salesData->isEmpty()) {
                throw new \Exception("Tidak ada data penjualan untuk produk ini.");
            }

            // 3. KIRIM DATA KE FLASK
            $targetDate = $jobLog->target_period instanceof Carbon 
                ? $jobLog->target_period->format('Y-m-d') 
                : Carbon::parse($jobLog->target_period)->format('Y-m-d');

            $response = Http::post('http://127.0.0.1:5000/forecast', [
                'sales_data' => $salesData,
                'target_date' => $targetDate,
                'params' => $params,
                'cutoff' => $this->cutoffLastMonth ? 1 : 0
            ]);

            // 4. Cek Response
            if ($response->failed()) {
                throw new \Exception("Python API Error: " . $response->body());
            }

            $output = $response->json();
            
            if(isset($output['error'])) {
                 throw new \Exception($output['error']);
            }

            // ==========================================================
            // LOGIKA DI BAWAH INI SAMA PERSIS DENGAN SEBELUMNYA
            // ==========================================================

            // 5. Simpan Metrics
            $sarimaConfig = SarimaConfig::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'rmse' => $output['metrics']['rmse'],
                    'mape' => $output['metrics']['mape'],
                    'last_trained_at' => now(),
                    'order_p' => $params[0], 'order_d' => $params[1], 'order_q' => $params[2],
                    'seasonal_P' => $params[3], 'seasonal_D' => $params[4], 'seasonal_Q' => $params[5], 'seasonal_s' => $params[6]
                ]
            );

            // 6. Simpan Logs
            $sarimaConfig->validationLogs()->delete();
            foreach ($output['validation_data'] as $log) {
                ValidationLog::create([
                    'sarima_config_id' => $sarimaConfig->id,
                    'period' => $log['date'],
                    'actual_qty' => $log['actual'],
                    'predicted_qty' => $log['predicted']
                ]);
            }

            // 7. Simpan Forecast
            $forecastVal = $output['forecast']['value'];

            Forecast::updateOrCreate(
                // Kriteria Pencarian: Cari baris milik product_id ini
                ['product_id' => $product->id], 
                
                // Data yang akan di-update (atau di-insert jika baru)
                [
                    'forecast_period' => $targetDate, // Tanggal akan ditimpa dengan yang baru
                    'predicted_amount' => $forecastVal
                ]
            );

            // Update SS & Production Plan (Logic sama)
            $targetCarbon = $jobLog->target_period instanceof Carbon ? $jobLog->target_period : Carbon::parse($jobLog->target_period);
            $referenceMonth = $targetCarbon->copy()->subMonth();
            
            $totalSalesRef = Sales::where('product_id', $product->id)
                ->whereYear('transaction_date', $referenceMonth->year)
                ->whereMonth('transaction_date', $referenceMonth->month)
                ->sum('quantity_sold');

            $newSafetyStock = ceil($totalSalesRef / 2);
            $product->update(['safety_stock' => $newSafetyStock]);

            $recQty = ($forecastVal + $newSafetyStock) - $product->current_stock;

            ProductionPlan::updateOrCreate(
                ['product_id' => $product->id, 'period' => $targetDate],
                [
                    'forecast_qty' => $forecastVal,
                    'current_stock_snapshot' => $product->current_stock,
                    'safety_stock_snapshot' => $newSafetyStock,
                    'recommended_production_qty' => max(0, $recQty),
                ]
            );

            $jobLog->update(['status' => 'completed', 'message' => 'Calculation finished successfully via API.']);

        } catch (\Exception $e) {
            $jobLog->update(['status' => 'failed', 'message' => substr($e->getMessage(), 0, 1000)]);
        }
    }
}