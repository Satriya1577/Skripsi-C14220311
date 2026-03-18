<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Production Plan Details | {{ $product->name }}</title>
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

    {{-- BREADCRUMB --}}
    <nav aria-label="breadcrumb" class="text-xs text-muted">
        <ol class="flex items-center space-x-2">
            <li><a href="{{ route('home.index') }}" class="hover:text-petronas transition-colors">Home</a></li>
            <li class="opacity-40">/</li>
            <li><a href="{{ route('production.index') }}" class="hover:text-petronas transition-colors">Production</a></li>
            <li class="opacity-40">/</li>
            <li><a href="{{ route('production.showPlan', $product) }}" class="hover:text-petronas transition-colors">Plan-{{ $product->code }}</a></li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold" aria-current="page">Plan-{{ $product->code }}-Details</li>
        </ol>
    </nav>

    <x-alert-messages />

    {{-- HEADER & SUMMARY CARDS --}}
    <header class="border-b border-carbon pb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div>
                <p class="text-xs uppercase tracking-widest text-muted mb-1">Plan details</p>
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-extrabold text-petronas">{{ $product->name }}</h1>
                    {{-- Status Badge Ditambahkan Di Sini --}}
                    @php
                        $statusClass = match($productionPlan->status) {
                            'approved' => 'bg-petronas/10 text-petronas border-petronas/30',
                            'rejected' => 'bg-danger/10 text-danger border-danger/30',
                            default    => 'bg-warning/10 text-warning border-warning/30',
                        };
                    @endphp
                    <span class="px-2 py-1 rounded text-xs font-bold uppercase tracking-wide border {{ $statusClass }}">
                        {{ $productionPlan->status }}
                    </span>
                </div>
                <p class="text-sm text-silver mt-1 flex flex-wrap items-center gap-2">
                    <span class="bg-carbon px-2 py-0.5 rounded text-xs font-mono border border-carbonSoft">{{ $product->code }}</span>
                    <span class="text-muted">|</span>
                    {{-- Packaging Ditambahkan Di Sini --}}
                    <span>Packaging: <strong>{{ $product->packaging ?? 'N/A' }}</strong></span>
                    <span class="text-muted">|</span>
                    <span>Plan Period: <strong>{{ \Carbon\Carbon::parse($productionPlan->period)->format('F Y') }}</strong></span>
                </p>
            </div>
        </div>

        {{-- Progress Info Grid (3 Columns) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6 items-stretch">
            {{-- Card 1: Target --}}
            <div class="bg-carbonSoft border border-carbon rounded-xl p-4 flex justify-between items-center">
                <div>
                    <p class="text-xs text-muted uppercase tracking-wide">Approved Qty</p>
                    <p class="text-2xl font-bold text-white">{{ number_format($targetQty, 0, ',', '.') }}</p>
                </div>
                <div class="p-3 bg-carbon rounded-lg text-petronas">🎯</div>
            </div>
            
            {{-- Card 2: Total Batched --}}
            <div class="bg-carbonSoft border border-carbon rounded-xl p-4 flex justify-between items-center">
                <div>
                    <p class="text-xs text-muted uppercase tracking-wide">Total Produced</p>
                    <p class="text-2xl font-bold text-petronas">{{ number_format($totalProduced, 0, ',', '.') }}</p>
                </div>
                <div class="p-3 bg-petronas/10 rounded-lg text-petronas">⚙️</div>
            </div>
            
            {{-- Card 3: Remaining Target --}}
            <div class="bg-carbonSoft border border-carbon rounded-xl p-4 flex justify-between items-center">
                <div>
                    <p class="text-xs text-muted uppercase tracking-wide">Remaining Target</p>
                    <p class="text-2xl font-bold {{ $remainingQty > 0 ? 'text-warning' : 'text-success' }}">
                        {{ number_format($remainingQty, 0, ',', '.') }}
                    </p>
                </div>
                <div class="p-3 bg-carbon rounded-lg {{ $remainingQty > 0 ? 'text-warning' : 'text-success' }}">
                    {{ $remainingQty > 0 ? '⚠️' : '✅' }}
                </div>
            </div>
        </div>
    </header>

    {{-- WRAPPER UNTUK TABS --}}
    <div class="bg-carbonSoft rounded-xl border border-carbon shadow-lg shadow-black/50 overflow-hidden">
        
        {{-- TABS NAVIGATION --}}
        <div class="flex border-b border-carbon bg-carbon/50 overflow-x-auto">
            <button onclick="openTab(event, 'tab-plan')" id="btn-tab-plan" class="tab-btn px-6 py-4 text-sm font-bold uppercase tracking-wide border-b-2 border-petronas text-petronas transition-colors whitespace-nowrap">
                Production Plan & BOM
            </button>
            
            {{-- Pengecekan Tab Batch List (Hanya Tampil Jika BUKAN Draft) --}}
            @if($productionPlan->status != 'draft')
            <button onclick="openTab(event, 'tab-batch')" id="btn-tab-batch" class="tab-btn px-6 py-4 text-sm font-bold uppercase tracking-wide border-b-2 border-transparent text-muted hover:text-silver transition-colors whitespace-nowrap">
                Production Batch List
            </button>
            @endif
        </div>

        {{-- TAB CONTENT 1: PRODUCTION PLAN & BOM --}}
        <div id="tab-plan" class="tab-content p-6 block">
            {{-- Action Approval Area --}}
            <div class="flex flex-col md:flex-row items-start md:items-end gap-6 mb-8">
                {{-- Left: Info & Recommendation --}}
                <div class="flex-1">
                    <h2 class="text-lg font-bold text-petronas mb-1">Production Planning Target</h2>
                    <p class="text-sm text-muted mb-4">Recommendation based on forecast, safety stock, and current snapshot.</p>
                    
                    <div class="flex gap-4">
                        <div class="bg-carbon rounded-lg p-4 border border-petronas/30 shadow-[0_0_10px_rgba(0,161,155,0.05)] min-w-45">
                            <p class="text-[10px] text-muted uppercase tracking-wide mb-1">System Suggestion</p>
                            <div class="flex items-baseline gap-1">
                                <p class="text-2xl font-bold text-silver">{{ number_format($productionPlan->recommended_production_qty ?? 0) }}</p>
                                <span class="text-[10px] text-muted">units</span>
                            </div>
                        </div>

                        @if($productionPlan->status == 'approved')
                            <div class="bg-petronas/10 rounded-lg p-4 border border-petronas min-w-45">
                                <p class="text-[10px] text-petronas uppercase tracking-wide mb-1 font-bold">Final Approved</p>
                                <div class="flex items-baseline gap-1">
                                    <p class="text-2xl font-bold text-white">{{ number_format($productionPlan->approved_production_qty) }}</p>
                                    <span class="text-[10px] text-petronas/70">units</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Right: Action Area --}}
                <div class="w-full md:w-auto">
                    @if($productionPlan->status == 'draft')
                        <form action="{{ route('forecast.approvePlan', $productionPlan->id) }}" method="POST" class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                            @csrf
                            @method('PATCH')
                            
                            <label for="approved_qty" class="block text-xs font-bold text-muted uppercase tracking-wide mb-2">Confirm Quantity</label>
                            
                            <div class="flex gap-2">
                                <input type="number" 
                                       id="approved_qty"
                                       name="approved_production_qty" 
                                       value="{{ $productionPlan->approved_production_qty ?? $productionPlan->recommended_production_qty ?? 0 }}"
                                       min="0" 
                                       step="1"
                                       class="w-32 bg-blackBase border border-carbonSoft rounded-lg px-3 py-2 text-silver text-right font-mono focus:outline-none focus:border-petronas transition focus:ring-1 focus:ring-petronas">
                                
                                <button type="submit" 
                                        class="bg-petronas text-blackBase font-bold px-5 py-2 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20 flex items-center gap-2 whitespace-nowrap text-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    <span>Approve Plan</span>
                                </button>
                            </div>
                            <p class="text-[10px] text-muted mt-2 italic text-right">* This will generate material request.</p>
                        </form>
                    @elseif($productionPlan->status == 'approved')
                        <div class="flex flex-col items-end justify-end h-full pb-2">
                            <div class="flex items-center gap-2 text-petronas">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                                </svg>
                                <span class="font-bold text-lg">Plan Approved</span>
                            </div>
                            <p class="text-xs text-muted mt-1">
                                Approved on {{ $productionPlan->updated_at->format('d M Y') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Material BOM Table --}}
            <div class="border-t border-carbon pt-6">
                <div class="flex justify-between items-end mb-4">
                    <div>
                        <h3 class="text-md font-bold text-petronas">Material Purchase Recommendations</h3>
                        <p class="text-xs text-muted mt-1">Estimated raw material required (BOM) to fulfill the production suggestion.</p>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-lg border border-carbon">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-carbon text-muted text-[10px] uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Material Code & Name</th>
                                <th class="px-4 py-3 font-semibold text-right">Qty Needed</th>
                                <th class="px-4 py-3 font-semibold text-right">Current Stock</th>
                                <th class="px-4 py-3 font-semibold text-right">Purchase OTW</th>
                                <th class="px-4 py-3 font-semibold text-right text-petronas">Purchase Suggestion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-carbon/50">
                            @forelse($materialRecommendations ?? [] as $item)
                                <tr class="hover:bg-carbon/40 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-silver">{{ $item->material->code ?? 'N/A' }}</span>
                                            <span class="text-[10px] text-muted">{{ $item->material->name ?? 'Unknown Material' }}</span>
                                        </div>
                                    </td>
                                    
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-mono text-warning font-bold">{{ number_format($item->qty_need, 2) }}</span> 
                                        <span class="text-[10px] text-muted">{{ $item->material->unit ?? '' }}</span>
                                    </td>
                                    
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-mono text-silver">{{ number_format($item->current_stock, 2) }}</span> 
                                        <span class="text-[10px] text-muted">{{ $item->material->unit ?? '' }}</span>
                                    </td>
                                    
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-mono text-silver">{{ number_format($item->purchase_otw, 2) }}</span> 
                                        <span class="text-[10px] text-muted">{{ $item->material->unit ?? '' }}</span>
                                    </td>
                                    
                                    <td class="px-4 py-3 text-right">
                                        @php
                                            $shortage = max(0, $item->qty_need - ($item->current_stock + $item->purchase_otw));
                                        @endphp
                                        <span class="font-mono font-bold {{ $shortage > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($shortage, 2) }}
                                        </span> 
                                        <span class="text-[10px] text-muted">{{ $item->material->unit ?? '' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-muted italic bg-carbon/20">
                                        <div class="flex flex-col items-center justify-center gap-1 opacity-50">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 mb-2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                            </svg>
                                            <span>No BOM data available to generate material recommendations.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pengecekan Tab Content Batch List (Hanya Tampil Jika BUKAN Draft) --}}
        @if($productionPlan->status != 'draft')
        {{-- TAB CONTENT 2: BATCH LIST TABLE --}}
        <div id="tab-batch" class="tab-content p-6 hidden">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-4 gap-4">
                <h2 class="text-lg font-bold text-petronas">Batch History</h2>
                
                {{-- Form Create Batch Diletakkan Di Sini --}}
                <form action="{{ route('production.storeBatch') }}" method="POST">
                    @csrf
                    <input type="hidden" name="production_plan_id" value="{{ $productionPlan->id }}">
                    <button type="submit" title="Batch Size: {{ number_format($product->batch_size, 0, ',', '.') }} Pcs"
                        class="bg-petronas text-blackBase text-sm font-bold px-4 py-2 rounded-lg hover:bg-petronas/90 transition-all shadow-lg shadow-petronas/20 flex items-center justify-center gap-2 group">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 group-hover:translate-x-0.5 transition-transform">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                        </svg>
                        Create new batch
                    </button>
                </form>
            </div>

           <div class="overflow-x-auto rounded-lg border border-carbon">
                <table class="w-full text-sm text-left">
                    <thead class="bg-carbon text-muted text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 font-medium">Batch Number</th>
                            <th class="px-4 py-3 font-medium">Start Date</th>
                            <th class="px-4 py-3 font-medium">End Date</th>
                            <th class="px-4 py-3 font-medium text-right text-petronas">Realized Qty</th>
                            <th class="px-4 py-3 font-medium text-right">Target Qty Per Batch</th>
                            <th class="px-4 py-3 font-medium text-center">Status</th>
                            <th class="px-4 py-3 font-medium text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon/50">
                        @forelse ($batches as $productionBatch)
                            @php
                                // Menghitung total realisasi untuk batch ini
                                // Pastikan relasi 'productionRealizations' sudah didefinisikan di model ProductionBatch
                                $realizedQty = $productionBatch->productionRealizations->sum('qty_produced') ?? 0;
                                $targetBatchQty = $productionBatch->qty_produced;
                            @endphp
                            <tr class="hover:bg-carbon/40 transition-colors group">
                                <td class="px-4 py-4 font-mono font-bold text-petronas">
                                    {{ $productionBatch->batch_number }}
                                </td>
                                <td class="px-4 py-4 text-silver">
                                    {{ $productionBatch->start_date ? \Carbon\Carbon::parse($productionBatch->start_date)->format('d M Y') : '-' }}
                                </td>
                                <td class="px-4 py-4 text-silver">
                                    {{ $productionBatch->end_date ? \Carbon\Carbon::parse($productionBatch->end_date)->format('d M Y') : '-' }}
                                </td>
                                
                                {{-- Kolom Baru: Realized Qty --}}
                                <td class="px-4 py-4 text-right font-mono font-bold {{ $realizedQty >= $targetBatchQty ? 'text-success' : 'text-warning' }}">
                                    {{ number_format($realizedQty, 0, ',', '.') }}
                                </td>

                                {{-- Target Qty --}}
                                <td class="px-4 py-4 text-right font-mono font-bold text-white">
                                    {{ number_format($targetBatchQty, 0, ',', '.') }}
                                </td>
                                
                                <td class="px-4 py-4 text-center">
                                    @if(is_null($productionBatch->end_date))
                                        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide bg-warning/10 text-warning border border-warning/30 flex items-center justify-center gap-1 w-28 mx-auto">
                                            <span class="animate-pulse w-1.5 h-1.5 bg-warning rounded-full"></span>
                                            In Progress
                                        </span>
                                    @else
                                        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide bg-success/10 text-success border border-success/30 inline-block w-28 mx-auto">
                                            Completed
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-4 text-center">
                                    @if(is_null($productionBatch->end_date))
                                        {{-- Ubah form menjadi tag <a> biasa --}}
                                        <a href="{{ route('production.showRealization', $productionBatch) }}" 
                                           title="Input Realization" 
                                           class="inline-flex items-center justify-center w-8 h-8 rounded border border-muted/30 text-muted hover:text-petronas hover:border-petronas hover:bg-carbon transition shadow-sm">
                                            
                                            {{-- Icon Mata (View / Input) --}}
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </a>
                                    @else
                                        <span class="text-xs text-muted italic">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center opacity-50">
                                        <span class="text-3xl mb-2">🏭</span>
                                        <p class="text-muted italic">Belum ada batch produksi untuk plan ini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($batches->hasPages())
                <div class="mt-4 border-t border-carbon pt-4">
                    {{ $batches->links('pagination::tailwind') }}
                </div>
            @endif
        </div>
        @endif
    </div>
