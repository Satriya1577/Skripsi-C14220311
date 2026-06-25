<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MaterialTransaction;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MaterialService
{
    // REVISI NOMOR 2: HAPUS ROP
    public function updateAllMaterialLeadTimeSafetyStock() 
    {
        // hanya menghitung material yang statusnya aktif saja
        $materials = Material::where('is_active', true)->get();
        
        DB::beginTransaction();
        try {
            foreach ($materials as $material) {
                
                // 1. Hitung Statistik Hari Tunggu (Lead Time Stats)
                $leadTimeStats = $this->calculateLeadTimeStats($material);
                $averageLeadTimeDays = $leadTimeStats['average']; // rata-rata hari lama waktu tungu
                $minLeadTimeDays     = $leadTimeStats['min']; // waktu hari tunggu tercepat
                $maxLeadTimeDays     = $leadTimeStats['max']; // waktu hari tunggu terlama

                // 2. Hitung Penggunaan Harian (Daily Usage Qty) selama 30 Hari Terakhir
                $usageStats = $this->calculateUsageStats($material);
                $averageDailyUsage = $usageStats['average']; // rata-rata pemakaian selama 30 hari terakhir
                $maxDailyUsage     = $usageStats['max']; // pemakaian harian tertinggi selama 30 hari terakhir

                // 3. Hitung Safety Stock (Kuantitas)
                // maxDemand - averageLeadTimeDemand
                // (Max Lead Time Days * Max Daily Usage Qty) - (Average Lead Time Days * Average Daily Usage Qty)
                $maxDemand = $maxLeadTimeDays * $maxDailyUsage;
                $averageLeadTimeDemand = $averageLeadTimeDays * $averageDailyUsage; // Lead Time Demand

                $safetyStock = max(0, $maxDemand - $averageLeadTimeDemand);

                // REVISI NOMOR 2: HAPUS ROP
                // 4. Hitung ROP (Kuantitas)
                // averageLeadTimeDemand + safetyStock
                // $rop = $averageLeadTimeDemand + $safetyStock;

                // 5. Update data material termasuk Min dan Max Lead Time
                $material->update([
                    'lead_time_average'  => $averageLeadTimeDays,
                    'min_lead_time_days' => $minLeadTimeDays,
                    'max_lead_time_days' => $maxLeadTimeDays,
                    'safety_stock'       => $safetyStock,
                    // REVISI NOMOR 2: HAPUS ROP
                    // 'reorder_point'      => $rop
                ]);
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // menghitung rata-rata hari tunggu barang tiba di gudang
    // dihitung sejak PO dibuat sampai barang diterima di gudang (sesuai expected_arrival_date di tabel purchase_orders)
    public function calculateLeadTimeStats(Material $material) 
    {
        // Jika manual, langsung gunakan nilai dari database
        if ($material->is_manual_lead_time === 'manual') {
            return [
                'average' => ($material->min_lead_time_days + $material->max_lead_time_days) / 2,
                'min'     => $material->min_lead_time_days,
                'max'     => $material->max_lead_time_days,
            ];
        }

        // --- Logika Automatic ---
        // ambil id PO dari tabel MaterialTransaction dengan tipe IN 
        // ambil 30 PO terakhir yang sudah selesai diterima (status 'received') dan expected_arrival_date tidak kosong
        $recentPoIds = MaterialTransaction::where('material_id', $material->id)
            ->where('type', 'in')
            ->whereNotNull('purchase_order_id')
            ->orderBy('transaction_date', 'desc')
            ->limit(100) // Tarik 100 data transaksi terakhir untuk disaring
            ->pluck('purchase_order_id') // Ambil kolom ID PO saja
            ->unique() // Saring ID duplikat menggunakan Collection Laravel (bukan SQL)
            ->take(30); // Ambil 30 ID PO unik terbaru

        // Fallback jika belum ada transaksi sama sekali
        if ($recentPoIds->isEmpty()) {
            return [
                'average' => ($material->min_lead_time_days + $material->max_lead_time_days) / 2,
                'min'     => $material->min_lead_time_days,
                'max'     => $material->max_lead_time_days,
            ];
        }

        // ambil list PO berdasarkan ID yang sudah difiltter diatas
        $purchaseOrders = PurchaseOrder::whereIn('id', $recentPoIds)
            ->where('status', 'received')
            ->whereNotNull('expected_arrival_date')
            ->get();

        // array berapa hari selisih antara order_date dan expected_arrival_date untuk setiap PO    
        $leadTimes = [];

        foreach ($purchaseOrders as $po) {
            $orderDate = Carbon::parse($po->order_date);
            $arrivalDate = Carbon::parse($po->expected_arrival_date);
            
            // Simpan selisih hari ke dalam array
            $leadTimes[] = $orderDate->diffInDays($arrivalDate);
        }

        // Fallback jika array kosong (misal expected_arrival_date kosong semua)
        if (empty($leadTimes)) {
            return [
                'average' => ($material->min_lead_time_days + $material->max_lead_time_days) / 2,
                'min'     => $material->min_lead_time_days,
                'max'     => $material->max_lead_time_days,
            ];
        }

        // return rata-rata lama lead time, nilai lead time tercepat, dan nilai lead time terlama dari sebuah PO
        return [
            'average' => array_sum($leadTimes) / count($leadTimes),
            'min'     => min($leadTimes),
            'max'     => max($leadTimes),
        ];
    }

    // menghitung rata-rata pemakaian bahan baku harian selama 30 hari terakhir
    // tipe bahan baku yang terpakai di tabel MaterialTransactionadalah OUT 
    public function calculateUsageStats(Material $material)
    {
        // Ambil data 30 hari ke belakang
        $thirtyDaysAgo = now()->subDays(30)->format('Y-m-d');
        
        // ambil data transaksi dengan tipe OUT untuk material ini selama 30 hari terakhir
        $usages = MaterialTransaction::where('material_id', $material->id)
            ->where('type', 'out')
            ->where('transaction_date', '>=', $thirtyDaysAgo)
            ->get();

        if ($usages->isEmpty()) {
            return ['average' => 0, 'max' => 0];
        }

        // grouping data berdasarkan tanggal transaksi atau harian nya lalu jumlahkan qty untuk setiap hari
        $dailyUsages = $usages->groupBy('transaction_date')->map(function ($dayTransactions) {
            return abs($dayTransactions->sum('qty')); 
        });

        return [
            'average' => $dailyUsages->sum() / 30, // Rata-rata dari 30 hari kalender
            'max'     => $dailyUsages->max()
        ];
    }
}