<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Material Detail | Production Planning System</title>
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
                        muted: '#9DA3A6'
                    }
                }
            }
        }
    </script>
    <style>
        /* Chrome, Safari, Edge, Opera */
        input[type=number]::-webkit-outer-spin-button,
        input[type=number]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        /* Firefox */
        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
</head>

<body class="bg-blackBase text-silver min-h-screen">

<main class="max-w-7xl mx-auto px-6 py-6 space-y-8">

    @php
        $factor = $material->conversion_factor > 0 ? $material->conversion_factor : 1;
        $pUnit  = $material->purchase_unit;
        $bUnit  = $material->unit;
    @endphp

    <nav aria-label="breadcrumb" class="text-xs text-muted">
        <ol class="flex items-center space-x-2">
            <li><a href="{{ route('home.index') }}" class="hover:text-petronas transition-colors">Home</a></li>
            <li class="opacity-40">/</li>
            <li><a href="{{ route('materials.index') }}" class="hover:text-petronas transition-colors">Materials</a></li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold">Detail</li>
        </ol>
    </nav>

    <x-alert-messages />

    <header>
        <p class="text-xs uppercase tracking-widest text-muted">Inventory Control</p>
        
        <div class="flex items-center gap-4 mt-1">
            <h1 class="text-3xl font-extrabold text-petronas">{{ $material->name }}</h1>
            
            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase border
                {{ $material->is_active ? 'bg-green-900/30 text-green-400 border-green-500/30' : 'bg-red-900/30 text-red-400 border-red-500/30' }}">
                {{ $material->is_active ? 'Active' : 'Non-Active' }}
            </span>
        </div>

        <p class="text-sm text-muted mt-2">
            <span class="bg-carbon px-2 py-1 rounded text-xs font-mono mr-2 border border-carbonSoft">{{ $material->code }}</span>
            Konversi Sistem: 1 {{ $pUnit }} = {{ number_format($factor) }} {{ $bUnit }}
        </p>
    </header>

    {{-- SECTION 1: INFORMASI MATERIAL --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-bold text-petronas">Informasi Material (Satuan Beli)</h2>
            <div class="text-xs text-muted bg-carbon px-3 py-1 rounded-full border border-carbon/50">
                Kategori: <span class="text-silver font-bold uppercase">{{ $material->category_type }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
            <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                <p class="text-xs text-muted uppercase tracking-wide mb-1">Stok Fisik ({{ $pUnit }})</p>
                <p class="text-2xl font-bold text-silver">{{ number_format($material->current_stock / $factor) }}</p>
            </div>
            
            <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                <p class="text-xs text-muted uppercase tracking-wide mb-1">Incoming Order</p>
                <p class="text-2xl font-bold text-blue-400">{{ number_format($material->ordered_stock / $factor) }} <span class="text-xs text-muted font-normal">{{ $pUnit }}</span></p>
            </div>

            <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                <p class="text-xs text-muted uppercase tracking-wide mb-1">Safety Stock</p>
                <div class="flex items-end gap-1">
                    <p class="text-lg font-bold text-silver">{{ number_format($material->safety_stock / $factor) }}</p>
                    <span class="text-xs text-muted mb-1">{{ $pUnit }}</span>
                </div>
                <p class="text-[10px] text-muted border-t border-white/10 mt-1 pt-1">
                    ROP: {{ number_format($material->reorder_point / $factor) }} {{ $pUnit }}
                </p>
            </div>

            <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                <p class="text-xs text-muted uppercase tracking-wide mb-1">Estimasi Harga / {{ $pUnit }}</p>
                <p class="text-2xl font-bold text-petronas">Rp {{ number_format($material->price_per_unit * $factor, 2, ',', '.') }}</p>
            </div>
        </div>

        {{-- LEAD TIME INFO --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                <div class="flex justify-between items-start mb-2">
                    <p class="text-xs text-muted uppercase tracking-wide">Lead Time Management</p>
                    <span class="text-[10px] uppercase px-2 py-0.5 rounded border 
                        {{ $material->is_manual_lead_time === 'manual' ? 'bg-gray-800 text-gray-300 border-gray-600' : 'bg-petronas/10 text-petronas border-petronas/30' }}">
                        {{ $material->is_manual_lead_time === 'manual' ? 'Manual' : 'Automatic' }}
                    </span>
                </div>
                <div class="flex gap-4 items-center">
                    <div>
                        <span class="text-xs text-muted block">Range (Min-Max)</span>
                        <span class="text-lg font-bold text-silver">{{ $material->min_lead_time_days }} - {{ $material->max_lead_time_days }} Hari</span>
                    </div>
                    <div class="h-8 w-px bg-white/10"></div>
                    <div>
                        <span class="text-xs text-muted block">Rata-rata Aktual</span>
                        <span class="text-lg font-bold text-petronas">{{ number_format($material->lead_time_average, 1) }} Hari</span>
                    </div>
                </div>
            </div>

            <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                <p class="text-xs text-muted uppercase tracking-wide mb-1">Packaging Detail</p>
                <p class="text-sm text-silver">
                    <span class="font-bold">1 {{ $pUnit }}</span> berisi 
                    <span class="font-bold text-petronas">{{ (float)$material->packaging_size }} {{ $material->packaging_unit }}</span>
                </p>
                <p class="text-[10px] text-muted mt-2">
                    *Sistem mengkonversi {{ $material->packaging_unit }} menjadi {{ $material->unit }} dengan faktor {{ number_format($factor / ($material->packaging_size ?: 1)) }}x
                </p>
            </div>
        </div>
    </section>

    {{-- SECTION 2: STOCK ADJUSTMENT --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
        <h2 class="text-lg font-bold text-petronas">Stock Adjustment (Opname)</h2>

        @if($material->is_active)
            <form action="{{ route('materials.adjustment.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @csrf
                <input type="hidden" name="material_id" value="{{ $material->id }}">

                <div>
                    <label class="text-xs text-muted uppercase tracking-wide">Jumlah Stok Aktual ({{ $pUnit }})</label>
                    <div class="relative mt-1">
                        <input type="number" step="0.01" name="actual_qty" value="{{ old('actual_qty') }}" required
                            class="w-full pl-4 pr-12 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none text-silver">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <span class="text-muted text-xs uppercase">{{ $pUnit }}</span>
                        </div>
                    </div>
                    <p class="text-xs text-muted mt-1">Masukkan jumlah stok fisik real saat ini.</p>
                </div>

                <div>
                    <label class="text-xs text-muted uppercase tracking-wide">Catatan</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Alasan penyesuaian..."
                        class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none text-silver">
                </div>

                @if($material->price_per_unit == 0)
                    <div class="md:col-span-2 bg-yellow-900/20 border border-yellow-700/50 p-4 rounded-lg">
                        <div class="flex items-start space-x-3">
                            <div class="text-yellow-500 mt-1">⚠️</div>
                            <div class="w-full">
                                <p class="text-sm text-yellow-500 font-bold">Harga Dasar Belum Ada</p>
                                <p class="text-xs text-muted mb-2">Mohon isi estimasi harga pasar per {{ $pUnit }}.</p>
                                <label class="text-xs text-muted uppercase tracking-wide">Harga Estimasi per {{ $pUnit }}</label>
                                <input type="number" name="manual_price" 
                                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-yellow-500 focus:outline-none text-silver">
                            </div>
                        </div>
                    </div>
                @endif

                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="bg-yellow-600 text-white font-bold px-6 py-2 rounded-lg hover:bg-yellow-700 transition shadow-lg shadow-yellow-900/20">
                        Simpan Adjustment
                    </button>
                </div>
            </form>
        @else
             <div class="p-4 bg-carbon rounded-lg border border-red-900/30 flex items-center gap-3">
                <span class="text-red-500 text-xl">🔒</span>
                <p class="text-sm text-muted">Form adjustment dikunci karena material ini berstatus <strong>Non-Active</strong>.</p>
            </div>
        @endif
    </section>

    {{-- SECTION 3: RIWAYAT TRANSAKSI --}}
    <section class="bg-carbonSoft rounded-xl p-6">
        <div class="flex justify-between items-end mb-4">
            <h2 class="text-lg font-bold text-petronas">Riwayat Transaksi</h2>
            <span class="text-xs text-muted italic">Semua angka ditampilkan dalam satuan {{ $pUnit }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-carbon">
                    <tr>
                        <th class="px-3 py-2 text-left text-muted">Date</th>
                        <th class="px-3 py-2 text-left text-muted">Type</th>
                        <th class="px-3 py-2 text-right text-muted">Qty ({{ $pUnit }})</th>
                        <th class="px-3 py-2 text-right text-muted">Buy Price / {{ $pUnit }}</th>
                        <th class="px-3 py-2 text-right text-muted">Total</th>
                        <th class="px-3 py-2 text-left text-muted">Desc</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr class="border-b border-carbon hover:bg-carbon transition-colors">
                            <td class="px-3 py-2 font-semibold text-silver">
                                {{ $transaction->transaction_date?->format('d M Y') ?? '-' }}
                            </td>

                            <td class="px-3 py-2">
                                @if($transaction->type == 'in')
                                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase bg-green-900/30 text-green-400 border border-green-500/30">
                                        PURCHASE IN
                                    </span>
                                @elseif($transaction->type == 'out')
                                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase bg-red-900/30 text-red-400 border border-red-500/30">
                                        USAGE OUT
                                    </span>
                                @elseif($transaction->type == 'adjustment')
                                    @if($transaction->qty >= 0)
                                        <span class="px-2 py-1 rounded text-[10px] font-bold uppercase bg-blue-900/30 text-blue-400 border border-blue-500/30">
                                            ADJ IN
                                        </span>
                                    @else
                                        <span class="px-2 py-1 rounded text-[10px] font-bold uppercase bg-orange-900/30 text-orange-400 border border-orange-500/30">
                                            ADJ OUT
                                        </span>
                                    @endif
                                @endif
                            </td>

                            <td class="px-3 py-2 font-semibold text-right {{ $transaction->qty > 0 ? 'text-green-400' : 'text-red-400' }}">
                                {{ $transaction->qty > 0 ? '+' : '' }}{{ number_format($transaction->qty / $factor, 2) }}
                            </td>

                            <td class="px-3 py-2 text-right text-muted">
                                Rp {{ $transaction->price_per_unit ? number_format($transaction->price_per_unit * $factor, 2, ',', '.') : '-' }}
                            </td>

                            <td class="px-3 py-2 font-semibold text-silver text-right">
                                Rp {{ $transaction->total_price ? number_format($transaction->total_price, 2, ',', '.') : '-' }}
                            </td>

                            <td class="px-3 py-2 text-muted text-xs max-w-xs truncate">
                                {{ $transaction->description ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada riwayat transaksi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($transactions->hasPages())
            <div class="mt-4 flex justify-between text-sm text-muted">
                <div>Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() ?? 0 }} transactions</div>
                {{ $transactions->links('pagination::tailwind') }}
            </div>
        @endif
    </section>

</main>

</body>
</html>