<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Order - {{ $salesOrder->so_code }} | Production Planning System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        blackBase: '#000000',
                        carbon: '#1B1D1F',
                        carbonSoft: '#24272A',
                        silver: '#C8CCCE',
                        petronas: '#00A19B',
                        muted: '#9DA3A6',
                        danger: '#EF4444',
                        warning: '#F59E0B',
                        success: '#10B981'
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-blackBase text-silver min-h-screen">

<main class="max-w-7xl mx-auto px-6 py-6 space-y-8">

    <nav aria-label="breadcrumb" class="text-xs text-muted">
        <ol class="flex items-center space-x-2">
            <li><a href="{{ route('home.index') }}" class="hover:text-petronas transition-colors">Home</a></li>
            <li class="opacity-40">/</li>
            <li><a href="{{ route('sales.index') }}" class="hover:text-petronas transition-colors">Sales Orders</a></li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold" aria-current="page">Detail</li>
        </ol>
    </nav>

    <x-alert-messages />

    <header class="flex justify-between items-end">
        <div>
            <div class="flex items-center gap-3">
                <p class="text-xs uppercase tracking-widest text-muted">Sales Transaction</p>
                @php
                    $badgeColor = match($salesOrder->status) {
                        'draft' => 'bg-gray-800 text-gray-400 border-gray-600',
                        'confirmed' => 'bg-blue-900/30 text-blue-400 border-blue-800',
                        'shipped' => 'bg-petronas/20 text-petronas border-petronas',
                        'cancelled' => 'bg-red-900/30 text-red-400 border-red-800',
                        default => 'bg-carbon'
                    };
                @endphp
                <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold border {{ $badgeColor }}">
                    {{ $salesOrder->status }}
                </span>
            </div>
            <h1 class="text-3xl font-extrabold text-petronas mt-1">{{ $salesOrder->so_code }}</h1>
            <p class="text-sm text-muted mt-1">
                <span class="bg-carbon px-2 py-1 rounded text-xs font-mono mr-2 border border-carbonSoft">
                    {{ \Carbon\Carbon::parse($salesOrder->transaction_date)->format('d M Y') }}
                </span>
                Distributor: <span class="font-bold text-silver">{{ $salesOrder->company_name }}</span>
            </p>
        </div>

        {{-- PERBAIKAN: Tombol Print hanya tampil jika status BUKAN draft --}}
        @if($salesOrder->status != 'draft' && $salesOrder->status != 'cancelled')
            <div>
                <a href="{{ route('sales.print', $salesOrder->id) }}" target="_blank" 
                   class="flex items-center gap-2 px-5 py-2.5 rounded-lg font-bold text-blackBase bg-silver hover:bg-white transition shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Generate Invoice</span>
                </a>
            </div>
        @endif
    </header>

    <form id="mainForm" action="{{ route('sales.updateStatus', $salesOrder->id) }}" method="POST">
        @csrf
        @method('PATCH')

        <div id="deletedItemsContainer"></div>

        <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
            <h2 class="text-lg font-bold text-petronas mb-4">Transaction Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 text-sm">
                <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                    <p class="text-xs text-muted uppercase tracking-wide mb-1">Payment Status</p>
                    <p class="text-lg font-bold uppercase {{ $salesOrder->payment_status === 'paid' ? 'text-success' : ($salesOrder->payment_status === 'partial' ? 'text-warning' : 'text-danger') }}">
                        {{ $salesOrder->payment_status }}
                    </p>
                </div>
                <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                    <p class="text-xs text-muted uppercase tracking-wide mb-1">Jatuh Tempo</p>
                    <p class="text-lg font-bold text-silver">
                        {{ $salesOrder->due_date ? \Carbon\Carbon::parse($salesOrder->due_date)->format('d M Y') : '-' }}
                    </p>
                </div>
                <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                    <p class="text-xs text-muted uppercase tracking-wide mb-1">Grand Total</p>
                    <p class="text-xl font-bold text-petronas">Rp {{ number_format($salesOrder->grand_total, 0, ',', '.') }}</p>
                </div>
                <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                    <p class="text-xs text-muted uppercase tracking-wide mb-1">Sisa Tagihan</p>
                    <p class="text-lg font-bold {{ $salesOrder->remaining_balance > 0 ? 'text-red-400' : 'text-muted' }}">
                        Rp {{ number_format($salesOrder->remaining_balance, 0, ',', '.') }}
                    </p>
                </div>
            </div>
            
            <div class="bg-carbon/50 p-4 rounded-xl border border-carbon mt-4">
                <div class="flex items-center gap-2 mb-3">
                    <h3 class="text-xs font-bold text-silver uppercase tracking-wider">Customer Snapshot</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-muted">
                    <div><span class="block uppercase tracking-wide text-[10px] mb-0.5">Contact Person</span><span class="text-silver font-medium">{{ $salesOrder->person_name ?? '-' }}</span></div>
                    <div><span class="block uppercase tracking-wide text-[10px] mb-0.5">Phone / Email</span><span class="text-silver font-medium">{{ $salesOrder->phone ?? '-' }} / {{ $salesOrder->email ?? '-' }}</span></div>
                    <div><span class="block uppercase tracking-wide text-[10px] mb-0.5">Address</span><span class="text-silver font-medium truncate">{{ $salesOrder->address ?? '-' }}</span></div>
                </div>
            </div>
        </section>

        {{-- SECTION: SHIPPING INFORMATION --}}
        <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6 mt-6">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-bold text-petronas">Shipping Information</h2>
                
                {{-- Badge Indikator --}}
                @if($salesOrder->status == 'confirmed')
                    <span class="text-xs bg-petronas/10 text-petronas px-2 py-1 rounded border border-petronas/20 animate-pulse">
                        Input Mode Active
                    </span>
                @elseif($salesOrder->status == 'shipped')
                    <span class="text-xs bg-carbon text-silver px-2 py-1 rounded border border-carbon">
                        Locked (Shipped)
                    </span>
                @endif
            </div>

            {{-- KONDISI 1: DRAFT (Hidden) --}}
            @if($salesOrder->status == 'draft')
                <div class="p-8 text-center border-2 border-dashed border-carbon rounded-xl">
                    <p class="text-muted text-sm italic">
                        "Informasi pengiriman dapat diinput setelah Sales Order dikonfirmasi (Status: Confirmed)."
                    </p>
                </div>

            {{-- KONDISI 2: CONFIRMED (Form Input Mode) --}}
            @elseif($salesOrder->status == 'confirmed')
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Input Tanggal --}}
                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide block mb-1">Tanggal Pengiriman</label>
                        <input type="date" name="shipping_date" 
                            value="{{ $salesOrder->shipping_date ? \Carbon\Carbon::parse($salesOrder->shipping_date)->format('Y-m-d') : date('Y-m-d') }}"
                            class="w-full px-4 py-2.5 rounded-lg bg-carbon border border-petronas/50 text-silver focus:outline-none focus:border-petronas transition"
                            required>
                    </div>

                    {{-- Input Tipe Ongkir --}}
                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide block mb-1">Pengaturan Ongkir</label>
                        <select name="shipping_payment_type" id="shippingTypeSelect" onchange="toggleShippingInput()"
                            class="w-full px-4 py-2.5 rounded-lg bg-carbon border border-petronas/50 text-silver focus:outline-none focus:border-petronas transition appearance-none cursor-pointer">
                            <option value="bill_to_customer" {{ $salesOrder->shipping_payment_type == 'bill_to_customer' ? 'selected' : '' }}>Ditanggung Pembeli (Diurus Sendiri)</option>
                            <option value="borne_by_company" {{ $salesOrder->shipping_payment_type == 'borne_by_company' ? 'selected' : '' }}>Ditanggung Perusahaan (Expense)</option>
                        </select>
                    </div>

                    {{-- Input Biaya --}}
                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide block mb-1">Nominal Biaya</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-muted text-sm">Rp</span>
                            <input type="number" name="shipping_cost" id="shippingCostInput" step="0.01" 
                                value="{{ old('shipping_cost', $salesOrder->shipping_cost) }}"
                                class="w-full pl-10 pr-4 py-2.5 rounded-lg bg-carbon border border-petronas/50 text-silver focus:outline-none focus:border-petronas transition"
                                placeholder="0">
                        </div>
                        <p id="shippingHint" class="text-[10px] text-muted mt-1 italic"></p>
                    </div>
                </div>

            {{-- KONDISI 3: SHIPPED / CANCELLED (Card View Mode) --}}
            @else
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                    {{-- Card Tanggal --}}
                    <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                        <p class="text-xs text-muted uppercase tracking-wide mb-1">Tanggal Pengiriman</p>
                        <p class="text-lg font-bold text-silver">
                            {{ $salesOrder->shipping_date ? \Carbon\Carbon::parse($salesOrder->shipping_date)->format('d M Y') : '-' }}
                        </p>
                    </div>

                    {{-- Card Tipe Ongkir --}}
                    <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                        <p class="text-xs text-muted uppercase tracking-wide mb-1">Pengaturan Ongkir</p>
                        <p class="text-lg font-bold text-silver">
                            @if($salesOrder->shipping_payment_type == 'bill_to_customer')
                                <span class="text-warning">Ditanggung Pembeli</span>
                            @else
                                <span class="text-petronas">Ditanggung Perusahaan</span>
                            @endif
                        </p>
                    </div>

                    {{-- Card Biaya --}}
                    <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                        <p class="text-xs text-muted uppercase tracking-wide mb-1">Biaya Realisasi</p>
                        <p class="text-lg font-bold text-silver">
                            Rp {{ number_format($salesOrder->shipping_cost, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            @endif
        </section>

        @if($salesOrder->status == 'draft')
            <section class="bg-carbonSoft rounded-xl p-6 border border-petronas/30 shadow-lg shadow-petronas/5 relative overflow-hidden mt-6">
                <div class="relative z-10">
                    <h2 class="text-lg font-bold text-petronas mb-1">Add Sales Item</h2>
                    <p class="text-xs text-muted mb-6">Tambahkan produk ke tabel di bawah sebelum melakukan konfirmasi.</p>
                    
                    <div class="flex flex-col md:flex-row gap-4 items-end">
                        <div class="grow w-full md:w-2/5">
                            <label class="text-xs text-silver font-semibold uppercase tracking-wide mb-2 block">Select Product</label>
                            <div class="relative">
                                <select id="productSelect" class="w-full appearance-none bg-carbon border border-muted/30 text-silver text-sm rounded-lg focus:ring-petronas focus:border-petronas block p-3 pr-10 hover:border-petronas/50 transition">
                                    <option value="" disabled selected>-- Choose Product --</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" 
                                            data-price="{{ $product->price }}" 
                                            data-code="{{ $product->code }}" 
                                            data-name="{{ $product->name }}"
                                            data-packaging="{{ $product->packaging }}">
                                            {{ $product->code }} - {{ $product->name }} (Stok: {{ $product->current_stock }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-muted"><svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg></div>
                            </div>
                        </div>
                        
                        <div class="w-full md:w-1/6">
                            <label class="text-xs text-silver font-semibold uppercase tracking-wide mb-2 block">Qty</label>
                            <input type="number" id="qtyInput" placeholder="1" min="1" value="1" class="block w-full p-3 bg-carbon border border-muted/30 rounded-lg text-white font-mono placeholder-muted/50 focus:ring-1 focus:ring-petronas focus:border-petronas hover:border-petronas/50 transition">
                        </div>

                        <div class="w-full md:w-1/6">
                            <label class="text-xs text-silver font-semibold uppercase tracking-wide mb-2 block">Disc (%)</label>
                            <input type="number" id="discInput" placeholder="0" min="0" max="100" value="0" step="0.1" class="block w-full p-3 bg-carbon border border-muted/30 rounded-lg text-white font-mono placeholder-muted/50 focus:ring-1 focus:ring-petronas focus:border-petronas hover:border-petronas/50 transition">
                        </div>

                        <div class="w-full md:w-auto">
                            <button type="button" onclick="addItemToTable()" class="w-full md:w-auto bg-petronas text-blackBase font-bold px-6 py-3 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20 flex items-center justify-center gap-2"><span>+ Add</span></button>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <section class="bg-carbonSoft rounded-xl p-6 border border-carbon mt-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-petronas">Order Items List</h2>
                <span class="text-xs bg-carbon px-3 py-1 rounded-full text-muted border border-carbon">Items Count: <span id="itemCountDisplay">{{ $salesOrder->items->count() }}</span></span>
            </div>

            <div class="overflow-x-auto rounded-lg border border-carbon">
                <table class="w-full text-sm">
                    <thead class="bg-carbon">
                        <tr>
                            <th class="px-4 py-3 text-left text-muted uppercase text-xs tracking-wider">Product</th>
                            <th class="px-4 py-3 text-center text-muted uppercase text-xs tracking-wider">Kemasan</th>
                            <th class="px-4 py-3 text-center text-muted uppercase text-xs tracking-wider">Qty</th>
                            <th class="px-4 py-3 text-right text-muted uppercase text-xs tracking-wider">Price</th>
                            <th class="px-4 py-3 text-center text-muted uppercase text-xs tracking-wider">Disc (%)</th>
                            <th class="px-4 py-3 text-right text-muted uppercase text-xs tracking-wider">Subtotal</th>
                            @if($salesOrder->status == 'draft') <th class="px-4 py-3 text-center text-muted uppercase text-xs tracking-wider">Action</th> @endif
                        </tr>
                    </thead>
                    
                    <tbody id="itemsTableBody" class="divide-y divide-carbon/50">
                        @foreach($salesOrder->items as $item)
                        <tr class="hover:bg-carbon transition-colors" id="row-db-{{ $item->id }}">
                            <td class="px-4 py-3">
                                <div class="text-silver font-bold">{{ $item->product->name }}</div>
                                <div class="text-xs text-muted font-mono">{{ $item->product->code }}</div>
                            </td>
                            <td class="px-4 py-3 text-center text-silver font-mono text-xs">{{ $item->product_packaging_snapshot ?? $item->product->packaging ?? '-' }}</td>
                            
                            {{-- KOLOM QTY (EDITABLE SAAT DRAFT) --}}
                            <td class="px-4 py-3 text-center">
                                @if($salesOrder->status == 'draft')
                                    <input type="number" 
                                           name="items[{{$item->id}}][quantity]" 
                                           value="{{ $item->quantity }}" 
                                           min="1"
                                           class="w-16 p-1 bg-blackBase border border-muted/30 rounded text-center text-silver font-mono text-xs focus:ring-1 focus:ring-petronas focus:border-petronas"
                                    >
                                @else
                                    <span class="text-silver font-mono">{{ $item->quantity }}</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right text-muted font-mono">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            
                            {{-- KOLOM DISKON (EDITABLE SAAT DRAFT) --}}
                            <td class="px-4 py-3 text-center">
                                @if($salesOrder->status == 'draft')
                                    <input type="number" 
                                           name="items[{{$item->id}}][discount_percent]" 
                                           value="{{ $item->discount_percent + 0 }}" 
                                           min="0" max="100" step="0.1"
                                           class="w-16 p-1 bg-blackBase border border-muted/30 rounded text-center text-silver font-mono text-xs focus:ring-1 focus:ring-petronas focus:border-petronas"
                                    >
                                @else
                                    <span class="text-silver font-mono">{{ $item->discount_percent + 0 }}%</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right text-white font-bold font-mono">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            
                            @if($salesOrder->status == 'draft')
                            <td class="px-4 py-3 text-center text-muted text-xs italic">
                                <button type="button" 
                                    onclick="deleteExistingItem(this, {{ $item->id }}, {{ $item->subtotal }})"
                                    class="text-red-400 hover:text-red-600 font-bold px-2 py-1 rounded hover:bg-red-900/20 transition">
                                    ✕
                                </button>
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                    
                    <tfoot class="bg-carbon/50">
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-right text-muted text-xs uppercase">Items Subtotal</td>
                            <td class="px-4 py-2 text-right text-silver font-mono text-sm">
                                @php $itemsTotal = $salesOrder->items->sum('subtotal'); @endphp
                                Rp {{ number_format($itemsTotal, 0, ',', '.') }}
                            </td>
                            @if($salesOrder->status == 'draft') <td></td> @endif
                        </tr>
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-right text-muted text-xs uppercase">Shipping Cost <span class="text-[10px] text-petronas ml-1">({{ $salesOrder->shipping_payment_type == 'bill_to_customer' ? 'Customer' : 'Company' }})</span></td>
                            <td class="px-4 py-2 text-right text-silver font-mono text-sm">
                                @if($salesOrder->shipping_payment_type == 'bill_to_customer') Rp {{ number_format($salesOrder->shipping_cost, 0, ',', '.') }}
                                @else <span class="text-muted line-through">Rp {{ number_format($salesOrder->shipping_cost, 0, ',', '.') }}</span> <span class="text-[10px] text-petronas block">Gratis Ongkir</span> @endif
                            </td>
                            @if($salesOrder->status == 'draft') <td></td> @endif
                        </tr>
                        <tr class="border-t border-carbon">
                            <td colspan="5" class="px-4 py-4 text-right text-silver font-bold uppercase text-xs">Total Amount</td>
                            <td class="px-4 py-4 text-right text-petronas font-extrabold font-mono text-lg">Rp <span id="grandTotalDisplay">{{ number_format($salesOrder->grand_total, 0, ',', '.') }}</span></td>
                            @if($salesOrder->status == 'draft') <td></td> @endif
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div id="newItemsContainer"></div>

            <div class="mt-6 pt-4 border-t border-carbon flex justify-end gap-3">
                @if($salesOrder->status == 'draft')
                    <button type="submit" name="status" value="cancelled" class="border border-danger text-danger font-bold px-6 py-2 rounded-lg hover:bg-danger hover:text-white transition" onclick="return confirm('Batalkan order?')">Cancel Order</button>
                    <button type="submit" name="status" value="draft" class="bg-carbon text-silver border border-silver font-bold px-6 py-2 rounded-lg hover:bg-silver hover:text-blackBase transition">Save Draft</button>
                    <button type="submit" name="status" value="confirmed" class="bg-blue-600 text-white font-bold px-6 py-2 rounded-lg hover:bg-blue-500 transition shadow-lg shadow-blue-500/20" onclick="return confirm('Konfirmasi order?')">Confirm Order</button>
                @elseif($salesOrder->status == 'confirmed')
                    <button type="submit" name="status" value="cancelled" class="border border-danger text-danger font-bold px-6 py-2 rounded-lg hover:bg-danger hover:text-white transition" onclick="return confirm('Yakin ingin membatalkan order yang sudah dikonfirmasi? Stok reserved akan dikembalikan.')">Cancel Order</button>
                    <button type="submit" name="status" value="shipped" class="bg-petronas text-blackBase font-bold px-6 py-2 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20" onclick="return confirm('Kirim Barang?')">Ship Order</button>
                @endif
            </div>
        </section>
    </form>

</main>

<script>
    function toggleShippingInput() {
        const typeSelect = document.getElementById('shippingTypeSelect');
        const costInput = document.getElementById('shippingCostInput');
        const hintText = document.getElementById('shippingHint');
        
        if (!typeSelect || !costInput) return;

        const isCustomer = typeSelect.value === 'bill_to_customer';

        if (isCustomer) {
            costInput.value = 0;
            costInput.readOnly = true;
            costInput.classList.add('bg-blackBase/50', 'text-muted', 'cursor-not-allowed');
            costInput.classList.remove('bg-carbon', 'text-silver');
            hintText.innerText = "* Pengiriman diatur oleh pembeli (Ex Works/FOB). Biaya dinolkan.";
        } else {
            costInput.readOnly = false;
            costInput.required = true;
            costInput.classList.remove('bg-blackBase/50', 'text-muted', 'cursor-not-allowed');
            costInput.classList.add('bg-carbon', 'text-silver');
            hintText.innerText = "* Masukkan nominal real yang dibayar perusahaan ke kurir.";
        }
    }

    let addedItemsCount = 0;

    function deleteExistingItem(btn, itemId, subtotal) {
        const row = btn.closest('tr');
        row.style.display = 'none';

        const container = document.getElementById('deletedItemsContainer');
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'deleted_items[]';
        input.value = itemId;
        container.appendChild(input);
    }

    function addItemToTable() {
        const productSelect = document.getElementById('productSelect');
        const qtyInput = document.getElementById('qtyInput');
        const discInput = document.getElementById('discInput');
        const tableBody = document.getElementById('itemsTableBody');
        const itemsContainer = document.getElementById('newItemsContainer');

        if (productSelect.value === "") { alert("Silakan pilih produk."); return; }
        if (qtyInput.value <= 0) { alert("Jumlah harus lebih dari 0."); return; }

        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const productId = productSelect.value;
        const productName = selectedOption.getAttribute('data-name');
        const productCode = selectedOption.getAttribute('data-code');
        const productPackaging = selectedOption.getAttribute('data-packaging') || '-';
        const price = parseFloat(selectedOption.getAttribute('data-price'));
        const qty = parseInt(qtyInput.value);
        const discount = parseFloat(discInput.value) || 0;

        const grossTotal = price * qty;
        const discountAmount = grossTotal * (discount / 100);
        const subtotal = grossTotal - discountAmount;

        const uniqueId = Date.now() + '_' + addedItemsCount; 

        // -- PERBAIKAN: Input Qty dan Diskon dibuat bisa diedit langsung --
        const row = document.createElement('tr');
        row.id = 'row_' + uniqueId;
        row.className = 'hover:bg-carbon transition-colors bg-petronas/5'; 
        row.innerHTML = `
            <td class="px-4 py-3">
                <div class="text-silver font-bold">${productName} <span class="text-xs text-petronas font-normal">(New)</span></div>
                <div class="text-xs text-muted font-mono">${productCode}</div>
            </td>
            <td class="px-4 py-3 text-center text-silver font-mono text-xs">${productPackaging}</td>
            
            {{-- KOLOM QTY BARU (INPUT) --}}
            <td class="px-4 py-3 text-center">
                <input type="number" 
                       name="new_items[${addedItemsCount}][quantity]" 
                       value="${qty}" 
                       min="1"
                       class="w-16 p-1 bg-blackBase border border-muted/30 rounded text-center text-silver font-mono text-xs focus:ring-1 focus:ring-petronas focus:border-petronas"
                >
            </td>

            <td class="px-4 py-3 text-right text-muted font-mono">Rp ${formatRupiah(price)}</td>
            
            {{-- KOLOM DISKON BARU (INPUT) --}}
            <td class="px-4 py-3 text-center">
                <input type="number" 
                       name="new_items[${addedItemsCount}][discount_percent]" 
                       value="${discount}" 
                       min="0" max="100" step="0.1"
                       class="w-16 p-1 bg-blackBase border border-muted/30 rounded text-center text-silver font-mono text-xs focus:ring-1 focus:ring-petronas focus:border-petronas"
                >
            </td>

            <td class="px-4 py-3 text-right text-white font-bold font-mono">Rp ${formatRupiah(subtotal)}</td>
            <td class="px-4 py-3 text-center text-xs text-muted">
                <button type="button" onclick="removeNewItem('${uniqueId}', ${subtotal})" class="text-red-400 hover:text-red-600 font-bold px-2 py-1">✕</button>
            </td>
        `;
        tableBody.appendChild(row);

        // -- PERBAIKAN: Input hidden hanya untuk Product ID saja --
        // Qty dan Diskon sudah ditangani oleh input di dalam tabel
        const inputDiv = document.createElement('div');
        inputDiv.id = 'input_' + uniqueId;
        inputDiv.innerHTML = `
            <input type="hidden" name="new_items[${addedItemsCount}][product_id]" value="${productId}">
        `;
        itemsContainer.appendChild(inputDiv);
        
        productSelect.value = "";
        qtyInput.value = 1;
        discInput.value = 0;
        addedItemsCount++;
    }

    function removeNewItem(uniqueId, subtotal) {
        const row = document.getElementById('row_' + uniqueId);
        if(row) row.remove();

        const inputDiv = document.getElementById('input_' + uniqueId);
        if(inputDiv) inputDiv.remove();
    }

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID').format(angka);
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleShippingInput();
    });
</script>

</body>
</html>