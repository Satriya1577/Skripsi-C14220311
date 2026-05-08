<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Material Stock Card | Production Planning System</title>
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
            success: '#10B981',
            info: '#3B82F6'
          }
        }
      }
    }
  </script>
</head>

<body class="bg-blackBase text-silver min-h-screen">

<main class="max-w-7xl mx-auto px-6 py-6 space-y-8">

  {{-- BREADCRUMB --}}
  <nav aria-label="breadcrumb" class="text-xs text-muted mb-6">
    <ol class="flex items-center space-x-2">
      <li><a href="{{ route('home.index') }}" class="hover:text-blue-600 transition-colors">Home</a></li>
      <li class="opacity-30 select-none">/</li>
      <li><a href="{{ route('reports.index') }}" class="hover:text-blue-600 transition-colors">Reports Center</a></li>
      <li class="opacity-30 select-none">/</li>
      <li class="text-slate-800 font-bold pointer-events-none" aria-current="page">Material Stock Card</li>
    </ol>
  </nav>

  <header class="border-b border-carbon pb-6">
    <div class="flex items-center gap-3">
      <p class="text-xs uppercase tracking-widest text-muted">Inventory Report</p>
    </div>
    <h1 class="text-3xl font-extrabold text-slate-800 mt-1">Laporan Stok Bahan Baku</h1>
    <p class="text-sm text-muted mt-1">Rekapitulasi total bahan baku masuk dan keluar selama 30 hari terakhir.</p>
  </header>

  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
      <h2 class="text-lg font-bold text-slate-800">Akumulasi Transaksi</h2>
      {{-- Filter form dihapus dari sini --}}
    </div>

    {{-- TABLE SECTION --}}
    <div class="overflow-x-auto rounded-lg border border-carbon">
      <table class="w-full text-sm">
        <thead class="bg-carbon text-xs uppercase tracking-wide">
          <tr>
            <th class="px-4 py-3 text-left text-black border-b border-carbonSoft">Material</th>
            <th class="px-4 py-3 text-center text-black border-b border-carbonSoft">Satuan (Purchase)</th>
            <th class="px-4 py-3 text-right text-black border-b border-carbonSoft">Total Masuk</th>
            <th class="px-4 py-3 text-right text-black border-b border-carbonSoft">Total Keluar</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-carbon/50">
          @forelse($reports as $report)
            <tr class="hover:bg-carbon transition-colors">
              
              {{-- MATERIAL INFO --}}
              <td class="px-4 py-3">
                <div class="font-bold text-silver">{{ $report->material_name }}</div>
                <div class="text-[10px] text-slate-800 ">{{ $report->material->code ?? '-' }}</div>
              </td>

              {{-- SATUAN --}}
              <td class="px-4 py-3 text-center text-silver text-xs">
                {{ $report->purchase_unit ?? '-' }}
              </td>

              {{-- TOTAL MASUK --}}
              <td class="px-4 py-3 text-right text-success font-bold">
                +{{ number_format($report->Masuk, 0, ',', '.') }}
              </td>

              {{-- TOTAL KELUAR --}}
              <td class="px-4 py-3 text-right text-danger font-bold">
                -{{ number_format(abs($report->Keluar), 0, ',', '.') }}
              </td>

            </tr>
          @empty
            <tr>
              <td colspan="4" class="px-6 py-12 text-center text-muted italic bg-slate-100">
                Belum ada data mutasi stok.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-6">
      {{ $reports->links('pagination::tailwind') }}
    </div>
  </section>

</main>

</body>
</html>