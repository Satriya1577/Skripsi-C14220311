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

    <header class="border-b border-carbon pb-6">
        <div class="flex items-center gap-3">
            <p class="text-xs uppercase tracking-widest text-muted">Inventory Report</p>
        </div>
        <h1 class="text-3xl font-extrabold text-slate-800 mt-1">Laporan Stok Produk</h1>
        <p class="text-sm text-muted mt-1">Rekapitulasi total barang masuk dan keluar per produk selama 30 hari terakhir.</p>
    </header>

    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <h2 class="text-lg font-bold text-slate-800">Akumulasi Transaksi</h2>
            {{-- Filter tanggal telah dihapus dari sini --}}
        </div>

        <div class="overflow-x-auto rounded-lg border border-carbon">
            <table class="w-full text-sm">
                <thead class="bg-carbon text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left text-muted border-b border-carbonSoft">Produk</th>
                        <th class="px-4 py-3 text-center text-muted border-b border-carbonSoft">Kemasan</th>
                        <th class="px-4 py-3 text-right text-muted border-b border-carbonSoft">Total Masuk</th>
                        <th class="px-4 py-3 text-right text-muted border-b border-carbonSoft">Total Keluar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon/50">
                    @forelse($reports as $report)
                        <tr class="hover:bg-carbon transition-colors">
                            
                            <td class="px-4 py-3">
                                <div class="font-bold text-silver">{{ $report->product_name }}</div>
                                <div class="text-[10px] text-slate-800 font-mono">{{ $report->product->code ?? '-' }}</div>
                            </td>

                            {{-- Kolom Kemasan Baru --}}
                            <td class="px-4 py-3 text-center text-silver font-mono text-xs">
                                {{ $report->product->packaging ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-right font-mono text-success font-bold">
                                +{{ number_format($report->Masuk, 0, ',', '.') }}
                            </td>

                            <td class="px-4 py-3 text-right font-mono text-danger font-bold">
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