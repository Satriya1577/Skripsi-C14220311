<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forecast Chart | {{ $product->name }}</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

<body class="bg-blackBase text-silver min-h-screen font-sans">

<main class="max-w-7xl mx-auto px-6 py-6 space-y-8">

  {{-- Breadcrumb --}}
  <nav aria-label="breadcrumb" class="text-xs text-muted">
    <ol class="flex items-center space-x-2">
      <li><a href="{{ route('home.index') }}" class="hover:text-blue-600 transition-colors">Home</a></li>
      <li class="opacity-40">/</li>
      <li><a href="{{ route('forecast.index') }}" class="hover:text-blue-600 transition-colors">Forecasting</a></li>
      <li class="opacity-40">/</li>
      <li class="text-slate-800 font-semibold" aria-current="page">Chart Analysis</li>
    </ol>
  </nav>

  <x-alert-messages />

  {{-- Header (Styling disamakan dengan forecast.show) --}}
  <header class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
    <div>
      <p class="text-xs uppercase tracking-widest text-muted">Forecast Result</p>
      <div class="flex items-center gap-3">
        <h1 class="text-3xl font-extrabold text-slate-800">{{ $product->name }}</h1>
        {{-- Status dipindah ke sebelah kanan nama produk --}}
        <span class="px-3 py-1 rounded-xl border shadow-sm text-xs font-bold uppercase tracking-wide 
          {{ $productionPlan->status == 'approved' ? 'bg-blue-100 text-blue-700 border-petronas/30' : ($productionPlan->status == 'rejected' ? 'bg-danger/10 text-danger border-danger/30' : 'bg-warning/10 text-warning border-warning/30') }}">
          Status: {{ ucfirst($productionPlan->status) }}
        </span>
      </div>
      <p class="text-sm text-muted mt-1 flex items-center gap-2">
        <span class="bg-carbon px-2 py-1 rounded text-xs border border-carbonSoft">{{ $product->code }}</span>
        <span>Target Period: <strong class="text-slate-800">{{ \Carbon\Carbon::parse($productionPlan->period)->format('F Y') }}</strong></span>
      </p>
    </div>
  </header>

  {{-- SECTION 1: METRICS & CONFIG SNAPSHOT (Styling disamakan dengan forecast.show) --}}
  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon shadow-lg shadow-slate-200/60 space-y-6">
    <h2 class="text-lg font-bold text-slate-800">Metrics & Configuration</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
      
      {{-- Card 1: Forecast Qty --}}
      <div class="bg-carbon rounded-lg p-4 border border-carbonSoft shadow-sm">
        <p class="text-xs text-muted uppercase tracking-wide mb-1">Forecast Quantity</p>
        <p class="text-2xl font-bold text-slate-800">{{ number_format($productionPlan->forecast_qty) }}</p>
        <p class="text-[10px] text-muted border-t border-white/10 mt-1 pt-1 flex justify-between">
          <span>Safety Stock:</span>
          <span class="font-bold text-slate-800">{{ number_format($productionPlan->safety_stock_snapshot) }}</span>
        </p>
      </div>

      {{-- Card 2: Model Parameters --}}
      <div class="bg-carbon rounded-lg p-4 border border-carbonSoft shadow-sm group hover:border-petronas/30 transition-colors">
        <p class="text-xs text-muted uppercase tracking-wide mb-1">SARIMA Config</p>
        <div class="flex items-end gap-1 mt-1">
          <span class="text-xl font-bold text-slate-800">({{ $productionPlan->order_p }},{{ $productionPlan->order_d }},{{ $productionPlan->order_q }})</span>
          <span class="text-muted text-xs mb-0.5">x</span>
          <span class="text-lg font-bold text-silver">({{ $productionPlan->seasonal_P }},{{ $productionPlan->seasonal_D }},{{ $productionPlan->seasonal_Q }})</span>
          <span class="text-[10px] text-muted mb-0.5">{{ $productionPlan->seasonal_s }}</span>
        </div>
        <p class="text-[10px] text-muted border-t border-white/10 mt-1 pt-1">Parameters used for this run.</p>
      </div>

      {{-- Card 3: RMSE --}}
      <div class="bg-carbon rounded-lg p-4 border border-carbonSoft shadow-sm">
        <p class="text-xs text-muted uppercase tracking-wide mb-1">Model Accuracy (RMSE)</p>
        <p class="text-2xl font-bold text-slate-800">{{ number_format($metrics['rmse'] ?? 0, 4) }}</p>
        <p class="text-[10px] text-muted border-t border-white/10 mt-1 pt-1">Root Mean Squared Error</p>
      </div>

      {{-- Card 4: MAPE --}}
      <div class="bg-carbon rounded-lg p-4 border border-carbonSoft shadow-sm">
        <p class="text-xs text-muted uppercase tracking-wide mb-1">Model Accuracy (MAPE)</p>
        @php 
          $mapeVal = $metrics['mape'] ?? 0; 
          // Jika nilai melebihi 1 Miliar, gunakan notasi ilmiah
          $formattedMape = $mapeVal >= 1000000000 ? sprintf('%.2e', $mapeVal) : number_format($mapeVal, 2);
        @endphp
        <p class="text-2xl font-bold {{ $mapeVal < 20 ? 'text-success' : ($mapeVal < 50 ? 'text-warning' : 'text-danger') }}">
          {{ $formattedMape }}%
        </p>
        <p class="text-[10px] text-muted border-t border-white/10 mt-1 pt-1">Mean Absolute Percentage Error</p>
      </div>
      
    </div>
  </section>

   {{-- SECTION 2: CHART VISUALIZATION (Styling Container disamakan dengan forecast.show) --}}
  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon shadow-lg shadow-slate-200/60 h-125 flex flex-col relative">
    <div class="flex justify-between items-center mb-4">
      <div>
        <h2 class="text-lg font-bold text-slate-800">Demand Visualization</h2>
        <p class="text-xs text-muted">Comparison between Actual Sales History and Forecasted Demand.</p>
      </div>
      
      {{-- Custom Legend Update Warna --}}
      <div class="flex gap-4 text-xs">
        <div class="flex items-center gap-2">
          <span class="w-3 h-3 rounded-full bg-[#64748B] opacity-70"></span>
          <span class="text-muted">Actual History</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="w-3 h-3 rounded-full bg-petronas"></span>
          <span class="text-slate-800 font-bold">Prediction</span>
        </div>
      </div>
    </div>
    
    <div class="flex-1 w-full relative min-h-0">
      <canvas id="forecastChart"></canvas>
    </div>
  </section>
