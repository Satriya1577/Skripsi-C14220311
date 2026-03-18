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