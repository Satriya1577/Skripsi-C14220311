<?php

namespace App\Jobs;

use App\Models\SarimaConfig;
use App\Models\Sales;
use Illuminate\Bus\Batchable; // Penting untuk Batching
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RunGridSearchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productId;

    // --- TIMEOUT SETTING ---
    // Kita set 3600 detik (1 jam) per produk.
    // Jika Flask Anda lebih lambat dari ini, naikkan angkanya.
    public $timeout = 3600; 
    
    // Jangan retry otomatis jika timeout, karena akan membuang waktu 12 jam lagi.
    public $tries = 1;

    public function __construct($productId)
    {
        $this->productId = $productId;
    }

    public function handle()
    {
        // 1. Cek Pembatalan Batch
        // Jika user membatalkan batch di tengah jalan, job ini tidak perlu dijalankan.
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        try {
            // 2. Ambil Data Sales (Sama seperti RunSarimaJob)
            // Grid Search butuh seluruh data historis untuk training.
            $salesData = Sales::where('product_id', $this->productId)
                ->orderBy('transaction_date', 'asc')
                ->get()
                ->map(function($sale) {
                    return [
                        'date' => $sale->transaction_date,
                        'qty' => $sale->quantity_sold
                    ];
                });

            if($salesData->isEmpty()) {
                Log::warning("Grid Search Skipped: No sales data for product ID {$this->productId}");
                return;
            }

            // 3. Panggil Flask API Endpoint Khusus Grid Search
            // Pastikan Anda membuat endpoint '/grid-search' di Python Flask Anda
            // Timeout HTTP diset sedikit di bawah timeout Job (misal 58 menit)
            $response = Http::timeout(3500)->post('http://127.0.0.1:5000/grid-search', [
                'sales_data' => $salesData,
                // Kita tidak kirim params p,d,q karena tugas Python adalah mencarinya
            ]);

            // 4. Cek Response Error
            if ($response->failed()) {
                throw new \Exception("Python Grid Search Error: " . $response->body());
            }

            $output = $response->json();

            if(isset($output['error'])) {
                 throw new \Exception($output['error']);
            }

            // 5. Simpan Hasil Terbaik ke Database
            // Asumsi output Python: {'best_params': [1,1,1,1,1,1,12], 'metrics': {'rmse': 10, 'mape': 5}}
            $bestParams = $output['best_params']; // Urutan: p, d, q, P, D, Q, s
            
            SarimaConfig::updateOrCreate(
                ['product_id' => $this->productId],
                [
                    'order_p' => $bestParams[0],
                    'order_d' => $bestParams[1],
                    'order_q' => $bestParams[2],
                    'seasonal_P' => $bestParams[3],
                    'seasonal_D' => $bestParams[4],
                    'seasonal_Q' => $bestParams[5],
                    'seasonal_s' => $bestParams[6],
                    
                    'rmse' => $output['metrics']['rmse'] ?? 0,
                    'mape' => $output['metrics']['mape'] ?? 0,
                    'last_trained_at' => now(),
                ]
            );

            Log::info("Grid Search Success for Product ID {$this->productId}");

        } catch (\Exception $e) {
            // Log error agar Anda bisa debug produk mana yang gagal
            Log::error("Grid Search Failed for Product ID {$this->productId}: " . $e->getMessage());
            
            // Re-throw agar batch menandai job ini sebagai 'failed'
            throw $e;
        }
    }
}