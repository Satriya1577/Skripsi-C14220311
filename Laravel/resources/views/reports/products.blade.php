<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Stock Movements | Production Planning System</title>
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
  <nav aria-label="breadcrumb" class="text-xs text-muted mb-6">
    <ol class="flex items-center space-x-2">
      <li>
        <a href="{{ route('home.index') }}" class="hover:text-blue-600 transition-colors">
          Home
        </a>
      </li>
      <li class="opacity-40">/</li>
      <li>
        <a href="{{ route('reports.index') }}" class="hover:text-blue-600 transition-colors">
          Reports Center
        </a>
      </li>
      <li class="opacity-40">/</li>
      <li class="text-slate-800 font-semibold" aria-current="page">
        Stock Movements
      </li>
    </ol>
  </nav>

  {{-- Spacing diatur ulang agar identik dengan purchase.show (mengikuti space-y-8 dari main) --}}
  <header>
    <div class="flex items-center gap-3">
      <p class="text-xs uppercase tracking-widest text-muted">Inventory Report</p>
    </div>
    <h1 class="text-3xl font-extrabold text-slate-800 mt-1">Laporan Stok Produk</h1>
    <p class="text-sm text-muted mt-1">Rekapitulasi total barang masuk dan keluar per produk selama 30 hari terakhir.</p>
  </header>

  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
      <h2 class="text-lg font-bold text-slate-800">Akumulasi Transaksi</h2>
    </div>

    <div class="overflow-x-auto rounded-lg border border-carbon">
      <table class="w-full text-sm">
        <thead class="bg-carbon text-xs uppercase tracking-wide">
          <tr>
            <th class="px-4 py-3 text-left text-black border-b border-carbonSoft">Produk</th>
            <th class="px-4 py-3 text-center text-black border-b border-carbonSoft">Kemasan</th>
            <th class="px-4 py-3 text-right text-black border-b border-carbonSoft">Total Masuk</th>
            <th class="px-4 py-3 text-right text-black border-b border-carbonSoft">Total Keluar</th>
            <th class="px-4 py-3 text-center text-black border-b border-carbonSoft">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-carbon/50">
          @forelse($reports as $report)
            <tr class="hover:bg-carbon transition-colors">
              
              <td class="px-4 py-3">
                <div class="font-bold text-silver">{{ $report->product_name }}</div>
                <div class="text-[10px] text-slate-800 ">{{ $report->product->code ?? '-' }}</div>
              </td>

              <td class="px-4 py-3 text-center text-silver text-xs">
                {{ $report->product->packaging ?? '-' }}
              </td>

              <td class="px-4 py-3 text-right text-success font-bold">
                +{{ number_format($report->Masuk, 0, ',', '.') }}
              </td>

              <td class="px-4 py-3 text-right text-danger font-bold">
                -{{ number_format(abs($report->Keluar), 0, ',', '.') }}
              </td>

              <td class="px-4 py-3 text-center">
                <a href="{{ route('reports.productChart', $report->product_id) }}" 
                  class="inline-flex items-center justify-center w-8 h-8 rounded bg-petronas text-white hover:bg-blue-700 transition shadow-sm"
                  title="View Product Movement Chart">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v17a1 1 0 001 1h17" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 14l4-5 4 2 5-6" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M7 14h.01M11 9h.01M15 11h.01M20 5h.01" />
                  </svg>
                </a>
              </td>

            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-6 py-12 text-center text-muted italic bg-slate-100">
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