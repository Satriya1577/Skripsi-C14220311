<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductTransaction;
use App\Models\SalesOrderItem;
use App\Models\SalesPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; 
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class SalesOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $salesOrders = SalesOrder::orderBy('id', 'desc')->paginate(10);
        $partners = Partner::whereIn('type', ['distributor', 'both'])
                       ->orderBy('company_name')
                       ->get();
        return view('sales.index', compact('salesOrders', 'partners'));
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
        if (!in_array($user->role, ['admin', 'sales'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk membuat Sales Order.')->withInput();
        }

        // 1. Validasi Input
        $request->validate([
            'transaction_date' => 'required|date',
            'partner_id'       => 'required|exists:partners,id', // Wajib pilih dari dropdown
            'so_code'          => 'nullable|string|max:255|unique:sales_orders,so_code',
            'due_date'         => 'required|date',
            'shipping_date'    => 'nullable|date',
            
            // Validasi Data Snapshot (Dikirim dari input readonly/hidden di view)
            'company_name'     => 'required|string|max:255',
            'person_name'      => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:50',
            'email'            => 'nullable|email|max:255',
            'address'          => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // 2. Generate Auto SO Code jika user mengosongkan
            // Format: SO-YYYYMMDD-XXXX (4 karakter random unik)
            $soCode = $request->filled('so_code') 
                ? $request->so_code 
                : 'SO-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

            // 3. Simpan ke Database
            $salesOrder = SalesOrder::create([
                'so_code'           => $soCode,
                'transaction_date'  => $request->transaction_date,
                'due_date'          => $request->due_date,
                'shipping_date'     => $request->shipping_date,
                
                // --- RELASI & SNAPSHOT PARTNER ---
                'partner_id'   => $request->partner_id,   // Foreign Key (Relasi)
                'company_name' => $request->company_name, // Snapshot Text (Agar data historis aman)
                'person_name'  => $request->person_name,  // Snapshot
                'phone'        => $request->phone,        // Snapshot
                'email'        => $request->email,        // Snapshot
                'address'      => $request->address,      // Snapshot

                // --- FINANCIAL DEFAULTS ---
                'grand_total'       => 0,
                'paid_amount'       => 0,
                'remaining_balance' => 0,
                'payment_status'    => 'unpaid',
                'status'            => 'draft',
                'shipping_cost'     => 0, // Default 0 dulu
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Draft SO berhasil dibuat: ' . $salesOrder->so_code);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SalesOrder $salesOrder)
    {
        $products = Product::all();
        $salesOrder = SalesOrder::with(['items.product'])->findOrFail($salesOrder->id);
        return view('sales.show', compact('salesOrder', 'products'));
    }

    public function showPayments(SalesOrder $salesOrder)
    {
        // 1. Ambil Data Sales Order
        $salesOrder = SalesOrder::findOrFail($salesOrder->id);

        // 2. Ambil History Pembayaran (urutkan dari yang terbaru)
        $payments = SalesPayment::where('sales_order_id', $salesOrder->id)
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // 3. Return View
        return view('sales.payment', compact('salesOrder', 'payments'));
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function updateStatus(Request $request, $id)
    {
        // --- 0. CEK HAK AKSES ---
        // Ambil user yang sedang login
        $user = Auth::user();
        $userRole = $user->role;
        $salesOrder = SalesOrder::with('items')->findOrFail($id);
        $oldStatus = $salesOrder->status;
        $newStatus = $request->status;

        $request->validate([
            'status' => 'required|in:draft,confirmed,shipped,cancelled',
            'shipping_date' => 'nullable|date',
            'shipping_cost' => 'nullable|numeric|min:0',
            'shipping_payment_type' => 'nullable|in:bill_to_customer,borne_by_company',
            
            // Validasi Item Baru
            'new_items' => 'nullable|array',
            'new_items.*.product_id' => 'required|exists:products,id',
            'new_items.*.quantity' => 'required|integer|min:1',
            'new_items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            
            // Validasi Item Lama (Update Diskon & Quantity)
            'items' => 'nullable|array',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100', 
            'items.*.quantity' => 'nullable|integer|min:1', // Validasi Quantity Item Lama

            'deleted_items' => 'nullable|array',
            'deleted_items.*' => 'exists:sales_order_items,id',
        ]);

        // GROUP 1: SALES & ADMIN ACTIONS
        // Draft -> Confirm, Draft -> Cancel, Confirm -> Cancel, Draft -> Draft (Save)
        if (
            ($oldStatus == 'draft' && in_array($newStatus, ['confirmed', 'cancelled', 'draft'])) ||
            ($oldStatus == 'confirmed' && $newStatus == 'cancelled')
        ) {
            if (!in_array($userRole, ['admin', 'sales'])) {
                return redirect()->back()->with('error', 'AKSES DITOLAK: Hanya tim Sales atau Admin yang dapat mengubah status pesanan/pembatalan.');
            }
        }

        // GROUP 2: INVENTORY & ADMIN ACTIONS
        // Confirm -> Shipped (Proses Pengiriman)
        // Inventory juga boleh update data pengiriman tanpa ubah status (misal status tetap confirmed tapi update tanggal)
        elseif (
            ($oldStatus == 'confirmed' && $newStatus == 'shipped') || 
            ($oldStatus == 'confirmed' && $newStatus == 'confirmed' && ($request->filled('shipping_date') || $request->filled('shipping_cost')))
        ) {
            if (!in_array($userRole, ['admin', 'inventory'])) {
                return redirect()->back()->with('error', 'AKSES DITOLAK: Hanya tim Inventory atau Admin yang dapat memproses pengiriman barang.');
            }
        }

        // Cek Keamanan Tambahan: Inventory tidak boleh membatalkan pesanan
        if ($userRole == 'inventory' && $newStatus == 'cancelled') {
            return redirect()->back()->with('error', 'AKSES DITOLAK: Tim Inventory tidak memiliki hak untuk membatalkan pesanan.');
        }

        return DB::transaction(function () use ($request, $salesOrder) {
            
            // 1. HAPUS ITEM
            if ($request->filled('deleted_items')) {
                SalesOrderItem::whereIn('id', $request->deleted_items)
                    ->where('sales_order_id', $salesOrder->id)
                    ->delete();
            }

            // 2. UPDATE ITEM LAMA (Hanya jika status DRAFT)
            if ($request->has('items') && $salesOrder->status == 'draft') {
                foreach ($request->items as $itemId => $data) {
                    $item = SalesOrderItem::find($itemId);
                    
                    if ($item && $item->sales_order_id == $salesOrder->id) {
                        // Ambil data baru atau gunakan data lama jika tidak dikirim
                        $discount = $data['discount_percent'] ?? 0;
                        $quantity = $data['quantity'] ?? $item->quantity; // Ambil Qty Baru
                        
                        // Hitung Ulang Subtotal: (Harga x Qty Baru) - Diskon
                        $grossTotal = $item->unit_price * $quantity;
                        $discountAmount = $grossTotal * ($discount / 100);
                        $subtotal = $grossTotal - $discountAmount;

                        $item->update([
                            'quantity' => $quantity, // Simpan Qty Baru
                            'discount_percent' => $discount,
                            'subtotal' => $subtotal
                        ]);
                    }
                }
            }

            // 3. CEK DUPLIKASI ITEM BARU
            if ($request->has('new_items')) {
                $existingProductIds = $salesOrder->items()
                    ->whereNotIn('id', $request->deleted_items ?? [])
                    ->pluck('product_id')->toArray();
                
                $newProductIds = array_column($request->new_items, 'product_id');

                if (!empty(array_intersect($newProductIds, $existingProductIds))) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'new_items' => "Produk sudah ada di dalam list. Edit item yang ada atau hapus dulu."
                    ]);
                }
            }

            // ... (Kode Bagian 4, 5, 6, 7 dst SAMA PERSIS SEPERTI SEBELUMNYA, TIDAK ADA PERUBAHAN) ...
            
            // --- 4. UPDATE INFO PENGIRIMAN ---
            if (in_array($request->status, ['confirmed', 'shipped'])) {
                $shippingChanged = false; 

                if ($request->filled('shipping_cost')) {
                    $salesOrder->shipping_cost = $request->shipping_cost;
                    $shippingChanged = true;
                }
                
                if ($request->filled('shipping_payment_type')) {
                    $salesOrder->shipping_payment_type = $request->shipping_payment_type;
                    $shippingChanged = true;
                }
                
                if ($request->filled('shipping_date')) {
                    $salesOrder->shipping_date = $request->shipping_date;
                    $shippingChanged = true;
                }

                if ($shippingChanged) {
                    $salesOrder->save(); 
                }
            }

            // --- 5. SIMPAN ITEM BARU ---
            if ($request->has('new_items') && $request->status != 'cancelled') {
                foreach ($request->new_items as $itemData) {
                    $product = Product::find($itemData['product_id']);
                    
                    $qty = $itemData['quantity'];
                    $price = $product->price;
                    $discount = $itemData['discount_percent'] ?? 0;
                    
                    $grossTotal = $price * $qty;
                    $discountAmount = $grossTotal * ($discount / 100);
                    $subtotal = $grossTotal - $discountAmount;

                    SalesOrderItem::create([
                        'sales_order_id' => $salesOrder->id,
                        'product_id' => $product->id,
                        'product_name_snapshot' => $product->name,
                        'product_packaging_snapshot' => $product->packaging,
                        'cogs_snapshot' => $product->cost_price, 
                        'quantity' => $qty,
                        'unit_price' => $price, 
                        'discount_percent' => $discount,
                        'subtotal' => $subtotal
                    ]);
                }
            }

            // --- 6. HITUNG ULANG GRAND TOTAL ---
            $salesOrder->load('items'); 
            $itemsTotal = $salesOrder->items->sum('subtotal');
            $shippingToAdd = ($salesOrder->shipping_payment_type == 'bill_to_customer') ? $salesOrder->shipping_cost : 0;
            $newGrandTotal = $itemsTotal + $shippingToAdd;

            $salesOrder->grand_total = $newGrandTotal;
            $salesOrder->remaining_balance = $newGrandTotal - $salesOrder->paid_amount;
            
            // --- 7. LOGIKA STATUS ---
            $oldStatus = $salesOrder->status;
            $newStatus = $request->status;

            if ($oldStatus == 'draft' && $newStatus == 'cancelled') {
                $salesOrder->grand_total = 0;
                $salesOrder->remaining_balance = 0;
                $salesOrder->shipping_cost = 0;
            }

            if ($oldStatus == 'confirmed' && $newStatus == 'cancelled') {
                foreach ($salesOrder->items as $item) {
                    $item->product->decrement('committed_stock', $item->quantity);
                }
                $salesOrder->grand_total = 0;
                $salesOrder->remaining_balance = 0;
                $salesOrder->shipping_cost = 0;
            }

            if ($oldStatus == 'draft' && $newStatus == 'confirmed') {
                foreach ($salesOrder->items as $item) {
                    $item->product->increment('committed_stock', $item->quantity);
                }
            }

            if ($oldStatus == 'confirmed' && $newStatus == 'shipped') {
                if (!$salesOrder->shipping_date) {
                    throw new \Exception("Tanggal pengiriman wajib diisi.");
                }

                foreach ($salesOrder->items as $item) {
                    $item->product->decrement('current_stock', $item->quantity);
                    $item->product->decrement('committed_stock', $item->quantity);
                    $cogs = $item->cogs_snapshot ?? $item->product->cost_price;

                    ProductTransaction::create([
                        'product_id' => $item->product_id,
                        'transaction_date' => $salesOrder->shipping_date,
                        'type' => 'sales_out', 
                        'qty' => -$item->quantity, 
                        'cost_price' => $cogs, 
                        'current_stock_balance' => $item->product->current_stock,
                        'product_name_snapshot' => $item->product_name_snapshot,
                        'product_packaging_snapshot' => $item->product_packaging_snapshot,
                        'sales_order_id' => $salesOrder->id,
                        'description' => 'Pengiriman SO: ' . $salesOrder->so_code,
                    ]);
                }
            }

            $salesOrder->status = $newStatus;
            $salesOrder->save();

            return redirect()->back()->with('success', 'Status Sales Order berhasil diperbarui.');
        });
    }

    public function print(SalesOrder $salesOrder)
    {

        // --- 0. CEK HAK AKSES ---
        // Ambil user yang sedang login
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'sales', 'inventory'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk mencetak RFQ/Invoice.')->withInput();
        } else {

            // 1. Ambil Data Sales Order beserta relasi yang dibutuhkan
            $salesOrder = SalesOrder::with(['items.product', 'partner'])->findOrFail($salesOrder->id);

            // Logika Judul Dokumen
            // Jika Draft -> RFQ / Quotation
            // Jika Confirmed/Shipped -> Invoice
            if ($salesOrder->status == 'draft') {
                $docTitle = 'REQUEST FOR QUOTATION';
                $docType  = 'RFQ';
            } else {
                $docTitle = 'INVOICE';
                $docType  = 'INV';
            }

            // Load View PDF
            $pdf = Pdf::loadView('sales.pdf', compact('salesOrder', 'docTitle', 'docType'));
            
            // Setup ukuran kertas (A4 Potrait)
            $pdf->setPaper('a4', 'portrait');

            // Stream (Buka di browser) atau Download
            return $pdf->stream($docType . '-' . $salesOrder->so_code . '.pdf');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SalesOrder $salesOrder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SalesOrder $salesOrder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesOrder $salesOrder)
    {
        //
    }
}
