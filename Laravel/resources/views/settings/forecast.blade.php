<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Settings | Production Planning System</title>
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
            danger: '#EF4444' 
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
      <li><a href="{{ route('home.index') }}" class="hover:text-blue-600 transition-colors">Home</a></li>
      <li class="opacity-40">/</li>
      <li><a href="{{ route('settings.index') }}" class="hover:text-blue-600 transition-colors">Settings</a></li>
      <li class="opacity-40">/</li>
      <li class="text-slate-800 font-semibold" aria-current="page">Model Configuration</li>
    </ol>
  </nav>

  @if (session('success'))
    <div class="bg-carbonSoft border border-petronas rounded-xl p-4 flex justify-between items-center">
      <p class="text-sm text-slate-800 font-semibold">{{ session('success') }}</p>
      <button onclick="this.parentElement.remove()" class="text-muted hover:text-blue-600 transition">✕</button>
    </div>
  @endif

  <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <p class="text-xs uppercase tracking-widest text-muted">System Configuration</p>
      <h1 class="text-3xl font-extrabold text-slate-800">Model Configuration</h1>
      <p class="text-sm text-muted mt-1">Atur parameter SARIMA dan pantau akurasi model per produk</p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
      
      {{-- TOMBOL HAPUS SEMUA DATA (HANYA UNTUK C14220311) --}}
      @if(Auth::check() && str_contains(Auth::user()->email, 'c14220311'))
        <form action="{{ route('settings.clearEvaluations') }}" method="POST">
          @csrf
          @method('DELETE')
          <button type="submit" onclick="return confirm('PERINGATAN: Apakah Anda yakin ingin menghapus SELURUH record evaluasi?')" 
                  class="group flex justify-center items-center gap-2 px-5 py-3 rounded-xl bg-red-100 border border-red-300 text-red-600 font-bold hover:bg-red-600 hover:text-white transition-all shadow-lg hover:shadow-red-500/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Clear Data
          </button>
        </form>
      @endif

    {{-- TOMBOL AUTO TUNE & CANCEL --}}
    @if(isset($isGridSearchRunning) && $isGridSearchRunning)
      <div class="flex items-center gap-2">
        {{-- Tombol disabled indikator processing --}}
        <button disabled class="flex justify-center items-center gap-2 px-5 py-3 rounded-xl bg-carbon border border-petronas/30 text-white font-bold opacity-75 cursor-not-allowed shadow-lg">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>Processing...</span>
        </button>
        
        {{-- Tombol Cancel --}}
        <form action="{{ route('settings.cancelGridSearch') }}" method="POST">
          @csrf
          <button type="submit" onclick="return confirm('PERINGATAN: Yakin ingin membatalkan dan menghapus antrean Grid Search?')" 
                  class="flex justify-center items-center gap-2 px-4 py-3 rounded-xl bg-danger/10 border border-danger text-danger font-bold hover:bg-danger hover:text-white transition-all shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
            </svg>
          </button>
        </form>
      </div>
    @else
      {{-- Tombol Normal --}}
      <form id="grid-all-form" action="{{ route('settings.gridSearchAll') }}" method="POST">
        @csrf
        <button type="submit" id="btn-tune-all"
          onclick="return confirm('PERINGATAN: Proses ini akan memakan waktu lama. Lanjutkan?')"
          class="group min-w-[200px] flex justify-center items-center gap-2 px-5 py-3 rounded-xl bg-carbonSoft border border-petronas/30 text-slate-800 font-bold hover:bg-petronas hover:text-white transition-all shadow-lg hover:shadow-petronas/20">
          <div id="btn-text-normal" class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:animate-pulse" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
            </svg>
            <span>Auto-tune All Products</span>
          </div>
          <div id="btn-text-loading" class="hidden text-white">
            <span>Initializing...</span>
          </div>
        </button>
      </form>
    @endif
    </div>
  </header>

  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon shadow-sm">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4 gap-4">
      <h2 class="text-lg font-bold text-slate-800">SARIMA Parameters & Performance</h2>
      <div class="text-xs text-slate-800 flex gap-4 bg-carbon px-3 py-1.5 rounded-lg border border-carbon">
        <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-petronas"></span> Non-Seasonal</span>
        <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-silver"></span> Seasonal</span>
      </div>
    </div>

    {{-- ========================================================== --}}
    {{-- KONDISI 1: TABEL EVALUASI KHUSUS (c14220311@john.petra.ac.id) --}}
    {{-- ========================================================== --}}
    @isset($sarimaProductEvaluations)
    
    {{-- Fungsi Bantuan Inline untuk Memformat Angka Miliar ke Scientific --}}
    @php
      if (!function_exists('formatLargeNumber')) {
          function formatLargeNumber($value, $isPercentage = false) {
              if ($value === null) return '-';
              
              $floatVal = (float)$value;
              // Jika angka >= 1 Miliar atau <= -1 Miliar, gunakan format Scientific (e+)
              if ($floatVal >= 1000000000 || $floatVal <= -1000000000) {
                  $formatted = sprintf('%.2e', $floatVal);
              } else {
                  $formatted = number_format($floatVal, 2);
              }
              
              return $isPercentage ? $formatted . '%' : $formatted;
          }
      }
    @endphp

    <div class="overflow-x-auto pb-4">
      <table class="w-full text-sm border-collapse">
        <thead class="bg-carbon text-xs uppercase tracking-wide">
          <tr>
            <th rowspan="2" class="px-3 py-2 text-center text-black align-middle border-b border-carbonSoft">No</th>
            <th rowspan="2" class="px-3 py-2 text-left text-black align-middle border-b border-carbonSoft">Product Info</th>
            <th colspan="7" class="px-1 py-2 text-center text-black border-b border-carbonSoft border-l border-carbon">Params (RAW)</th>
            <th colspan="2" class="px-2 py-2 text-center text-black border-b border-carbonSoft border-l border-carbon">Raw Data</th>
            <th colspan="2" class="px-2 py-2 text-center text-black border-b border-carbonSoft border-l border-carbon">Moving Avg</th>
            <th colspan="2" class="px-2 py-2 text-center text-black border-b border-carbonSoft border-l border-carbon">Savitzky-Golay</th>
            <th colspan="2" class="px-2 py-2 text-center text-black border-b border-carbonSoft border-l border-carbon">Box-Cox</th>
            <th colspan="2" class="px-2 py-2 text-center text-black border-b border-carbonSoft border-l border-carbon">Yeo-Johnson</th>
            {{-- KOLOM AKSI (HANYA UNTUK C14220311) --}}
            @if(Auth::check() && str_contains(Auth::user()->email, 'c14220311'))
              <th rowspan="2" class="px-2 py-2 text-center text-black align-middle border-b border-carbonSoft border-l border-carbon">Aksi</th>
            @endif
          </tr>
          <tr>
            <th class="px-1 py-2 text-center font-bold text-black border-l border-carbon">p</th>
            <th class="px-1 py-2 text-center font-bold text-black">d</th>
            <th class="px-1 py-2 text-center font-bold text-black">q</th>
            <th class="px-1 py-2 text-center font-bold text-black border-l border-carbon/50">P</th>
            <th class="px-1 py-2 text-center font-bold text-black">D</th>
            <th class="px-1 py-2 text-center font-bold text-black">Q</th>
            <th class="px-1 py-2 text-center font-bold text-black">s</th>
            <th class="px-2 py-2 text-right text-black border-l border-carbon">RMSE</th>
            <th class="px-2 py-2 text-right text-black">MAPE</th>
            <th class="px-2 py-2 text-right text-black border-l border-carbon">RMSE</th>
            <th class="px-2 py-2 text-right text-black">MAPE</th>
            <th class="px-2 py-2 text-right text-black border-l border-carbon">RMSE</th>
            <th class="px-2 py-2 text-right text-black">MAPE</th>
            <th class="px-2 py-2 text-right text-black border-l border-carbon">RMSE</th>
            <th class="px-2 py-2 text-right text-black">MAPE</th>
            <th class="px-2 py-2 text-right text-black border-l border-carbon">RMSE</th>
            <th class="px-2 py-2 text-right text-black">MAPE</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-carbon/50">
          @forelse($sarimaProductEvaluations as $eval)
            
            {{-- BLOK PENCARIAN RMSE TERKECIL --}}
            @php
              $metrics = [
                  'raw' => ['rmse' => $eval->raw_rmse !== null ? (float)$eval->raw_rmse : null, 'mape' => $eval->raw_mape !== null ? (float)$eval->raw_mape : null],
                  'ma'  => ['rmse' => $eval->ma_rmse !== null ? (float)$eval->ma_rmse : null, 'mape' => $eval->ma_mape !== null ? (float)$eval->ma_mape : null],
                  'sg'  => ['rmse' => $eval->sg_rmse !== null ? (float)$eval->sg_rmse : null, 'mape' => $eval->sg_mape !== null ? (float)$eval->sg_mape : null],
                  'bc'  => ['rmse' => $eval->bc_rmse !== null ? (float)$eval->bc_rmse : null, 'mape' => $eval->bc_mape !== null ? (float)$eval->bc_mape : null],
                  'yj'  => ['rmse' => $eval->yj_rmse !== null ? (float)$eval->yj_rmse : null, 'mape' => $eval->yj_mape !== null ? (float)$eval->yj_mape : null],
              ];

              // Buang yang nilainya null (jika ada error saat training)
              $validMetrics = array_filter($metrics, function($item) {
                  return $item['rmse'] !== null && $item['mape'] !== null;
              });

              $bestMethod = null;
              if (!empty($validMetrics)) {
                  // Sort menggunakan usort: Jika RMSE sama, cek MAPE
                  uasort($validMetrics, function($a, $b) {
                      if ($a['rmse'] === $b['rmse']) {
                          return $a['mape'] <=> $b['mape']; // Tie-breaker: MAPE terkecil
                      }
                      return $a['rmse'] <=> $b['rmse']; // Utama: RMSE terkecil
                  });
                  
                  // Ambil nama metode yang menang (raw, ma, sg, bc, atau yj)
                  $bestMethod = array_key_first($validMetrics);
              }

              // Variabel class CSS untuk Highlight Hijau
              $hl = 'bg-green-100 text-green-800 font-bold';
            @endphp

            <tr class="hover:bg-carbon transition-colors">
              {{-- Kolom Nomor --}}
              <td class="px-3 py-2 text-center text-slate-800 font-medium">{{ $loop->iteration }}</td>
              
              <td class="px-3 py-2">
                <div class="flex flex-col">
                  <span class="font-semibold text-silver">{{ $eval->product_code }}</span>
                  <span class="text-xs text-muted truncate max-w-[150px]">{{ $eval->product_name }}</span>
                </div>
              </td>

              {{-- Parameters --}}
              <td class="px-1 py-2 text-center text-slate-800 border-l border-carbon">{{ $eval->raw_order_p }}</td>
              <td class="px-1 py-2 text-center text-slate-800">{{ $eval->raw_order_d }}</td>
              <td class="px-1 py-2 text-center text-slate-800">{{ $eval->raw_order_q }}</td>
              <td class="px-1 py-2 text-center text-silver border-l border-carbon/50">{{ $eval->raw_seasonal_P }}</td>
              <td class="px-1 py-2 text-center text-silver">{{ $eval->raw_seasonal_D }}</td>
              <td class="px-1 py-2 text-center text-silver">{{ $eval->raw_seasonal_Q }}</td>
              <td class="px-1 py-2 text-center text-silver">{{ $eval->raw_seasonal_s }}</td>

              {{-- RAW --}}
              <td class="px-2 py-2 text-right text-xs border-l border-carbon {{ $bestMethod === 'raw' ? $hl : '' }}">{{ formatLargeNumber($eval->raw_rmse) }}</td>
              <td class="px-2 py-2 text-right text-xs {{ $bestMethod === 'raw' ? $hl : '' }}">{{ formatLargeNumber($eval->raw_mape, true) }}</td>

              {{-- MA --}}
              <td class="px-2 py-2 text-right text-xs border-l border-carbon {{ $bestMethod === 'ma' ? $hl : '' }}">{{ formatLargeNumber($eval->ma_rmse) }}</td>
              <td class="px-2 py-2 text-right text-xs {{ $bestMethod === 'ma' ? $hl : '' }}">{{ formatLargeNumber($eval->ma_mape, true) }}</td>

              {{-- SG --}}
              <td class="px-2 py-2 text-right text-xs border-l border-carbon {{ $bestMethod === 'sg' ? $hl : '' }}">{{ formatLargeNumber($eval->sg_rmse) }}</td>
              <td class="px-2 py-2 text-right text-xs {{ $bestMethod === 'sg' ? $hl : '' }}">{{ formatLargeNumber($eval->sg_mape, true) }}</td>

              {{-- BC --}}
              <td class="px-2 py-2 text-right text-xs border-l border-carbon {{ $bestMethod === 'bc' ? $hl : '' }}">{{ formatLargeNumber($eval->bc_rmse) }}</td>
              <td class="px-2 py-2 text-right text-xs {{ $bestMethod === 'bc' ? $hl : '' }}">{{ formatLargeNumber($eval->bc_mape, true) }}</td>

              {{-- YJ --}}
              <td class="px-2 py-2 text-right text-xs border-l border-carbon {{ $bestMethod === 'yj' ? $hl : '' }}">{{ formatLargeNumber($eval->yj_rmse) }}</td>
              <td class="px-2 py-2 text-right text-xs {{ $bestMethod === 'yj' ? $hl : '' }}">{{ formatLargeNumber($eval->yj_mape, true) }}</td>
              
              {{-- TOMBOL HAPUS BARIS (HANYA UNTUK C14220311) --}}
              @if(Auth::check() && str_contains(Auth::user()->email, 'c14220311'))
                <td class="px-2 py-2 text-center border-l border-carbon">
                  <form action="{{ route('settings.deleteEvaluation', $eval->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus record evaluasi ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-1.5 rounded transition-colors" title="Hapus Record">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </form>
                </td>
              @endif

            </tr>
          @empty
            <tr>
              <td colspan="{{ (Auth::check() && str_contains(Auth::user()->email, 'c14220311')) ? '20' : '19' }}" class="px-3 py-6 text-center text-muted italic">
                Tidak ada data evaluasi SARIMA yang tersedia.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- ========================================================== --}}
    {{-- KONDISI 2: TABEL NORMAL UNTUK USER LAINNYA         --}}
    {{-- ========================================================== --}}
    @else
    <div class="overflow-x-auto pb-4"> 
      <table class="w-full text-sm">
        <thead class="bg-carbon">
          <tr>
            <th class="px-3 py-3 text-center text-black">No</th>
            <th class="px-3 py-3 text-left text-black">Product Info</th>
            <th class="px-1 py-3 text-center text-black font-bold">p</th>
            <th class="px-1 py-3 text-center text-black font-bold">d</th>
            <th class="px-1 py-3 text-center text-black font-bold">q</th>
            <th class="px-1 py-3 text-center text-black font-bold border-l border-carbonSoft">P</th>
            <th class="px-1 py-3 text-center text-black font-bold">D</th>
            <th class="px-1 py-3 text-center text-black font-bold">Q</th>
            <th class="px-1 py-3 text-center text-black font-bold">s</th> 
            <th class="px-3 py-3 text-right text-black border-l border-carbonSoft">RMSE</th>
            <th class="px-3 py-3 text-right text-black">MAPE</th>
            <th class="px-3 py-3 text-left text-black border-l border-carbonSoft">Last Trained</th>
            <th class="px-3 py-3 text-center text-black">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($products as $product)
            <form id="form-{{ $product->id }}" action="{{ route('settings.updateSarima') }}" method="POST">
              @csrf
              @method('PUT')
              <input type="hidden" name="product_id" value="{{ $product->id }}">
            </form>

            <tr class="border-b border-carbon hover:bg-carbon transition-colors group">
              
              {{-- Kolom Nomor --}}
              <td class="px-3 py-2 text-center text-slate-800 font-medium">{{ $loop->iteration }}</td>

              <td class="px-3 py-2">
                <div class="flex flex-col">
                  <span class="font-semibold text-silver">{{ $product->code }}</span>
                  <span class="text-xs text-muted truncate max-w-[150px]">{{ $product->name }}</span>
                </div>
              </td>

              <td class="px-1 py-2 text-center"><input form="form-{{ $product->id }}" type="number" min="0" max="5" name="order_p" value="{{ $product->order_p }}" class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-slate-800 font-bold focus:outline-none focus:border-petronas transition-colors"></td>
              <td class="px-1 py-2 text-center"><input form="form-{{ $product->id }}" type="number" min="0" max="5" name="order_d" value="{{ $product->order_d }}" class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-slate-800 font-bold focus:outline-none focus:border-petronas transition-colors"></td>
              <td class="px-1 py-2 text-center"><input form="form-{{ $product->id }}" type="number" min="0" max="5" name="order_q" value="{{ $product->order_q }}" class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-slate-800 font-bold focus:outline-none focus:border-petronas transition-colors"></td>

              <td class="px-1 py-2 text-center border-l border-carbon"><input form="form-{{ $product->id }}" type="number" min="0" max="5" name="seasonal_P" value="{{ $product->seasonal_P }}" class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-silver font-medium focus:outline-none focus:border-petronas transition-colors"></td>
              <td class="px-1 py-2 text-center"><input form="form-{{ $product->id }}" type="number" min="0" max="5" name="seasonal_D" value="{{ $product->seasonal_D }}" class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-silver font-medium focus:outline-none focus:border-petronas transition-colors"></td>
              <td class="px-1 py-2 text-center"><input form="form-{{ $product->id }}" type="number" min="0" max="5" name="seasonal_Q" value="{{ $product->seasonal_Q }}" class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-silver font-medium focus:outline-none focus:border-petronas transition-colors"></td>

              <td class="px-1 py-2 text-center">
                <select form="form-{{ $product->id }}" name="seasonal_s" 
                    class="w-14 text-center bg-blackBase border border-carbon rounded-lg py-1 text-silver font-medium focus:outline-none focus:border-petronas transition-colors appearance-none cursor-pointer hover:bg-carbonSoft">
                  @foreach([2, 3, 6, 9, 12] as $sVal)
                    <option value="{{ $sVal }}" {{ $product->seasonal_s == $sVal ? 'selected' : '' }}>
                      {{ $sVal }}
                    </option>
                  @endforeach
                </select>
              </td>

              <td class="px-3 py-2 text-right border-l border-carbon text-xs text-silver">
                {{ $product->rmse !== null ? number_format($product->rmse, 2) : '-' }}
              </td>
              <td class="px-3 py-2 text-right text-xs text-silver">
                {{ $product->mape !== null ? number_format($product->mape, 2) . '%' : '-' }}
              </td> 

              <td class="px-3 py-2 text-xs text-muted border-l border-carbon">
                @if($product->last_trained_at)
                  <div class="font-medium text-slate-700">{{ \Carbon\Carbon::parse($product->last_trained_at)->format('d M Y') }}</div>
                  <div class="text-[10px]">{{ \Carbon\Carbon::parse($product->last_trained_at)->format('H:i') }}</div>
                @else
                  <span class="opacity-50 italic">Not trained</span>
                @endif
              </td> 

              <td class="px-3 py-2 text-center">
                <button form="form-{{ $product->id }}" type="submit" disabled
                    class="save-btn inline-flex items-center justify-center w-8 h-8 rounded 
                      transition-all duration-200
                      bg-carbon text-slate-800 cursor-not-allowed opacity-50"
                    title="Save Configuration">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                  </svg>
                </button>
              </td>
            </tr>
          @endforeach

          @if(isset($products) && $products->isEmpty())
            <tr><td colspan="13" class="px-3 py-6 text-center text-muted italic">Tidak ada data konfigurasi yang tersedia.</td></tr>
          @endif
        </tbody>
      </table>
    </div>
    @endisset
  </section>

