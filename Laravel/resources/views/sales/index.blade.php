<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Management | Production Planning System</title>
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
                        muted: '#9DA3A6'
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
            <li class="text-petronas font-semibold">Sales</li>
        </ol>
    </nav>

    <x-alert-messages />

    <header>
        <p class="text-xs uppercase tracking-widest text-muted">Transaction Data</p>
        <h1 class="text-3xl font-extrabold text-petronas">Sales Management</h1>
        <p class="text-sm text-muted mt-1">Rekapitulasi penjualan produk jadi</p>
    </header>

    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
        <h2 class="text-lg font-bold text-petronas">Input Sales Transaction</h2>
        
        <form action="{{ route('sales.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @csrf
            <input type="hidden" name="sale_id" id="sale_id">
            
            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Tanggal Transaksi</label>
                <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" required
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none text-silver appearance-none">
            </div>

            <div class="lg:col-span-1">
                <label class="text-xs text-muted uppercase tracking-wide">Pilih Produk</label>
                <select name="product_id" required 
                        onchange="updatePriceSuggestion(this)"
                        class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none text-silver">
                    <option value="" disabled selected>-- Select Product --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" data-price="{{ $product->price ?? 0 }}">
                            {{ $product->name }} (Stok: {{ $product->current_stock ?? 0 }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="text-xs text-muted uppercase tracking-wide">Nama Distributor</label>
                <input type="text" name="nama_distributor" id="distributor_name" required
                       placeholder="Cth: PT. Distribusi Maju Jaya"
                       class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none text-silver placeholder-muted/50">
            </div>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Quantity Sold</label>
                <input type="number" name="quantity_sold" id="qty" min="1" required
                       oninput="calculateTotal()"
                       class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none">
            </div>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Harga Satuan (Rp)</label>
                <input type="number" name="price_per_unit" id="price" min="0" required
                       oninput="calculateTotal()"
                       class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none">
            </div>

            <div class="lg:col-span-2">
                <label class="text-xs text-muted uppercase tracking-wide">Total (Auto)</label>
                <input type="text" id="total_display" readonly
                       class="w-full mt-1 px-4 py-2 rounded-lg bg-blackBase border border-carbon text-petronas font-bold focus:outline-none cursor-not-allowed">
            </div>

            <div class="md:col-span-2 lg:col-span-4 flex justify-end gap-3 pt-4">
                <button type="button" id="cancelBtn" onclick="resetSalesForm()" class="hidden px-6 py-2 rounded-lg border border-muted text-muted hover:bg-carbon transition">Cancel</button>
                
                <button type="submit" id="submitBtn" class="bg-petronas text-blackBase font-bold px-6 py-2 rounded-lg hover:bg-petronas/90 transition">
                    Simpan Transaksi
                </button>
            </div>
        </form>
    </section>

    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <h2 class="text-lg font-bold text-petronas">Riwayat Penjualan</h2>
            
            <form action="{{ route('sales.index') }}" method="GET" class="flex flex-wrap gap-2 items-center w-full md:w-auto">
                
                <select name="filter_product" 
                        class="px-4 py-2 bg-carbon rounded-lg text-xs text-silver focus:outline-none border border-transparent focus:border-petronas w-full md:w-auto">
                    <option value="">All Products</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ request('filter_product') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>

                <input type="date" name="filter_date" value="{{ request('filter_date') }}" 
                       class="px-4 py-2 bg-carbon rounded-lg text-xs text-silver focus:outline-none border border-transparent focus:border-petronas appearance-none">
                
                <button type="submit" class="px-5 py-2 bg-carbon border border-muted text-xs rounded-lg hover:text-petronas transition uppercase tracking-wider">
                    Filter
                </button>

                @if(request('filter_date') || request('filter_product'))
                    <a href="{{ route('sales.index') }}" class="text-xs text-red-400 hover:text-red-300 ml-2">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-carbon text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left text-muted font-medium border-b border-carbonSoft">Tanggal</th>
                        <th class="px-4 py-3 text-left text-muted font-medium border-b border-carbonSoft">Nama Produk</th>
                        <th class="px-4 py-3 text-left text-muted font-medium border-b border-carbonSoft">Distributor</th> <th class="px-4 py-3 text-center text-muted font-medium border-b border-carbonSoft">Qty</th>
                        <th class="px-4 py-3 text-right text-muted font-medium border-b border-carbonSoft">Harga Satuan</th>
                        <th class="px-4 py-3 text-right text-muted font-medium border-b border-carbonSoft">Total</th>
                        <th class="px-4 py-3 text-center text-muted font-medium border-b border-carbonSoft">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon/50">
                    @forelse ($sales as $sale)
                        <tr class="hover:bg-carbon transition-colors group">
                            <td class="px-4 py-3 text-silver font-mono text-xs">
                                {{ \Carbon\Carbon::parse($sale->transaction_date)->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 font-semibold text-silver">
                                {{ $sale->product->name ?? 'Unknown Product' }}
                            </td>
                            <td class="px-4 py-3 text-silver"> {{ $sale->nama_distributor ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-center text-silver">
                                {{ number_format($sale->quantity_sold) }}
                            </td>
                            <td class="px-4 py-3 text-right text-muted">
                                {{ number_format($sale->price_per_unit, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-petronas">
                                {{ number_format($sale->total_price, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center space-x-2">
                                <button type="button" onclick='editSale(@json($sale))' 
                                        class="inline-flex items-center justify-center w-8 h-8 rounded border border-petronas text-petronas hover:bg-petronas/10 transition">
                                    ✏️
                                </button>
                                
                                <form action="{{ route('sales.destroy', $sale->id) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="openDeleteModal(this)" 
                                            class="inline-flex w-8 h-8 items-center justify-center border border-red-500 text-red-500 rounded hover:bg-red-500 hover:text-blackBase transition">
                                        🗑️
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-8">
                                Belum ada data penjualan. Silahkan input atau import excel.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sales->hasPages())
            <div class="mt-6 flex justify-between text-sm text-muted">
                <div>Showing {{ $sales->firstItem() }} to {{ $sales->lastItem() }} of {{ $sales->total() }} entries</div>
                {{ $sales->links('pagination::tailwind') }}
            </div>
        @endif
    </section>

</main>

<div id="deleteModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50">
    <div class="bg-carbonSoft rounded-xl p-6 w-full max-w-md border border-red-500/50 shadow-2xl">
        <h3 class="text-lg font-bold text-red-500 mb-2">Hapus Data Penjualan?</h3>
        <p class="text-sm text-muted mb-6">
            Stok produk akan dikembalikan (bertambah) jika Anda menghapus data ini. 
            <span class="text-red-400 font-semibold">Tindakan tidak bisa dibatalkan.</span>
        </p>
        <div class="flex justify-end gap-3">
            <button onclick="closeDeleteModal()" class="px-5 py-2 rounded-lg border border-muted text-muted hover:bg-carbon transition">Cancel</button>
            <button onclick="confirmDelete()" class="px-5 py-2 rounded-lg bg-red-500 text-blackBase font-bold hover:bg-red-600 transition">Delete</button>
        </div>
    </div>
</div>

<script>
    // --- Logic Perhitungan Form ---
    function updatePriceSuggestion(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const suggestedPrice = selectedOption.getAttribute('data-price');
        
        const priceInput = document.getElementById('price');
        if(suggestedPrice && suggestedPrice > 0) {
            priceInput.value = suggestedPrice;
            calculateTotal();
        }
    }

    function calculateTotal() {
        const qty = document.getElementById('qty').value || 0;
        const price = document.getElementById('price').value || 0;
        const total = qty * price;
        
        document.getElementById('total_display').value = "Rp " + new Intl.NumberFormat('id-ID').format(total);
    }

    // --- Logic Edit Data ---
    function editSale(sale) {
        document.getElementById('sale_id').value = sale.id;
        document.querySelector('input[name="transaction_date"]').value = sale.transaction_date;
        document.querySelector('select[name="product_id"]').value = sale.product_id;
        document.querySelector('input[name="distributor_name"]').value = sale.distributor_name; // Load distributor name
        document.querySelector('input[name="quantity_sold"]').value = sale.quantity_sold;
        document.querySelector('input[name="price_per_unit"]').value = sale.price_per_unit;
        
        calculateTotal();

        document.getElementById('submitBtn').innerText = 'Update Transaksi';
        document.getElementById('cancelBtn').classList.remove('hidden');
        
        const uploadBtn = document.getElementById('uploadBtn');
        uploadBtn.classList.add('opacity-50', 'pointer-events-none');
        
        document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
    }

    function resetSalesForm() {
        document.querySelector('form').reset();
        document.getElementById('sale_id').value = '';
        document.getElementById('total_display').value = '';
        
        document.getElementById('submitBtn').innerText = 'Simpan Transaksi';
        document.getElementById('cancelBtn').classList.add('hidden');
        
        const uploadBtn = document.getElementById('uploadBtn');
        uploadBtn.classList.remove('opacity-50', 'pointer-events-none');
    }

    // --- Logic Modal Delete ---
    let deleteForm = null;
    function openDeleteModal(button) {
        deleteForm = button.closest('form');
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deleteModal').classList.remove('flex');
        deleteForm = null;
    }
    function confirmDelete() {
        if (deleteForm) deleteForm.submit();
    }
</script>

</body>
</html>