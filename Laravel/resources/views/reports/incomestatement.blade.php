<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laporan Laba Rugi | Production Planning System</title>
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
            <li><a href="{{ route('home.index') }}" class="hover:text-petronas transition-colors">Home</a></li>
            <li class="opacity-30 select-none">/</li>
            <li><a href="{{ route('reports.index') }}" class="hover:text-petronas transition-colors">Reports Center</a></li>
            <li class="opacity-30 select-none">/</li>
            <li class="text-petronas font-bold pointer-events-none" aria-current="page">Laporan Laba Rugi</li>
        </ol>
    </nav>

    {{-- HEADER & PERIODE INFO --}}
    <header class="border-b border-carbon pb-6">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <div class="flex items-center gap-3">
                    <p class="text-xs uppercase tracking-widest text-muted">Financial Report</p>
                </div>
                <h1 class="text-3xl font-extrabold text-slate-800 mt-1">Laporan Laba Rugi (Kotor)</h1>
                <p class="text-sm text-muted mt-1">Laporan pendapatan penjualan dan harga pokok penjualan dalam 30 hari terakhir.</p>
            </div>
            
            {{-- INDIKATOR PERIODE --}}
            <div class="flex items-center gap-3">
                <div class="bg-carbon border border-carbonSoft rounded-lg px-5 py-2.5 flex items-center gap-3 shadow-lg shadow-slate-200/60">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-petronas" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-sm font-bold text-silver">
                        {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                    </span>
                </div>
            </div>
        </div>
    </header>

    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
        
        {{-- SUMMARY CARDS --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Revenue -->
            <div class="bg-carbon p-6 rounded-lg border border-carbonSoft relative overflow-hidden shadow-lg">
                <div class="absolute right-0 top-0 h-full w-1.5 bg-success"></div>
                <p class="text-xs font-bold text-muted uppercase tracking-widest">Total Pendapatan</p>
                <h3 class="text-3xl font-bold text-slate-800 mt-2 font-mono">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h3>
                <p class="text-[10px] text-muted mt-2 uppercase tracking-wide">Dari Sales Order (Confirmed/Shipped)</p>
            </div>

            <!-- Total COGS -->
            <div class="bg-carbon p-6 rounded-lg border border-carbonSoft relative overflow-hidden shadow-lg">
                <div class="absolute right-0 top-0 h-full w-1.5 bg-danger"></div>
                <p class="text-xs font-bold text-muted uppercase tracking-widest">HPP (Modal Produksi)</p>
                <h3 class="text-3xl font-bold text-slate-800 mt-2 font-mono">Rp {{ number_format($totalCogs, 0, ',', '.') }}</h3>
                <p class="text-[10px] text-muted mt-2 uppercase tracking-wide">Total Modal Produksi Terjual</p>
            </div>

            <!-- Gross Profit -->
            <div class="bg-carbon p-6 rounded-lg border border-carbonSoft relative overflow-hidden shadow-lg">
                <div class="absolute right-0 top-0 h-full w-1.5 bg-petronas"></div>
                <p class="text-xs font-bold text-muted uppercase tracking-widest">Laba Kotor (Gross Profit)</p>
                <h3 class="text-3xl font-bold text-petronas mt-2 font-mono">Rp {{ number_format($grossProfit, 0, ',', '.') }}</h3>
                <p class="text-xs font-bold mt-2 tracking-wide {{ $profitMargin > 0 ? 'text-success' : 'text-danger' }}">
                    Margin Keuntungan: {{ number_format($profitMargin, 2) }}%
                </p>
            </div>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
            <h2 class="text-lg font-bold text-petronas">Rincian Kontribusi per Produk</h2>
        </div>

        {{-- TABLE SECTION --}}
        <div class="overflow-x-auto rounded-lg border border-carbon">
            <table class="w-full text-sm">
                <thead class="bg-carbon text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left text-muted border-b border-carbonSoft">Nama Produk</th>
                        <th class="px-4 py-3 text-center text-muted border-b border-carbonSoft">Qty Terjual</th>
                        <th class="px-4 py-3 text-right text-muted border-b border-carbonSoft">Pendapatan</th>
                        <th class="px-4 py-3 text-right text-muted border-b border-carbonSoft">Total HPP</th>
                        <th class="px-4 py-3 text-right text-petronas border-b border-carbonSoft">Laba Kotor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon/50">
                    @forelse($salesDetails as $detail)
                        @php
                            $detailProfit = $detail->total_revenue - $detail->total_cogs;
                        @endphp
                        <tr class="hover:bg-carbon transition-colors">
                            {{-- PRODUCT INFO --}}
                            <td class="px-4 py-3 font-bold text-silver">
                                {{ $detail->product_name }}
                            </td>
                            
                            {{-- QTY --}}
                            <td class="px-4 py-3 text-center font-mono text-silver text-xs">
                                {{ number_format($detail->total_qty, 0, ',', '.') }}
                            </td>
                            
                            {{-- PENDAPATAN --}}
                            <td class="px-4 py-3 text-right font-mono text-success font-bold">
                                Rp {{ number_format($detail->total_revenue, 0, ',', '.') }}
                            </td>
                            
                            {{-- HPP --}}
                            <td class="px-4 py-3 text-right font-mono text-danger font-bold">
                                Rp {{ number_format($detail->total_cogs, 0, ',', '.') }}
                            </td>
                            
                            {{-- LABA KOTOR --}}
                            <td class="px-4 py-3 text-right font-mono text-petronas font-bold">
                                Rp {{ number_format($detailProfit, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-muted italic bg-slate-100">
                                Belum ada data penjualan yang dikonfirmasi/dikirim pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                {{-- TABLE FOOTER (GRAND TOTAL) --}}
                <tfoot class="bg-carbon font-bold text-slate-800 border-t border-carbonSoft">
                    <tr>
                        <td class="px-4 py-4 text-right tracking-widest text-xs text-muted" colspan="2">GRAND TOTAL</td>
                        <td class="px-4 py-4 text-right font-mono text-success">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right font-mono text-danger">Rp {{ number_format($totalCogs, 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right font-mono text-petronas text-base">Rp {{ number_format($grossProfit, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </section>

</main>

</body>
</html>