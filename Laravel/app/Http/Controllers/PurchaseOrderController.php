<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialTransaction;
use App\Models\Partner;
use App\Models\PurchaseOrderItem;
use App\Models\PurchasePayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Inisialisasi Query
        $query = PurchaseOrder::query();

        // 2. Logika Search (Menangkap input 'search' dari View)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        // 3. Sorting & Pagination
        // Urutkan berdasarkan tanggal order terbaru, jika sama urutkan berdasarkan ID
        $purchaseOrders = $query->orderBy('order_date', 'desc')
                                ->orderBy('id', 'desc')
                                ->paginate(10);

        // Penting: Agar parameter search tetap ada saat klik halaman 2, 3, dst
        $purchaseOrders->appends(['search' => $request->search]);

        // 4. Ambil Data Supplier untuk Dropdown 'Create Purchase Order'
        // Filter hanya tipe 'supplier' atau 'both'
        $partners = Partner::whereIn('type', ['supplier', 'both'])
                           ->orderBy('company_name')
                           ->get();

        return view('purchase.index', compact('purchaseOrders', 'partners'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         // --- 0. CEK HAK AKSES ---
        // Ambil user yang sedang login
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'purchase'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk membuat Purchase Order.')->withInput();
        }

        // 1. Validasi Input
        $request->validate([
            'order_date'   => 'required|date',
            'partner_id'   => 'required|exists:partners,id', // Harus partner tipe supplier/both
            'po_number'    => 'nullable|string|max:255|unique:purchase_orders,po_number',
            'due_date'         => 'required|date',
            
            // Validasi Data Snapshot (Dikirim dari input readonly/hidden di view)
            'company_name' => 'required|string|max:255',
            'person_name'  => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:50',
            'email'        => 'nullable|email|max:255',
            'address'      => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // 2. Generate Auto PO Number jika user mengosongkan
            // Format: PO-YYYYMMDD-XXXX (5 karakter random unik)
            $poNumber = $request->filled('po_number') 
                ? $request->po_number 
                : 'PO-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

            // 3. Simpan ke Database
           // 3. Simpan ke Database
            $purchaseOrder = PurchaseOrder::create([
                'po_number'    => $poNumber,
                'order_date'   => $request->order_date,
                'status'       => 'draft', // Default status selalu draft
                'due_date'       => $request->due_date,
                
                // --- RELASI & SNAPSHOT SUPPLIER ---
                'partner_id'   => $request->partner_id,
                'company_name' => $request->company_name, 
                'person_name'  => $request->person_name,
                'phone'        => $request->phone,
                'email'        => $request->email,
                'address'      => $request->address,

                // --- FINANCIAL DEFAULTS (PERBAIKAN DI SINI) ---
                'grand_total'       => 0, // Ganti 'total_cost' jadi 'grand_total'
                'paid_amount'       => 0, // Tambahkan ini agar tidak null
                'remaining_balance' => 0, // Tambahkan ini agar tidak null
                'payment_status'    => 'unpaid', // Default payment status

                'shipping_cost'     => 0,
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Draft PO berhasil dibuat: ' . $purchaseOrder->po_number);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $materials = Material::where('is_active', true)->get();
        $purchaseOrder = PurchaseOrder::with(['items.material'])->findOrFail($purchaseOrder->id);
        return view('purchase.show', compact('purchaseOrder', 'materials'));
    }

    public function updateStatus(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::with('items')->findOrFail($id);



        $request->validate([
            'status' => 'required|in:draft,ordered,received,cancelled',
            'expected_arrival_date' => 'nullable|date',
            'shipping_cost' => 'nullable|numeric|min:0',
            'shipping_terms' => 'nullable|in:FOB_shipping_point,FOB_destination',
            'due_date' => 'nullable|date',
            
            // Validasi Item Baru
            'new_items' => 'nullable|array',
            'new_items.*.material_id' => 'required|exists:materials,id', // Cek ke tabel materials
            'new_items.*.quantity' => 'required|numeric|min:0.01', // Material bisa koma (kg/liter)
            'new_items.*.cost' => 'required|numeric|min:0', // Harga Beli
            'new_items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            
            // Validasi Item Lama
            'items' => 'nullable|array',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100', 
            'items.*.quantity' => 'nullable|numeric|min:0.01', 

            'deleted_items' => 'nullable|array',
            'deleted_items.*' => 'exists:purchase_order_items,id',
        ]);


        // --- AUTHORIZATION CHECK (HAK AKSES) ---
        $user = Auth::user(); 
        $userRole = $user->role; 
        $oldStatus = $purchaseOrder->status;
        $newStatus = $request->status;

        // GROUP 1: PURCHASE & ADMIN ACTIONS (Manajemen Order)
        // Draft -> Ordered (Kirim PO ke Supplier)
        // Draft -> Cancelled (Batal beli)
        // Ordered -> Cancelled (Batal setelah dikirim, tapi sebelum diterima)
        // Draft -> Draft (Update item/Simpan)
        if (
            ($oldStatus == 'draft' && in_array($newStatus, ['ordered', 'cancelled', 'draft'])) ||
            ($oldStatus == 'ordered' && $newStatus == 'cancelled')
        ) {
            if (!in_array($userRole, ['admin', 'purchase'])) {
                return redirect()->back()->with('error', 'AKSES DITOLAK: Hanya tim Purchase atau Admin yang dapat memproses pemesanan ke Supplier.');
            }
        }

        // GROUP 2: INVENTORY & ADMIN ACTIONS (Penerimaan Barang)
        // Ordered -> Received (Terima Barang)
        // Atau: Ordered -> Ordered (Update info logistik tanpa ubah status)
        elseif (
            ($oldStatus == 'ordered' && $newStatus == 'received') || 
            ($oldStatus == 'ordered' && $newStatus == 'ordered' && ($request->filled('expected_arrival_date') || $request->filled('shipping_cost')))
        ) {
            if (!in_array($userRole, ['admin', 'inventory'])) {
                return redirect()->back()->with('error', 'AKSES DITOLAK: Hanya tim Inventory atau Admin yang dapat melakukan penerimaan barang (Goods Receipt).');
            }
        }

        // SECURITY TAMBAHAN: Inventory dilarang membatalkan PO
        if ($userRole == 'inventory' && $newStatus == 'cancelled') {
            return redirect()->back()->with('error', 'AKSES DITOLAK: Tim Inventory tidak memiliki hak untuk membatalkan Purchase Order.');
        }

        return DB::transaction(function () use ($request, $purchaseOrder) {
            
            // 1. HAPUS ITEM
            if ($request->filled('deleted_items')) {
                PurchaseOrderItem::whereIn('id', $request->deleted_items)
                    ->where('purchase_order_id', $purchaseOrder->id)
                    ->delete();
            }

            // 2. UPDATE ITEM LAMA (Hanya jika status DRAFT)
            if ($request->has('items') && $purchaseOrder->status == 'draft') {
                foreach ($request->items as $itemId => $data) {
                    $item = PurchaseOrderItem::find($itemId);
                    
                    if ($item && $item->purchase_order_id == $purchaseOrder->id) {
                        $discount = $data['discount_percent'] ?? 0;
                        $quantity = $data['quantity'] ?? $item->quantity;
                        
                        $grossTotal = $item->unit_price * $quantity;
                        $discountAmount = $grossTotal * ($discount / 100);
                        $subtotal = $grossTotal - $discountAmount;

                        $item->update([
                            'quantity' => $quantity,
                            'discount_percent' => $discount,
                            'subtotal' => $subtotal
                        ]);
                    }
                }
            }

            // 3. CEK DUPLIKASI ITEM BARU
            if ($request->has('new_items')) {
                $existingMaterialIds = $purchaseOrder->items()
                    ->whereNotIn('id', $request->deleted_items ?? [])
                    ->pluck('material_id')->toArray();
                
                $newMaterialIds = array_column($request->new_items, 'material_id');

                if (!empty(array_intersect($newMaterialIds, $existingMaterialIds))) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'new_items' => "Material sudah ada di dalam list. Edit item yang ada atau hapus dulu."
                    ]);
                }
            }

            // 4. UPDATE INFO PENGIRIMAN & DUE DATE
            // Bisa diupdate kapan saja sebelum status 'received' atau 'cancelled'
            if (!in_array($purchaseOrder->status, ['received', 'cancelled'])) {
                $infoChanged = false; 

                if ($request->filled('shipping_cost')) {
                    $purchaseOrder->shipping_cost = $request->shipping_cost;
                    $infoChanged = true;
                }
                
                if ($request->filled('shipping_terms')) {
                    $purchaseOrder->shipping_terms = $request->shipping_terms;
                    $infoChanged = true;
                }
                
                if ($request->filled('expected_arrival_date')) {
                    $purchaseOrder->expected_arrival_date = $request->expected_arrival_date;
                    $infoChanged = true;
                }

                if ($request->filled('due_date')) {
                    $purchaseOrder->due_date = $request->due_date;
                    $infoChanged = true;
                }

                if ($infoChanged) {
                    $purchaseOrder->save(); 
                }
            }

            // 5. SIMPAN ITEM BARU
            if ($request->has('new_items') && $request->status != 'cancelled') {
                foreach ($request->new_items as $itemData) {
                    $material = Material::find($itemData['material_id']);
                    
                    $qty = $itemData['quantity'];
                    $cost = $itemData['cost']; // Harga beli manual dari user
                    $discount = $itemData['discount_percent'] ?? 0;
                    
                    $grossTotal = $cost * $qty;
                    $discountAmount = $grossTotal * ($discount / 100);
                    $subtotal = $grossTotal - $discountAmount;

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'material_id' => $material->id,
                        
                        // Snapshot Material
                        'material_name_snapshot' => $material->name,
                        'unit_snapshot' => $material->purchase_unit, // Snapshot satuan beli
                        'conversion_factor_snapshot' => $material->conversion_factor,
                        
                        'quantity' => $qty,
                        'unit_price' => $cost, 
                        'discount_percent' => $discount,
                        'subtotal' => $subtotal
                    ]);
                }
            }

            // 6. HITUNG ULANG GRAND TOTAL
            $purchaseOrder->load('items'); 
            $itemsTotal = $purchaseOrder->items->sum('subtotal');
            
            // Logika Ongkir: 
            // Jika FOB Shipping Point (Kita tanggung), maka ongkir menambah hutang ke supplier 
            // (Asumsi supplier menalangi atau menagih ongkir di invoice yang sama).
            // Jika FOB Destination (Supplier tanggung), ongkir tidak menambah tagihan kita.
            // *Catatan: Jika ongkir dibayar ke pihak ketiga (ekspedisi), logic ini perlu disesuaikan.*
            // Disini saya asumsi ongkir masuk ke tagihan PO supplier.
            $shippingToAdd = ($purchaseOrder->shipping_terms == 'FOB_shipping_point') ? $purchaseOrder->shipping_cost : 0;
            
            $newGrandTotal = $itemsTotal + $shippingToAdd;

            $purchaseOrder->grand_total = $newGrandTotal;
            $purchaseOrder->remaining_balance = $newGrandTotal - $purchaseOrder->paid_amount;
            
            // 7. LOGIKA STATUS & STOK
            $oldStatus = $purchaseOrder->status;
            $newStatus = $request->status;

            // Kasus A: Draft -> Ordered (KONFIRMASI PESANAN)
            // Tambahkan ke Ordered Stock (Barang OTW)
            if ($oldStatus == 'draft' && $newStatus == 'ordered') {
                foreach ($purchaseOrder->items as $item) {
                    $material = $item->material;
                    // Konversi ke Base Unit
                    $conversion = $item->conversion_factor_snapshot ?? $material->conversion_factor;
                    $qtyBase = $item->quantity * $conversion;
                    
                    $material->increment('ordered_stock', $qtyBase);
                }
            }

            // Kasus B: Ordered -> Received (BARANG SAMPAI)
            // Pindahkan dari Ordered Stock ke Current Stock
            // Kasus B: Ordered -> Received (BARANG SAMPAI)
            if ($oldStatus == 'ordered' && $newStatus == 'received') {
                foreach ($purchaseOrder->items as $item) {
                    $material = $item->material;
                    
                    // 1. Siapkan Data Konversi & Qty
                    $conversion = $item->conversion_factor_snapshot ?? 1;
                    $qtyBaseIn = $item->quantity * $conversion; // Qty masuk dalam satuan dasar (gram/ml)
                    
                    // 2. Kurangi Ordered Stock (Barang sudah bukan OTW lagi)
                    $newOrdered = max(0, $material->ordered_stock - $qtyBaseIn);
                    $material->ordered_stock = $newOrdered;

                    // --- 3. HITUNG HPP (WEIGHTED AVERAGE) ---
                    // Ambil stok dan harga saat ini (Sebelum ditambah)
                    $currentStock = $material->current_stock;
                    $currentPrice = $material->price_per_unit;

                    // Hitung Valuasi
                    $oldAssetValue = $currentStock * $currentPrice; // Nilai aset lama
                    $newAssetValue = $item->subtotal; // Nilai aset baru (Harga Beli - Diskon)

                    $totalStock = $currentStock + $qtyBaseIn;
                    $totalValue = $oldAssetValue + $newAssetValue;

                    // Hitung Harga Per Unit Baru
                    // Cegah division by zero
                    $newPricePerUnit = ($totalStock > 0) ? ($totalValue / $totalStock) : $item->unit_price / $conversion;

                    // Update Master Material (Stok & Harga)
                    $material->current_stock = $totalStock;
                    $material->price_per_unit = $newPricePerUnit;
                    $material->save();
                    // ----------------------------------------

                    // 4. Catat Transaksi Material (History)
                    MaterialTransaction::create([
                        'material_id' => $material->id,
                        'transaction_date' => now(), 
                        'type' => 'in', 
                        'qty' => $qtyBaseIn,
                        
                        // Simpan harga valuasi per unit yang BARU saja dihitung (atau harga beli saat itu)
                        // Biasanya di history transaksi IN, orang mencatat harga beli aktual saat itu agar bisa ditelusuri
                        'price_per_unit' => ($qtyBaseIn > 0) ? ($item->subtotal / $qtyBaseIn) : 0, 
                        'total_price' => $item->subtotal,
                        
                        'current_stock_balance' => $material->current_stock,
                        
                        // SNAPSHOT LENGKAP
                        'material_name_snapshot' => $item->material_name_snapshot,
                        'material_packaging_size_snapshot' => $material->packaging_size,
                        'material_packaging_unit_snapshot' => $material->packaging_unit,
                        'material_conversion_factor_snapshot' => $conversion,
                        'purchase_unit_snapshot' => $item->unit_snapshot,
                        'material_unit_snapshot' => $material->unit,
                        
                        'purchase_order_id' => $purchaseOrder->id,
                        'description' => 'Penerimaan PO: ' . $purchaseOrder->po_number,
                    ]);
                }
            }

            // Kasus C: Ordered -> Cancelled (BATAL PESAN)
            // Kembalikan Ordered Stock
            if ($oldStatus == 'ordered' && $newStatus == 'cancelled') {
                foreach ($purchaseOrder->items as $item) {
                    $material = $item->material;
                    $conversion = $item->conversion_factor_snapshot ?? 1;
                    $qtyBase = $item->quantity * $conversion;
                    
                    $newOrdered = max(0, $material->ordered_stock - $qtyBase);
                    $material->update(['ordered_stock' => $newOrdered]);
                }
                
                // Reset Keuangan
                $purchaseOrder->grand_total = 0;
                $purchaseOrder->remaining_balance = 0;
                $purchaseOrder->shipping_cost = 0;
            }

            $purchaseOrder->status = $newStatus;
            $purchaseOrder->save();

            return redirect()->back()->with('success', 'Status Purchase Order berhasil diperbarui.');
        });
    }

    public function showPayments(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($purchaseOrder->id);

        // Asumsi ada model PurchasePayment
        $payments = PurchasePayment::where('purchase_order_id', $purchaseOrder->id)
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('purchase.payment', compact('purchaseOrder', 'payments'));
    }

    public function print(PurchaseOrder $purchaseOrder)
    {
         $user = Auth::user();
        if (!in_array($user->role, ['admin', 'purchase'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk mencetak Purchase Order.')->withInput();
        } else {
            $purchaseOrder = PurchaseOrder::with(['items.material', 'partner'])->findOrFail($purchaseOrder->id);

            $docTitle = 'PURCHASE ORDER';
            $docType  = 'PO';

            $pdf = Pdf::loadView('purchase.pdf', compact('purchaseOrder', 'docTitle', 'docType'));
            $pdf->setPaper('a4', 'portrait');

            return $pdf->stream('PO-' . $purchaseOrder->po_number . '.pdf');
        }
    }
}
