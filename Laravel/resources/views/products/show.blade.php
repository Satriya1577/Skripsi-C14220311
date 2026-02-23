<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $product->name }} - Detail | Production Planning System</title>
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
            <li><a href="{{ route('products.index') }}" class="hover:text-petronas transition-colors">Products</a></li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold" aria-current="page">View & Recipe</li>
        </ol>
    </nav>

    <x-alert-messages />

    <header class="flex justify-between items-end">
        <div>
            <p class="text-xs uppercase tracking-widest text-muted">Product Details & Formulation</p>
            <h1 class="text-3xl font-extrabold text-petronas">{{ $product->name }}</h1>
            <p class="text-sm text-muted mt-1">
                <span class="bg-carbon px-2 py-1 rounded text-xs font-mono mr-2 border border-carbonSoft">{{ $product->code }}</span>
                {{ $product->packaging ?? 'No Packaging Info' }}
            </p>
        </div>
    </header>

    {{-- SECTION 1: PRODUCT SPECIFICATION --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
        <h2 class="text-lg font-bold text-petronas">Product Specification</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
            {{-- Stock Info --}}
            <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                <p class="text-xs text-muted uppercase tracking-wide mb-1">On Hand Stock</p>
                <p class="text-2xl font-bold text-silver">{{ number_format($product->current_stock) }}</p>
            </div>
            
            <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                <p class="text-xs text-muted uppercase tracking-wide mb-1">Reserved Stock</p>
                <p class="text-2xl font-bold text-petronas">{{ number_format($product->committed_stock) }}</p>
            </div>

            <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                <p class="text-xs text-muted uppercase tracking-wide mb-1">Available Stock</p>
                <div class="flex items-end gap-1">
                    <p class="text-2xl font-bold text-white">{{ number_format($product->current_stock - $product->committed_stock) }}</p>
                </div>
                <p class="text-[10px] text-muted border-t border-white/10 mt-1 pt-1">
                    Safety Stock: {{ number_format($product->safety_stock) }}
                </p>
            </div>

            <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                <p class="text-xs text-muted uppercase tracking-wide mb-1">Selling Price</p>
                <p class="text-2xl font-bold text-silver">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                <p class="text-[10px] text-muted border-t border-white/10 mt-1 pt-1">
                    HPP: Rp {{ number_format($product->cost_price, 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- LEAD TIME & PRODUCTION INFO --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                <div class="flex justify-between items-start mb-2">
                    <p class="text-xs text-muted uppercase tracking-wide">Lead Time Management</p>
                    <span class="text-[10px] uppercase px-2 py-0.5 rounded border 
                        {{ $product->is_manual_lead_time === 'manual' ? 'bg-gray-800 text-gray-300 border-gray-600' : 'bg-petronas/10 text-petronas border-petronas/30' }}">
                        {{ $product->is_manual_lead_time === 'manual' ? 'Manual' : 'Automatic' }}
                    </span>
                </div>
                <div class="flex gap-6 items-center">
                    <div>
                        <span class="text-xs text-muted block">Range (Min-Max)</span>
                        <span class="text-lg font-bold text-silver">{{ $product->min_lead_time_days }} - {{ $product->max_lead_time_days }} Hari</span>
                    </div>
                    <div class="h-8 w-px bg-white/10"></div>
                    <div>
                        <span class="text-xs text-muted block">Rata-rata Aktual</span>
                        <span class="text-lg font-bold text-petronas">{{ number_format($product->lead_time_average, 1) }} Hari</span>
                    </div>
                </div>
            </div>

            <div class="bg-carbon rounded-lg p-4 border border-carbonSoft">
                <p class="text-xs text-muted uppercase tracking-wide mb-1">Production Config</p>
                <div class="flex gap-6 items-center">
                    <div>
                        <span class="text-xs text-muted block">Batch Size (Lot)</span>
                        <span class="text-lg font-bold text-silver">{{ number_format($product->batch_size) }} Pcs</span>
                    </div>
                    {{-- Placeholder untuk info lain jika ada, misal Machine Capacity --}}
                </div>
                <p class="text-[10px] text-muted mt-2">
                    *Batch Size digunakan sebagai acuan jumlah produksi minimum per siklus.
                </p>
            </div>
        </div>
    </section>

    {{-- SECTION 2: RECIPE MANAGEMENT --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-petronas/30 shadow-lg shadow-petronas/5 relative overflow-hidden">
        <div class="relative z-10">
            <h2 class="text-lg font-bold text-petronas mb-1">
                Product Recipe (Bill of Materials)
            </h2>
            <p class="text-xs text-muted mb-4">Tambahkan bahan baku yang dibutuhkan untuk memproduksi <strong>1 Unit</strong> produk ini.</p>
            
            <form action="{{ route('product_materials.store', $product->id) }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="grow w-full md:w-3/5">
                        <label class="text-xs text-silver font-semibold uppercase tracking-wide mb-2 block">Select Material</label>
                        <select name="material_id" id="materialSelect" required class="w-full appearance-none bg-carbon border border-muted/30 text-silver text-sm rounded-lg focus:ring-petronas focus:border-petronas block w-full p-3 pr-10">
                            <option value="" disabled selected>-- Choose Material --</option>
                            @foreach($materials as $material)
                                <option value="{{ $material->id }}" data-unit="{{ $material->unit }}">
                                    {{ $material->code }} - {{ $material->name }} (Satuan: {{ $material->unit }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full md:w-1/4">
                        <label class="text-xs text-silver font-semibold uppercase tracking-wide mb-2 block">Usage Quantity</label>
                        <div class="relative">
                            <input type="number" step="0.001" name="amount_needed" placeholder="0.00" required class="block w-full p-3 bg-carbon border border-muted/30 rounded-lg text-white font-mono focus:ring-1 focus:ring-petronas focus:border-petronas pr-12">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <span class="text-muted text-xs uppercase" id="unitLabel">Unit</span>
                            </div>
                        </div>
                    </div>
                    <div class="w-full md:w-auto">
                        <button type="submit" class="w-full md:w-auto bg-petronas text-blackBase font-bold px-6 py-3 rounded-lg hover:bg-petronas/90 transition shadow-lg flex items-center justify-center gap-2"><span>+ Add</span></button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    {{-- SECTION 3: RECIPE LIST --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
         <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-petronas">Current Recipe List</h2>
            <span class="text-xs bg-carbon px-3 py-1 rounded-full text-muted border border-carbon">Items: {{ $product->productMaterials->count() }}</span>
        </div>
        <div class="overflow-x-auto rounded-lg border border-carbon">
            <table class="w-full text-sm">
                <thead class="bg-carbon">
                    <tr>
                        <th class="px-4 py-3 text-left text-muted uppercase text-xs tracking-wider">Material Code</th>
                        <th class="px-4 py-3 text-left text-muted uppercase text-xs tracking-wider">Material Name</th>
                        <th class="px-4 py-3 text-right text-muted uppercase text-xs tracking-wider">Qty Usage</th>
                        <th class="px-4 py-3 text-center text-muted uppercase text-xs tracking-wider">Unit</th>
                        <th class="px-4 py-3 text-right text-muted uppercase text-xs tracking-wider">Est. Cost</th>
                        <th class="px-4 py-3 text-center text-muted uppercase text-xs tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon/50">
                    @forelse ($product->productMaterials as $pm)
                        <tr class="hover:bg-carbon transition-colors group">
                            <td class="px-4 py-3 font-mono text-petronas">{{ $pm->material->code }}</td>
                            <td class="px-4 py-3 text-silver">{{ $pm->material->name }}</td>
                            <td class="px-4 py-3 text-right font-bold text-white">{{ number_format($pm->amount_needed, 2) }}</td>
                            <td class="px-4 py-3 text-center text-muted">{{ $pm->material->unit }}</td>
                            <td class="px-4 py-3 text-right text-muted font-mono text-xs">
                                {{ number_format($pm->amount_needed * $pm->material->price_per_unit, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <form action="{{ route('product_materials.destroy', $pm->id) }}" method="POST" onsubmit="return confirm('Remove ingredient?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-500 transition">✕</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-muted italic">No ingredients added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- SECTION 4: STOCK ADJUSTMENT --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
        <h2 class="text-lg font-bold text-petronas">Stock Adjustment (Opname)</h2>
        <form action="{{ route('products.adjustment.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">

            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Jumlah Stok Aktual</label>
                <div class="relative mt-1">
                    <input type="number" step="1" name="actual_qty" value="{{ old('actual_qty') }}" required
                        class="w-full pl-4 pr-12 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none text-silver">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <span class="text-muted text-xs uppercase">Pcs</span>
                    </div>
                </div>
                <p class="text-xs text-muted mt-1">Masukkan jumlah stok fisik real produk jadi saat ini.</p>
            </div>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Catatan</label>
                <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Alasan penyesuaian..."
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none text-silver">
            </div>

            @if($product->cost_price == 0)
                <div class="md:col-span-2 bg-yellow-900/20 border border-yellow-700/50 p-4 rounded-lg">
                    <div class="flex items-start space-x-3">
                        <div class="text-yellow-500 mt-1">⚠️</div>
                        <div class="w-full">
                            <p class="text-sm text-yellow-500 font-bold">HPP Dasar Belum Ada</p>
                            <p class="text-xs text-muted mb-2">Mohon isi estimasi biaya produksi (HPP) per unit.</p>
                            <label class="text-xs text-muted uppercase tracking-wide">Estimasi HPP per Unit</label>
                            <input type="number" name="manual_price" 
                                class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-yellow-500 focus:outline-none text-silver">
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
    </section>

    {{-- SECTION 5: RIWAYAT TRANSAKSI --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-petronas">Riwayat Transaksi</h2>
            <div class="flex gap-2">
                <span class="text-xs bg-carbon px-3 py-1.5 rounded-lg text-muted border border-carbon">
                    Total Data: {{ $transactions->total() }}
                </span>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-carbon">
            <table class="w-full text-sm">
                <thead class="bg-carbon text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left text-muted border-b border-carbonSoft">Tanggal</th>
                        <th class="px-4 py-3 text-center text-muted border-b border-carbonSoft">Tipe</th>
                        <th class="px-4 py-3 text-left text-muted border-b border-carbonSoft">Ref. & Keterangan</th>
                        <th class="px-4 py-3 text-right text-muted border-b border-carbonSoft">Masuk</th>
                        <th class="px-4 py-3 text-right text-muted border-b border-carbonSoft">Keluar</th>
                        <th class="px-4 py-3 text-right text-silver font-bold border-b border-carbonSoft bg-carbon/50">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon/50">
                    @forelse($transactions as $trx)
                        <tr class="hover:bg-carbon transition-colors group">
                            <td class="px-4 py-3 text-silver font-mono text-xs whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($trx->transaction_date)->format('d/m/Y') }}
                                <span class="block text-[10px] text-muted">
                                    {{ $trx->created_at->format('H:i') }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-center">
                                @if($trx->type == 'production_in')
                                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase text-success bg-success/10 border border-success/30">
                                        PROD IN
                                    </span>
                                @elseif($trx->type == 'sales_out')
                                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase text-danger bg-danger/10 border border-danger/30">
                                        SALES OUT
                                    </span>
                                @elseif($trx->type == 'return_in')
                                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase text-warning bg-warning/10 border border-warning/30">
                                        RETURN IN
                                    </span>
                                @elseif($trx->type == 'adjustment')
                                    @if($trx->qty >= 0)
                                        <span class="px-2 py-1 rounded text-[10px] font-bold uppercase text-blue-400 bg-blue-400/10 border border-blue-400/30">
                                            ADJ IN
                                        </span>
                                    @else
                                        <span class="px-2 py-1 rounded text-[10px] font-bold uppercase text-orange-400 bg-orange-400/10 border border-orange-400/30">
                                            ADJ OUT
                                        </span>
                                    @endif
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="text-silver text-sm font-bold">
                                    {{ $trx->reference ?? '-' }}
                                </div>
                                <div class="text-xs text-muted mt-0.5 truncate max-w-xs">
                                    {{ $trx->description }}
                                </div>
                            </td>

                            <td class="px-4 py-3 text-right font-mono text-success">
                                @if($trx->qty > 0)
                                    +{{ number_format($trx->qty) }}
                                @else
                                    <span class="text-carbonSoft">-</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right font-mono text-danger"> 
                                @if($trx->qty < 0)
                                    {{ number_format($trx->qty) }} @else
                                    <span class="text-carbonSoft">-</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right font-mono font-bold text-white bg-carbon/20">
                                {{ number_format($trx->current_stock_balance) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-muted italic bg-carbon/20 rounded-b-lg">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="text-2xl">📦</span>
                                    <p>Belum ada pergerakan stok untuk produk ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $transactions->links('pagination::tailwind') }}
        </div>
    </section>

</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const materialSelect = document.getElementById('materialSelect');
        const unitLabel = document.getElementById('unitLabel');

        function updateUnitLabel() {
            if(!materialSelect) return;
            const selectedOption = materialSelect.options[materialSelect.selectedIndex];
            const unit = selectedOption.getAttribute('data-unit');
            unitLabel.innerText = unit ? unit : 'Qty';
        }

        if(materialSelect) {
            materialSelect.addEventListener('change', updateUnitLabel);
        }
    });
</script>

</body>
</html>