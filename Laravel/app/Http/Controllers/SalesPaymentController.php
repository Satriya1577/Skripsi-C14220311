<?php

namespace App\Http\Controllers;

use App\Models\SalesPayment;
use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesPaymentController extends Controller
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
        // --- 0. CEK HAK AKSES ---
        // Ambil user yang sedang login
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'accounting'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk menyimpan pembayaran.')->withInput();
        }

        // 1. Validasi tetap di luar transaction
        $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'amount'         => 'required|numeric',
            'payment_date'   => 'required|date',
        ]);

        try {
            // 2. Mulai Transaction
            DB::transaction(function () use ($request) {
                
                $salesOrderId = $request->sales_order_id; 

                // A. Simpan Data Pembayaran (Insert)
                SalesPayment::create([
                    'sales_order_id'   => $salesOrderId,
                    'payment_date'     => $request->payment_date,
                    'amount'           => $request->amount,
                    'payment_method'   => $request->payment_method,
                    'reference_number' => $request->reference_number,
                    'notes'            => $request->notes,
                ]);

                // B. Update Header Sales Order (Update)
                $salesOrder = SalesOrder::findOrFail($salesOrderId); // Pakai findOrFail agar aman
                
                // Hitung ulang total bayar
                $totalPaid = $salesOrder->payments()->sum('amount');
                
                // Hitung sisa hutang
                $remaining = $salesOrder->grand_total - $totalPaid;
                
                // Tentukan status
                $status = 'unpaid';
                if ($remaining <= 0.01) {
                    $status = 'paid';
                    $remaining = 0; 
                } elseif ($totalPaid > 0) {
                    $status = 'partial';
                }

                // Simpan perubahan ke header
                $salesOrder->update([
                    'paid_amount'       => $totalPaid,
                    'remaining_balance' => $remaining,
                    'payment_status'    => $status
                ]);
            });

            return redirect()->back()->with('success', 'Pembayaran berhasil disimpan.');

        } catch (\Exception $e) {
            // Jika ada error di dalam blok transaction, semua perubahan dibatalkan (Rollback)
            return redirect()->back()->with('error', 'Gagal menyimpan pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SalesPayment $salesPayment)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SalesPayment $salesPayment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SalesPayment $salesPayment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesPayment $salesPayment)
    {
        // --- 0. CEK HAK AKSES ---
        // Ambil user yang sedang login
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'accounting'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk menghapus pembayaran.')->withInput();
        }

        try {
            // Gunakan DB Transaction untuk menjaga integritas data
            DB::transaction(function () use ($salesPayment) {
                
                // 1. Ambil ID Sales Order sebelum pembayaran dihapus
                $salesOrderId = $salesPayment->sales_order_id;

                // 2. Hapus data pembayaran ini
                $salesPayment->delete();

                // 3. Ambil Sales Order terkait
                $salesOrder = SalesOrder::findOrFail($salesOrderId);

                // 4. REKALKULASI (Hitung Ulang)
                // Kita hitung ulang dari nol berdasarkan sisa data yang ada di database.
                // Ini lebih aman daripada sekedar pengurangan manual ($order->paid - $amount)
                // untuk menghindari bug jika ada selisih koma/decimal.
                
                $totalPaid = $salesOrder->payments()->sum('amount'); // Total bayar turun
                $remaining = $salesOrder->grand_total - $totalPaid;  // Hutang naik

                // 5. Tentukan Status Baru
                $status = 'unpaid';
                
                // Cek toleransi koma
                if ($remaining <= 0.01) {
                    $status = 'paid'; // (Jarang terjadi saat delete, tapi untuk safety)
                    $remaining = 0;
                } elseif ($totalPaid > 0) {
                    $status = 'partial'; // Masih ada pembayaran lain, tapi belum lunas
                }
                // Jika totalPaid == 0, maka status otomatis 'unpaid'

                // 6. Update Header Sales Order
                $salesOrder->update([
                    'paid_amount'       => $totalPaid,
                    'remaining_balance' => $remaining,
                    'payment_status'    => $status
                ]);
            });

            return redirect()->back()->with('success', 'Riwayat pembayaran dihapus. Saldo hutang telah disesuaikan.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus pembayaran: ' . $e->getMessage());
        }
    }
}
