<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Orders | Production Planning System</title>
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

    {{-- Breadcrumbs --}}
    <nav aria-label="breadcrumb" class="text-xs text-muted">
        <ol class="flex items-center space-x-2">
            <li><a href="{{ route('home.index') }}" class="hover:text-petronas transition-colors">Home</a></li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold">Purchase Orders</li>
        </ol>
    </nav>

    <x-alert-messages />

    {{-- Header --}}
    <header>
        <p class="text-xs uppercase tracking-widest text-muted">Procurement Data</p>
        <h1 class="text-3xl font-extrabold text-petronas">Purchase Order Management</h1>
        <p class="text-sm text-muted mt-1">Kelola pembelian bahan baku ke supplier, penerimaan barang, dan pembayaran.</p>
    </header>

    {{-- Form Section: Create Purchase Order --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6 shadow-lg shadow-blackBase">
        <h2 class="text-lg font-bold text-petronas">Create Purchase Order (Draft)</h2>

        <form action="{{ route('purchases.store') }}" method="POST" class="space-y-6">
            @csrf
            
            {{-- ROW 1: INFORMASI PO --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="text-xs text-muted uppercase tracking-wide block mb-1">No. PO (Opsional)</label>
                    <input type="text" name="po_number" placeholder="(Auto Generated)"
                        class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas placeholder-muted/30 transition">
                </div>

                <div>
                    <label class="text-xs text-muted uppercase tracking-wide block mb-1">Tanggal Order</label>
                    <input type="date" name="order_date" value="{{ date('Y-m-d') }}" required
                        class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas appearance-none transition">
                </div>

                <div>
                    <label class="text-xs text-muted uppercase tracking-wide block mb-1">Jatuh Tempo</label>
                    <input type="date" name="due_date" 
                        class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas appearance-none transition">
                </div>
            </div>

            {{-- ROW 2: DATA SUPPLIER (DALAM 1 KOTAK) --}}
            <div class="bg-carbon/40 p-5 rounded-xl border border-carbon space-y-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-bold text-petronas uppercase tracking-wider">
                        Data Supplier
                    </h3>
                </div>

                {{-- Partner Select --}}
                <div>
                    <label class="text-xs text-muted uppercase tracking-wide mb-1 block">Pilih Supplier</label>
                    <div class="relative">
                        <select name="partner_id" id="partnerSelect" onchange="fillPartnerDetails()" required
                            class="w-full px-4 py-2 rounded-lg bg-carbon border border-petronas/30 text-silver focus:outline-none focus:border-petronas focus:ring-1 focus:ring-petronas appearance-none cursor-pointer transition">
                            <option value="" disabled selected>-- Klik untuk memilih --</option>
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" 
                                    data-company="{{ $partner->company_name }}"
                                    data-person="{{ $partner->person_name }}"
                                    data-phone="{{ $partner->phone }}"
                                    data-email="{{ $partner->email }}"
                                    data-address="{{ $partner->address }}">
                                    {{ $partner->company_name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-petronas">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                </div>

                {{-- Hidden Snapshot Input --}}
                <input type="hidden" name="company_name" id="snap_company">

                {{-- Readonly Details --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                    <div>
                        <label class="text-[10px] text-muted uppercase block mb-1">Contact Person</label>
                        <input type="text" name="person_name" id="snap_person" readonly
                            class="w-full px-3 py-2 rounded bg-blackBase/50 border border-carbon text-muted text-sm focus:outline-none cursor-not-allowed">
                    </div>
                    <div>
                        <label class="text-[10px] text-muted uppercase block mb-1">Phone</label>
                        <input type="text" name="phone" id="snap_phone" readonly
                            class="w-full px-3 py-2 rounded bg-blackBase/50 border border-carbon text-muted text-sm focus:outline-none cursor-not-allowed">
                    </div>
                    <div>
                        <label class="text-[10px] text-muted uppercase block mb-1">Email</label>
                        <input type="email" name="email" id="snap_email" readonly
                            class="w-full px-3 py-2 rounded bg-blackBase/50 border border-carbon text-muted text-sm focus:outline-none cursor-not-allowed">
                    </div>
                    <div class="md:col-span-3">
                        <label class="text-[10px] text-muted uppercase block mb-1">Alamat Lengkap</label>
                        <input type="text" name="address" id="snap_address" readonly
                            class="w-full px-3 py-2 rounded bg-blackBase/50 border border-carbon text-muted text-sm focus:outline-none cursor-not-allowed">
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-petronas text-blackBase font-bold px-6 py-2 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20">
                    Create Purchase Order
                </button>
            </div>
        </form>
    </section>

    {{-- List Purchase Orders --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <h2 class="text-lg font-bold text-petronas">Purchase Order List</h2>
            
            <form action="{{ route('purchases.index') }}" method="GET" class="flex gap-2 w-full md:w-auto">
                <input type="text" name="search" placeholder="Search PO No. or Supplier..." value="{{ request('search') }}"
                    class="w-full md:w-64 px-4 py-2 bg-carbon rounded-lg text-xs text-silver focus:outline-none border border-transparent focus:border-petronas">
                <button type="submit" class="px-4 py-2 bg-carbon border border-muted text-xs rounded-lg hover:text-petronas transition">Search</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-carbon">
                    <tr>
                        <th class="px-4 py-3 text-left text-muted text-xs uppercase tracking-wide border-b border-carbonSoft">Date</th>
                        <th class="px-4 py-3 text-left text-muted text-xs uppercase tracking-wide border-b border-carbonSoft">PO No.</th>
                        <th class="px-4 py-3 text-left text-muted text-xs uppercase tracking-wide border-b border-carbonSoft">Supplier</th>
                        <th class="px-4 py-3 text-center text-muted text-xs uppercase tracking-wide border-b border-carbonSoft">Status</th>
                        <th class="px-4 py-3 text-center text-muted text-xs uppercase tracking-wide border-b border-carbonSoft">Payment</th>
                        <th class="px-4 py-3 text-right text-muted text-xs uppercase tracking-wide border-b border-carbonSoft">Total</th>
                        <th class="px-4 py-3 text-right text-muted text-xs uppercase tracking-wide border-b border-carbonSoft">Balance</th>
                        <th class="px-4 py-3 text-center text-muted text-xs uppercase tracking-wide border-b border-carbonSoft">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon/50">
                    @forelse ($purchaseOrders as $po)
                        <tr class="hover:bg-carbon transition-colors group">
                            
                            {{-- Date --}}
                            <td class="px-4 py-3 text-silver font-mono text-xs">
                                {{ \Carbon\Carbon::parse($po->order_date)->format('d/m/Y') }}
                            </td>
                            
                            {{-- PO No --}}
                            <td class="px-4 py-3 font-semibold text-petronas font-mono text-xs">
                                {{ $po->po_number }}
                            </td>
                            
                            {{-- Supplier --}}
                            <td class="px-4 py-3 text-silver">
                                {{ $po->company_name ?? '-' }}
                            </td>
                            
                            {{-- Status Badge --}}
                            <td class="px-4 py-3 text-center">
                                @php
                                    $statusColor = match($po->status) {
                                        'draft'     => 'bg-gray-800 text-gray-400 border-gray-600',
                                        'ordered'   => 'bg-blue-900/30 text-blue-400 border-blue-800',   // OTW
                                        'received'  => 'bg-petronas/20 text-petronas border-petronas',   // Done
                                        'cancelled' => 'bg-red-900/30 text-red-400 border-red-800',
                                        default     => 'bg-carbon text-muted'
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase border {{ $statusColor }}">
                                    {{ $po->status }}
                                </span>
                            </td>

                            {{-- Payment Badge --}}
                            <td class="px-4 py-3 text-center">
                                @php
                                    $payColor = match($po->payment_status) {
                                        'paid'    => 'text-success bg-success/10 border-success/30',
                                        'partial' => 'text-warning bg-warning/10 border-warning/30',
                                        'unpaid'  => 'text-danger bg-danger/10 border-danger/30',
                                        default   => 'text-muted'
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase border {{ $payColor }}">
                                    {{ $po->payment_status }}
                                </span>
                            </td>

                            {{-- Grand Total --}}
                            <td class="px-4 py-3 text-right font-bold text-silver">
                                Rp {{ number_format($po->grand_total, 0, ',', '.') }}
                            </td>

                            {{-- Balance (Sisa Hutang) --}}
                            <td class="px-4 py-3 text-right font-mono text-xs {{ $po->remaining_balance > 0 ? 'text-red-400' : 'text-muted' }}">
                                {{ number_format($po->remaining_balance, 0, ',', '.') }}
                            </td>

                            {{-- Action --}}
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center items-center gap-2">
                                    <a href="{{ route('purchases.show', $po->id) }}" 
                                       class="w-8 h-8 flex items-center justify-center rounded bg-petronas/10 text-petronas border border-petronas/30 hover:bg-petronas hover:text-blackBase transition"
                                       title="View Detail">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>

                                    <a href="{{ route('purchases.showPayments', $po->id) }}" 
                                       class="w-8 h-8 flex items-center justify-center rounded bg-carbon text-silver border border-muted/30 hover:border-petronas hover:text-petronas transition"
                                       title="Payment History">
                                        <span class="text-sm">💸</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-8 italic">
                                No purchase orders found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $purchaseOrders->links('pagination::tailwind') }}
        </div>
    </section>

</main>

<script>
    function fillPartnerDetails() {
        const select = document.getElementById('partnerSelect');
        const selectedOption = select.options[select.selectedIndex];
        
        const company = selectedOption.getAttribute('data-company') || '';
        const person  = selectedOption.getAttribute('data-person') || '';
        const phone   = selectedOption.getAttribute('data-phone') || '';
        const email   = selectedOption.getAttribute('data-email') || '';
        const address = selectedOption.getAttribute('data-address') || '';

        // Isi Snapshot Hidden Fields
        document.getElementById('snap_company').value = company;
        
        // Isi Form Readonly
        document.getElementById('snap_person').value  = person;
        document.getElementById('snap_phone').value   = phone;
        document.getElementById('snap_email').value   = email;
        document.getElementById('snap_address').value = address;
    }
</script>

</body>
</html>