</main>

{{-- JAVASCRIPT UNTUK TABS --}}
<script>
    function openTab(evt, tabName) {
        // Sembunyikan semua tab content
        let tabContents = document.getElementsByClassName("tab-content");
        for (let i = 0; i < tabContents.length; i++) {
            tabContents[i].classList.add("hidden");
            tabContents[i].classList.remove("block");
        }

        // Reset styling semua tombol tab
        let tabBtns = document.getElementsByClassName("tab-btn");
        for (let i = 0; i < tabBtns.length; i++) {
            tabBtns[i].classList.remove("border-petronas", "text-petronas");
            tabBtns[i].classList.add("border-transparent", "text-muted");
        }

        // Tampilkan tab yang dipilih dan berikan styling aktif pada tombolnya
        let targetTab = document.getElementById(tabName);
        if(targetTab) {
            targetTab.classList.remove("hidden");
            targetTab.classList.add("block");
            
            evt.currentTarget.classList.remove("border-transparent", "text-muted");
            evt.currentTarget.classList.add("border-petronas", "text-petronas");
        }
    }

    // Buka tab batch otomatis jika ada URL parameter page (dari pagination)
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        let btnTabBatch = document.getElementById('btn-tab-batch');
        if(urlParams.has('page') && btnTabBatch) {
            btnTabBatch.click();
        }
    });
</script>

</body>
</html>