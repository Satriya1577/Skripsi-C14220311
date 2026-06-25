<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forecasting | Production Planning System</title>
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
            muted: '#64748B'
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
      <li>
        <a href="{{ route('home.index') }}" class="hover:text-blue-600 transition-colors">
          Home
        </a>
      </li>
      <li class="opacity-40">/</li>
      <li class="text-slate-800 font-semibold" aria-current="page">
        Forecasting
      </li>
    </ol>
  </nav>

  {{-- Komponen Alert untuk menampilkan pesan success/error dari Controller --}}
  <x-alert-messages />

  <header class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
    <div>
      <p class="text-xs uppercase tracking-widest text-muted">
        Predictive Analytics
      </p>
      <h1 class="text-3xl font-extrabold text-slate-800">
        Sales Forecasting
      </h1>
      <p class="text-sm text-muted mt-1">
        Pilih produk untuk melihat prediksi penjualan bulan depan menggunakan metode SARIMA.
      </p>
    </div>
    
    {{-- TOMBOL GENERATE BATCH --}}
    <div>
      <form action="{{ route('forecast.generateAllForecasts') }}" method="POST">
        @csrf
        <button type="submit" 
          onclick="return confirm('Mulai antrean perhitungan forecast untuk SEMUA produk? Proses ini akan berjalan di latar belakang.')" 
          class="flex items-center gap-2 px-5 py-2.5 rounded-lg bg-petronas text-blackBase font-bold uppercase tracking-wide text-sm hover:bg-petronas/90 hover:scale-105 transition-all shadow-lg shadow-petronas/20">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Generate All Forecasts
        </button>
      </form>
    </div>
  </header>

  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon shadow-lg shadow-slate-200/60">
    <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
      </svg>
      Product Forecast List
    </h2>

    <div class="overflow-x-auto rounded-lg border border-carbon">
      <table class="w-full text-sm">
        <thead class="bg-carbon">
          <tr>
            <th class="px-4 py-3 text-left text-black text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Product Code</th>
            <th class="px-4 py-3 text-left text-black text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Product Name</th>
            <th class="px-4 py-3 text-center text-black text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-carbon/50 bg-carbonSoft">
          @foreach ($products as $product)
            <tr class="hover:bg-carbon transition-colors group">
              <td class="px-4 py-3 text-slate-800 font-bold">
                {{ $product->code }}
              </td>
              
              <td class="px-4 py-3 text-silver font-medium">
                {{ $product->name }}
              </td>

              <td class="px-4 py-3 text-center">
                <a href="{{ route('forecast.show', $product->id) }}" 
                  class="inline-flex items-center gap-2 px-4 py-1.5 rounded-lg 
                     bg-petronas text-blackBase text-xs font-bold uppercase tracking-wide
                     hover:bg-petronas/90 hover:scale-105 transition-all shadow-lg shadow-petronas/20">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                  </svg>
                  View Forecast
                </a>
              </td>
            </tr>
          @endforeach

          @if ($products->count() === 0)
            <tr>
              <td colspan="5" class="px-4 py-8 text-center text-muted italic">
                Belum ada data produk. Silakan import atau tambah produk terlebih dahulu.
              </td>
            </tr>
          @endif
        </tbody>
      </table>
    </div>

    @if ($products->hasPages())
      <div class="mt-6 flex justify-between items-center text-sm text-muted border-t border-carbon pt-4">
        <div>
          Showing <span class="font-bold text-silver">{{ $products->firstItem() }}</span> 
          to <span class="font-bold text-silver">{{ $products->lastItem() }}</span> 
          of <span class="font-bold text-silver">{{ $products->total() }}</span> products
        </div>
        <div>
          {{ $products->links('pagination::tailwind') }}
        </div>
      </div>
    @endif
  </section>

</main>

</body>
</html>