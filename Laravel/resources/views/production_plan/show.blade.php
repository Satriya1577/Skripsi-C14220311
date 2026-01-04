<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Plan Details | {{ $plan->product->name ?? 'Product' }}</title>
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

    <nav aria-label="breadcrumb" class="text-xs text-muted">
        <ol class="flex items-center space-x-2">
            <li><a href="{{ route('home.index') }}" class="hover:text-petronas transition-colors">Home</a></li>
            <li class="opacity-40">/</li>
            <li><a href="{{ route('forecast.index') }}" class="hover:text-petronas transition-colors">Forecasting</a></li>
            <li class="opacity-40">/</li>
            <li>
                <a href="{{ route('forecast.show', $plan->product->id) }}" class="hover:text-petronas transition-colors">
                    {{ $plan->product->code }}
                </a>
            </li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold" aria-current="page">Plan #{{ $plan->id }}</li>
        </ol>
    </nav>

    <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <p class="text-xs uppercase tracking-widest text-muted">Production Plan Details</p>
                
                @php
                    $statusColor = match($plan->status) {
                        'completed' => 'bg-success/10 text-success border-success/20',
                        'approved'  => 'bg-petronas/10 text-petronas border-petronas/20',
                        'rejected'  => 'bg-danger/10 text-danger border-danger/20',
                        default     => 'bg-warning/10 text-warning border-warning/20',
                    };
                @endphp
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $statusColor }}">
                    {{ $plan->status }}
                </span>
            </div>
            
            <h1 class="text-3xl font-extrabold text-petronas">
                {{ $plan->product->name ?? 'Unknown Product' }}
            </h1>
            <p class="text-sm text-muted mt-1">
                Target Periode: <span class="text-silver font-semibold">{{ \Carbon\Carbon::parse($plan->period)->format('F Y') }}</span>
            </p>
        </div>

        @if($plan->status == 'draft')
            <div class="flex gap-3">
                <button onclick="openActionModal('approve', {{ $plan->id }})" 
                        class="px-5 py-2 rounded-lg bg-petronas text-blackBase font-bold hover:bg-petronas/90 transition shadow-[0_0_15px_rgba(0,161,155,0.3)]">
                    Approve Plan
                </button>
            </div>
        @endif
    </header>

    <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="bg-carbonSoft rounded-xl p-6 border border-petronas">
            <p class="text-sm text-muted uppercase tracking-wide">Forecast Demand</p>
            <div class="flex items-end gap-2 mt-2">
                <span class="text-3xl font-bold text-silver">{{ number_format($plan->forecast_qty) }}</span>
                <span class="text-sm text-muted mb-1 font-normal">Units</span>
            </div>
        </div>

        <div class="bg-carbonSoft rounded-xl p-6 border border-petronas/50 hover:border-petronas transition-colors">
            <p class="text-sm text-muted uppercase tracking-wide">Stock at Planning</p>
            <div class="flex items-end gap-2 mt-2">
                <span class="text-3xl font-bold text-silver">{{ number_format($plan->current_stock_snapshot) }}</span>
                <span class="text-sm text-muted mb-1 font-normal">Units</span>
            </div>
        </div>

        <div class="bg-carbonSoft rounded-xl p-6 border border-petronas/50 hover:border-petronas transition-colors relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-petronas" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
            </div>
            <p class="text-sm text-muted uppercase tracking-wide">Target Production</p>
            <div class="flex items-end gap-2 mt-2">
                <span class="text-4xl font-extrabold text-petronas">{{ number_format($plan->recommended_production_qty) }}</span>
                <span class="text-sm text-silver mb-1 font-normal">Units</span>
            </div>
        </div>
    </section>

    @if($plan->status == 'completed')
        <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-success flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Production Realization Report
                </h2>
                <span class="text-[10px] text-muted uppercase tracking-wide">
                    Historical Data
                </span>
            </div>

            <div class="overflow-x-auto -mx-3 px-3">
                <table class="w-full text-sm">
                    <thead class="bg-carbon sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="px-3 py-2 text-left text-muted font-normal">Material Name</th>
                            <th class="px-3 py-2 text-left text-muted font-normal">Usage Date</th>
                            <th class="px-3 py-2 text-right text-muted font-normal">Actual Qty Used</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usageReport as $item)
                            <tr class="border-b border-carbon hover:bg-carbon transition-colors">
                                <td class="px-3 py-3 font-semibold text-silver">
                                    {{ $item['material_name'] }}
                                </td>
                                <td class="px-3 py-3 text-muted">
                                    {{ \Carbon\Carbon::parse($item['date_used'])->format('d M Y, H:i') }}
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <span class="font-bold text-white">{{ number_format($item['qty_used']) }}</span>
                                    <span class="text-[10px] font-normal text-muted ml-1">Units</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-8 text-center text-muted italic">
                                    Tidak ada data penggunaan material untuk plan ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    @else
        <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-petronas flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Material Requirement Planning (BOM)
                </h2>
                <span class="text-[10px] text-muted uppercase tracking-wide">
                    Estimasi Kebutuhan
                </span>
            </div>

            <div class="overflow-x-auto -mx-3 px-3">
                <table class="w-full text-sm">
                    <thead class="bg-carbon sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="px-3 py-2 text-left text-muted font-normal">Material Name</th>
                            <th class="px-3 py-2 text-right text-muted font-normal">Current Stock</th>
                            <th class="px-3 py-2 text-right text-muted font-normal">Est. Needed</th>
                            <th class="px-3 py-2 text-right text-muted font-normal">Shortage (To Buy)</th>
                            <th class="px-3 py-2 text-center text-muted font-normal">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseList as $item)
                            <tr class="border-b border-carbon hover:bg-carbon transition-colors">
                                <td class="px-3 py-3 font-semibold text-silver">
                                    {{ $item['material_name'] }}
                                </td>
                                <td class="px-3 py-3 text-right text-muted">
                                    {{ number_format($item['current_stock']) }}
                                </td>
                                <td class="px-3 py-3 text-right text-silver">
                                    {{ number_format($item['needed']) }}
                                </td>
                                <td class="px-3 py-3 text-right">
                                    @if($item['must_buy'] > 0)
                                        <span class="text-danger font-bold">
                                            {{ number_format($item['must_buy']) }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if($item['must_buy'] > 0)
                                        <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold bg-danger/10 text-danger border border-danger/20 uppercase tracking-wide">
                                            Restock
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold bg-success/10 text-success border border-success/20 uppercase tracking-wide">
                                            OK
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-8 text-center text-muted italic">
                                    Tidak ada data resep (BOM) untuk produk ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

</main>

<div id="actionModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div id="modalBox" class="bg-carbonSoft rounded-xl p-6 w-full max-w-md border transition-colors duration-300 border-petronas shadow-2xl">
        
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
    const modal = document.getElementById('actionModal');
    const modalBox = document.getElementById('modalBox');
    const modalTitle = document.getElementById('modalTitle');
    const modalDesc = document.getElementById('modalDesc');
    const actionForm = document.getElementById('actionForm');
    const confirmBtn = document.getElementById('confirmBtn');

    function openActionModal(type, planId) {
        // Ganti URL sesuai route Anda
        actionForm.action = `/production-plans/${planId}/${type}`;

        if (type === 'approve') {
            modalBox.classList.remove('border-danger');
            modalBox.classList.add('border-petronas');
            
            modalTitle.innerText = 'Confirm Approval';
            modalTitle.classList.remove('text-danger');
            modalTitle.classList.add('text-petronas');
            
            modalDesc.innerHTML = `Are you sure you want to approve this plan?<br><span class="text-petronas font-semibold">Material requests will be generated.</span>`;
            
            confirmBtn.innerText = 'Approve Plan';
            confirmBtn.classList.remove('bg-danger', 'hover:bg-danger/90', 'shadow-red-500/30', 'text-white');
            confirmBtn.classList.add('bg-petronas', 'text-blackBase', 'hover:bg-petronas/90', 'shadow-[0_0_15px_rgba(0,161,155,0.3)]');
            
        } else {
            modalBox.classList.remove('border-petronas');
            modalBox.classList.add('border-danger');
            
            modalTitle.innerText = 'Confirm Rejection';
            modalTitle.classList.remove('text-petronas');
            modalTitle.classList.add('text-danger');
            
            modalDesc.innerHTML = `Are you sure you want to reject this plan?<br><span class="text-danger font-semibold">This action cannot be undone.</span>`;
            
            confirmBtn.innerText = 'Reject Plan';
            confirmBtn.classList.remove('bg-petronas', 'text-blackBase', 'hover:bg-petronas/90', 'shadow-[0_0_15px_rgba(0,161,155,0.3)]');
            confirmBtn.classList.add('bg-danger', 'text-white', 'hover:bg-danger/90', 'shadow-[0_0_15px_rgba(239,68,68,0.3)]');
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeActionModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeActionModal();
    });
</script>

</body>
</html>