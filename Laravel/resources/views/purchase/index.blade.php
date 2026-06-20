<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Purchase Orders | Production Planning System</title>
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

  {{-- Breadcrumbs --}}
  <nav aria-label="breadcrumb" class="text-xs text-muted">
    <ol class="flex items-center space-x-2">
      <li><a href="{{ route('home.index') }}" class="hover:text-blue-600 transition-colors">Home</a></li>
      <li class="opacity-40">/</li>
      <li class="text-slate-800 font-semibold">Purchase Orders</li>
    </ol>
  </nav>

  <x-alert-messages />

  {{-- Header --}}
  <header>
    <p class="text-xs uppercase tracking-widest text-muted">Procurement Data</p>
    <h1 class="text-3xl font-extrabold text-slate-800">Purchase Order Management</h1>
    <p class="text-sm text-muted mt-1">Kelola pembelian bahan baku ke supplier, penerimaan barang, dan pembayaran.</p>
  </header>

  {{-- Form Section: Create Purchase Order --}}
  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6 shadow-lg shadow-blackBase">
    <h2 class="text-lg font-bold text-slate-800">Create Purchase Order (Draft)</h2>

    <form action="{{ route('purchases.store') }}" method="POST" class="space-y-6">
      @csrf
      
      {{-- ROW 1: INFORMASI PO --}}
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <label class="text-xs text-muted uppercase tracking-wide block mb-1">Kode PO (Opsional)</label>
          <input type="text" name="po_number" placeholder="(Auto Generated)"
            class="w-full mt-1 px-4 py-3 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas placeholder-muted/30 transition">
        </div>

        <div>
          <label class="text-xs text-muted uppercase tracking-wide block mb-1">Tanggal Order</label>
          <input type="date" name="order_date" value="{{ date('Y-m-d') }}" required
            class="w-full mt-1 px-4 py-3 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas appearance-none transition">
        </div>

        <div>
          <label class="text-xs text-muted uppercase tracking-wide block mb-1">Jatuh Tempo</label>
          <input type="date" name="due_date" 
            class="w-full mt-1 px-4 py-3 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas appearance-none transition">
        </div>
      </div>

      {{-- ROW 2: DATA SUPPLIER (DALAM 1 KOTAK) --}}
      <div class="bg-carbon/40 p-5 rounded-xl border border-carbon space-y-4">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">
            Data Supplier
          </h3>
        </div>

        {{-- Partner Select --}}
        <div>
          <label class="text-xs text-muted uppercase tracking-wide mb-1 block">Pilih Supplier</label>
          <div class="relative">
            <select name="partner_id" id="partnerSelect" onchange="fillPartnerDetails()" required
              class="w-full px-4 py-3 rounded-lg bg-carbon border border-petronas/30 text-silver focus:outline-none focus:border-petronas focus:ring-1 focus:ring-petronas appearance-none cursor-pointer transition">
              <option value="" disabled selected>-- Klik untuk memilih --</option>
              @foreach($partners as $partner)
                <option value="{{ $partner->id }}" 
                  data-company="{{ $partner->company_name }}"
                  data-person="{{ $partner->person_name }}"
                  data-phone="{{ $partner->phone }}"
                  data-email="{{ $partner->email }}"
                  data-address="{{ $partner->address }}">
                  {{ $partner->company_name }}
                </option>
              @endforeach
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-800">
              <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
            </div>
          </div>
        </div>

        {{-- Hidden Snapshot Input --}}
        <input type="hidden" name="company_name" id="snap_company">

        {{-- Readonly Details --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
          <div>
            <label class="text-[10px] text-muted uppercase block mb-1">Contact Person</label>
            <input type="text" name="person_name" id="snap_person" readonly
              class="w-full px-3 py-3 rounded bg-blackBase/50 border border-carbon text-muted text-sm focus:outline-none cursor-not-allowed">
          </div>
          <div>
            <label class="text-[10px] text-muted uppercase block mb-1">Phone</label>
            <input type="text" name="phone" id="snap_phone" readonly
              class="w-full px-3 py-3 rounded bg-blackBase/50 border border-carbon text-muted text-sm focus:outline-none cursor-not-allowed">
          </div>
          <div>
            <label class="text-[10px] text-muted uppercase block mb-1">Email</label>
            <input type="email" name="email" id="snap_email" readonly
              class="w-full px-3 py-3 rounded bg-blackBase/50 border border-carbon text-muted text-sm focus:outline-none cursor-not-allowed">
          </div>
          <div class="md:col-span-3">
            <label class="text-[10px] text-muted uppercase block mb-1">Alamat Lengkap</label>
            <input type="text" name="address" id="snap_address" readonly
              class="w-full px-3 py-3 rounded bg-blackBase/50 border border-carbon text-muted text-sm focus:outline-none cursor-not-allowed">
          </div>
        </div>
      </div>

      {{-- Action Buttons --}}
      <div class="flex justify-end pt-2">
        <button type="submit" class="bg-petronas text-blackBase font-bold px-6 py-2 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20">
          Create Purchase Order
        </button>
      </div>
    </form>
  </section>

  {{-- List Purchase Orders --}}
  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
      <h2 class="text-lg font-bold text-slate-800">Purchase Order List</h2>
      
      <!-- <form action="{{ route('purchases.index') }}" method="GET" class="flex gap-2 w-full md:w-auto">
        <input type="text" name="search" placeholder="Search PO No. or Supplier..." value="{{ request('search') }}"
          class="w-full md:w-64 px-4 py-3 bg-carbon rounded-lg text-xs text-silver focus:outline-none border border-transparent focus:border-petronas">
        <button type="submit" class="px-4 py-3 bg-carbon border border-muted text-xs rounded-lg hover:text-blue-600 transition">Search</button>
      </form> -->
    </div>

    {{-- FRONTEND TABS FILTER (Direvisi Menjadi Ordered) --}}
    <div class="flex space-x-6 border-b border-carbon/50 mb-6 overflow-x-auto" id="frontendTabs">
      <button type="button" onclick="filterTable('all', this)" class="tab-btn pb-3 text-sm font-bold border-b-2 whitespace-nowrap transition-colors border-petronas text-petronas">
        All Orders
      </button>
      <button type="button" onclick="filterTable('draft', this)" class="tab-btn pb-3 text-sm font-bold border-b-2 whitespace-nowrap transition-colors border-transparent text-muted hover:text-silver">
        Draft
      </button>
      <button type="button" onclick="filterTable('ordered', this)" class="tab-btn pb-3 text-sm font-bold border-b-2 whitespace-nowrap transition-colors border-transparent text-muted hover:text-silver">
        Ordered
      </button>
      <button type="button" onclick="filterTable('received', this)" class="tab-btn pb-3 text-sm font-bold border-b-2 whitespace-nowrap transition-colors border-transparent text-muted hover:text-silver">
        Received
      </button>
      <button type="button" onclick="filterTable('cancelled', this)" class="tab-btn pb-3 text-sm font-bold border-b-2 whitespace-nowrap transition-colors border-transparent text-muted hover:text-silver">
        Cancelled
      </button>
    </div>

    <div class="overflow-x-auto rounded-lg border border-carbon">
      <table class="w-full text-sm">
        <thead class="bg-carbon">
          <tr>
            <th class="px-4 py-3 text-left text-black text-xs uppercase tracking-wide border-b border-carbonSoft">Tanggal Order</th>
            <th class="px-4 py-3 text-left text-black text-xs uppercase tracking-wide border-b border-carbonSoft">Kode PO</th>
            <th class="px-4 py-3 text-left text-black text-xs uppercase tracking-wide border-b border-carbonSoft">Nama Supplier</th>
            <th class="px-4 py-3 text-center text-black text-xs uppercase tracking-wide border-b border-carbonSoft">Order Status</th>
            <th class="px-4 py-3 text-center text-black text-xs uppercase tracking-wide border-b border-carbonSoft">Payment</th>
            <th class="px-4 py-3 text-right text-black text-xs uppercase tracking-wide border-b border-carbonSoft">Total</th>
            <th class="px-4 py-3 text-right text-black text-xs uppercase tracking-wide border-b border-carbonSoft">Sisa Hutang</th>
            <th class="px-4 py-3 text-center text-black text-xs uppercase tracking-wide border-b border-carbonSoft">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-carbon/50" id="tableBody">
          @forelse ($purchaseOrders as $po)
            <tr class="hover:bg-carbon transition-colors group filterable-row" data-status="{{ strtolower($po->status) }}">
              
              {{-- Date --}}
              <td class="px-4 py-3 text-silver text-xs whitespace-nowrap">
                {{ \Carbon\Carbon::parse($po->order_date)->format('d/m/Y') }}
              </td>
              
              {{-- PO No --}}
              <td class="px-4 py-3 font-semibold text-slate-800 text-xs whitespace-nowrap">
                {{ $po->po_number }}
              </td>
              
              {{-- Supplier --}}
              <td class="px-4 py-3 text-silver whitespace-nowrap">
                {{ $po->company_name ?? '-' }}
              </td>
              
              {{-- Status Badge (Dikembalikan ke status aslinya) --}}
              <td class="px-4 py-3 text-center whitespace-nowrap">
                @php
                  $statusColor = match($po->status) {
                    'draft'   => 'bg-gray-800 text-gray-400 border-gray-600',
                    'ordered'  => 'bg-blue-900/30 text-blue-800 border-blue-800', 
                    'received' => 'bg-blue-200 text-blue-800 border-petronas',
                    'cancelled' => 'bg-red-900/30 text-red-400 border-red-800',
                    default   => 'bg-carbon text-slate-800'
                  };
                @endphp
                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase border {{ $statusColor }}">
                  {{ $po->status }}
                </span>
              </td>

              {{-- Payment Badge --}}
              <td class="px-4 py-3 text-center whitespace-nowrap">
                @php
                  $payColor = match($po->payment_status) {
                    'paid'  => 'text-success bg-success/10 border-success/30',
                    'partial' => 'text-warning bg-warning/10 border-warning/30',
                    'unpaid' => 'text-danger bg-danger/10 border-danger/30',
                    default  => 'text-muted'
                  };
                @endphp
                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase border {{ $payColor }}">
                  {{ $po->payment_status }}
                </span>
              </td>

              {{-- Grand Total --}}
              <td class="px-4 py-3 text-right font-bold text-silver whitespace-nowrap">
                Rp {{ number_format($po->grand_total, 2, ',', '.') }}
              </td>

              {{-- Balance (Sisa Hutang) --}}
              <td class="px-4 py-3 text-right text-xs {{ $po->remaining_balance > 0 ? 'text-red-400' : 'text-muted' }} whitespace-nowrap">
                Rp {{ number_format($po->remaining_balance, 2, ',', '.') }}
              </td>

              {{-- Action --}}
              <td class="px-4 py-3 text-center whitespace-nowrap">
                <div class="flex justify-center items-center gap-2">
                  <a href="{{ route('purchases.show', $po->id) }}" 
                    class="inline-flex items-center justify-center w-8 h-8 rounded bg-petronas text-white hover:bg-blue-700 transition"
                    title="View Details">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                  </a>

                  <a href="{{ route('purchases.showPayments', $po->id) }}" 
                    class="inline-flex items-center justify-center w-8 h-8 rounded bg-green-500 text-white hover:bg-green-600 transition"
                    title="Payment History">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            {{-- Dikosongkan agar ditangani oleh logika Empty Row di bawah --}}
          @endforelse
          
          <tr id="emptyRow" style="{{ count($purchaseOrders) === 0 ? '' : 'display: none;' }}">
            <td colspan="8" class="text-center text-muted py-8 italic">
              No purchase orders found for this status.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <div class="mt-4">
      {{ $purchaseOrders->appends(request()->query())->links('pagination::tailwind') }}
    </div>
  </section>

</main>

<script>
  function fillPartnerDetails() {
    const select = document.getElementById('partnerSelect');
    const selectedOption = select.options[select.selectedIndex];
    
    const company = selectedOption.getAttribute('data-company') || '';
    const person = selectedOption.getAttribute('data-person') || '';
    const phone  = selectedOption.getAttribute('data-phone') || '';
    const email  = selectedOption.getAttribute('data-email') || '';
    const address = selectedOption.getAttribute('data-address') || '';

    // Isi Snapshot Hidden Fields
    document.getElementById('snap_company').value = company;
    
    // Isi Form Readonly
    document.getElementById('snap_person').value = person;
    document.getElementById('snap_phone').value  = phone;
    document.getElementById('snap_email').value  = email;
    document.getElementById('snap_address').value = address;
  }

  // Logika Filter Frontend
  function filterTable(status, btn) {
    const tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach(tab => {
      tab.classList.remove('border-petronas', 'text-petronas');
      tab.classList.add('border-transparent', 'text-muted');
    });

    btn.classList.remove('border-transparent', 'text-muted');
    btn.classList.add('border-petronas', 'text-petronas');

    const rows = document.querySelectorAll('.filterable-row');
    let visibleCount = 0;

    rows.forEach(row => {
      if (status === 'all' || row.dataset.status === status) {
        row.style.display = ''; 
        visibleCount++;
      } else {
        row.style.display = 'none'; 
      }
    });

    const emptyRow = document.getElementById('emptyRow');
    if (emptyRow) {
      if (visibleCount === 0) {
        emptyRow.style.display = '';
      } else {
        emptyRow.style.display = 'none';
      }
    }
  }
</script>

</body>
</html>