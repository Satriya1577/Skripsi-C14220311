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

        <p class="text-sm text-muted mt-2">Konversi Sistem: 1 {{ $pUnit }} = {{ number_format($factor) }} {{ $bUnit }}</p>
    </header>

    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
        <h2 class="text-lg font-bold text-petronas">Informasi Material (Satuan Beli)</h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
            <div class="bg-carbon rounded-lg p-4">
                <p class="text-xs text-muted uppercase">Kode</p>
                <p class="font-semibold">{{ $material->code }}</p>
            </div>
            <div class="bg-carbon rounded-lg p-4">
                <p class="text-xs text-muted uppercase">Satuan Beli</p>
                <p class="font-semibold uppercase text-petronas">{{ $pUnit }}</p>
            </div>
            <div class="bg-carbon rounded-lg p-4">
                <p class="text-xs text-muted uppercase">Stok Sistem ({{ $pUnit }})</p>
                <p class="text-lg font-bold">{{ number_format($material->current_stock / $factor, 2) }}</p>
            </div>
            <div class="bg-carbon rounded-lg p-4">
                <p class="text-xs text-muted uppercase">Estimasi Harga / {{ $pUnit }}</p>
                <p class="text-lg font-bold">{{ number_format($material->price_per_unit * $factor, 2) }}</p>
            </div>
            <div class="bg-carbon rounded-lg p-4">
                <p class="text-xs text-muted uppercase">Lead Time</p>
                <p class="font-semibold">{{ $material->lead_time_days }} hari</p>
            </div>
            <div class="bg-carbon rounded-lg p-4 md:col-span-3">
                <p class="text-xs text-muted uppercase">Base Unit (Internal)</p>
                <p class="text-muted">
                    Sistem menyimpan stok dalam <b>{{ number_format($material->current_stock) }} {{ $bUnit }}</b> 
                    dengan HPP Rp {{ number_format($material->price_per_unit, 2) }} / {{ $bUnit }}.
                </p>
            </div>
        </div>
    </section>

    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-petronas">Input Barang Masuk (Purchase)</h2>
            <span class="text-xs text-muted bg-carbon px-2 py-1 rounded">Type: IN</span>
        </div>

        @if($material->is_active)
            <form action="{{ route('materials.in.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @csrf
                <input type="hidden" name="material_id" value="{{ $material->id }}">

                <div>
                    <label class="text-xs text-muted uppercase tracking-wide">Tanggal Transaksi</label>
                    <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" required
                        class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none text-silver [color-scheme:dark]">
                </div>

                <div>
                    <label class="text-xs text-muted uppercase tracking-wide font-bold text-petronas">Jumlah Beli ({{ $pUnit }})</label>
                    <div class="relative mt-1">
                        <input type="number" step="0.01" name="qty" required placeholder="0"
                            class="w-full pl-4 pr-12 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <span class="text-muted text-xs uppercase">{{ $pUnit }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-xs text-muted uppercase tracking-wide">Harga Beli per {{ $pUnit }}</label>
                    <div class="relative mt-1">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <span class="text-muted text-xs">Rp</span>
                        </div>
                        <input type="number" step="0.01" name="price_per_unit" required placeholder="0"
                            class="w-full pl-10 pr-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none">
                    </div>
                    <p class="text-[10px] text-muted mt-1">Masukkan harga total per 1 {{ $pUnit }} (Bukan per {{ $bUnit }})</p>
                </div>

                <div class="md:col-span-3 flex justify-end">
                    <button type="submit" class="bg-petronas text-blackBase font-bold px-6 py-2 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20 flex items-center gap-2">
                         Simpan Pembelian
                    </button>
                </div>
            </form>
        @else
            <div class="p-4 bg-carbon rounded-lg border border-red-900/30 flex items-center gap-3">
                <span class="text-red-500 text-xl">🚫</span>
                <p class="text-sm text-muted">Form pembelian dikunci karena material ini berstatus <strong>Non-Active</strong>.</p>
            </div>
        @endif
    </section>

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
                            class="w-full pl-4 pr-12 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <span class="text-muted text-xs uppercase">{{ $pUnit }}</span>
                        </div>
                    </div>
                    <p class="text-xs text-muted mt-1">Masukkan jumlah stok fisik real saat ini.</p>
                </div>

                <div>
                    <label class="text-xs text-muted uppercase tracking-wide">Catatan</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Alasan penyesuaian..."
                        class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none">
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
                                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-yellow-500 focus:outline-none">
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
                        <th class="px-3 py-2 text-right text-muted">Price / {{ $pUnit }}</th>
                        <th class="px-3 py-2 text-right text-muted">Total</th>
                        <th class="px-3 py-2 text-left text-muted">Desc</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr class="border-b border-carbon hover:bg-carbon transition-colors">
                            <td class="px-3 py-2 font-semibold text-silver">{{ $transaction->transaction_date?->format('d M Y') ?? '-' }}</td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase
                                    {{ $transaction->type == 'in' ? 'bg-green-900/30 text-green-400' : '' }}
                                    {{ $transaction->type == 'out' ? 'bg-red-900/30 text-red-400' : '' }}
                                    {{ $transaction->type == 'adjustment' ? 'bg-blue-900/30 text-blue-400' : '' }}">
                                    {{ $transaction->type }}
                                </span>
                            </td>
                            <td class="px-3 py-2 font-semibold text-silver text-right">
                                {{ number_format($transaction->qty / $factor, 2) }}
                            </td>
                            <td class="px-3 py-2 text-right text-muted">
                                {{ $transaction->price_per_unit ? number_format($transaction->price_per_unit * $factor, 2) : '-' }}
                            </td>
                            <td class="px-3 py-2 font-semibold text-silver text-right">
                                {{ $transaction->total_price ? number_format($transaction->total_price, 2) : '-' }}
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