</main>

<script>
  // --- SETUP CHART JS ---
  let chartData = @json($chartData);
  
  // 1. Cek apakah data validation history kosong
  let isChartEmpty = false;
  if (!chartData || !chartData.labels || chartData.labels.length === 0) {
      isChartEmpty = true;
  }
  
  // 2. Ambil data forecast masa depan (Single Point)
  const futureQty = {{ $productionPlan->forecast_qty }};
  const futureLabel = "{{ \Carbon\Carbon::parse($productionPlan->period)->format('M Y') }}";

  // 3. Tambahkan titik masa depan ke array
  if(chartData && chartData.labels) {
    chartData.labels.push(futureLabel);
    chartData.actual.push(null);
    chartData.forecast.push(futureQty);
  }

  const ctx = document.getElementById('forecastChart');
  let gradientForecast;

  // 4. Jika chart kosong (tidak ada validation log), tampilkan pesan error
  if (!isChartEmpty && chartData.labels.length > 1) {
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: chartData.labels,
        datasets: [
          {
            label: 'Actual Sales',
            data: chartData.actual,
            borderColor: '#64748B', // Warna abu-abu tegas (Slate-500)
            backgroundColor: 'rgba(100, 116, 139, 0.1)',
            tension: 0.4, 
            pointRadius: 4, 
            pointHoverRadius: 6,
            borderWidth: 3, // Dipertebal agar kontras
            fill: true,
            pointBackgroundColor: '#64748B',
            spanGaps: false 
          },
          {
            label: 'Forecast Prediction',
            data: chartData.forecast,
            borderColor: '#2563EB', // Warna Biru Petronas
            backgroundColor: (context) => {
              const ctx = context.chart.ctx;
              if (!gradientForecast) {
                gradientForecast = ctx.createLinearGradient(0, 0, 0, 400);
                gradientForecast.addColorStop(0, 'rgba(37, 99, 235, 0.3)');
                gradientForecast.addColorStop(1, 'rgba(37, 99, 235, 0.0)');
              }
              return gradientForecast;
            },
            borderDash: [5, 5],
            tension: 0.4, 
            pointRadius: (ctx) => {
              const index = ctx.dataIndex;
              const lastIndex = ctx.chart.data.labels.length - 1;
              return index === lastIndex ? 6 : 4;
            }, 
            pointHoverRadius: 8,
            borderWidth: 3, // Dipertebal agar kontras
            fill: true,
            pointBackgroundColor: (ctx) => {
              const index = ctx.dataIndex;
              const lastIndex = ctx.chart.data.labels.length - 1;
              return index === lastIndex ? '#2563EB' : '#ffffff';
            },
            pointBorderColor: '#2563EB',
            pointBorderWidth: 2
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { 
          legend: { display: false }, 
          tooltip: {
            backgroundColor: 'rgba(27, 29, 31, 0.95)',
            titleColor: '#2563EB', // Disesuaikan dengan warna baru
            bodyColor: '#E2E8F0', // Warna text tooltip diperterang
            borderColor: '#3f3f46',
            borderWidth: 1,
            padding: 12,
            displayColors: true,
            usePointStyle: true,
            callbacks: {
              label: function(context) {
                let label = context.dataset.label || '';
                if (label) { label += ': '; }
                if (context.parsed.y !== null) {
                  label += new Intl.NumberFormat('en-US').format(context.parsed.y);
                }
                if (context.datasetIndex === 1 && context.dataIndex === context.chart.data.labels.length - 1) {
                  label += ' (Target)';
                }
                return label;
              }
            }
          }
        },
        scales: {
          x: { ticks: { color: '#64748B', font: { size: 11, family: 'sans-serif' } }, grid: { color: 'rgba(100,116,139,0.1)', drawBorder: false } },
          y: { ticks: { color: '#64748B', font: { size: 11, family: 'sans-serif' } }, grid: { color: 'rgba(100,116,139,0.1)', drawBorder: false }, beginAtZero: true }
        }
      }
    });
  } else {
    // Tampilan jika data kosong - Menggunakan SVG Chart Line
    ctx.parentNode.innerHTML = `
      <div class="flex flex-col items-center justify-center h-full text-muted">
        <svg class="w-16 h-16 mb-4 opacity-50" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3v17.25a.75.75 0 00.75.75h17.25M7.5 14.25l3.75-3.75 3.75 3.75 4.5-4.5M19.5 10.5V6h-4.5" />
        </svg>
        <span class="text-xl font-bold text-slate-800">Tidak ada chart yang tersedia</span>
        <span class="text-sm mt-2">Data historis penjualan tidak ditemukan atau tidak cukup untuk menggambar visualisasi grafik.</span>
      </div>
    `;
  }
</script>

</body>
</html>