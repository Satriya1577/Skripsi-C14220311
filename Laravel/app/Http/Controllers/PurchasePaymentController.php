<?php

namespace App\Http\Controllers;

use App\Models\PurchasePayment;
use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchasePaymentController extends Controller
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

        $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'payment_date'      => 'required|date',
            'amount'            => 'required|numeric|min:0.01',
            'payment_method'    => 'nullable|string|max:50',
            'reference_number'  => 'nullable|string|max:100',
            'notes'             => 'nullable|string|max:500',
        ]);

        $po = PurchaseOrder::findOrFail($request->purchase_order_id);

        // Validasi: Pembayaran tidak boleh melebihi sisa hutang
        if ($request->amount > $po->remaining_balance) {
            return redirect()->back()
                ->with('error', 'Jumlah pembayaran melebihi sisa hutang (Rp ' . number_format($po->remaining_balance, 0, ',', '.') . ').')
                ->withInput();
        }

        try {
            DB::transaction(function () use ($request, $po) {
                // 1. Simpan Transaksi Pembayaran
                PurchasePayment::create([
                    'purchase_order_id' => $po->id,
                    'payment_date'      => $request->payment_date,
                    'amount'            => $request->amount,
                    'payment_method'    => $request->payment_method,
                    'reference_number'  => $request->reference_number,
                    'notes'             => $request->notes,
                ]);

                // 2. Update Header Purchase Order
                $po->paid_amount += $request->amount;
                $po->remaining_balance = $po->grand_total - $po->paid_amount;

                // 3. Update Status Pembayaran Otomatis
                if ($po->remaining_balance <= 0) {
                    $po->payment_status = 'paid';
                } elseif ($po->paid_amount > 0) {
                    $po->payment_status = 'partial';
                } else {
                    $po->payment_status = 'unpaid';
                }

                $po->save();
            });

            return redirect()->back()->with('success', 'Pembayaran berhasil disimpan.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchasePayment $purchasePayment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchasePayment $purchasePayment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchasePayment $purchasePayment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchasePayment $purchasePayment)
    {
        // --- 0. CEK HAK AKSES ---
        // Ambil user yang sedang login
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'accounting'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk menghapus pembayaran.')->withInput();
        }
        
        $payment = PurchasePayment::findOrFail($purchasePayment->id);
        $po = PurchaseOrder::findOrFail($payment->purchase_order_id);

        try {
            DB::transaction(function () use ($payment, $po) {
                // 1. Kurangi Saldo Terbayar di PO
                $po->paid_amount -= $payment->amount;
                // Pastikan tidak negatif (safety)
                if ($po->paid_amount < 0) $po->paid_amount = 0;
                
                $po->remaining_balance = $po->grand_total - $po->paid_amount;

                // 2. Update Status Pembayaran Kembali
                if ($po->remaining_balance <= 0 && $po->grand_total > 0) {
                    $po->payment_status = 'paid';
                } elseif ($po->paid_amount > 0) {
                    $po->payment_status = 'partial';
                } else {
                    $po->payment_status = 'unpaid';
                }
                $po->save();

                // 3. Hapus Data Pembayaran
                $payment->delete();
            });

            return redirect()->back()->with('success', 'Pembayaran berhasil dihapus. Saldo hutang telah disesuaikan.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }
}
