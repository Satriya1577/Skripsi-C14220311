<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Forecast: {{ $product->name }} | Production Planning</title>
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

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="text-xs text-muted">
        <ol class="flex items-center space-x-2">
            <li><a href="{{ route('home.index') }}" class="hover:text-petronas transition-colors">Home</a></li>
            <li class="opacity-40">/</li>
            <li><a href="{{ route('forecast.index') }}" class="hover:text-petronas transition-colors">Forecasting</a></li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold" aria-current="page">Show</li>
        </ol>
    </nav>

    <div id="js-alert-container">
        {{-- Alert bawaan PHP (Session) tetap ada di sini --}}
        <x-alert-messages />
    </div>

    {{-- Header --}}
    <header class="flex justify-between items-end">
        <div>
            <p class="text-xs uppercase tracking-widest text-muted">Forecast Generation</p>
            <h1 class="text-3xl font-extrabold text-petronas">{{ $product->name }}</h1>
            <p class="text-sm text-muted mt-1">
                <span class="bg-carbon px-2 py-1 rounded text-xs font-mono mr-2 border border-carbonSoft">{{ $product->code }}</span>
                {{ $product->packaging ?? 'No Packaging Info' }}
            </p>
        </div>
    </header>

    {{-- SECTION 1: PRODUCT INFO & CONFIGURATION --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon shadow-lg shadow-black/50 space-y-6">
        <h2 class="text-lg font-bold text-petronas">Configuration & Status</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
            {{-- Current Stock Info (Mirip Product Show) --}}
            <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                <p class="text-xs text-muted uppercase tracking-wide mb-1">On Hand Stock</p>
                <p class="text-2xl font-bold text-silver">{{ number_format($product->current_stock) }}</p>
            </div>

            {{-- Target Period Card --}}
            <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                <p class="text-xs text-muted uppercase tracking-wide mb-1">Target Period</p>
                <p class="text-2xl font-bold text-white">{{ now()->addMonth()->format('F Y') }}</p>
                <p class="text-[10px] text-muted border-t border-white/10 mt-1 pt-1">
                    Type: <span class="text-petronas font-bold">FUTURE</span>
                </p>
            </div>

            {{-- Action Form (Full Width di Sisa Kolom) --}}
            <div class="md:col-span-2 bg-carbon/50 rounded-lg p-4 border border-petronas/30 flex flex-col justify-center items-center">
                <form action="{{ route('forecast.generate', $product->id) }}" method="POST" id="generateForm" class="w-full flex items-center gap-4">
                    @csrf
                    <input type="hidden" name="forecastPeriod" value="nextPeriod">
                    
                    <div class="flex-1">
                        <p class="text-xs text-silver font-bold uppercase tracking-wide">Generate Forecast</p>
                        <p class="text-[10px] text-muted mt-0.5">Run SARIMA algorithm to predict demand.</p>
                    </div>

                    <button type="submit" id="btn-generate" class="bg-petronas text-blackBase font-bold px-6 py-2.5 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                        <span>Start Process</span>
                    </button>
                </form>
            </div>
        </div>
    </section>

    {{-- SECTION 2: HISTORY TABLE --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon shadow-lg shadow-black/50">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-petronas">History & Production Plans</h2>
            <span class="text-xs bg-carbon px-3 py-1 rounded-full text-muted border border-carbon">Total: {{ $productionPlans->total() }}</span>
        </div>

        <div class="overflow-x-auto rounded-lg border border-carbon h-125"> {{-- Fixed Height Scroll --}}
            <table class="w-full text-sm relative">
                <thead class="bg-carbon sticky top-0 z-10"> {{-- Sticky Header --}}
                    <tr>
                        <th class="px-4 py-3 text-left text-muted uppercase text-xs tracking-wider">Period</th>
                        <th class="px-4 py-3 text-right text-muted uppercase text-xs tracking-wider">Forecast</th>
                        <th class="px-4 py-3 text-right text-muted uppercase text-xs tracking-wider">Stock Snap</th>
                        <th class="px-4 py-3 text-right text-muted uppercase text-xs tracking-wider">Rec. Prod</th>
                        <th class="px-4 py-3 text-center text-muted uppercase text-xs tracking-wider">Status</th>
                        <th class="px-4 py-3 text-center text-muted uppercase text-xs tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon/50 overflow-y-auto">
                    @forelse($productionPlans as $plan)
                        <tr class="hover:bg-carbon transition-colors group">
                            {{-- Period --}}
                            <td class="px-4 py-3">
                                <span class="text-silver font-bold block">{{ \Carbon\Carbon::parse($plan->period)->format('F Y') }}</span>
                                <span class="text-[10px] text-muted">{{ $plan->created_at->format('d/m/Y H:i') }}</span>
                            </td>

                            {{-- Forecast --}}
                            <td class="px-4 py-3 text-right text-muted font-mono">
                                {{ number_format($plan->forecast_qty) }}
                            </td>

                            {{-- Stock --}}
                            <td class="px-4 py-3 text-right text-muted font-mono">
                                {{ number_format($plan->current_stock_snapshot) }}
                            </td>

                            {{-- Recommended --}}
                            <td class="px-4 py-3 text-right">
                                <span class="font-bold font-mono {{ $plan->recommended_production_qty > 0 ? 'text-petronas text-base' : 'text-muted' }}">
                                    {{ number_format($plan->recommended_production_qty) }}
                                </span>
                            </td>

                            {{-- Status Badge --}}
                            <td class="px-4 py-3 text-center">
                                @php
                                    $status = $plan->status ?? 'pending';
                                    $badgeClass = match($status) {
                                        'approved' => 'bg-petronas/10 text-petronas border-petronas/30',
                                        'rejected' => 'bg-danger/10 text-danger border-danger/30',
                                        default    => 'bg-warning/10 text-warning border-warning/30',
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide border {{ $badgeClass }}">
                                    {{ $status }}
                                </span>
                            </td>

                            {{-- Action --}}
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('forecast.chart', $plan) }}" {{-- Isi Route --}}
                                   class="inline-flex items-center justify-center w-8 h-8 rounded border border-muted/30 text-muted hover:text-petronas hover:border-petronas transition shadow-sm"
                                   title="View Details">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-muted italic bg-carbon/20">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="text-2xl opacity-50">📊</span>
                                    <span>No history available for this product yet.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            @if($productionPlans instanceof \Illuminate\Pagination\LengthAwarePaginator && $productionPlans->hasPages())
                {{ $productionPlans->links('pagination::tailwind') }}
            @endif
        </div>
    </section>

</main>

<script>
    const form = document.getElementById('generateForm');
    const btn = document.getElementById('btn-generate');
    const alertContainer = document.getElementById('js-alert-container'); // Ambil container
    const productId = "{{ $product->id }}";
    
    // --- FUNGSI 1: Render Alert Error via JS ---
    function renderErrorAlert(message) {
        // Hapus alert error JS sebelumnya jika ada
        const existingAlert = document.getElementById('js-dynamic-alert');
        if(existingAlert) existingAlert.remove();

        // Buat elemen HTML (HAPUS 'animate-pulse' di sini)
        const alertHtml = `
            <div id="js-dynamic-alert" class="bg-carbonSoft border border-red-500 rounded-xl p-4 flex items-start gap-4 shadow-[0_0_15px_rgba(239,68,68,0.1)] mb-6 transition-all duration-300 ease-in-out opacity-0 transform -translate-y-2">
                <span class="text-red-500 text-lg font-bold mt-0.5 shrink-0">⚠</span>
                <div class="text-sm text-red-500 flex-1 pt-1">
                    <p class="font-semibold">${message}</p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-muted hover:text-red-500 transition font-bold text-lg shrink-0 -mt-1">✕</button>
            </div>
        `;

        // Masukkan ke dalam container (di paling atas)
        alertContainer.insertAdjacentHTML('afterbegin', alertHtml);

        // Efek Fade In Halus (Tanpa Kedip)
        // Kita perlu sedikit delay agar transisi CSS 'opacity-0' ke 'opacity-100' terbaca browser
        setTimeout(() => {
            const alertEl = document.getElementById('js-dynamic-alert');
            if(alertEl) {
                alertEl.classList.remove('opacity-0', '-translate-y-2');
                alertEl.classList.add('opacity-100', 'translate-y-0');
            }
        }, 10);

        // Scroll halus ke atas (opsional, jika error di luar viewport)
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // --- FUNGSI 2: Loading State ---
    function setBtnLoading(isLoading, text = 'Start Process') {
        if (isLoading) {
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin h-5 w-5 text-blackBase" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Processing...</span>
            `;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
        } else {
            btn.disabled = false;
            btn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                </svg>
                <span>${text}</span>
            `;
            btn.classList.remove('opacity-75', 'cursor-not-allowed');
        }
    }

    // --- FUNGSI 3: Polling ---
    function startPolling() {
        const pollInterval = setInterval(() => {
            fetch(`/forecast/check-status/${productId}`)
                .then(response => response.json())
                .then(data => {
                    console.log("Job Status:", data.status);

                    if (data.status === 'completed') {
                        clearInterval(pollInterval);
                        setBtnLoading(false, 'Success! Reloading...');
                        setTimeout(() => window.location.reload(), 1000);
                        
                    } else if (data.status === 'failed') {
                        clearInterval(pollInterval);
                        setBtnLoading(false, 'Failed');
                        // Ganti alert JS dengan Alert HTML Custom
                        renderErrorAlert('Forecast generation failed: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => console.error('Polling Error:', error));
        }, 2000); 
    }

    // --- MAIN HANDLER ---
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Bersihkan alert lama jika ada
        const existingAlert = document.getElementById('js-dynamic-alert');
        if(existingAlert) existingAlert.remove();

        setBtnLoading(true);

        const formData = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest', // Penting agar Laravel tahu ini AJAX
                'Accept': 'application/json'
            }
        })
        .then(response => {
            // Jika status code bukan 200 (misal 422 Unprocessable Entity atau 403 Forbidden)
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(data => {
            console.log("Job started:", data);
            startPolling();
        })
        .catch(error => {
            console.error('Error:', error);
            setBtnLoading(false);
            
            let msg = "An unexpected error occurred.";
            
            // Ambil pesan error dari JSON Laravel
            if(error.error) {
                msg = error.error;
            } else if (error.message) {
                msg = error.message;
            }
            
            // TAMPILKAN CUSTOM ALERT (Bukan alert browser)
            renderErrorAlert(msg);
        });
    });
</script>

</body>
</html>