</main>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    
    // --- LOGIC TOMBOL AUTO TUNE ---
    const gridAllForm = document.getElementById('grid-all-form');
    const btnTuneAll = document.getElementById('btn-tune-all');
    const textNormal = document.getElementById('btn-text-normal');
    const textLoading = document.getElementById('btn-text-loading');

    if (gridAllForm && btnTuneAll) {
      gridAllForm.addEventListener('submit', function() {
        btnTuneAll.disabled = true;
        if(textNormal && textLoading) {
          textNormal.classList.add('hidden');
          textLoading.classList.remove('hidden');
        }
      });
    }

    // --- LOGIC MANUAL SAVE BUTTON ---
    const forms = document.querySelectorAll('form[id^="form-"]');

    forms.forEach(form => {
      const formId = form.id;
      const inputs = document.querySelectorAll(`input[form="${formId}"], select[form="${formId}"]`);
      const saveBtn = document.querySelector(`button[form="${formId}"]`);

      if(!saveBtn) return; 

      const activeClasses = ['bg-petronas', 'text-white', 'hover:bg-blue-700', 'shadow-lg', 'shadow-petronas/20', 'cursor-pointer', 'opacity-100'];
      const inactiveClasses = ['bg-carbon', 'text-slate-800', 'cursor-not-allowed', 'opacity-50'];

      // Simpan nilai asli
      inputs.forEach(input => {
        input.dataset.original = input.value;
        
        input.addEventListener('input', () => checkDirtyState(inputs, saveBtn, activeClasses, inactiveClasses));
        input.addEventListener('change', () => checkិតirState(inputs, saveBtn, activeClasses, inactiveClasses));
      });
    });

    function checkDirtyState(inputs, btn, activeClasses, inactiveClasses) {
      let isDirty = false;
      inputs.forEach(input => {
        if (input.value !== input.dataset.original) {
          isDirty = true;
        }
      });

      if (isDirty) {
        btn.disabled = false;
        btn.classList.remove(...inactiveClasses);
        btn.classList.add(...activeClasses);
      } else {
        btn.disabled = true;
        btn.classList.remove(...activeClasses);
        btn.classList.add(...inactiveClasses);
      }
    }
  });
</script>

</body>
</html>