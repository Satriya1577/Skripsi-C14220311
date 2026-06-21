<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{{ $pageData['type'] }} Detail Chart | Production Planning System</title>
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
  <nav aria-label="breadcrumb" class="text-xs text-muted mb-6">
    <ol class="flex items-center space-x-2">
      <li><a href="{{ route('home.index') }}" class="hover:text-blue-600 transition-colors">Home</a></li>
      <li class="opacity-40">/</li>
      <li><a href="{{ route('reports.index') }}" class="hover:text-blue-600 transition-colors">Reports Center</a></li>
      <li class="opacity-40">/</li>
      <li><a href="{{ $pageData['backRoute'] }}" class="hover:text-blue-600 transition-colors">Stock Movements</a></li>
      <li class="opacity-40">/</li>
      <li class="text-slate-800 font-semibold" aria-current="page">Detail Chart</li>
    </ol>
  </nav>

  {{-- Header Dinamis --}}
  <header class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
    <div>
      <p class="text-xs uppercase tracking-widest text-muted">{{ $pageData['type'] }} Movement Analysis</p>
      <div class="flex items-center gap-3 mt-1">
        <h1 class="text-3xl font-extrabold text-slate-800">{{ $item->name }}</h1>
      </div>
      <p class="text-sm text-muted mt-2 flex items-center gap-2">
        <span class="bg-carbon px-2 py-1 rounded text-xs border border-carbonSoft font-bold text-slate-800">{{ $item->code }}</span>
        <span><strong class="text-slate-800">{{ $pageData['unitLabel'] }}</strong></span>
      </p>
    </div>

    <div class="flex items-center">
      <div class="text-right">
        <p class="text-xs text-muted uppercase tracking-wide">Current On Hand Stock</p>
        <p class="text-2xl font-bold text-petronas">{{ $pageData['stockFormat'] }}</p>
      </div>
    </div>
  </header>

  {{-- CHART VISUALIZATION --}}
  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon shadow-lg shadow-slate-200/60 h-[600px] flex flex-col relative">
    <div class="flex justify-between items-center mb-4">
      <div>
        <h2 class="text-lg font-bold text-slate-800">Daily Transaction Trend</h2>
        <p class="text-xs text-muted">{{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }} (30 Hari Terakhir)</p>
      </div>
      
      {{-- Custom Legend Dinamis --}}
      <div class="flex gap-4 text-xs font-bold">
        <div class="flex items-center gap-2">
          <span class="w-3 h-3 rounded-full bg-blue-600"></span>
          <span class="text-slate-800">{{ $pageData['type'] }} Masuk (IN)</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="w-3 h-3 rounded-full bg-red-500"></span>
          <span class="text-slate-800">{{ $pageData['type'] }} Keluar (OUT)</span>
        </div>
      </div>
    </div>
    
    <div class="flex-1 w-full relative min-h-0">
      <canvas id="movementChart"></canvas>
    </div>
  </section>

</main>

<script>
  const chartData = @json($chartData);
  const ctx = document.getElementById('movementChart').getContext('2d');

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: chartData.labels,
      datasets: [
        {
          label: 'Masuk (In)',
          data: chartData.dataIn,
          borderColor: '#2563EB',
          backgroundColor: 'rgba(37, 99, 235, 0.1)',
          borderWidth: 3,
          tension: 0.4,
          pointRadius: 4,
          pointHoverRadius: 6,
          fill: true
        },
        {
          label: 'Keluar (Out)',
          data: chartData.dataOut,
          borderColor: '#EF4444',
          backgroundColor: 'transparent',
          borderWidth: 3,
          borderDash: [5, 5],
          tension: 0.4,
          pointRadius: 4,
          pointHoverRadius: 6,
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
          titleColor: '#CBD5E1',
          bodyColor: '#E2E8F0',
          borderColor: '#3f3f46',
          borderWidth: 1,
          padding: 12,
          usePointStyle: true,
        }
      },
      scales: {
        x: { ticks: { color: '#64748B', font: { size: 11 } }, grid: { color: 'rgba(100,116,139,0.1)', drawBorder: false } },
        y: { ticks: { color: '#64748B', font: { size: 11 } }, grid: { color: 'rgba(100,116,139,0.1)', drawBorder: false }, beginAtZero: true }
      }
    }
  });
</script>

</body>
</html>