<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reports | Production Planning System</title>
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

  {{-- BREADCRUMB --}}
  <nav aria-label="breadcrumb" class="text-xs text-muted">
    <ol class="flex items-center space-x-2">
      <li>
        <a href="{{ route('home.index') }}" class="hover:text-blue-600 transition-colors">
          Home
        </a>
      </li>
      <li class="opacity-40">/</li>
      <li class="text-slate-800 font-semibold" aria-current="page">
        Reports Center
      </li>
    </ol>
  </nav>

  {{-- HEADER --}}
  <header>
    <p class="text-xs uppercase tracking-widest text-muted">
      Analytics & History
    </p>
    <h1 class="text-3xl font-extrabold text-slate-800">
      Reports
    </h1>
    <p class="text-sm text-muted mt-1">
      Pantau pergerakan stok dan performa keuangan perusahaan.
    </p>
  </header>

  {{-- REPORT GRID SECTION --}}
  <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

    {{-- 1. PRODUCT STOCK CARD --}}
    <a href="{{ route('reports.product') }}" 
      class="text-center p-6 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center transform transition-all duration-300 hover:-translate-y-1 hover:border-blue-500 hover:shadow-lg hover:shadow-blue-500/20 group">
      <div class="text-blue-500 group-hover:text-slate-800 transition-colors text-4xl mb-3">
        <i class="bi bi-box-seam"></i>
      </div>
      <h5 class="text-lg font-bold text-silver mb-1">Product Stock Report</h5>
      <p class="text-xs text-muted">Laporan stok produk jadi (Finish Good). Melacak riwayat produksi masuk dan penjualan keluar.</p>
    </a>

    {{-- 2. MATERIAL STOCK CARD --}}
    <a href="{{ route('reports.material') }}" 
      class="text-center p-6 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center transform transition-all duration-300 hover:-translate-y-1 hover:border-green-500 hover:shadow-lg hover:shadow-green-500/20 group">
      <div class="text-green-500 group-hover:text-slate-800 transition-colors text-4xl mb-3">
        <i class="bi bi-layers-half"></i>
      </div>
      <h5 class="text-lg font-bold text-silver mb-1">Material Stock Card</h5>
      <p class="text-xs text-muted">Laporan mutasi bahan baku (Raw Material). Memonitor pembelian masuk dan pemakaian produksi.</p>
    </a>

    {{-- 3. INCOME STATEMENT (LABA RUGI) --}}
    <a href="{{ route('reports.incomeStatement') }}" 
      class="text-center p-6 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center transform transition-all duration-300 hover:-translate-y-1 hover:border-yellow-500 hover:shadow-lg hover:shadow-yellow-500/20 group">
      <div class="text-yellow-500 group-hover:text-slate-800 transition-colors text-4xl mb-3">
        <i class="bi bi-graph-up"></i>
      </div>
      <h5 class="text-lg font-bold text-silver mb-1">Income Statement</h5>
      <p class="text-xs text-muted">Laporan Laba Rugi sederhana. Menghitung selisih pendapatan penjualan dengan biaya HPP dan operasional.</p>
    </a>

  </section>

</main>

{{-- Bootstrap Icons --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

</body>
</html>