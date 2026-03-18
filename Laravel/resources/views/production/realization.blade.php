<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Batch Realization: {{ $batch->batch_number }} | Production</title>
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

    {{-- BREADCRUMB --}}
    <nav aria-label="breadcrumb" class="text-xs text-muted">
        <ol class="flex items-center space-x-2">
            <li><a href="{{ route('home.index') }}" class="hover:text-petronas transition-colors">Home</a></li>
            <li class="opacity-40">/</li>
            <li><a href="{{ route('production.index') }}" class="hover:text-petronas transition-colors">Production</a></li>
            <li class="opacity-40">/</li>
            {{-- Sesuaikan route ini dengan nama route Anda yang sebenarnya --}}
            <li><a href="{{ route('production.showPlan', $product) }}" class="hover:text-petronas transition-colors">Plan-{{ $product->code }}</a></li>
            <li class="opacity-40">/</li>
            @if($productionPlan)
                <li><a href="{{ route('production.showPlanDetails', $productionPlan) }}" class="hover:text-petronas transition-colors">Plan-{{ $product->code }}-Details</a></li>
            @else
                <li class="text-muted">Plan details unavailable</li>
            @endif
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold" aria-current="page">Realization-{{ $batch->batch_number }}</li>
        </ol>
    </nav>

    <x-alert-messages />

    {{-- HEADER & BATCH SUMMARY --}}
    <header class="border-b border-carbon pb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div>
                <p class="text-xs uppercase tracking-widest text-muted mb-1">Batch Realization</p>
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-extrabold text-petronas">{{ $batch->batch_number }}</h1>
                    @if(is_null($batch->end_date))
                        <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide bg-warning/10 text-warning border border-warning/30 flex items-center gap-1">
                            <span class="animate-pulse w-1.5 h-1.5 bg-warning rounded-full"></span> In Progress
                        </span>
                    @else
                        <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide bg-success/10 text-success border border-success/30">
                            Completed
                        </span>
                    @endif
                </div>
                <p class="text-sm text-silver mt-2 flex flex-wrap items-center gap-2">
                    <span class="bg-carbon px-2 py-0.5 rounded text-xs font-mono border border-carbonSoft">{{ $product->code }}</span>
                    <span><strong>{{ $product->name }}</strong></span>
                    <span class="text-muted">|</span>
                    <span>Started: <strong>{{ \Carbon\Carbon::parse($batch->start_date)->format('d M Y') }}</strong></span>
                </p>
            </div>
        </div>

        {{-- Progress Info Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6 items-stretch">
            <div class="bg-carbonSoft border border-carbon rounded-xl p-4 flex justify-between items-center">
                <div>
                    <p class="text-xs text-muted uppercase tracking-wide">Target Batch Qty</p>
                    <p class="text-2xl font-bold text-white">{{ number_format($batch->qty_produced, 0, ',', '.') }}</p>
                </div>
                <div class="p-3 bg-carbon rounded-lg text-petronas">🎯</div>
            </div>
            
            <div class="bg-carbonSoft border border-carbon rounded-xl p-4 flex justify-between items-center">
                <div>
                    <p class="text-xs text-muted uppercase tracking-wide">Realized Qty</p>
                    <p class="text-2xl font-bold text-petronas">{{ number_format($totalRealized ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="p-3 bg-petronas/10 rounded-lg text-petronas">⚙️</div>
            </div>
            
            <div class="bg-carbonSoft border border-carbon rounded-xl p-4 flex justify-between items-center">
                <div>
                    <p class="text-xs text-muted uppercase tracking-wide">Remaining Need</p>
                    <p class="text-2xl font-bold {{ ($remainingBatchQty ?? 0) > 0 ? 'text-warning' : 'text-success' }}">
                        {{ number_format(max(0, $remainingBatchQty ?? 0), 0, ',', '.') }}
                    </p>
                </div>
                <div class="p-3 bg-carbon rounded-lg {{ ($remainingBatchQty ?? 0) > 0 ? 'text-warning' : 'text-success' }}">
                    {{ ($remainingBatchQty ?? 0) > 0 ? '⚠️' : '✅' }}
                </div>
            </div>
        </div>
    </header>

    {{-- SECTION 1: FORM INPUT REALIZATION --}}
    @if(is_null($batch->end_date))
    <section class="bg-carbonSoft rounded-xl p-6 border border-petronas/30 shadow-lg shadow-petronas/5 relative overflow-hidden">
        <div class="relative z-10">
            <h2 class="text-lg font-bold text-petronas mb-1">Add Production Realization</h2>
            <p class="text-xs text-muted mb-4">Catat jumlah barang jadi yang berhasil diproduksi pada hari tertentu untuk batch ini.</p>
            
            {{-- Form Route sesuaikan dengan web.php Anda --}}
            <form action="{{ route('production.storeRealization') }}" method="POST">
                @csrf
                <input type="hidden" name="production_batch_id" value="{{ $batch->id }}">
                
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    {{-- <div class="w-full md:w-1/3">
                        <label class="text-xs text-silver font-semibold uppercase tracking-wide mb-2 block">Tanggal Produksi</label>
                        <input type="date" name="production_date" value="{{ date('Y-m-d') }}" required
                            class="block w-full p-3 bg-carbon border border-muted/30 rounded-lg text-white focus:ring-1 focus:ring-petronas focus:border-petronas transition [color-scheme:dark]">
                    </div> --}}

                    <div class="w-full md:w-1/3">
                        <label class="text-xs text-silver font-semibold uppercase tracking-wide mb-2 block">Jumlah Produksi (Qty)</label>
                        <div class="relative">
                            <input type="number" name="qty_produced" value="{{ max(0, $remainingBatchQty ?? 0) }}" min="1" required 
                                class="block w-full p-3 bg-carbon border border-muted/30 rounded-lg text-white font-mono focus:ring-1 focus:ring-petronas focus:border-petronas pr-12 transition">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <span class="text-muted text-xs uppercase">{{ $product->packaging ?? 'Pcs' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="w-full md:w-auto">
                        <button type="submit" class="w-full md:w-auto bg-petronas text-blackBase font-bold px-8 py-3 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20 flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span>Save Input</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>
    @else
    <div class="bg-success/10 border border-success/30 rounded-xl p-4 flex items-center gap-3 text-success">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
        </svg>
        <div>
            <p class="font-bold">Batch Completed</p>
            <p class="text-xs">Input realisasi ditutup karena status batch ini sudah selesai.</p>
        </div>
    </div>
    @endif

    {{-- SECTION 2: REALIZATION HISTORY TABLE --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-petronas">Realization History</h2>
            <span class="text-xs bg-carbon px-3 py-1 rounded-full text-muted border border-carbon">
                Total Entries: {{ $realizations->count() ?? 0 }}
            </span>
        </div>

        <div class="overflow-x-auto rounded-lg border border-carbon">
            <table class="w-full text-sm text-left">
                <thead class="bg-carbon text-muted text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 font-medium w-16 text-center">No</th>
                        <th class="px-4 py-3 font-medium">Tanggal Produksi</th>
                        <th class="px-4 py-3 font-medium text-right">Qty Realisasi</th>
                        <th class="px-4 py-3 font-medium">Waktu Input Sistem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon/50">
                    @forelse ($realizations as $index => $rz)
                        <tr class="hover:bg-carbon/40 transition-colors group">
                            <td class="px-4 py-4 text-center text-muted">
                                {{ $index + 1 }}
                            </td>
                            
                            <td class="px-4 py-4 text-silver font-bold">
                                {{ \Carbon\Carbon::parse($rz->production_date)->format('d F Y') }}
                            </td>
                            
                            <td class="px-4 py-4 text-right font-mono font-bold text-petronas text-base">
                                +{{ number_format($rz->qty_produced, 0, ',', '.') }}
                            </td>
                            
                            <td class="px-4 py-4 text-muted text-xs">
                                {{ $rz->created_at->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-muted italic">
                                <div class="flex flex-col items-center justify-center opacity-50">
                                    <span class="text-2xl mb-2">📋</span>
                                    <p>Belum ada data realisasi produksi yang diinput untuk batch ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</main>

</body>
</html>