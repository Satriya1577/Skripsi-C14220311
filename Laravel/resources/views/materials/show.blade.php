<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Material Detail | Production Planning System</title>
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
            warning: '#F59E0B',
            success: '#10B981',
            danger: '#EF4444'
          }
        }
      }
    }
  </script>
  <style>
    /* Chrome, Safari, Edge, Opera */
    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }
    /* Firefox */
    input[type=number] {
      -moz-appearance: textfield;
    }
  </style>
</head>

<body class="bg-blackBase text-silver min-h-screen">

<main class="max-w-7xl mx-auto px-6 py-6 space-y-8">

  @php
    $factor = $material->conversion_factor > 0 ? $material->conversion_factor : 1;
    $pUnit = $material->purchase_unit;
    $bUnit = $material->unit;
  @endphp

  <nav aria-label="breadcrumb" class="text-xs text-muted">
    <ol class="flex items-center space-x-2">
      <li><a href="{{ route('home.index') }}" class="hover:text-blue-600 transition-colors">Home</a></li>
      <li class="opacity-40">/</li>
      <li><a href="{{ route('materials.index') }}" class="hover:text-blue-600 transition-colors">Materials</a></li>
      <li class="opacity-40">/</li>
      <li class="text-slate-800 font-semibold">Detail</li>
    </ol>
  </nav>

  <x-alert-messages />

  <header>
    <p class="text-xs uppercase tracking-widest text-muted">Inventory Control</p>
    
    <div class="flex items-center gap-4 mt-1">
      <h1 class="text-3xl font-extrabold text-slate-800">{{ $material->name }}</h1>
      
      <span class="px-3 py-1 rounded-full text-xs font-bold uppercase border
      bg-success/10
        {{ $material->is_active ? 'bg-green-900/10 text-green-400 border-green-500/10' : 'bg-red-900/30 text-red-400 border-red-500/30' }}">
        {{ $material->is_active ? 'Active' : 'Non-Active' }}
      </span>
    </div>

    <p class="text-sm text-muted mt-2">
      <span class="bg-carbon px-2 py-1 rounded text-xs mr-2 border border-carbonSoft">{{ $material->code }}</span>
      Konversi Sistem: 1 {{ $pUnit }} = {{ number_format($factor) }} {{ $bUnit }}
    </p>
  </header>

  {{-- SECTION 1: INFORMASI MATERIAL --}}
  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-lg font-bold text-slate-800">Informasi Material (Satuan Beli)</h2>
      <div class="text-xs text-slate-800 bg-carbon px-3 py-1 rounded-full border border-carbon/50">
        Kategori: <span class="text-silver font-bold uppercase">{{ $material->category_type }}</span>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
      <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
        <p class="text-xs text-muted uppercase tracking-wide mb-1">Stok Fisik ({{ $pUnit }})</p>
        <p class=" text-2xl font-bold text-silver">{{ number_format($material->current_stock / $factor, 0, '', '') }}</p>
      </div>
      
      <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
        <p class="text-xs text-muted uppercase tracking-wide mb-1">Incoming Order</p>
        <p class=" text-2xl font-bold text-silver">{{ number_format($material->ordered_stock / $factor, 0, '', '') }} <span class=" text-xs text-muted font-normal">{{ $pUnit }}</span></p>
      </div>

      <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
        <p class="text-xs text-muted uppercase tracking-wide mb-1">Safety Stock</p>
        <p class=" text-2xl font-bold text-silver">{{ number_format($material->safety_stock / $factor, 0, '', '') }} <span class=" text-xs text-muted font-normal">{{ $pUnit }}</span></p>
      </div>

      {{-- REVISI NOMOR 2: HAPUS ROP --}}
      {{-- <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
        <p class="text-xs text-muted uppercase tracking-wide mb-1">Safety Stock</p>
        <div class="flex items-end gap-1">
          <p class=" text-lg font-bold text-silver">{{ number_format($material->safety_stock / $factor, 0, '', '') }}</p>
          <span class="text-xs text-muted mb-1">{{ $pUnit }}</span>
        </div>
        <p class="text-[10px] text-muted border-t border-white/10 mt-1 pt-1">
          ROP: {{ number_format($material->reorder_point / $factor, 0, '', '') }} {{ $pUnit }}
        </p>
      </div> --}}

      <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
        <p class="text-xs text-muted uppercase tracking-wide mb-1">Estimasi Harga / {{ $pUnit }}</p>
        <p class=" text-2xl font-bold text-slate-800">Rp {{ number_format($material->price_per_unit * $factor, 2, ',', '.') }}</p>
      </div>
    </div>

    {{-- LEAD TIME INFO --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
        <div class="flex justify-between items-start mb-2">
          <p class="text-xs text-muted uppercase tracking-wide">Lead Time Management</p>
          <span class="text-[10px] uppercase px-2 py-0.5 rounded border 
            {{ $material->is_manual_lead_time === 'manual' ? 'bg-gray-800 text-gray-300 border-gray-600' : 'bg-blue-100 text-blue-700 border-petronas/30' }}">
            {{ $material->is_manual_lead_time === 'manual' ? 'Manual' : 'Automatic' }}
          </span>
        </div>
        <div class="flex gap-4 items-center">
          <div>
            <span class="text-xs text-muted block">Range (Min-Max)</span>
            <span class="text-lg font-bold text-silver">{{ $material->min_lead_time_days }} - {{ $material->max_lead_time_days }} Hari</span>
          </div>
          <div class="h-8 w-px bg-white/10"></div>
          <div>
            <span class="text-xs text-muted block">Rata-rata Aktual</span>
            <span class=" text-lg font-bold text-slate-800">{{ number_format($material->lead_time_average, 1) }} Hari</span>
          </div>
        </div>
      </div>

      <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
        <p class="text-xs text-muted uppercase tracking-wide mb-1">Packaging Detail</p>
        <p class="text-sm text-silver">
          <span class="font-bold">1 {{ $pUnit }}</span> berisi 
          <span class="font-bold text-slate-800">{{ (float)$material->packaging_size }} {{ $material->packaging_unit }}</span>
        </p>
        <p class="text-[10px] text-muted mt-2">
          *Sistem mengkonversi {{ $material->packaging_unit }} menjadi {{ $material->unit }} dengan faktor {{ number_format($factor / ($material->packaging_size ?: 1)) }}x
        </p>
      </div>
    </div>
  </section>

  {{-- SECTION 2: ADJUSTMENTS (TABBED SYSTEM) --}}
  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-4">
    
    {{-- NAVIGATION TABS --}}
    <div class="flex border-b border-carbon/50 gap-6">
      <button onclick="switchTab('stock')" id="tab-btn-stock" class="pb-3 text-sm font-bold border-b-2 border-petronas text-petronas hover:text-petronas transition-colors">
        Stock Adjustment (Opname)
      </button>
      <button onclick="switchTab('cost')" id="tab-btn-cost" class="pb-3 text-sm font-bold border-b-2 border-transparent text-muted hover:text-silver transition-colors">
        Cost Adjustment (Update HPP)
      </button>
    </div>

    @if($material->is_active)
      {{-- CONTENT TAB 1: STOCK ADJUSTMENT --}}
      <div id="tab-content-stock" class="block pt-2">
        <form action="{{ route('materials.stockAdjustment') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
          @csrf
          <input type="hidden" name="material_id" value="{{ $material->id }}">

          <div>
            <label class="text-xs text-muted uppercase tracking-wide">Jumlah Stok Aktual ({{ $pUnit }})</label>
            <div class="relative mt-1">
              <input type="number" step="0.01" name="actual_qty" value="{{ old('actual_qty') }}" required
                class="w-full pl-4 pr-12 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none text-silver font-bold">
              <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <span class="text-muted text-xs uppercase">{{ $pUnit }}</span>
              </div>
            </div>
            <p class="text-xs text-muted mt-1">Stok sistem saat ini: <strong>{{ number_format($material->current_stock / $factor, 2) }} {{ $pUnit }}</strong></p>
          </div>

          <div>
            <label class="text-xs text-muted uppercase tracking-wide">Catatan Opname</label>
            <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Alasan penyesuaian kuantitas fisik..." required
              class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none text-silver">
          </div>

          <div class="md:col-span-2 flex justify-end">
            <button type="submit" class="bg-yellow-600 text-white font-bold px-6 py-2 rounded-lg hover:bg-yellow-700 transition shadow-lg shadow-yellow-900/20">
              Simpan Adjustment Stok
            </button>
          </div>
        </form>
      </div>

      {{-- CONTENT TAB 2: COST ADJUSTMENT --}}
      <div id="tab-content-cost" class="hidden pt-2">
        <form action="{{ route('materials.costAdjustment') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
          @csrf
          <input type="hidden" name="material_id" value="{{ $material->id }}">

          <div>
            <label class="text-xs text-muted uppercase tracking-wide">Estimasi Harga Baru per {{ $pUnit }}</label>
            <div class="relative mt-1">
              <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <span class="text-muted text-xs font-bold">Rp</span>
              </div>
              <input type="number" step="0.01" name="new_price" value="{{ $material->price_per_unit * $factor }}" required
                class="w-full pl-10 pr-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-blue-500 focus:outline-none text-silver font-bold">
            </div>
            <p class="text-xs text-muted mt-1">Harga saat ini: Rp {{ number_format($material->price_per_unit * $factor, 2, ',', '.') }}</p>
          </div>

          <div>
            <label class="text-xs text-muted uppercase tracking-wide">Alasan Penyesuaian Harga</label>
            <input type="text" name="reason" value="{{ old('reason') }}" placeholder="Contoh: Kenaikan harga kontrak vendor supplier..." required
              class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-blue-500 focus:outline-none text-silver">
          </div>

          <div class="md:col-span-2 flex justify-end">
            <button type="submit" class="bg-blue-600 text-white font-bold px-6 py-2 rounded-lg hover:bg-blue-700 transition shadow-lg shadow-blue-900/20">
              Simpan Update HPP
            </button>
          </div>
        </form>
      </div>
    @else
       <div class="p-4 bg-carbon rounded-lg border border-red-900/30 flex items-center gap-3">
        <span class="text-danger text-xl">🔒</span>
        <p class="text-sm text-muted">Panel adjustment dikunci karena material ini berstatus <strong>Non-Active</strong>.</p>
      </div>
    @endif
  </section>

  {{-- SECTION 3: RIWAYAT TRANSAKSI & VALUASI --}}
  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-bold text-slate-800">Riwayat Transaksi & Valuasi</h2>
      <div class="flex gap-2">
        <span class="text-xs bg-carbon px-3 py-1.5 rounded-lg text-slate-800 border border-carbon">
          Satuan: <strong>{{ $pUnit }}</strong>
        </span>
      </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-carbon">
      <table class="w-full text-sm">
        <thead class="bg-carbon text-xs uppercase tracking-wide">
          <tr>
            <th class="px-4 py-3 text-left text-black border-b border-carbonSoft">Tanggal</th>
            <th class="px-4 py-3 text-center text-black border-b border-carbonSoft">Tipe</th>
            <th class="px-4 py-3 text-left text-black border-b border-carbonSoft">Ref. & Keterangan</th>
            <th class="px-4 py-3 text-right text-black border-b border-carbonSoft">Masuk</th>
            <th class="px-4 py-3 text-right text-black border-b border-carbonSoft">Keluar</th>
            <th class="px-4 py-3 text-right text-black font-bold border-b border-carbonSoft bg-carbon/50">Saldo Fisik</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-carbon/50">
          @forelse($transactions as $trx)
            <tr class="hover:bg-carbon transition-colors group">
              {{-- KOLOM 1: TANGGAL & WAKTU --}}
              <td class="px-4 py-3 text-silver text-xs whitespace-nowrap">
                {{ $trx->transaction_date?->format('d/m/Y') ?? '-' }}
                <span class="block text-[10px] text-muted">
                  {{ $trx->created_at->format('H:i') }} WIB
                </span>
              </td>

              {{-- KOLOM 2: TIPE BADGES --}}
              <td class="px-4 py-3 text-center">
                @if($trx->type == 'in')
                  <span class="px-2 py-1 rounded text-[10px] font-bold uppercase text-green-600 bg-success/10 border border-success/30">
                    PURCHASE IN
                  </span>
                @elseif($trx->type == 'out')
                  <span class="px-2 py-1 rounded text-[10px] font-bold uppercase text-danger bg-danger/10 border border-danger/30">
                    USAGE OUT
                  </span>
                @elseif($trx->type == 'adjustment')
                  @if($trx->qty >= 0)
                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase text-blue-500 bg-blue-500/10 border border-blue-500/30">
                      STOCK ADJ IN
                    </span>
                  @else
                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase text-orange-500 bg-orange-500/10 border border-orange-500/30">
                      STOCK ADJ OUT
                    </span>
                  @endif
                @elseif($trx->type == 'cost_adjustment')
                  <span class="px-2 py-1 rounded text-[10px] font-bold uppercase text-indigo-500 bg-indigo-500/10 border border-indigo-500/30" title="Revaluasi Nilai Harga Material">
                    COST ADJ
                  </span>
                @endif
              </td>

              {{-- KOLOM 3: REFERENSI, KETERANGAN & HARGA --}}
              <td class="px-4 py-3">
                <div class="text-slate-800 text-sm font-bold">
                  @if($trx->purchase_order_id)
                    PO #{{ $trx->purchase_order_id }}
                  @elseif($trx->production_realization_id)
                    Pemakaian Produksi
                  @else
                    Manual / Opname System
                  @endif
                </div>
                <div class="text-xs text-muted mt-0.5 truncate max-w-xs md:max-w-md" title="{{ $trx->description }}">
                  {{ $trx->description ?? '-' }}
                </div>
              </td>

              {{-- KOLOM 4: MASUK (+) --}}
              <td class="px-4 py-3 text-right text-success font-semibold">
                @if($trx->type != 'cost_adjustment' && $trx->qty > 0)
                  +{{ number_format($trx->qty / $factor, 0, '', '') }}
                @else
                  <span class="text-carbonSoft">-</span>
                @endif
              </td>

              {{-- KOLOM 5: KELUAR (-) --}}
              <td class="px-4 py-3 text-right text-danger font-semibold"> 
                @if($trx->type != 'cost_adjustment' && $trx->qty < 0)
                  {{ number_format(abs($trx->qty) / $factor, 0, '', '') }} 
                @else
                  <span class="text-carbonSoft">-</span>
                @endif
              </td>

              {{-- KOLOM 6: SALDO FISIK AKHIR (HIGHLIGHTED) --}}
              <td class="px-4 py-3 text-right font-bold text-slate-800 bg-slate-100">
                {{ number_format($trx->current_stock_balance / $factor, 0, '', '') }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-6 py-12 text-center text-muted italic bg-slate-100 rounded-b-lg">
                <div class="flex flex-col items-center justify-center gap-2">
                  <span class="text-2xl">📦</span>
                  <p>Belum ada rekaman pergerakan stok maupun harga untuk material ini.</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- PAGINATION LINK AREA --}}
    @if ($transactions->hasPages())
      <div class="mt-4">
        {{ $transactions->links('pagination::tailwind') }}
      </div>
    @endif
  </section>

</main>

<script>
  // Control Material Unit Label Input
  document.addEventListener('DOMContentLoaded', function() {
    const materialSelect = document.getElementById('materialSelect');
    const unitLabel = document.getElementById('unitLabel');

    function updateUnitLabel() {
      if(!materialSelect) return;
      const selectedOption = materialSelect.options[materialSelect.selectedIndex];
      const unit = selectedOption.getAttribute('data-unit');
      unitLabel.innerText = unit ? unit : 'Qty';
    }

    if(materialSelect) {
      materialSelect.addEventListener('change', updateUnitLabel);
    }
  });

  // Control Tab Navigation Adjustments
  function switchTab(tab) {
    const btnStock = document.getElementById('tab-btn-stock');
    const btnCost = document.getElementById('tab-btn-cost');
    const contentStock = document.getElementById('tab-content-stock');
    const contentCost = document.getElementById('tab-content-cost');

    if (tab === 'stock') {
      btnStock.classList.replace('border-transparent', 'border-petronas');
      btnStock.classList.replace('text-muted', 'text-petronas');
      
      btnCost.classList.replace('border-petronas', 'border-transparent');
      btnCost.classList.replace('text-petronas', 'text-muted');

      contentStock.classList.remove('hidden');
      contentCost.classList.add('hidden');
    } else {
      btnCost.classList.replace('border-transparent', 'border-petronas');
      btnCost.classList.replace('text-muted', 'text-petronas');

      btnStock.classList.replace('border-petronas', 'border-transparent');
      btnStock.classList.replace('text-petronas', 'text-muted');

      contentCost.classList.remove('hidden');
      contentStock.classList.add('hidden');
    }
  }
</script>

</body>
</html>