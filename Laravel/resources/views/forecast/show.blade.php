<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forecasting | {{ $product->name }}</title>
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
                        danger: '#EF4444'
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-blackBase text-silver min-h-screen">

<main class="max-w-7xl mx-auto px-6 py-6 space-y-8">

    @if(session('success'))
        <div class="bg-petronas/20 border border-petronas text-petronas px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-danger/20 border border-danger text-danger px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <nav aria-label="breadcrumb" class="text-xs text-muted">
        <ol class="flex items-center space-x-2">
            <li><a href="{{ route('home.index') }}" class="hover:text-petronas transition-colors">Home</a></li>
            <li class="opacity-40">/</li>
            <li><a href="{{ route('forecast.index') }}" class="hover:text-petronas transition-colors">Forecasting</a></li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold" aria-current="page">{{ $product->code }}</li>
        </ol>
    </nav>

    <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-widest text-muted">Forecast Result</p>
            <h1 class="text-3xl font-extrabold text-petronas">{{ $product->name }}</h1>
        </div>

        {{-- <form action="{{ route('forecast.generate', $product->id) }}" method="POST" class="flex items-center gap-4">
            @csrf
            <div class="flex items-center gap-4 text-xs text-muted">
                <label class="flex items-center gap-1 cursor-pointer">
                    <input type="radio" name="forecastPeriod" value="thisPeriod" class="accent-petronas" checked>
                    <div>
                        <p class="uppercase tracking-widest text-silver">This Period</p>
                        <p class="text-[10px]">{{ $currentMonthDate->format('M Y') }} (Backtest)</p>
                    </div>
                </label>
                <label class="flex items-center gap-1 cursor-pointer">
                    <input type="radio" name="forecastPeriod" value="nextPeriod" class="accent-petronas">
                    <div>
                        <p class="uppercase tracking-widest text-silver">Next Period</p>
                        <p class="text-[10px]">{{ $nextMonthDate->format('M Y') }} (Future)</p>
                    </div>
                </label>
            </div>

            <button type="submit" id="btn-generate" 
                class="bg-petronas text-blackBase font-bold px-5 py-2 rounded-lg 
                       hover:bg-blackBase hover:text-petronas hover:border hover:border-petronas transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                @if($jobStatus == 'pending' || $jobStatus == 'processing')
                    Processing...
                @else
                    Generate Forecast
                @endif
            </button>
        </form> --}}

        <form id="forecastForm" action="{{ route('forecast.generate', $product->id) }}" method="POST" class="flex items-center gap-4">
            @csrf
            
            <div class="flex items-center gap-4 text-xs text-muted">
                <label class="flex items-center gap-1 cursor-pointer">
                    <input type="radio" name="forecastPeriod" value="thisPeriod" class="accent-petronas" checked>
                    <div>
                        <p class="uppercase tracking-widest text-silver">This Period</p>
                        <p class="text-[10px]">{{ $currentMonthDate->format('M Y') }} (Backtest)</p>
                    </div>
                </label>
                <label class="flex items-center gap-1 cursor-pointer">
                    <input type="radio" name="forecastPeriod" value="nextPeriod" class="accent-petronas">
                    <div>
                        <p class="uppercase tracking-widest text-silver">Next Period</p>
                        <p class="text-[10px]">{{ $nextMonthDate->format('M Y') }} (Future)</p>
                    </div>
                </label>
            </div>
            <button type="submit" id="btn-generate" 
                class="bg-petronas text-blackBase font-bold px-5 py-2 rounded-lg 
                        hover:bg-blackBase hover:text-petronas hover:border hover:border-petronas transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                
                <svg id="loading-icon" class="animate-spin h-4 w-4 text-blackBase hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                
                <span id="btn-text">
                    @if($jobStatus == 'pending' || $jobStatus == 'processing')
                        Processing...
                    @else
                        Generate Forecast
                    @endif
                </span>
            </button>
        </form>
    </header>


    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <div class="bg-carbonSoft rounded-xl p-6 border border-petronas">
            <div>
                <p class="text-sm text-muted">Product Code</p>
                <p class="text-xl font-bold text-silver">{{ $product->code }}</p>
            </div>
            <div class="mt-4">
                <p class="text-xs text-muted">Current Stock</p>
                <p class="text-2xl font-bold text-petronas">{{ number_format($product->current_stock) }} <span class="text-sm text-muted font-normal">Units</span></p>
            </div>
        </div>

        <div class="bg-carbonSoft rounded-xl p-6 border border-petronas/50 hover:border-petronas transition-colors group">
            <p class="text-sm text-muted uppercase tracking-wide">Model Config</p>
            <p class="text-lg font-bold text-silver">SARIMA</p>
            
            <div class="flex items-end gap-1 mt-1 font-mono">
                <span class="text-2xl font-extrabold text-petronas group-hover:text-white transition-colors">
                    ({{ $sarimaConfig->order_p ?? '1' }},{{ $sarimaConfig->order_d ?? '1' }},{{ $sarimaConfig->order_q ?? '1' }})
                </span>
                
                <span class="text-muted text-sm mb-1">x</span>
                
                <span class="text-xl font-bold text-petronas/80 group-hover:text-white/90 transition-colors">
                    ({{ $sarimaConfig->seasonal_P ?? '1' }},{{ $sarimaConfig->seasonal_D ?? '1' }},{{ $sarimaConfig->seasonal_Q ?? '1' }})
                </span>
                
                <span class="text-xs text-muted mb-1">
                    {{ $sarimaConfig->seasonal_s ?? '12' }}
                </span>
            </div>
            <p class="text-[10px] text-muted mt-2">Format: (p,d,q) x (P,D,Q)s</p>
        </div>

        <div class="bg-carbonSoft rounded-xl p-6 border border-petronas/50 hover:border-petronas transition-colors">
            <p class="text-sm text-muted uppercase tracking-wide">Model Accuracy</p>
            <p class="text-lg font-bold text-silver">RMSE</p>
            <p class="text-3xl font-extrabold text-petronas">{{ $metrics['rmse'] }}</p>
            <p class="text-xs text-muted mt-1">Root Mean Squared Error</p>
        </div>

        <div class="bg-carbonSoft rounded-xl p-6 border border-petronas/50 hover:border-petronas transition-colors">
            <p class="text-sm text-muted uppercase tracking-wide">Model Accuracy</p>
            <p class="text-lg font-bold text-silver">MAPE</p>
            <p class="text-3xl font-extrabold text-petronas">{{ $metrics['mape'] }}</p>
            <p class="text-xs text-muted mt-1">Mean Absolute Percentage Error</p>
        </div>
    </section>

    <section class="bg-carbonSoft rounded-xl p-6">
        <h3 class="text-lg font-bold text-petronas mb-4">Actual vs Forecast Demand</h3>
        <div class="relative h-72 w-full">
            <canvas id="forecastChart"></canvas>
        </div>
    </section>

    {{-- <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="bg-carbonSoft rounded-xl p-6 h-96 overflow-y-auto">
            <h3 class="text-lg font-bold text-petronas mb-4 sticky top-0 bg-carbonSoft pb-2">Validation & Forecast Log</h3>
            <table class="w-full text-sm">
                <thead class="bg-carbon sticky top-10">
                    <tr>
                        <th class="px-3 py-2 text-left text-muted">Period</th>
                        <th class="px-3 py-2 text-right text-muted">Actual</th>
                        <th class="px-3 py-2 text-right text-muted">Predicted</th>
                        <th class="px-3 py-2 text-center text-muted">Type</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logTable as $row)
                        <tr class="border-b border-carbon {{ $row['is_forecast'] ? 'bg-petronas/10 border-l-4 border-l-petronas' : '' }}">
                            <td class="px-3 py-2">{{ $row['period'] }}</td>
                            <td class="px-3 py-2 text-right">{{ $row['actual'] }}</td>
                            <td class="px-3 py-2 text-right font-semibold {{ $row['is_forecast'] ? 'text-petronas' : '' }}">
                                {{ $row['predicted'] }}
                            </td>
                            <td class="px-3 py-2 text-center text-xs {{ $row['is_forecast'] ? 'font-bold text-petronas uppercase' : 'text-muted' }}">
                                {{ $row['type'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No data available. Please generate forecast.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-carbonSoft rounded-xl p-6 h-96 overflow-y-auto">
            <h3 class="text-lg font-bold text-petronas mb-4 sticky top-0 bg-carbonSoft pb-2">Production Recommendation</h3>
            <table class="w-full text-sm">
                <thead class="bg-carbon sticky top-10">
                    <tr>
                        <th class="px-3 py-2 text-left text-muted">Period</th>
                        <th class="px-3 py-2 text-right text-muted">Forecast</th>
                        <th class="px-3 py-2 text-right text-muted">Stock</th>
                        <th class="px-3 py-2 text-right text-muted">Prod. Qty</th>
                        <th class="px-3 py-2 text-center text-muted">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productionPlans as $plan)
                        <tr class="border-b border-carbon">
                            <td class="px-3 py-2">{{ \Carbon\Carbon::parse($plan->period)->format('M Y') }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($plan->forecast_qty) }}</td>
                            <td class="px-3 py-2 text-right text-muted">{{ number_format($plan->current_stock_snapshot) }}</td>
                            <td class="px-3 py-2 text-right font-bold {{ $plan->recommended_production_qty > 0 ? 'text-petronas' : 'text-silver' }}">
                                {{ number_format($plan->recommended_production_qty) }}
                            </td>
                            <td class="px-3 py-2 text-center text-xs">
                                @if($plan->recommended_production_qty > 0)
                                    <span class="text-petronas font-bold uppercase">Produce</span>
                                @else
                                    <span class="text-muted">Safe</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No production plan generated yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </section> --}}

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <section class="lg:col-span-1 bg-carbonSoft rounded-xl p-6 border border-carbon h-[600px] flex flex-col">
            <h2 class="text-lg font-bold text-petronas mb-4 flex justify-between items-center">
                <span>Validation Log</span>
                <span class="text-xs font-normal text-muted tracking-wide uppercase">History & Future</span>
            </h2>

            <div class="overflow-y-auto flex-1 custom-scrollbar -mx-3 px-3">
                <table class="w-full text-sm">
                    <thead class="bg-carbon sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="px-3 py-2 text-left text-muted font-normal">Period</th>
                            <th class="px-3 py-2 text-right text-muted font-normal">Act / Pred</th>
                            <th class="px-3 py-2 text-center text-muted font-normal">Diff</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logTable as $row)
                            <tr class="border-b border-carbon transition-colors hover:bg-carbon {{ $row['is_forecast'] ? 'bg-petronas/5' : '' }}">
                                <td class="px-3 py-2">
                                    <div class="flex flex-col">
                                        <span class="font-semibold {{ $row['is_forecast'] ? 'text-petronas' : 'text-silver' }}">
                                            {{ $row['period'] }}
                                        </span>
                                        <span class="text-[10px] uppercase tracking-wider {{ $row['is_forecast'] ? 'text-petronas/70' : 'text-muted' }}">
                                            {{ $row['type'] }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex flex-col items-end">
                                        @if(!$row['is_forecast'])
                                            <span class="text-[10px] text-muted">Act: {{ $row['actual'] }}</span>
                                        @endif
                                        <span class="{{ $row['is_forecast'] ? 'text-petronas font-bold text-base' : 'text-silver' }}">
                                            {{ $row['predicted'] }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-center align-middle">
                                    @if(!$row['is_forecast'])
                                        @php
                                            $diff = $row['predicted'] - $row['actual'];
                                            // Warna error: Merah jika selisih ekstrem, Silver jika wajar
                                            $color = abs($diff) > 50 ? 'text-red-500' : 'text-muted'; 
                                        @endphp
                                        <span class="text-xs font-mono {{ $color }}">
                                            {{ $diff > 0 ? '+' : '' }}{{ $diff }}
                                        </span>
                                    @else
                                        <span class="text-muted text-lg">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-4 text-center text-muted italic">
                                    No data available.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="lg:col-span-2 bg-carbonSoft rounded-xl p-6 border border-carbon h-[600px] flex flex-col">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-2">
                <h2 class="text-lg font-bold text-petronas">
                    Production Plan
                </h2>
                
                <div class="flex gap-2">
                    <span class="px-2 py-1 rounded bg-yellow-500/10 border border-yellow-500/20 text-[10px] font-bold text-yellow-500 uppercase tracking-wide">
                        Pending
                    </span>
                    <span class="px-2 py-1 rounded bg-petronas/10 border border-petronas/20 text-[10px] font-bold text-petronas uppercase tracking-wide">
                        Approved
                    </span>
                    <span class="px-2 py-1 rounded bg-red-500/10 border border-red-500/20 text-[10px] font-bold text-red-500 uppercase tracking-wide">
                        Rejected
                    </span>
                </div>
            </div>

            <div class="overflow-auto flex-1 custom-scrollbar -mx-3 px-3">
                <table class="w-full text-sm">
                    <thead class="bg-carbon sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="px-3 py-2 text-left text-muted font-normal">Period</th>
                            <th class="px-3 py-2 text-right text-muted font-normal">Forecast</th>
                            <th class="px-3 py-2 text-right text-muted font-normal">Stock</th>
                            <th class="px-3 py-2 text-right text-muted font-normal">Prod. Qty</th>
                            <th class="px-3 py-2 text-center text-muted font-normal">Status</th>
                            <th class="px-3 py-2 text-center text-muted font-normal">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productionPlans as $plan)
                            <tr class="border-b border-carbon hover:bg-carbon transition-colors group">
                                <td class="px-3 py-3 font-semibold text-silver">
                                    {{ \Carbon\Carbon::parse($plan->period)->format('F Y') }}
                                </td>
                                <td class="px-3 py-3 text-right text-muted">
                                    {{ number_format($plan->forecast_qty) }}
                                </td>
                                <td class="px-3 py-3 text-right text-muted">
                                    {{ number_format($plan->current_stock_snapshot) }}
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <span class="text-lg font-bold {{ $plan->recommended_production_qty > 0 ? 'text-petronas' : 'text-muted' }}">
                                        {{ number_format($plan->recommended_production_qty) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @php
                                        $status = $plan->status ?? 'pending'; 
                                        $statusColor = match($status) {
                                            'approved' => 'text-petronas',
                                            'rejected' => 'text-red-500',
                                            default    => 'text-yellow-500',
                                        };
                                    @endphp
                                    <span class="text-xs font-bold uppercase tracking-wide {{ $statusColor }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2 opacity-80 group-hover:opacity-100 transition-opacity">
                                        
                                        <a href="{{ route('production_plans.show', $plan->id) }}" 
                                        title="View Details"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded bg-petronas text-blackBase hover:bg-petronas/90 transition shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </a>

                                        @if($status === 'draft') <button type="button" 
                                                    onclick="openActionModal('approve', {{ $plan->id }})"
                                                    title="Approve"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded border border-petronas text-petronas hover:bg-petronas/10 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            </button>

                                            <button type="button" 
                                                    onclick="openActionModal('reject', {{ $plan->id }})"
                                                    title="Reject"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded border border-danger text-danger hover:bg-danger hover:text-blackBase transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>

                                        @else
                                            <span class="text-[10px] text-muted italic ml-2 cursor-not-allowed">
                                                Locked
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-12 text-center text-muted">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 opacity-20">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                        <span>No production plan available.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

</main>

<div id="actionModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div id="modalBox" class="bg-carbonSoft rounded-xl p-6 w-full max-w-md border transition-colors duration-300 border-petronas">
        
        <h3 id="modalTitle" class="text-lg font-bold mb-2 text-petronas">
            Confirm Action
        </h3>

        <p id="modalDesc" class="text-sm text-muted mb-6">
            Are you sure you want to proceed?
        </p>

        <form id="actionForm" method="POST">
            @csrf
            @method('PATCH')
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeActionModal()"
                    class="px-5 py-2 rounded-lg border border-muted text-muted hover:bg-carbon transition">
                    Cancel
                </button>

                <button type="submit" id="confirmBtn"
                    class="px-5 py-2 rounded-lg bg-petronas text-blackBase font-bold hover:bg-petronas/90 transition shadow-lg">
                    Confirm
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- 1. SETUP CHART JS (Sama seperti sebelumnya) ---
    const chartData = @json($chartData);
    const ctx = document.getElementById('forecastChart');
    
    if(chartData.labels.length > 0) {
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
                        tension: 0.3, pointRadius: 4, borderWidth: 2
                    },
                    {
                        label: 'Forecast / Prediction',
                        data: chartData.forecast,
                        borderColor: '#00A19B',
                        backgroundColor: 'rgba(0,161,155,0.1)',
                        borderDash: [5, 5],
                        tension: 0.3, pointRadius: 4, borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { color: '#9DA3A6' } } },
                scales: {
                    x: { ticks: { color: '#C8CCCE' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                    y: { ticks: { color: '#C8CCCE' }, grid: { color: 'rgba(255,255,255,0.05)' } }
                }
            }
        });
    } else {
        ctx.parentNode.innerHTML = '<div class="flex items-center justify-center h-full text-muted">No chart data available</div>';
    }

    // --- 2. LOGIKA TOMBOL & POLLING ---
    const productId = "{{ $product->id }}";
    const jobStatusAwal = "{{ $jobStatus }}";
    
    const form = document.getElementById('forecastForm');
    const btnGenerate = document.getElementById('btn-generate');
    const btnText = document.getElementById('btn-text');
    const loadingIcon = document.getElementById('loading-icon');

    // Fungsi untuk mengubah tampilan tombol jadi "Loading"
    function setLoadingState() {
        btnGenerate.disabled = true;
        btnText.innerText = 'Processing...';
        loadingIcon.classList.remove('hidden'); // Munculkan spinner
    }

    // Fungsi untuk memulai Polling Cek Status
    function startPolling() {
        const interval = setInterval(() => {
            fetch(`/forecast/check-status/${productId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'completed') {
                        clearInterval(interval);
                        btnText.innerText = 'Success! Reloading...';
                        loadingIcon.classList.add('hidden');
                        setTimeout(() => window.location.reload(), 500); // Reload halaman
                    } else if (data.status === 'failed') {
                        clearInterval(interval);
                        alert('Forecast generation failed. Please check logs.');
                        window.location.reload();
                    }
                })
                .catch(err => console.error(err));
        }, 2000); // Cek setiap 2 detik
    }

    // A. Cek Status saat Halaman Pertama Dimuat
    if (jobStatusAwal === 'pending' || jobStatusAwal === 'processing') {
        setLoadingState();
        startPolling();
    }

    // B. Handle Klik Tombol (AJAX Submit)
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Mencegah reload halaman biasa

        if(!confirm("Start forecasting process? This may take a moment.")) return;

        // 1. Ubah UI Tombol seketika
        setLoadingState();

        // 2. Kirim Data via Fetch (AJAX)
        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest', // Memberitahu Laravel ini AJAX
                'Accept': 'application/json'
            }
        })
        .then(response => {
            // Kita tidak peduli responnya apa (redirect atau json),
            // yang penting request sudah terkirim ke server.
            // Langsung mulai polling status.
            startPolling();
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Gagal mengirim request.");
            window.location.reload();
        });
    });

    // --- 3. LOGIKA MODAL APPROVE/REJECT ---
    
    const modal = document.getElementById('actionModal');
    const modalBox = document.getElementById('modalBox');
    const modalTitle = document.getElementById('modalTitle');
    const modalDesc = document.getElementById('modalDesc');
    const actionForm = document.getElementById('actionForm');
    const confirmBtn = document.getElementById('confirmBtn');

    function openActionModal(type, planId) {
        // 1. Set URL Action (Sesuaikan route Anda)
        // Asumsi Route: /production-plans/{id}/approve atau /reject
        // Ganti 'production-plans' dengan prefix route Anda jika berbeda
        actionForm.action = `/production-plans/${planId}/${type}`;

        // 2. Atur Tampilan Berdasarkan Tipe
        if (type === 'approve') {
            // Style untuk APPROVE (Warna Petronas)
            modalBox.classList.remove('border-danger');
            modalBox.classList.add('border-petronas');
            
            modalTitle.innerText = 'Confirm Approval';
            modalTitle.classList.remove('text-danger');
            modalTitle.classList.add('text-petronas');
            
            modalDesc.innerHTML = `Are you sure you want to approve this plan?<br>
                                   <span class="text-petronas font-semibold">Material requests will be generated automatically.</span>`;
            
            confirmBtn.innerText = 'Approve Plan';
            confirmBtn.classList.remove('bg-danger', 'hover:bg-danger/90', 'shadow-red-500/30');
            confirmBtn.classList.add('bg-petronas', 'text-blackBase', 'hover:bg-petronas/90', 'shadow-[0_0_15px_rgba(0,161,155,0.3)]');
            
        } else {
            // Style untuk REJECT (Warna Danger/Red)
            modalBox.classList.remove('border-petronas');
            modalBox.classList.add('border-danger'); // border-red-500
            
            modalTitle.innerText = 'Confirm Rejection';
            modalTitle.classList.remove('text-petronas');
            modalTitle.classList.add('text-danger');
            
            modalDesc.innerHTML = `Are you sure you want to reject this plan?<br>
                                   <span class="text-danger font-semibold">This action cannot be undone.</span>`;
            
            confirmBtn.innerText = 'Reject Plan';
            confirmBtn.classList.remove('bg-petronas', 'text-blackBase', 'hover:bg-petronas/90', 'shadow-[0_0_15px_rgba(0,161,155,0.3)]');
            confirmBtn.classList.add('bg-danger', 'text-white', 'hover:bg-danger/90', 'shadow-[0_0_15px_rgba(239,68,68,0.3)]');
        }

        // 3. Tampilkan Modal
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeActionModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close modal on click outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeActionModal();
        }
    });
</script>

</body>
</html>

