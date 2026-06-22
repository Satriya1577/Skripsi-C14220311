<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Purchase Order - {{ $purchaseOrder->po_number }} | Production Planning System</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            blackBase: '#E2E8F0',
            carbon: '#CBD5E1',
            carbonSoft: '#F8FAFC',
            silver: '#334155',
            petronas: '#2563EB',
            muted: '#64748B',
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

  {{-- BREADCRUMB --}}
  <nav aria-label="breadcrumb" class="text-xs text-muted">
    <ol class="flex items-center space-x-2">
      <li><a href="{{ route('home.index') }}" class="hover:text-blue-600 transition-colors">Home</a></li>
      <li class="opacity-40">/</li>
      <li><a href="{{ route('purchases.index') }}" class="hover:text-blue-600 transition-colors">Purchase Orders</a></li>
      <li class="opacity-40">/</li>
      <li class="text-slate-800 font-semibold" aria-current="page">Detail</li>
    </ol>
  </nav>

  <x-alert-messages />

  {{-- HEADER --}}
  <header class="flex justify-between items-end">
    <div>
      <div class="flex items-center gap-3">
        <p class="text-xs uppercase tracking-widest text-muted">Purchase Transaction</p>
        @php
          $badgeColor = match($purchaseOrder->status) {
            'draft'   => 'bg-gray-100 text-gray-700 border-gray-300',
            'ordered'  => 'bg-yellow-100 text-yellow-800 border-yellow-300',
            'received' => 'bg-green-100 text-green-700 border-green-300',
            'cancelled' => 'bg-red-100 text-red-700 border-red-300',
            default   => 'bg-gray-100 text-gray-700 border-gray-300'
          };
        @endphp
        <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold border {{ $badgeColor }}">
          {{ $purchaseOrder->status }}
        </span>
      </div>
      <h1 class="text-3xl font-extrabold text-slate-800 mt-1">{{ $purchaseOrder->po_number }}</h1>
      <p class="text-sm text-muted mt-1">
        <span class="bg-carbon px-2 py-1 rounded text-xs mr-2 border border-carbonSoft">
          {{ \Carbon\Carbon::parse($purchaseOrder->order_date)->format('d M Y') }}
        </span>
        Supplier: <span class="font-bold text-silver">{{ $purchaseOrder->company_name }}</span>
      </p>
    </div>

    @if($purchaseOrder->status != 'draft' && $purchaseOrder->status != 'cancelled')
      <div>
        <a href="{{ route('purchases.print', $purchaseOrder->id) }}" target="_blank" 
        class="flex items-center gap-2 px-5 py-2.5 rounded-lg font-bold text-white bg-silver hover:bg-white transition shadow-lg">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          <span>Print PO</span>
        </a>
      </div>
    @endif
  </header>

  <form id="mainForm" action="{{ route('purchases.updateStatus', $purchaseOrder->id) }}" method="POST">
    @csrf
    @method('PATCH')

    <div id="deletedItemsContainer"></div>

    {{-- SECTION 1: INFO UTAMA --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
      <h2 class="text-lg font-bold text-slate-800 mb-4">Transaction Information</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 text-sm">
        <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
          <p class="text-xs text-muted uppercase tracking-wide mb-1">Payment Status</p>
          <p class="text-lg font-bold uppercase {{ $purchaseOrder->payment_status === 'paid' ? 'text-success' : ($purchaseOrder->payment_status === 'partial' ? 'text-warning' : 'text-danger') }}">
            {{ $purchaseOrder->payment_status }}
          </p>
        </div>
        <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
          <p class="text-xs text-muted uppercase tracking-wide mb-1">Jatuh Tempo</p>
          <p class="text-lg font-bold text-silver">
            {{ $purchaseOrder->due_date ? \Carbon\Carbon::parse($purchaseOrder->due_date)->format('d M Y') : '-' }}
          </p>
        </div>
        <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
          <p class="text-xs text-muted uppercase tracking-wide mb-1">Grand Total</p>
          <p class=" text-xl font-bold text-slate-800">Rp {{ number_format($purchaseOrder->grand_total, 2, ',', '.') }}</p>
        </div>
        <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
          <p class="text-xs text-muted uppercase tracking-wide mb-1">Sisa Hutang</p>
          <p class="text-lg font-bold {{ $purchaseOrder->remaining_balance > 0 ? 'text-red-400' : 'text-muted' }}">
            Rp {{ number_format($purchaseOrder->remaining_balance, 2, ',', '.') }}
          </p>
        </div>
      </div>
      
      <div class="bg-carbon/50 p-4 rounded-xl border border-carbon mt-4">
        <div class="flex items-center gap-2 mb-3">
          <h3 class="text-xs font-bold text-silver uppercase tracking-wider">Supplier Snapshot</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-muted">
          <div><span class="block uppercase tracking-wide text-[10px] mb-0.5">Contact Person</span><span class="text-silver font-medium">{{ $purchaseOrder->person_name ?? '-' }}</span></div>
          <div><span class="block uppercase tracking-wide text-[10px] mb-0.5">Phone / Email</span><span class="text-silver font-medium">{{ $purchaseOrder->phone ?? '-' }} / {{ $purchaseOrder->email ?? '-' }}</span></div>
          <div><span class="block uppercase tracking-wide text-[10px] mb-0.5">Address</span><span class="text-silver font-medium truncate">{{ $purchaseOrder->address ?? '-' }}</span></div>
        </div>
      </div>
    </section>

    {{-- SECTION 2: SHIPPING DENGAN LOGIKA ACCOUNTING --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6 mt-6 relative overflow-hidden">
      <div class="flex justify-between items-center relative z-10">
        <h2 class="text-lg font-bold text-slate-800">Shipping & Arrival</h2>
        
        @if($purchaseOrder->status == 'ordered')
          @if($purchaseOrder->is_shipping_paid)
            <span class="text-xs bg-green-100 text-green-700 px-3 py-1.5 rounded-full border border-green-300 font-bold flex items-center gap-1 shadow-sm">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              Verified by Accounting
            </span>
          @else
            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded border border-petronas/20 animate-pulse">
              Input Mode Active
            </span>
          @endif
        @elseif($purchaseOrder->status == 'received')
          <span class="text-xs bg-carbon text-silver px-2 py-1 rounded border border-carbon">
            Locked (Received)
          </span>
        @endif
      </div>

      @if($purchaseOrder->status == 'draft')
        <div class="p-8 text-center border-2 border-dashed border-carbon rounded-xl">
          <p class="text-muted text-sm italic">
            "Informasi pengiriman dan tanggal kedatangan dapat diinput setelah PO dikonfirmasi ke Supplier (Status: Ordered)."
          </p>
        </div>

      @elseif($purchaseOrder->status == 'ordered' && !$purchaseOrder->is_shipping_paid)
        {{-- MODE EDIT UNTUK GUDANG (BELUM VERIFIKASI) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative z-10">
          <div>
            <label class="text-xs text-muted uppercase tracking-wide block mb-1">Estimasi Tanggal Kedatangan</label>
            <input type="date" name="expected_arrival_date" 
              value="{{ $purchaseOrder->expected_arrival_date ? \Carbon\Carbon::parse($purchaseOrder->expected_arrival_date)->format('Y-m-d') : date('Y-m-d') }}"
              class="w-full px-4 py-2.5 rounded-lg bg-carbon border border-petronas/50 text-silver focus:outline-none focus:border-petronas transition"
              required>
          </div>

          <div>
            <label class="text-xs text-muted uppercase tracking-wide block mb-1">Syarat Pengiriman</label>
            <select name="shipping_terms" id="shippingTypeSelect" onchange="toggleShippingInput()"
              class="w-full px-4 py-2.5 rounded-lg bg-carbon border border-petronas/50 text-silver focus:outline-none focus:border-petronas transition appearance-none cursor-pointer">
              <option value="FOB_destination" {{ $purchaseOrder->shipping_terms == 'FOB_destination' ? 'selected' : '' }}>FOB Destination (Ditanggung Supplier)</option>
              <option value="FOB_shipping_point" {{ $purchaseOrder->shipping_terms == 'FOB_shipping_point' ? 'selected' : '' }}>FOB Shipping Point (Ditanggung Perusahaan)</option>
            </select>
          </div>

          <div>
            <label class="text-xs text-muted uppercase tracking-wide block mb-1">Nominal Biaya Ekspedisi</label>
            <div class="relative">
              <span class="absolute left-3 top-2.5 text-muted text-sm">Rp</span>
              <input type="number" name="shipping_cost" id="shippingCostInput" step="0.01" 
                value="{{ old('shipping_cost', $purchaseOrder->shipping_cost) }}"
                class="w-full pl-10 pr-4 py-2.5 rounded-lg bg-carbon border border-petronas/50 text-silver focus:outline-none focus:border-petronas transition"
                placeholder="0">
            </div>
            <p id="shippingHint" class="text-[10px] text-muted mt-1 italic"></p>
          </div>
        </div>
        
        {{-- TOMBOL ACTION KHUSUS SHIPPING --}}
        <div class="flex justify-end gap-3 mt-4 pt-4 relative z-10">
          <button type="submit" formaction="{{ route('purchases.updateShippingInfo', $purchaseOrder->id) }}" formmethod="POST" class="bg-carbon text-silver border border-muted/50 font-bold px-5 py-2 rounded-lg hover:bg-slate-300 transition text-sm">
            Save Shipping Info
          </button>
          
          {{-- Tombol Verifikasi Hanya Muncul Jika FOB Shipping Point (Ditanggung Perusahaan) --}}
          @if(in_array(Auth::user()->role, ['admin', 'accounting']) && $purchaseOrder->shipping_terms == 'FOB_shipping_point' && $purchaseOrder->shipping_cost > 0)
            <button type="submit" formaction="{{ route('purchases.verifyShippingPayment', $purchaseOrder->id) }}" formmethod="POST" onclick="return confirm('Verifikasi pembayaran? Ongkir tidak akan bisa diedit lagi dan PO ini tidak bisa dicancel.')" class="bg-green-600 text-white font-bold px-5 py-2 rounded-lg hover:bg-green-500 transition text-sm shadow-lg shadow-green-600/20">
              Verify & Pay Shipping
            </button>
          @endif
        </div>

      @else
        {{-- MODE LOCKED (SUDAH DIVERIFIKASI / SUDAH RECEIVED) --}}
        @if($purchaseOrder->is_shipping_paid)
          <div class="absolute -right-8 -top-8 w-32 h-32 bg-green-500/5 rounded-full blur-3xl"></div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm relative z-10">
          <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
            <p class="text-xs text-muted uppercase tracking-wide mb-1">
              {{ $purchaseOrder->status == 'received' ? 'Tanggal Aktual Kedatangan' : 'Estimasi Tanggal Kedatangan' }}
            </p>
            <p class="text-lg font-bold text-silver">
              {{ $purchaseOrder->expected_arrival_date ? \Carbon\Carbon::parse($purchaseOrder->expected_arrival_date)->format('d M Y') : '-' }}
            </p>
          </div>

          <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
            <p class="text-xs text-muted uppercase tracking-wide mb-1">Syarat Pengiriman</p>
            <p class="text-lg font-bold text-silver">
              @if($purchaseOrder->shipping_terms == 'FOB_shipping_point')
                <span class="text-warning">FOB Shipping Point (Perusahaan)</span>
              @else
                <span class="text-slate-800">FOB Destination (Supplier)</span>
              @endif
            </p>
          </div>

          <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
            <p class="text-xs text-muted uppercase tracking-wide mb-1">Biaya Realisasi</p>
            <p class="text-lg font-bold text-silver">
              Rp {{ number_format($purchaseOrder->shipping_cost, 2, ',', '.') }}
            </p>
          </div>
        </div>
      @endif
    </section>

    {{-- SECTION 3: ADD ITEM (DRAFT ONLY) --}}
    @if($purchaseOrder->status == 'draft')
      <section class="bg-carbonSoft rounded-xl p-6 border border-petronas/30 shadow-lg shadow-petronas/5 relative overflow-hidden mt-6">
        <div class="relative z-10">
          <h2 class="text-lg font-bold text-slate-800 mb-1">Add Material Item</h2>
          <p class="text-xs text-muted mb-6">Tambahkan bahan baku yang akan dibeli.</p>
          
          <div class="flex flex-col md:flex-row gap-4 items-end">
            <div class="grow w-full md:w-2/5">
              <label class="text-xs text-silver font-semibold uppercase tracking-wide mb-2 block">Select Material</label>
              <div class="relative">
                <select id="productSelect" class="w-full appearance-none bg-carbon border border-muted/30 text-silver text-sm rounded-lg focus:ring-petronas focus:border-petronas block p-3 pr-10 hover:border-petronas/50 transition">
                  <option value="" disabled selected>-- Choose Material --</option>
                  
                  @foreach($materials as $material)
                    <option value="{{ $material->id }}" 
                      data-price="{{ $material->price_per_unit }}" 
                      data-code="{{ $material->code }}" 
                      data-name="{{ $material->name }}"
                      data-unit="{{ $material->purchase_unit }}">
                      
                      {{ $material->code }} - {{ $material->name }} 
                      (On Hand: {{ number_format($material->on_hand_purchase_unit, 0, ',', '.') }}
                      | Rekomendasi re-stok: {{ number_format($material->recommended_restock, 0, ',', '.') }} {{ $material->purchase_unit }})
                      
                    </option>
                  @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-muted"><svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg></div>
              </div>
            </div>
            
            <div class="w-full md:w-1/6">
              <label class="text-xs text-silver font-semibold uppercase tracking-wide mb-2 block">Qty</label>
              <input type="number" id="qtyInput" placeholder="1" min="1" value="1" class="block w-full p-3 bg-carbon border border-muted/30 rounded-lg text-slate-800 placeholder-muted/50 focus:ring-1 focus:ring-petronas focus:border-petronas hover:border-petronas/50 transition">
            </div>

            <div class="w-full md:w-1/5">
              <label class="text-xs text-silver font-semibold uppercase tracking-wide mb-2 block">Harga Beli Per Satuan (Rp)</label>
              <input type="number" id="priceInput" placeholder="0" class="block w-full p-3 bg-carbon border border-muted/30 rounded-lg text-slate-800 placeholder-muted/50 focus:ring-1 focus:ring-petronas focus:border-petronas hover:border-petronas/50 transition">
            </div>

            <div class="w-full md:w-auto">
              <button type="button" onclick="addItemToTable()" class="w-full md:w-auto bg-green-600 text-white font-bold px-6 py-3 rounded-lg hover:bg-green-600/90 transition shadow-lg shadow-green-600/20 flex items-center justify-center gap-2"><span>+ Add</span></button>
            </div>
          </div>
        </div>
      </section>
    @endif

    {{-- SECTION 4: ORDER ITEMS LIST --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon mt-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-slate-800">Items List</h2>
        <span class="text-xs bg-carbon px-3 py-1 rounded-full text-slate-800 border border-carbon">Items Count: <span id="itemCountDisplay">{{ $purchaseOrder->items->count() }}</span></span>
      </div>

      <div class="overflow-x-auto rounded-lg border border-carbon">
        <table class="w-full text-sm">
          <thead class="bg-carbon">
            <tr>
              <th class="px-4 py-3 text-left text-black uppercase text-xs tracking-wider">Material</th>
              <th class="px-4 py-3 text-center text-black uppercase text-xs tracking-wider">Satuan</th>
              <th class="px-4 py-3 text-center text-black uppercase text-xs tracking-wider">Qty</th>
              <th class="px-4 py-3 text-right text-black uppercase text-xs tracking-wider">Harga Beli Per Satuan</th>
              <th class="px-4 py-3 text-right text-black uppercase text-xs tracking-wider">Subtotal</th>
              @if($purchaseOrder->status == 'draft') <th class="px-4 py-3 text-center text-black uppercase text-xs tracking-wider">Action</th> @endif
            </tr>
          </thead>
          
          <tbody id="itemsTableBody" class="divide-y divide-carbon/50">
            @foreach($purchaseOrder->items as $item)
            <tr class="hover:bg-carbon transition-colors" id="row-db-{{ $item->id }}">
              <td class="px-4 py-3">
                <div class="text-silver font-bold">{{ $item->material->name }}</div>
                <div class="text-xs text-muted ">{{ $item->material->code }}</div>
              </td>
              <td class="px-4 py-3 text-center text-silver text-xs">{{ $item->unit_snapshot ?? $item->material->purchase_unit }}</td>
              
              <td class="px-4 py-3 text-center">
                @if($purchaseOrder->status == 'draft')
                  <input type="number" name="items[{{$item->id}}][quantity]" value="{{ $item->quantity + 0 }}" min="1" class="w-16 p-1 bg-blackBase border border-muted/30 rounded text-center text-silver text-xs focus:ring-1 focus:ring-petronas">
                @else
                  <span class="text-silver ">{{ $item->quantity + 0 }}</span>
                @endif
              </td>

              <td class="px-4 py-3 text-right text-muted ">Rp {{ number_format($item->unit_price, 2, ',', '.') }}</td>
              <td class="px-4 py-3 text-right text-slate-800 font-bold ">Rp {{ number_format($item->subtotal, 2, ',', '.') }}</td>
              
              @if($purchaseOrder->status == 'draft')
              <td class="px-4 py-3 text-center text-muted text-xs italic">
                <button type="button" onclick="deleteExistingItem(this, {{ $item->id }}, {{ $item->subtotal }})" class="text-red-400 hover:text-red-600 font-bold px-2 py-1 rounded hover:bg-red-900/20 transition">✕</button>
              </td>
              @endif
            </tr>
            @endforeach
          </tbody>
          
          <tfoot class="bg-carbon/50">
            <tr>
              <td colspan="4" class="px-4 py-2 text-right text-muted text-xs uppercase">Items Subtotal</td>
              <td class="px-4 py-2 text-right text-silver text-sm">
                @php $itemsTotal = $purchaseOrder->items->sum('subtotal'); @endphp
                Rp {{ number_format($itemsTotal, 2, ',', '.') }}
              </td>
              @if($purchaseOrder->status == 'draft') <td></td> @endif
            </tr>
            <tr>
              <td colspan="4" class="px-4 py-2 text-right text-muted text-xs uppercase">Shipping Cost <span class="text-[10px] text-slate-800 ml-1">({{ $purchaseOrder->shipping_terms == 'FOB_shipping_point' ? 'Perusahaan' : 'Supplier' }})</span></td>
              <td class="px-4 py-2 text-right text-silver text-sm">
                @if($purchaseOrder->shipping_terms == 'FOB_shipping_point') 
                  Rp {{ number_format($purchaseOrder->shipping_cost, 2, ',', '.') }}
                @else 
                  <span class=" text-muted line-through">Rp {{ number_format($purchaseOrder->shipping_cost, 2, ',', '.') }}</span> <span class=" text-[10px] text-slate-800 block">Gratis Ongkir</span> 
                @endif
              </td>
              @if($purchaseOrder->status == 'draft') <td></td> @endif
            </tr>
            <tr class="border-t border-carbon">
              <td colspan="4" class="px-4 py-4 text-right text-silver font-bold uppercase text-xs">Total Amount</td>
              <td class="px-4 py-4 text-right text-slate-800 font-extrabold text-lg">Rp <span id="grandTotalDisplay">{{ number_format($purchaseOrder->grand_total, 2, ',', '.') }}</span></td>
              @if($purchaseOrder->status == 'draft') <td></td> @endif
            </tr>
          </tfoot>
        </table>
      </div>
      
      <div id="newItemsContainer"></div>

      <div class="mt-6 pt-4 border-t border-carbon flex justify-end gap-3">
        @if($purchaseOrder->status == 'draft')
          <button type="submit" name="status" value="cancelled" class="border border-danger text-danger font-bold px-6 py-2 rounded-lg hover:bg-danger hover:text-white transition" onclick="return confirm('Batalkan PO?')">Cancel PO</button>
          <button type="submit" name="status" value="draft" class="bg-yellow-50 text-yellow-600 border border-yellow-500 font-bold px-6 py-2 rounded-lg hover:bg-yellow-500 hover:text-white transition">Save Draft</button>
          <button type="submit" name="status" value="ordered" class="bg-blue-600 text-white font-bold px-6 py-2 rounded-lg hover:bg-blue-500 transition shadow-lg shadow-blue-500/20" onclick="return confirm('Konfirmasi pesanan ke Supplier?')">Place Order</button>
        @elseif($purchaseOrder->status == 'ordered')
          {{-- TOMBOL CANCEL DILINDUNGI JIKA ONGKIR SUDAH DIBAYAR --}}
          @if($purchaseOrder->is_shipping_paid)
            <button type="button" class="border border-muted text-muted font-bold px-6 py-2 rounded-lg cursor-not-allowed" title="Tidak dapat dibatalkan karena ongkir sudah dibayar Accounting">Cancel PO (Locked)</button>
          @else
            <button type="submit" name="status" value="cancelled" class="border border-danger text-danger font-bold px-6 py-2 rounded-lg hover:bg-danger hover:text-white transition" onclick="return confirm('Batalkan order?')">Cancel PO</button>
          @endif
          
          <button type="submit" name="status" value="received" class="bg-green-600 text-white font-bold px-6 py-2 rounded-lg hover:bg-green-600/90 transition shadow-lg shadow-green-600/20" onclick="return confirm('Barang sudah diterima lengkap dan masuk stok?')">Receive Goods</button>
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

    const isDestination = typeSelect.value === 'FOB_destination';

    if (isDestination) {
      costInput.value = 0;
      costInput.readOnly = true;
      costInput.classList.add('bg-blackBase/50', 'text-muted', 'cursor-not-allowed');
      costInput.classList.remove('bg-carbon', 'text-silver');
      hintText.innerText = "* Ongkir ditanggung Supplier (FOB Destination).";
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
    const priceInput = document.getElementById('priceInput'); 
    const tableBody = document.getElementById('itemsTableBody');
    const itemsContainer = document.getElementById('newItemsContainer');

    if (productSelect.value === "") { alert("Silakan pilih material."); return; }
    if (qtyInput.value <= 0) { alert("Jumlah harus lebih dari 0."); return; }
    if (priceInput.value < 0) { alert("Harga tidak boleh negatif."); return; }

    const selectedOption = productSelect.options[productSelect.selectedIndex];
    const materialId = productSelect.value;
    const materialName = selectedOption.getAttribute('data-name');
    const materialCode = selectedOption.getAttribute('data-code');
    const materialUnit = selectedOption.getAttribute('data-unit');
    
    let cost = parseFloat(priceInput.value);
    if(!cost) cost = parseFloat(selectedOption.getAttribute('data-price')) || 0;

    const qty = parseInt(qtyInput.value);

    const subtotal = cost * qty;

    const uniqueId = Date.now() + '_' + addedItemsCount; 

    const row = document.createElement('tr');
    row.id = 'row_' + uniqueId;
    row.className = 'hover:bg-carbon transition-colors bg-petronas/5'; 
    row.innerHTML = `
      <td class="px-4 py-3">
        <div class="text-silver font-bold">${materialName} <span class="text-xs text-slate-800 font-normal">(New)</span></div>
        <div class="text-xs text-muted ">${materialCode}</div>
      </td>
      <td class="px-4 py-3 text-center text-silver text-xs">${materialUnit}</td>
      
      <td class="px-4 py-3 text-center">
        <input type="number" name="new_items[${addedItemsCount}][quantity]" value="${qty}" min="1"
            class="w-16 p-1 bg-blackBase border border-muted/30 rounded text-center text-silver text-xs focus:ring-1 focus:ring-petronas">
      </td>

      <td class="px-4 py-3 text-right text-muted ">Rp ${formatRupiah(cost)}</td>
      <td class="px-4 py-3 text-right text-slate-800 font-bold ">Rp ${formatRupiah(subtotal)}</td>
      <td class="px-4 py-3 text-center text-xs text-muted">
        <button type="button" onclick="removeNewItem('${uniqueId}', ${subtotal})" class="text-red-400 hover:text-red-600 font-bold px-2 py-1">✕</button>
      </td>
    `;
    tableBody.appendChild(row);

    const inputDiv = document.createElement('div');
    inputDiv.id = 'input_' + uniqueId;
    inputDiv.innerHTML = `
      <input type="hidden" name="new_items[${addedItemsCount}][material_id]" value="${materialId}">
      <input type="hidden" name="new_items[${addedItemsCount}][cost]" value="${cost}">
    `;
    itemsContainer.appendChild(inputDiv);
    
    productSelect.value = "";
    qtyInput.value = 1;
    priceInput.value = ""; 
    addedItemsCount++;
  }

  function removeNewItem(uniqueId, subtotal) {
    const row = document.getElementById('row_' + uniqueId);
    if(row) row.remove();

    const inputDiv = document.getElementById('input_' + uniqueId);
    if(inputDiv) inputDiv.remove();
  }

  function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(angka);
  }

  document.addEventListener('DOMContentLoaded', function() {
    toggleShippingInput();
  });
</script>

</body>
</html>