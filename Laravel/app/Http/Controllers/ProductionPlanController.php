<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MaterialTransaction;
use App\Models\ProductionPlan;
use App\Models\ProductionRealization;
use App\Models\ProductMaterial;
use Illuminate\Http\Request;

class ProductionPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
  /**
     * Menampilkan Detail Rencana Produksi
     */
    public function show(ProductionPlan $production_plan)
    {
        // 1. Ambil Data Plan
        $plan = ProductionPlan::findOrFail($production_plan->id);

        // Inisialisasi variabel agar tidak error di View jika kondisi tidak terpenuhi
        $purchaseList = [];
        $usageReport = [];

        // --- SKENARIO 1: PLAN AKTIF (Draft / Approved) ---
        // Logika: Hitung estimasi kebutuhan bahan baku berdasarkan Resep & Stok Saat Ini
        if ($plan->status == 'draft' || $plan->status == 'approved') {
            
            // Ambil Resep (BOM) untuk produk ini
            $recipes = ProductMaterial::where('product_id', $plan->product_id)
                                      ->with('material') // Eager load material agar performa cepat
                                      ->get();
            
            foreach($recipes as $recipe) {
                // Hitung total butuh = Jumlah Rencana x Takaran Resep
                $totalNeeded = $plan->recommended_production_qty * $recipe->amount_needed;
                
                // Ambil stok gudang SAAT INI (Real-time)
                $currentStock = $recipe->material->current_stock;
                
                // Hitung selisih (Kurang berapa?)
                $shortage = $totalNeeded - $currentStock;
                
                // Masukkan ke array untuk dikirim ke View
                $purchaseList[] = [
                    'material_name' => $recipe->material->name,
                    'current_stock' => $currentStock,
                    'needed'        => $totalNeeded,
                    // Jika shortage > 0, berarti harus beli. Jika minus/nol, berarti 0.
                    'must_buy'      => ($shortage > 0) ? $shortage : 0
                ];
            }
        }

        // --- SKENARIO 2: PLAN SELESAI (Completed) ---
        // Logika: Jangan hitung stok sekarang! Ambil dari history 'material_transactions'
        elseif ($plan->status == 'completed') {
            
            // Cari data Realisasi yang terhubung dengan Plan ini
            $realization = ProductionRealization::where('production_plan_id', $plan->id)->first();
            
            if($realization) {
                // Ambil transaksi material tipe 'out' yang terhubung dengan ID realisasi tersebut
                $transactions = MaterialTransaction::where('production_realization_id', $realization->id)
                                                   ->where('type', 'out')
                                                   ->with('material')
                                                   ->get();
                                                   
                foreach($transactions as $trans) {
                    $usageReport[] = [
                        'material_name' => $trans->material->name,
                        'qty_used'      => $trans->qty, // Data historis
                        'date_used'     => $trans->transaction_date // Tanggal kejadian
                    ];
                }
            }
        }

        // Kirim semua data ke View
        return view('production_plan.show', compact('plan', 'purchaseList', 'usageReport'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductionPlan $production_plan)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductionPlan $production_plan)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductionPlan $production_plan)
    {
        //
    }
}
