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
                        blackBase: '#000000',
                        carbon: '#1B1D1F',
                        carbonSoft: '#24272A',
                        silver: '#C8CCCE',
                        petronas: '#00A19B',
                        muted: '#9DA3A6',
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
            {{-- Level 1: Home --}}
            <li>
                <a href="{{ route('home.index') }}" class="hover:text-petronas transition-colors">
                    Home
                </a>
            </li>
            
            {{-- Separator --}}
            <li class="opacity-40">/</li>
            
            {{-- Level 2: Reports Center --}}
            <li>
                <a href="{{ route('reports.index') }}" class="hover:text-petronas transition-colors">
                    Reports Center
                </a>
            </li>
            
            {{-- Separator --}}
            <li class="opacity-40">/</li>
            
            {{-- Level 3: Current Page --}}
            <li class="text-petronas font-semibold" aria-current="page">
                Stock Movements
            </li>
        </ol>
    </nav>

    <header class="border-b border-carbon pb-6">
        <div class="flex items-center gap-3">
            <p class="text-xs uppercase tracking-widest text-muted">Inventory Report</p>
        </div>
        <h1 class="text-3xl font-extrabold text-white mt-1">Laporan Mutasi Stok</h1>
        <p class="text-sm text-muted mt-1">Rekapitulasi seluruh transaksi masuk dan keluar barang.</p>
    </header>

    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <h2 class="text-lg font-bold text-petronas">Data Transaksi</h2>
            
            <form action="#" method="GET" class="flex gap-2 w-full md:w-auto">
                <input type="date" name="start_date" value="{{ request('start_date') }}" 
                    class="bg-carbon border border-muted/30 text-xs text-silver rounded-lg px-4 py-2 focus:outline-none focus:border-petronas">
                <button type="submit" class="px-4 py-2 rounded-lg bg-petronas text-blackBase text-xs font-bold hover:bg-petronas/90 transition">
                    Filter
                </button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-lg border border-carbon">
            <table class="w-full text-sm">
                <thead class="bg-carbon text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left text-muted border-b border-carbonSoft">Tanggal</th>
                        <th class="px-4 py-3 text-left text-muted border-b border-carbonSoft">Produk</th>
                        <th class="px-4 py-3 text-center text-muted border-b border-carbonSoft">Tipe</th>
                        <th class="px-4 py-3 text-left text-muted border-b border-carbonSoft">Keterangan</th>
                        <th class="px-4 py-3 text-right text-muted border-b border-carbonSoft">Masuk</th>
                        <th class="px-4 py-3 text-right text-muted border-b border-carbonSoft">Keluar</th>
                        <th class="px-4 py-3 text-right text-silver font-bold border-b border-carbonSoft bg-carbon/50">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon/50">
                    @forelse($transactions as $trx)
                        <tr class="hover:bg-carbon transition-colors">
                            
                            <td class="px-4 py-3 text-silver font-mono text-xs whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($trx->transaction_date)->format('d/m/Y') }}
                                <span class="block text-[10px] text-muted">{{ $trx->created_at->format('H:i') }}</span>
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-bold text-silver">{{ $trx->product->name }}</div>
                                <div class="text-[10px] text-petronas font-mono">{{ $trx->product->code }}</div>
                            </td>

                            <td class="px-4 py-3 text-center">
                                @php
                                    $typeStyle = match($trx->type) {
                                        'production_in' => 'text-success bg-success/10 border-success/30',
                                        'sales_out'     => 'text-danger bg-danger/10 border-danger/30',
                                        'return_in'     => 'text-warning bg-warning/10 border-warning/30',
                                        'adjustment'    => 'text-blue-400 bg-blue-400/10 border-blue-400/30',
                                        default         => 'text-muted bg-carbon border-muted'
                                    };
                                    $typeName = str_replace('_', ' ', strtoupper($trx->type));
                                @endphp
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase border {{ $typeStyle }}">
                                    {{ $typeName }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-silver text-xs">
                                {{ $trx->description }}
                                @if($trx->sales_order_id)
                                    <div class="text-[10px] text-muted mt-0.5">Ref: {{ $trx->salesOrder->so_code ?? '-' }}</div>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right font-mono text-success">
                                @if(in_array($trx->type, ['production_in', 'return_in']) || ($trx->type == 'adjustment' && $trx->qty > 0))
                                    +{{ number_format($trx->qty) }}
                                @else
                                    <span class="text-carbonSoft">-</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right font-mono text-danger">
                                @if($trx->type == 'sales_out' || ($trx->type == 'adjustment' && $trx->qty < 0))
                                    -{{ number_format(abs($trx->qty)) }}
                                @else
                                    <span class="text-carbonSoft">-</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right font-mono font-bold text-white bg-carbon/20">
                                {{ number_format($trx->current_stock_balance) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-muted italic bg-carbon/20">
                                Belum ada data transaksi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $transactions->links('pagination::tailwind') }}
        </div>
    </section>

</main>

</body>
</html>