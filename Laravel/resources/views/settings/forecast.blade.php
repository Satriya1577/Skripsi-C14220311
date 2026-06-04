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
  <style>
    .sortable-col {
      cursor: pointer;
      user-select: none;
      transition: background-color 0.2s;
    }
    .sortable-col:hover {
      background-color: #94a3b8; 
    }
  </style>
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

    @if(isset($isGridSearchRunning) && $isGridSearchRunning)
      <div class="flex items-center gap-2">
        <button disabled class="flex justify-center items-center gap-2 px-5 py-3 rounded-xl bg-carbon border border-petronas/30 text-white font-bold opacity-75 cursor-not-allowed shadow-lg">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>Processing...</span>
        </button>
        
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
    
    @php
      if (!function_exists('formatLargeNumber')) {
          function formatLargeNumber($value, $isPercentage = false) {
              if ($value === null) return '-';
              
              $floatVal = (float)$value;
              if ($floatVal >= 1000000000 || $floatVal <= -1000000000) {
                  $formatted = sprintf('%.2e', $floatVal);
              } else {
                  $formatted = number_format($floatVal, 2);
              }
              
              return $isPercentage ? $formatted . '%' : $formatted;
          }
      }
    @endphp

    {{-- KOTAK SUMMARY --}}
    <div class="bg-white border border-carbon rounded-xl p-4 mb-4 shadow-sm">
      <div class="flex flex-col md:flex-row md:items-center justify-between mb-3 border-b border-carbon/50 pb-3 gap-3">
        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-petronas" viewBox="0 0 20 20" fill="currentColor">
            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
          </svg>
          Evaluation Insight <span id="summary-count" class="text-muted text-xs font-normal ml-1">(0 rows included)</span>
        </h3>
        
        {{-- RADIO BUTTON FILTER --}}
        <div class="flex items-center gap-4 text-xs font-medium text-slate-700 bg-carbonSoft px-3 py-1.5 rounded-lg border border-carbon">
          <span class="text-muted mr-1">Filter Insight:</span>
          <label class="flex items-center gap-1.5 cursor-pointer hover:text-petronas transition-colors">
            <input type="radio" name="mape_filter" value="all" class="accent-petronas w-3.5 h-3.5" checked> Semua Data
          </label>
          <label class="flex items-center gap-1.5 cursor-pointer hover:text-petronas transition-colors">
            <input type="radio" name="mape_filter" value="1000" class="accent-petronas w-3.5 h-3.5"> MAPE 0-1000%
          </label>
          <label class="flex items-center gap-1.5 cursor-pointer hover:text-petronas transition-colors">
            <input type="radio" name="mape_filter" value="100" class="accent-petronas w-3.5 h-3.5"> MAPE 0-100%
          </label>
        </div>
      </div>
      
      <div class="overflow-x-auto mb-3">
        <table class="w-full text-xs text-left border-collapse">
          <thead class="bg-carbonSoft text-slate-700">
            <tr>
              <th class="py-1.5 px-2 border-b border-carbon">Method</th>
              <th class="py-1.5 px-2 border-b border-carbon text-right">Avg RMSE</th>
              <th class="py-1.5 px-2 border-b border-carbon text-right">Min RMSE</th>
              <th class="py-1.5 px-2 border-b border-carbon text-right">Max RMSE</th>
              <th class="py-1.5 px-2 border-b border-carbon border-l border-carbon/50 text-right">Avg MAPE</th>
              <th class="py-1.5 px-2 border-b border-carbon text-right">Min MAPE</th>
              <th class="py-1.5 px-2 border-b border-carbon text-right">Max MAPE</th>
            </tr>
          </thead>
          <tbody id="summary-metrics-body" class="divide-y divide-carbon/50 text-silver font-medium">
            </tbody>
        </table>
      </div>

      <div class="bg-carbonSoft p-2 rounded-lg border border-carbon flex flex-wrap items-center gap-x-4 gap-y-2">
        <span class="text-xs font-bold text-slate-800">Most Frequent Parameters (Mode):</span>
        <div id="summary-params" class="flex gap-2 text-xs">
          </div>
      </div>
    </div>

    <div class="overflow-x-auto pb-4">
      <table id="eval-table" class="w-full text-sm border-collapse" data-sort-dir="asc">
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
            
            <th class="px-2 py-2 text-right text-black border-l border-carbon sortable-col" onclick="sortTable('eval-table', 8)" title="Sort by Raw RMSE">RMSE ↕</th>
            <th class="px-2 py-2 text-right text-black sortable-col" onclick="sortTable('eval-table', 9)" title="Sort by Raw MAPE">MAPE ↕</th>
            
            <th class="px-2 py-2 text-right text-black border-l border-carbon sortable-col" onclick="sortTable('eval-table', 10)" title="Sort by MA RMSE">RMSE ↕</th>
            <th class="px-2 py-2 text-right text-black sortable-col" onclick="sortTable('eval-table', 11)" title="Sort by MA MAPE">MAPE ↕</th>
            
            <th class="px-2 py-2 text-right text-black border-l border-carbon sortable-col" onclick="sortTable('eval-table', 12)" title="Sort by SG RMSE">RMSE ↕</th>
            <th class="px-2 py-2 text-right text-black sortable-col" onclick="sortTable('eval-table', 13)" title="Sort by SG MAPE">MAPE ↕</th>
            
            <th class="px-2 py-2 text-right text-black border-l border-carbon sortable-col" onclick="sortTable('eval-table', 14)" title="Sort by BC RMSE">RMSE ↕</th>
            <th class="px-2 py-2 text-right text-black sortable-col" onclick="sortTable('eval-table', 15)" title="Sort by BC MAPE">MAPE ↕</th>
            
            <th class="px-2 py-2 text-right text-black border-l border-carbon sortable-col" onclick="sortTable('eval-table', 16)" title="Sort by YJ RMSE">RMSE ↕</th>
            <th class="px-2 py-2 text-right text-black sortable-col" onclick="sortTable('eval-table', 17)" title="Sort by YJ MAPE">MAPE ↕</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-carbon/50" id="eval-table-body">
          @forelse($sarimaProductEvaluations as $eval)
            
            @php
              $metrics = [
                  'raw' => ['rmse' => $eval->raw_rmse !== null ? (float)$eval->raw_rmse : null, 'mape' => $eval->raw_mape !== null ? (float)$eval->raw_mape : null],
                  'ma'  => ['rmse' => $eval->ma_rmse !== null ? (float)$eval->ma_rmse : null, 'mape' => $eval->ma_mape !== null ? (float)$eval->ma_mape : null],
                  'sg'  => ['rmse' => $eval->sg_rmse !== null ? (float)$eval->sg_rmse : null, 'mape' => $eval->sg_mape !== null ? (float)$eval->sg_mape : null],
                  'bc'  => ['rmse' => $eval->bc_rmse !== null ? (float)$eval->bc_rmse : null, 'mape' => $eval->bc_mape !== null ? (float)$eval->bc_mape : null],
                  'yj'  => ['rmse' => $eval->yj_rmse !== null ? (float)$eval->yj_rmse : null, 'mape' => $eval->yj_mape !== null ? (float)$eval->yj_mape : null],
              ];

              $validMetrics = array_filter($metrics, function($item) {
                  return $item['rmse'] !== null && $item['mape'] !== null;
              });

              $bestMethod = null;
              $bestMape = null;
              if (!empty($validMetrics)) {
                  uasort($validMetrics, function($a, $b) {
                      if ($a['rmse'] === $b['rmse']) return $a['mape'] <=> $b['mape'];
                      return $a['rmse'] <=> $b['rmse']; 
                  });
                  $bestMethod = array_key_first($validMetrics);
                  $bestMape = $validMetrics[$bestMethod]['mape']; 
              }

              $isHighMape = $bestMape !== null && $bestMape > 1000;
              $rowClass = $isHighMape ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-carbon transition-colors';
              $hl = 'bg-green-100 text-green-800 font-bold';
            @endphp

            <tr class="{{ $rowClass }} eval-data-row"
                data-p="{{ $eval->raw_order_p }}" data-d="{{ $eval->raw_order_d }}" data-q="{{ $eval->raw_order_q }}"
                data-sp="{{ $eval->raw_seasonal_P }}" data-sd="{{ $eval->raw_seasonal_D }}" data-sq="{{ $eval->raw_seasonal_Q }}" data-ss="{{ $eval->raw_seasonal_s }}"
                data-raw-rmse="{{ $eval->raw_rmse }}" data-raw-mape="{{ $eval->raw_mape }}"
                data-ma-rmse="{{ $eval->ma_rmse }}" data-ma-mape="{{ $eval->ma_mape }}"
                data-sg-rmse="{{ $eval->sg_rmse }}" data-sg-mape="{{ $eval->sg_mape }}"
                data-bc-rmse="{{ $eval->bc_rmse }}" data-bc-mape="{{ $eval->bc_mape }}"
                data-yj-rmse="{{ $eval->yj_rmse }}" data-yj-mape="{{ $eval->yj_mape }}"
            >
              
              <td class="px-3 py-2 text-center text-slate-800 font-medium">{{ $loop->iteration }}</td>
              
              <td class="px-3 py-2">
                <div class="flex flex-col">
                  <span class="font-semibold text-silver">
                    {{ $eval->product_code }}
                  </span>
                  <span class="text-xs text-muted truncate max-w-[150px]">{{ $eval->product_name }}</span>
                </div>
              </td>

              <td class="px-1 py-2 text-center text-slate-800 border-l border-carbon">{{ $eval->raw_order_p }}</td>
              <td class="px-1 py-2 text-center text-slate-800">{{ $eval->raw_order_d }}</td>
              <td class="px-1 py-2 text-center text-slate-800">{{ $eval->raw_order_q }}</td>
              <td class="px-1 py-2 text-center text-silver border-l border-carbon/50">{{ $eval->raw_seasonal_P }}</td>
              <td class="px-1 py-2 text-center text-silver">{{ $eval->raw_seasonal_D }}</td>
              <td class="px-1 py-2 text-center text-silver">{{ $eval->raw_seasonal_Q }}</td>
              <td class="px-1 py-2 text-center text-silver">{{ $eval->raw_seasonal_s }}</td>

              <td class="px-2 py-2 text-right text-xs border-l border-carbon {{ $bestMethod === 'raw' ? $hl : '' }}">{{ formatLargeNumber($eval->raw_rmse) }}</td>
              <td class="px-2 py-2 text-right text-xs {{ $bestMethod === 'raw' ? $hl : '' }} {{ $eval->raw_mape > 1000 ? 'text-red-600 font-bold' : '' }}">{{ formatLargeNumber($eval->raw_mape, true) }}</td>

              <td class="px-2 py-2 text-right text-xs border-l border-carbon {{ $bestMethod === 'ma' ? $hl : '' }}">{{ formatLargeNumber($eval->ma_rmse) }}</td>
              <td class="px-2 py-2 text-right text-xs {{ $bestMethod === 'ma' ? $hl : '' }} {{ $eval->ma_mape > 1000 ? 'text-red-600 font-bold' : '' }}">{{ formatLargeNumber($eval->ma_mape, true) }}</td>

              <td class="px-2 py-2 text-right text-xs border-l border-carbon {{ $bestMethod === 'sg' ? $hl : '' }}">{{ formatLargeNumber($eval->sg_rmse) }}</td>
              <td class="px-2 py-2 text-right text-xs {{ $bestMethod === 'sg' ? $hl : '' }} {{ $eval->sg_mape > 1000 ? 'text-red-600 font-bold' : '' }}">{{ formatLargeNumber($eval->sg_mape, true) }}</td>

              <td class="px-2 py-2 text-right text-xs border-l border-carbon {{ $bestMethod === 'bc' ? $hl : '' }}">{{ formatLargeNumber($eval->bc_rmse) }}</td>
              <td class="px-2 py-2 text-right text-xs {{ $bestMethod === 'bc' ? $hl : '' }} {{ $eval->bc_mape > 1000 ? 'text-red-600 font-bold' : '' }}">{{ formatLargeNumber($eval->bc_mape, true) }}</td>

              <td class="px-2 py-2 text-right text-xs border-l border-carbon {{ $bestMethod === 'yj' ? $hl : '' }}">{{ formatLargeNumber($eval->yj_rmse) }}</td>
              <td class="px-2 py-2 text-right text-xs {{ $bestMethod === 'yj' ? $hl : '' }} {{ $eval->yj_mape > 1000 ? 'text-red-600 font-bold' : '' }}">{{ formatLargeNumber($eval->yj_mape, true) }}</td>
              
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
              <td colspan="{{ (Auth::check() && str_contains(Auth::user()->email, 'c14220311')) ? '19' : '18' }}" class="px-3 py-6 text-center text-muted italic">
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
      <table id="normal-table" class="w-full text-sm" data-sort-dir="asc">
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
            
            <th class="px-3 py-3 text-right text-black border-l border-carbonSoft sortable-col" onclick="sortTable('normal-table', 9)">RMSE ↕</th>
            <th class="px-3 py-3 text-right text-black sortable-col" onclick="sortTable('normal-table', 10)">MAPE ↕</th>
            
            <th class="px-3 py-3 text-left text-black border-l border-carbonSoft">Last Trained</th>
            <th class="px-3 py-3 text-center text-black">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($products as $product)
            @php
              $isHighMape = $product->mape !== null && (float)$product->mape > 1000;
              $rowClass = $isHighMape ? 'bg-red-50 hover:bg-red-100 group' : 'border-b border-carbon hover:bg-carbon transition-colors group';
            @endphp
            
            <form id="form-{{ $product->id }}" action="{{ route('settings.updateSarima') }}" method="POST">
              @csrf
              @method('PUT')
              <input type="hidden" name="product_id" value="{{ $product->id }}">
            </form>

            <tr class="{{ $rowClass }}">
              
              <td class="px-3 py-2 text-center text-slate-800 font-medium">{{ $loop->iteration }}</td>

              <td class="px-3 py-2">
                <div class="flex flex-col">
                  <span class="font-semibold text-silver">
                    {{ $product->code }}
                    @if($isHighMape)
                      <span class="text-[10px] text-red-600 bg-red-200 px-1 rounded ml-1 font-bold inline-block" title="MAPE sangat tinggi/error">⚠️ High Error</span>
                    @endif
                  </span>
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

              <td class="px-3 py-2 text-right border-l border-carbon text-xs {{ $isHighMape ? 'text-slate-800' : 'text-silver' }}">
                {{ $product->rmse !== null ? number_format($product->rmse, 2) : '-' }}
              </td>
              <td class="px-3 py-2 text-right text-xs {{ $isHighMape ? 'text-red-700 font-bold' : 'text-silver' }}">
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
  function formatJsLargeNumber(num, isPercentage = false) {
    if (num === null || num === undefined || isNaN(num)) return '-';
    let formatted;
    if (num >= 1e9 || num <= -1e9) {
      formatted = num.toExponential(2);
    } else {
      formatted = num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    return isPercentage ? formatted + '%' : formatted;
  }

  function getMode(obj) {
    if (Object.keys(obj).length === 0) return '-';
    return Object.keys(obj).reduce((a, b) => obj[a] > obj[b] ? a : b);
  }

  function updateSummary() {
    const radio = document.querySelector('input[name="mape_filter"]:checked');
    if(!radio) return;
    
    const filterVal = radio.value;
    const allRows = document.querySelectorAll('.eval-data-row');
    let includedCount = 0;

    let metrics = {
      raw: { rmse: [], mape: [] },
      ma:  { rmse: [], mape: [] },
      sg:  { rmse: [], mape: [] },
      bc:  { rmse: [], mape: [] },
      yj:  { rmse: [], mape: [] }
    };
    
    let paramsCount = { p: {}, d: {}, q: {}, sp: {}, sd: {}, sq: {}, ss: {} };

    allRows.forEach(tr => {
      let include = false;
      const methods = ['raw', 'ma', 'sg', 'bc', 'yj'];
      
      // LOGIKA FILTER KETAT: Cek kelima metode MAPE
      if (filterVal === 'all') {
        include = true;
      } else {
        let maxLimit = parseInt(filterVal);
        let allValid = true;
        
        for (let m of methods) {
          let mapeVal = parseFloat(tr.getAttribute(`data-${m}-mape`));
          // Gugurkan baris jika ada satu nilai yang error/NaN, negatif, atau melebihi batas radio
          if (isNaN(mapeVal) || mapeVal < 0 || mapeVal > maxLimit) {
            allValid = false;
            break; 
          }
        }
        include = allValid;
      }

      if (include) {
        tr.style.display = ''; // Tampilkan baris
        includedCount++;

        methods.forEach(m => {
          let rmseVal = parseFloat(tr.getAttribute(`data-${m}-rmse`));
          let mapeVal = parseFloat(tr.getAttribute(`data-${m}-mape`));
          if (!isNaN(rmseVal)) metrics[m].rmse.push(rmseVal);
          if (!isNaN(mapeVal)) metrics[m].mape.push(mapeVal);
        });

        ['p', 'd', 'q', 'sp', 'sd', 'sq', 'ss'].forEach(param => {
          let pVal = tr.getAttribute(`data-${param}`);
          if (pVal !== null && pVal !== '') {
            paramsCount[param][pVal] = (paramsCount[param][pVal] || 0) + 1;
          }
        });
      } else {
        tr.style.display = 'none'; // Sembunyikan baris
      }
    });

    const countEl = document.getElementById('summary-count');
    if(countEl) countEl.innerText = `(${includedCount} rows included)`;

    const calcStats = (arr) => {
      if (arr.length === 0) return { avg: null, min: null, max: null };
      let min = Math.min(...arr);
      let max = Math.max(...arr);
      let avg = arr.reduce((a, b) => a + b, 0) / arr.length;
      return { avg, min, max };
    };

    const tbody = document.getElementById('summary-metrics-body');
    if (tbody) {
      tbody.innerHTML = '';
      const methodNames = { raw: 'Raw Data', ma: 'Moving Avg', sg: 'Savitzky-Golay', bc: 'Box-Cox', yj: 'Yeo-Johnson' };
      
      Object.keys(metrics).forEach(m => {
        let rmseStats = calcStats(metrics[m].rmse);
        let mapeStats = calcStats(metrics[m].mape);
        
        let tr = document.createElement('tr');
        tr.innerHTML = `
          <td class="py-1.5 px-2">${methodNames[m]}</td>
          <td class="py-1.5 px-2 text-right">${formatJsLargeNumber(rmseStats.avg)}</td>
          <td class="py-1.5 px-2 text-right">${formatJsLargeNumber(rmseStats.min)}</td>
          <td class="py-1.5 px-2 text-right">${formatJsLargeNumber(rmseStats.max)}</td>
          <td class="py-1.5 px-2 border-l border-carbon/50 text-right">${formatJsLargeNumber(mapeStats.avg, true)}</td>
          <td class="py-1.5 px-2 text-right">${formatJsLargeNumber(mapeStats.min, true)}</td>
          <td class="py-1.5 px-2 text-right">${formatJsLargeNumber(mapeStats.max, true)}</td>
        `;
        tbody.appendChild(tr);
      });
    }

    const paramsDiv = document.getElementById('summary-params');
    if (paramsDiv) {
      const modeNames = { p:'p', d:'d', q:'q', sp:'P', sd:'D', sq:'Q', ss:'s' };
      paramsDiv.innerHTML = '';
      Object.keys(paramsCount).forEach(key => {
        let mode = getMode(paramsCount[key]);
        let span = document.createElement('span');
        span.className = 'bg-white border border-carbon px-2 py-0.5 rounded shadow-sm text-slate-800';
        span.innerHTML = `<span class="font-bold mr-1 text-petronas">${modeNames[key]}:</span>${mode}`;
        paramsDiv.appendChild(span);
      });
    }
  }


  function sortTable(tableId, colIndex) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    if (rows.length === 0 || rows[0].querySelector('td[colspan]')) return;

    const currentDir = table.getAttribute('data-sort-dir') || 'asc';
    const newDir = currentDir === 'asc' ? 'desc' : 'asc';
    table.setAttribute('data-sort-dir', newDir);

    rows.sort((a, b) => {
      if(!a.cells[colIndex] || !b.cells[colIndex]) return 0;

      let valA = a.cells[colIndex].innerText.trim().replace(/,/g, '').replace('%', '');
      let valB = b.cells[colIndex].innerText.trim().replace(/,/g, '').replace('%', '');
      
      let numA = parseFloat(valA);
      let numB = parseFloat(valB);

      if (isNaN(numA)) numA = newDir === 'asc' ? 99999999999 : -99999999999;
      if (isNaN(numB)) numB = newDir === 'asc' ? 99999999999 : -99999999999;

      return newDir === 'asc' ? numA - numB : numB - numA;
    });

    rows.forEach(row => tbody.appendChild(row));
  }

  document.addEventListener('DOMContentLoaded', function() {
    
    // --- INIT RADIO BUTTON FILTER ---
    const radioFilters = document.querySelectorAll('input[name="mape_filter"]');
    if (radioFilters.length > 0) {
      radioFilters.forEach(radio => {
        radio.addEventListener('change', updateSummary);
      });
      // Hitung summary saat halaman pertama kali diload
      updateSummary();
    }

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

      inputs.forEach(input => {
        input.dataset.original = input.value;
        
        input.addEventListener('input', () => checkDirtyState(inputs, saveBtn, activeClasses, inactiveClasses));
        input.addEventListener('change', () => checkDirtyState(inputs, saveBtn, activeClasses, inactiveClasses));
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