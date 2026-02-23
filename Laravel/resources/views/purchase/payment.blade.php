<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment - {{ $purchaseOrder->po_number }} | Production Planning System</title>
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
            <li><a href="{{ route('purchases.index') }}" class="hover:text-petronas transition-colors">Purchase Orders</a></li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold" aria-current="page">Payment</li>
        </ol>
    </nav>

    <x-alert-messages />

    {{-- HEADER --}}
    <header class="flex justify-between items-end">
        <div>
            <div class="flex items-center gap-3">
                <p class="text-xs uppercase tracking-widest text-muted">Procurement Payment</p>
                @php
                    $badgeColor = match($purchaseOrder->payment_status) {
                        'paid' => 'bg-green-900/30 text-green-400 border-green-800',
                        'partial' => 'bg-yellow-900/30 text-yellow-400 border-yellow-800',
                        'unpaid' => 'bg-red-900/30 text-red-400 border-red-800',
                        default => 'bg-carbon'
                    };
                @endphp
                <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold border {{ $badgeColor }}">
                    {{ $purchaseOrder->payment_status }}
                </span>
            </div>
            <h1 class="text-3xl font-extrabold text-petronas mt-1">Pembayaran: {{ $purchaseOrder->po_number }}</h1>
            <p class="text-sm text-muted mt-1">
                Supplier: <span class="font-bold text-silver">{{ $purchaseOrder->company_name }}</span>
            </p>
        </div>
    </header>

    {{-- SECTION 1: SUMMARY CARD --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
            {{-- Grand Total --}}
            <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                <p class="text-xs text-muted uppercase tracking-wide mb-1">Total Pembelian (Grand Total)</p>
                <p class="text-xl font-bold text-silver">Rp {{ number_format($purchaseOrder->grand_total, 0, ',', '.') }}</p>
            </div>

            {{-- Sudah Dibayar --}}
            <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                <p class="text-xs text-muted uppercase tracking-wide mb-1">Sudah Dibayar (Out)</p>
                <p class="text-xl font-bold text-success">Rp {{ number_format($purchaseOrder->paid_amount, 0, ',', '.') }}</p>
            </div>

            {{-- Sisa Hutang --}}
            <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                <p class="text-xs text-muted uppercase tracking-wide mb-1">Sisa Hutang</p>
                <p class="text-xl font-bold {{ $purchaseOrder->remaining_balance > 0 ? 'text-danger' : 'text-muted' }}">
                    Rp {{ number_format($purchaseOrder->remaining_balance, 0, ',', '.') }}
                </p>
            </div>
        </div>
    </section>

    {{-- SECTION 2: STATUS & FORM PEMBAYARAN BARU --}}
    
    {{-- KONDISI 1: JIKA STATUS DRAFT (THEME WARNING) --}}
    @if($purchaseOrder->status == 'draft')
        <div class="bg-warning/10 border border-warning/30 rounded-xl p-6 flex items-center gap-4">
            <div class="p-3 bg-warning/20 rounded-full text-warning shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-warning">Purchase Order Masih Draft</h3>
                <p class="text-sm text-silver mt-1">
                    Silakan konfirmasi pesanan (Place Order) terlebih dahulu di halaman detail transaksi untuk mengaktifkan pembayaran.
                </p>
            </div>
        </div>

    {{-- KONDISI 2: JIKA STATUS CANCELLED (THEME DANGER) --}}
    @elseif($purchaseOrder->status == 'cancelled')
        <div class="bg-danger/10 border border-danger/30 rounded-xl p-6 flex items-center gap-4">
            <div class="p-3 bg-danger/20 rounded-full text-danger shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-danger">Pesanan Dibatalkan</h3>
                <p class="text-sm text-silver mt-1">
                    Purchase Order ini telah dibatalkan. Fitur pembayaran dinonaktifkan.
                </p>
            </div>
        </div>

    {{-- KONDISI 3: FORM INPUT BAYAR --}}
    @elseif($purchaseOrder->remaining_balance > 0)
        <section class="bg-carbonSoft rounded-xl p-6 border border-petronas/30 shadow-lg shadow-petronas/5">
            <h2 class="text-lg font-bold text-petronas mb-4">Input Pembayaran Keluar (Out)</h2>
            
            <form action="{{ route('purchase_payments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    {{-- Tanggal Bayar --}}
                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide block mb-1">Tanggal Bayar</label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                            class="w-full px-4 py-2.5 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas transition">
                    </div>

                    {{-- Jumlah Bayar --}}
                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide block mb-1">Jumlah Bayar</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-muted text-sm">Rp</span>
                            <input type="number" name="amount" max="{{ $purchaseOrder->remaining_balance }}" step="0.01" required
                                class="w-full pl-10 pr-4 py-2.5 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas transition"
                                placeholder="Maks: {{ $purchaseOrder->remaining_balance }}">
                        </div>
                        <p class="text-[10px] text-muted mt-1 italic">* Tidak boleh melebihi sisa hutang.</p>
                    </div>

                    {{-- Metode Bayar --}}
                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide block mb-1">Metode Pembayaran</label>
                        <select name="payment_method" class="w-full px-4 py-2.5 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas transition appearance-none cursor-pointer">
                            <option value="Transfer Bank">Transfer Bank</option>
                            <option value="Tunai">Tunai / Cash</option>
                            <option value="Giro">Giro</option>
                            <option value="Cek">Cek</option>
                        </select>
                    </div>

                    {{-- Ref No --}}
                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide block mb-1">No. Referensi / Bukti</label>
                        <input type="text" name="reference_number" placeholder="Contoh: TRF-OUT-001"
                            class="w-full px-4 py-2.5 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas transition">
                    </div>

                    {{-- Catatan (Full Width) --}}
                    <div class="md:col-span-2 lg:col-span-4">
                        <label class="text-xs text-muted uppercase tracking-wide block mb-1">Catatan Tambahan (Opsional)</label>
                        <input type="text" name="notes" placeholder="Keterangan pembayaran ke supplier..."
                            class="w-full px-4 py-2.5 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas transition">
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="bg-petronas text-blackBase font-bold px-8 py-2.5 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20 flex items-center gap-2">
                        <span>Simpan Pembayaran</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </form>
        </section>

    {{-- KONDISI 4: JIKA LUNAS (THEME SUCCESS) --}}
    @else
        <div class="bg-success/10 border border-success/30 rounded-xl p-6 flex items-center gap-4">
            <div class="p-3 bg-success/20 rounded-full text-success shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-success">Lunas!</h3>
                <p class="text-sm text-silver mt-1">Semua tagihan ke supplier ini telah dibayar lunas.</p>
            </div>
        </div>
    @endif

    {{-- SECTION 3: RIWAYAT PEMBAYARAN --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
        <h2 class="text-lg font-bold text-petronas mb-4">Riwayat Pembayaran</h2>

        <div class="overflow-x-auto rounded-lg border border-carbon">
            <table class="w-full text-sm">
                <thead class="bg-carbon">
                    <tr>
                        <th class="px-4 py-3 text-left text-muted uppercase text-xs tracking-wider">Tanggal</th>
                        <th class="px-4 py-3 text-left text-muted uppercase text-xs tracking-wider">Metode</th>
                        <th class="px-4 py-3 text-left text-muted uppercase text-xs tracking-wider">No. Ref</th>
                        <th class="px-4 py-3 text-left text-muted uppercase text-xs tracking-wider">Catatan</th>
                        <th class="px-4 py-3 text-right text-muted uppercase text-xs tracking-wider">Jumlah</th>
                        <th class="px-4 py-3 text-center text-muted uppercase text-xs tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon/50">
                    @forelse($payments as $payment)
                        <tr class="hover:bg-carbon transition-colors">
                            <td class="px-4 py-3 text-silver font-mono text-xs">
                                {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 text-silver font-bold">
                                {{ $payment->payment_method }}
                            </td>
                            <td class="px-4 py-3 text-muted font-mono text-xs">
                                {{ $payment->reference_number ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-muted text-xs italic">
                                {{ $payment->notes ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-right text-success font-mono font-bold">
                                Rp {{ number_format($payment->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <form action="{{ route('purchase_payments.destroy', $payment->id) }}" method="POST" onsubmit="return confirm('Hapus pembayaran ini? Saldo hutang akan kembali bertambah.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-500 transition" title="Hapus Pembayaran">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-muted italic">
                                Belum ada riwayat pembayaran.
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