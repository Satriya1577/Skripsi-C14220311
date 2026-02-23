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
                        blackBase: '#000000',
                        carbon: '#1B1D1F',
                        carbonSoft: '#24272A',
                        silver: '#C8CCCE',
                        petronas: '#00A19B',
                        muted: '#9DA3A6',
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
            <li class="text-petronas font-bold pointer-events-none" aria-current="page">Material Stock Card</li>
        </ol>
    </nav>

    <header class="border-b border-carbon pb-6">
        <div class="flex items-center gap-3">
            <p class="text-xs uppercase tracking-widest text-muted">Raw Material Report</p>
        </div>
        <h1 class="text-3xl font-extrabold text-white mt-1">Kartu Stok Bahan Baku</h1>
        <p class="text-sm text-muted mt-1">Rekapitulasi mutasi masuk (Pembelian) dan keluar (Produksi) material.</p>
    </header>

    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
        
        {{-- FILTER SECTION --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <h2 class="text-lg font-bold text-petronas">Riwayat Transaksi</h2>
            
            <form action="{{ route('reports.material') }}" method="GET" class="flex gap-2 w-full md:w-auto">
                <input type="date" name="start_date" value="{{ request('start_date') }}" 
                    class="bg-carbon border border-muted/30 text-xs text-silver rounded-lg px-4 py-2 focus:outline-none focus:border-petronas transition-colors">
                <button type="submit" class="px-4 py-2 rounded-lg bg-petronas text-blackBase text-xs font-bold hover:bg-petronas/90 transition shadow-lg shadow-petronas/20">
                    Filter
                </button>
            </form>
        </div>

        {{-- TABLE SECTION --}}
        <div class="overflow-x-auto rounded-lg border border-carbon">
            <table class="w-full text-sm">
                <thead class="bg-carbon text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left text-muted border-b border-carbonSoft">Tanggal</th>
                        <th class="px-4 py-3 text-left text-muted border-b border-carbonSoft">Material (Snapshot)</th>
                        <th class="px-4 py-3 text-center text-muted border-b border-carbonSoft">Tipe</th>
                        <th class="px-4 py-3 text-left text-muted border-b border-carbonSoft">Keterangan</th>
                        <th class="px-4 py-3 text-right text-muted border-b border-carbonSoft">Konversi (Ref)</th>
                        <th class="px-4 py-3 text-right text-muted border-b border-carbonSoft">Qty Mutasi</th>
                        <th class="px-4 py-3 text-right text-muted border-b border-carbonSoft">Valuasi</th>
                        <th class="px-4 py-3 text-right text-silver font-bold border-b border-carbonSoft bg-carbon/50">Saldo Akhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon/50">
                    @forelse($transactions as $trx)
                        <tr class="hover:bg-carbon transition-colors">
                            
                            {{-- TANGGAL --}}
                            <td class="px-4 py-3 text-silver font-mono text-xs whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($trx->transaction_date)->format('d/m/Y') }}
                                <span class="block text-[10px] text-muted">{{ $trx->created_at->format('H:i') }}</span>
                            </td>

                            {{-- MATERIAL INFO (MENGGUNAKAN SNAPSHOT) --}}
                            <td class="px-4 py-3">
                                <div class="font-bold text-silver">{{ $trx->material_name_snapshot }}</div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    {{-- Kode ambil dari relasi master untuk referensi --}}
                                    <span class="text-[10px] text-petronas font-mono bg-petronas/10 px-1 rounded">
                                        {{ $trx->material->code ?? 'Unknown' }}
                                    </span>
                                    <span class="text-[10px] text-muted">
                                        (Base: {{ $trx->material_unit_snapshot }})
                                    </span>
                                </div>
                            </td>

                            {{-- TIPE TRANSAKSI --}}
                            <td class="px-4 py-3 text-center">
                                @php
                                    $typeStyle = match($trx->type) {
                                        'in'         => 'text-success bg-success/10 border-success/30',
                                        'out'        => 'text-danger bg-danger/10 border-danger/30',
                                        'adjustment' => 'text-info bg-info/10 border-info/30',
                                        default      => 'text-muted bg-carbon border-muted'
                                    };
                                    $typeLabel = match($trx->type) {
                                        'in'         => 'PURCHASE',
                                        'out'        => 'USAGE',
                                        'adjustment' => 'ADJUST',
                                        default      => strtoupper($trx->type)
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase border {{ $typeStyle }}">
                                    {{ $typeLabel }}
                                </span>
                            </td>

                            {{-- KETERANGAN & REFERENSI --}}
                            <td class="px-4 py-3 text-silver text-xs max-w-xs truncate" title="{{ $trx->description }}">
                                {{ $trx->description }}
                                
                                @if($trx->purchase_order_id)
                                    <div class="text-[10px] text-muted mt-0.5 flex items-center gap-1">
                                        <i class="bi bi-receipt"></i> Ref PO: {{ $trx->purchaseOrder->po_number ?? '-' }}
                                    </div>
                                @endif

                                @if($trx->production_realization_id)
                                    <div class="text-[10px] text-muted mt-0.5 flex items-center gap-1">
                                        <i class="bi bi-gear-wide"></i> Ref Prod: #{{ $trx->production_realization_id }}
                                    </div>
                                @endif
                            </td>

                            {{-- INFO KONVERSI SNAPSHOT --}}
                            <td class="px-4 py-3 text-right text-[10px] text-muted font-mono">
                                <div>1 {{ $trx->purchase_unit_snapshot }}</div>
                                <div class="text-silver">= {{ number_format($trx->material_conversion_factor_snapshot, 0) }} {{ $trx->material_unit_snapshot }}</div>
                            </td>

                            {{-- QTY MUTASI (BASE + PURCHASE UNIT) --}}
                            <td class="px-4 py-3 text-right">
                                @php
                                    $isPositive = ($trx->type == 'in' || ($trx->type == 'adjustment' && $trx->qty > 0));
                                    $colorClass = $isPositive ? 'text-success' : 'text-danger';
                                    $sign = $isPositive ? '+' : '';
                                    
                                    // Hitung estimasi satuan beli (Qty Base / Faktor Konversi Snapshot)
                                    $qtyPurch = 0;
                                    if($trx->material_conversion_factor_snapshot > 0) {
                                        $qtyPurch = $trx->qty / $trx->material_conversion_factor_snapshot;
                                    }
                                @endphp

                                {{-- 1. Tampilkan Satuan Besar (Purchase Unit) --}}
                                <div class="{{ $colorClass }} font-bold text-xs">
                                    {{ $sign }}{{ number_format(abs($qtyPurch), 2) }} {{ $trx->purchase_unit_snapshot }}
                                </div>

                                {{-- 2. Tampilkan Satuan Dasar (Base Unit - Real Value) --}}
                                <div class="text-[10px] text-muted font-mono mt-0.5">
                                    ({{ $sign }}{{ number_format(abs($trx->qty), 2) }} {{ $trx->material_unit_snapshot }})
                                </div>
                            </td>

                            {{-- VALUASI (TOTAL PRICE) --}}
                            <td class="px-4 py-3 text-right text-xs">
                                @if($trx->total_price)
                                    <div class="text-silver">Rp {{ number_format($trx->total_price, 0, ',', '.') }}</div>
                                    <div class="text-[10px] text-muted">@ Rp {{ number_format($trx->price_per_unit, 2, ',', '.') }}</div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            {{-- SALDO AKHIR --}}
                            <td class="px-4 py-3 text-right font-mono font-bold text-white bg-carbon/20">
                                {{ number_format($trx->current_stock_balance, 2) }} 
                                <span class="text-[10px] text-muted font-normal">{{ $trx->material_unit_snapshot }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-muted italic bg-carbon/20">
                                Belum ada data transaksi material.
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

{{-- Bootstrap Icons --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

</body>
</html>