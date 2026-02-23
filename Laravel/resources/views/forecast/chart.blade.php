<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forecast Chart | {{ $product->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

<body class="bg-blackBase text-silver min-h-screen font-sans">

<main class="max-w-7xl mx-auto px-6 py-8 space-y-8">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="text-xs text-muted">
        <ol class="flex items-center space-x-2">
            <li><a href="{{ route('home.index') }}" class="hover:text-petronas transition-colors">Home</a></li>
            <li class="opacity-40">/</li>
            <li><a href="{{ route('forecast.index') }}" class="hover:text-petronas transition-colors">Forecasting</a></li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold" aria-current="page">Chart Analysis</li>
        </ol>
    </nav>

    <x-alert-messages />

    {{-- Header --}}
    <header class="flex flex-col md:flex-row justify-between items-end border-b border-carbon pb-6">
        <div>
            <p class="text-xs uppercase tracking-widest text-muted mb-1">Forecast Result</p>
            <h1 class="text-3xl font-extrabold text-petronas">{{ $product->name }}</h1>
            <p class="text-sm text-silver mt-1 flex items-center gap-2">
                <span class="bg-carbon px-2 py-0.5 rounded text-xs font-mono border border-carbonSoft">{{ $product->code }}</span>
                <span>Target Period: <strong>{{ \Carbon\Carbon::parse($productionPlan->period)->format('F Y') }}</strong></span>
            </p>
        </div>
        
        <div class="mt-4 md:mt-0">
            <span class="px-3 py-1 rounded-full border text-xs font-bold uppercase tracking-wide 
                {{ $productionPlan->status == 'approved' ? 'bg-petronas/10 text-petronas border-petronas/30' : ($productionPlan->status == 'rejected' ? 'bg-danger/10 text-danger border-danger/30' : 'bg-warning/10 text-warning border-warning/30') }}">
                {{ ucfirst($productionPlan->status) }}
            </span>
        </div>
    </header>

    {{-- SECTION 1: METRICS & CONFIG SNAPSHOT --}}
    <section class="grid grid-cols-1 md:grid-cols-4 gap-4">
        
        {{-- Card 1: Forecast Qty --}}
        <div class="bg-carbon rounded-lg p-4 border border-petronas/50 shadow-[0_0_10px_rgba(0,161,155,0.1)]">
            <p class="text-xs text-muted uppercase tracking-wide mb-1">Forecast Quantity</p>
            <p class="text-2xl font-bold text-white">{{ number_format($productionPlan->forecast_qty) }}</p>
            <p class="text-[10px] text-muted border-t border-white/10 mt-2 pt-2">
                Safety Stock: {{ number_format($productionPlan->safety_stock_snapshot) }}
            </p>
        </div>

        {{-- Card 2: Model Parameters --}}
        <div class="bg-carbon rounded-lg p-4 border border-carbonSoft group hover:border-petronas/30 transition-colors">
            <p class="text-xs text-muted uppercase tracking-wide mb-1">SARIMA Config</p>
            <div class="flex items-end gap-1 mt-1 font-mono">
                <span class="text-xl font-bold text-petronas">
                    ({{ $productionPlan->order_p }},{{ $productionPlan->order_d }},{{ $productionPlan->order_q }})
                </span>
                <span class="text-muted text-xs mb-1">x</span>
                <span class="text-lg font-bold text-silver">
                    ({{ $productionPlan->seasonal_P }},{{ $productionPlan->seasonal_D }},{{ $productionPlan->seasonal_Q }})
                </span>
                <span class="text-[10px] text-muted mb-1">
                    {{ $productionPlan->seasonal_s }}
                </span>
            </div>
            <p class="text-[10px] text-muted mt-2">Parameters used for this run.</p>
        </div>

        {{-- Card 3: RMSE --}}
        <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
            <p class="text-xs text-muted uppercase tracking-wide mb-1">Model Accuracy (RMSE)</p>
            <p class="text-2xl font-bold text-silver">{{ number_format($metrics['rmse'], 4) }}</p>
            <p class="text-[10px] text-muted mt-2">Root Mean Squared Error</p>
        </div>

        {{-- Card 4: MAPE --}}
        <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
            <p class="text-xs text-muted uppercase tracking-wide mb-1">Model Accuracy (MAPE)</p>
            <p class="text-2xl font-bold {{ $metrics['mape'] < 20 ? 'text-success' : ($metrics['mape'] < 50 ? 'text-warning' : 'text-danger') }}">
                {{ number_format($metrics['mape'], 2) }}%
            </p>
            <p class="text-[10px] text-muted mt-2">Mean Absolute Percentage Error</p>
        </div>
    </section>

      {{-- SECTION 2: CHART VISUALIZATION --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon h-125 flex flex-col relative">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-lg font-bold text-petronas">Demand Visualization</h2>
                <p class="text-xs text-muted">Comparison between Actual Sales History and Forecasted Demand.</p>
            </div>
            
            {{-- Custom Legend --}}
            <div class="flex gap-4 text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-[#C8CCCE] opacity-50"></span>
                    <span class="text-muted">Actual History</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-[#00A19B]"></span>
                    <span class="text-petronas font-bold">Prediction</span>
                </div>
            </div>
        </div>
        
        <div class="flex-1 w-full relative min-h-0">
            <canvas id="forecastChart"></canvas>
        </div>
    </section>
    
    {{-- SECTION: RECOMMENDED PRODUCTION & MATERIAL BOM --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon shadow-lg shadow-black/50">
        
        {{-- BAGIAN ATAS: ACTION APPROVAL --}}
        <div class="flex flex-col md:flex-row items-start md:items-end gap-6">
            
            {{-- Left: Info & Recommendation --}}
            <div class="flex-1">
                <h2 class="text-lg font-bold text-petronas mb-1">Production Planning</h2>
                <p class="text-sm text-muted mb-4">Recommendation based on forecast, safety stock, and current snapshot.</p>
                
                <div class="flex gap-4">
                    {{-- Card System Recommendation --}}
                    <div class="bg-carbon rounded-lg p-4 border border-petronas/30 shadow-[0_0_10px_rgba(0,161,155,0.05)] min-w-45">
                        <p class="text-[10px] text-muted uppercase tracking-wide mb-1">System Suggestion</p>
                        <div class="flex items-baseline gap-1">
                            <p class="text-2xl font-bold text-silver">{{ number_format($productionPlan->recommended_production_qty ?? 0) }}</p>
                            <span class="text-[10px] text-muted">units</span>
                        </div>
                    </div>

                    {{-- Jika Approved, Tampilkan Card Approved Qty di sini juga --}}
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
                    {{-- STATE: DRAFT (Show Input Form) --}}
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
                    {{-- STATE: APPROVED (Show Status Badge Only) --}}
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

        {{-- BAGIAN BAWAH: MATERIAL BOM TABLE --}}
        <div class="mt-8 border-t border-carbon pt-6">
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
                                
                                {{-- Qty Needed --}}
                                <td class="px-4 py-3 text-right">
                                    <span class="font-mono text-warning font-bold">{{ number_format($item->qty_need, 2) }}</span> 
                                    <span class="text-[10px] text-muted">{{ $item->material->unit ?? '' }}</span>
                                </td>
                                
                                {{-- Current Stock --}}
                                <td class="px-4 py-3 text-right">
                                    <span class="font-mono text-silver">{{ number_format($item->current_stock, 2) }}</span> 
                                    <span class="text-[10px] text-muted">{{ $item->material->unit ?? '' }}</span>
                                </td>
                                
                                {{-- Purchase OTW --}}
                                <td class="px-4 py-3 text-right">
                                    <span class="font-mono text-silver">{{ number_format($item->purchase_otw, 2) }}</span> 
                                    <span class="text-[10px] text-muted">{{ $item->material->unit ?? '' }}</span>
                                </td>
                                
                                {{-- Suggestion (Shortage) --}}
                                <td class="px-4 py-3 text-right">
                                    @php
                                        // Asumsi shortage = Kebutuhan - (Stok + OTW). Jika negatif, berarti tidak perlu beli (0).
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

    </section>

</main>

<script>
    // --- SETUP CHART JS ---
    let chartData = @json($chartData);
    
    // 2. Ambil data forecast masa depan (Single Point)
    const futureQty = {{ $productionPlan->forecast_qty }};
    const futureLabel = "{{ \Carbon\Carbon::parse($productionPlan->period)->format('M Y') }}";

    // 3. Tambahkan titik masa depan ke array
    if(chartData && chartData.labels) {
        chartData.labels.push(futureLabel);
        chartData.actual.push(null);
        chartData.forecast.push(futureQty);
    }

    const ctx = document.getElementById('forecastChart');
    let gradientForecast;

    if(chartData && chartData.labels && chartData.labels.length > 0) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Actual Sales',
                        data: chartData.actual,
                        borderColor: '#C8CCCE',
                        backgroundColor: 'rgba(200,204,206,0.1)',
                        tension: 0.4, 
                        pointRadius: 4, 
                        pointHoverRadius: 6,
                        borderWidth: 2,
                        fill: true,
                        pointBackgroundColor: '#C8CCCE',
                        spanGaps: false 
                    },
                    {
                        label: 'Forecast Prediction',
                        data: chartData.forecast,
                        borderColor: '#00A19B',
                        backgroundColor: (context) => {
                            const ctx = context.chart.ctx;
                            if (!gradientForecast) {
                                gradientForecast = ctx.createLinearGradient(0, 0, 0, 400);
                                gradientForecast.addColorStop(0, 'rgba(0, 161, 155, 0.4)');
                                gradientForecast.addColorStop(1, 'rgba(0, 161, 155, 0.0)');
                            }
                            return gradientForecast;
                        },
                        borderDash: [5, 5],
                        tension: 0.4, 
                        pointRadius: (ctx) => {
                            const index = ctx.dataIndex;
                            const lastIndex = ctx.chart.data.labels.length - 1;
                            return index === lastIndex ? 6 : 4;
                        }, 
                        pointHoverRadius: 8,
                        borderWidth: 2,
                        fill: true,
                        pointBackgroundColor: (ctx) => {
                            const index = ctx.dataIndex;
                            const lastIndex = ctx.chart.data.labels.length - 1;
                            return index === lastIndex ? '#00A19B' : '#ffffff';
                        },
                        pointBorderColor: '#00A19B',
                        pointBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { 
                    legend: { display: false }, 
                    tooltip: {
                        backgroundColor: 'rgba(27, 29, 31, 0.95)',
                        titleColor: '#00A19B',
                        bodyColor: '#C8CCCE',
                        borderColor: '#3f3f46',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: true,
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('en-US').format(context.parsed.y);
                                }
                                if (context.datasetIndex === 1 && context.dataIndex === context.chart.data.labels.length - 1) {
                                    label += ' (Target)';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: { ticks: { color: '#9DA3A6', font: { size: 11, family: 'sans-serif' } }, grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false } },
                    y: { ticks: { color: '#9DA3A6', font: { size: 11, family: 'sans-serif' } }, grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false }, beginAtZero: true }
                }
            }
        });
    } else {
        ctx.parentNode.innerHTML = `<div class="flex flex-col items-center justify-center h-full text-muted opacity-50"><span>No forecast data available.</span></div>`;
    }
</script>

</body>
</html>