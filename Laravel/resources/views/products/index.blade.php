<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products | Production Planning System</title>
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
                        danger: '#EF4444' 
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
            <li class="text-petronas font-semibold">Products</li>
        </ol>
    </nav>
    <x-alert-messages />
    <header>
        <p class="text-xs uppercase tracking-widest text-muted">Master Data</p>
        <h1 class="text-3xl font-extrabold text-petronas">Product Management</h1>
        <p class="text-sm text-muted mt-1">Kelola data produk, stok, dan parameter produksi</p>
    </header>

    {{-- Form Section --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
        <h2 class="text-lg font-bold text-petronas">Add New Product</h2>

        <form action="{{ route('products.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-6"> 
            @csrf
            <input id="product_id" type="hidden" name="product_id">

            {{-- Row 1: Identity --}}
            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Product Code</label>
                <input id="code" type="text" name="code" required
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas">
            </div>

            <div class="md:col-span-2"> 
                <label class="text-xs text-muted uppercase tracking-wide">Product Name</label>
                <input id="name" type="text" name="name" required
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas">
            </div>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Packaging (Kemasan)</label>
                <input id="packaging" type="text" name="packaging" 
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas"
                    placeholder="Cth: 24 Pak x 180 Gr">
            </div>

            {{-- Row 2: System Info & Pricing --}}
            <div>
                <label class="text-xs text-petronas uppercase tracking-wide">Reserved (System)</label>
                <input id="committed_stock" type="number" name="committed_stock" value="0" readonly
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-petronas/30 text-petronas cursor-not-allowed focus:outline-none">
            </div>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Safety Stock (System)</label>
                <input id="safety_stock" type="number" name="safety_stock" value="0" readonly
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-muted cursor-not-allowed focus:outline-none"
                    title="Dihitung otomatis berdasarkan history penjualan">
            </div>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Batch Size (Pcs)</label>
                <input id="batch_size" type="number" name="batch_size" value="50" min="1"
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas"
                    placeholder="Lot Size">
            </div>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Selling Price</label>
                <input id="price" type="number" step="0.01" name="price" value="0"
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas">
            </div>

            {{-- Row 3: Lead Time Management --}}
            <div class="md:col-span-4 bg-carbon/50 p-4 rounded-xl border border-carbon">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-2">
                    <label class="text-sm font-bold text-petronas uppercase tracking-wide">
                        <i class="bi bi-clock-history mr-1"></i> Lead Time Average Calculation
                    </label>
                    
                    {{-- Radio Button Mode --}}
                    <div class="flex items-center gap-4 text-xs bg-carbon px-3 py-1.5 rounded-lg border border-carbonSoft">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="radio" name="is_manual_lead_time" value="manual" checked 
                                class="accent-petronas cursor-pointer" onchange="toggleLeadTimeInputs()">
                            <span class="text-silver group-hover:text-white transition">Manual Setting</span>
                        </label>
                        <div class="w-px h-4 bg-muted/30"></div>
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="radio" name="is_manual_lead_time" value="automatic" 
                                class="accent-petronas cursor-pointer" onchange="toggleLeadTimeInputs()">
                            <span class="text-muted group-hover:text-silver transition">Automatic (Production Batch)</span>
                        </label>
                    </div>
                </div>

                {{-- Container Input (3 Kolom) --}}
                <div id="leadTimeInputs" class="grid grid-cols-1 md:grid-cols-3 gap-6 transition-all duration-300">
                    
                    {{-- Min --}}
                    <div>
                        <span class="text-xs text-muted uppercase block mb-1">Min Lead Time (Days)</span>
                        <input id="min_lead_time_days" type="number" name="min_lead_time_days" value="1" min="1"
                            class="w-full px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:border-petronas focus:outline-none transition-colors">
                        <p class="text-[10px] text-muted mt-1">Waktu produksi tercepat.</p>
                    </div>
                    
                    {{-- Max --}}
                    <div>
                        <span class="text-xs text-muted uppercase block mb-1">Max Lead Time (Days)</span>
                        <input id="max_lead_time_days" type="number" name="max_lead_time_days" value="3" min="1"
                            class="w-full px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:border-petronas focus:outline-none transition-colors">
                        <p class="text-[10px] text-muted mt-1">Waktu produksi terlama (buffer).</p>
                    </div>

                    {{-- Avg (Warna Teal) --}}
                    <div>
                        <span class="text-xs text-petronas uppercase block mb-1 font-bold">Avg Actual (Days)</span>
                        <input id="lead_time_average" type="text" value="0" readonly
                            class="w-full px-4 py-2 rounded-lg bg-blackBase border border-carbonSoft text-petronas font-extrabold cursor-not-allowed focus:outline-none tracking-wider">
                        <p class="text-[10px] text-muted mt-1">Rata-rata aktual (System Calculated).</p>
                    </div>
                </div>
                
                {{-- Pesan Info untuk Automatic --}}
                <div id="automaticInfo" class="hidden mt-4 p-3 bg-petronas/10 border border-petronas/20 rounded-lg text-left text-xs">
                    <div class="flex items-start gap-2">
                        <i class="bi bi-info-circle-fill text-petronas mt-0.5"></i>
                        <div class="text-silver">
                            <strong class="text-petronas">Mode Automatic Aktif:</strong>
                            <p class="mt-1 opacity-80">
                                Sistem akan otomatis mengupdate Min & Max Lead Time berdasarkan rata-rata <strong>30 Batch Produksi terakhir</strong>. 
                                Kolom input manual dikunci untuk menjaga integritas data perhitungan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Row 4: Stock Opname Logic --}}
            <div id="stockOpnameSection" class="md:col-span-4 bg-carbon rounded-xl p-4 border border-carbonSoft mt-2">
                <div class="flex items-center gap-2 mb-4 border-b border-carbon pb-2">
                    <h3 class="text-sm font-bold text-petronas uppercase tracking-wide">
                        <i class="bi bi-box-seam mr-1"></i> Stock Opname Management
                    </h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide">Current Stock (On Hand)</label>
                        <input id="current_stock" type="number" name="current_stock" min="0"
                            value="{{ old('current_stock', 0) }}"
                            oninput="checkStockRequirement()" 
                            class="w-full mt-1 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon focus:border-petronas text-silver focus:outline-none transition">
                    </div>

                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide transition-colors" id="hppLabel">
                            Est. Production Cost (HPP) 
                            <span id="hppAsterisk" class="text-danger hidden">* (Wajib)</span>
                        </label>
                        
                        <input id="cost_price" type="number" step="0.01" name="cost_price" min="0"
                            value="{{ old('cost_price', 0) }}"
                            oninput="checkStockRequirement()"
                            class="w-full mt-1 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon focus:outline-none transition text-silver
                            @error('cost_price') border-danger ring-1 ring-danger @enderror"
                            placeholder="Estimasi biaya produksi per unit">
                        
                        <p id="hppErrorMsg" class="text-xs text-danger mt-1 font-bold hidden">
                            ⚠ Harga tidak boleh 0 jika ada stok awal.
                        </p>

                        @error('cost_price')
                            <p class="text-xs text-danger mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="md:col-span-4 flex flex-wrap justify-end gap-3 pt-2"> 
                <button type="button" id="cancelBtn" onclick="resetForm()"
                    class="hidden px-6 py-2 rounded-lg border border-muted text-muted hover:bg-carbon transition">
                    Cancel
                </button>
                
                <button type="submit" id="submitBtn"
                    class="bg-petronas text-blackBase font-bold px-6 py-2 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20 
                    disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-600 disabled:text-gray-400 disabled:shadow-none">
                    Save Product
                </button>
            </div>
        </form>
    </section>

    {{-- Product List (UPDATED) --}}
    <section class="bg-carbonSoft rounded-xl p-6">
        <h2 class="text-lg font-bold text-petronas mb-4">Product List</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-carbon">
                    <tr>
                        <th class="px-3 py-2 text-center text-muted">Code</th>
                        <th class="px-3 py-2 text-left text-muted">Name / Specs</th> 
                        <th class="px-3 py-2 text-center text-muted">On Hand</th> 
                        <th class="px-3 py-2 text-center text-petronas font-semibold">Reserved</th> 
                        <th class="px-3 py-2 text-center text-silver font-bold border-l border-carbon">Available</th> 
                        <th class="px-3 py-2 text-center text-muted">Safety</th>
                        <th class="px-3 py-2 text-center text-muted">HPP</th>
                        <th class="px-3 py-2 text-center text-muted">Price</th>
                        <th class="px-3 py-2 text-center text-muted">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr class="border-b border-carbon hover:bg-carbon transition-colors">
                            <td class="px-3 py-2 text-center font-semibold">{{ $product->code }}</td>
                            
                            <td class="px-3 py-2 text-left">
                                <div class="font-medium text-silver">{{ $product->name }}</div>
                                <div class="flex flex-wrap gap-2 text-[10px] text-muted mt-1">
                                    @if($product->packaging)
                                        <span class="bg-carbon px-1.5 py-0.5 rounded border border-carbon/50">{{ $product->packaging }}</span>
                                    @endif
                                    
                                    {{-- Tampilkan Info Lead Time sesuai Mode --}}
                                    <span class="flex items-center gap-1 border border-carbon/50 px-1.5 py-0.5 rounded" 
                                          title="Lead Time Settings">
                                        
                                        @if($product->is_manual_lead_time === 'manual')
                                            <span class="text-silver">⏱ Manual</span>
                                        @else
                                            <span class="text-petronas">⏱ Auto</span>
                                        @endif
                                        
                                        <span class="text-muted mx-1">|</span>
                                        
                                        {{-- Always Show Avg --}}
                                        <span class="{{ $product->lead_time_average ? 'text-silver' : 'text-muted' }} font-bold">
                                            Avg {{ number_format($product->lead_time_average ?? 0, 1) }}d
                                        </span>

                                        {{-- Show Range if Manual --}}
                                        @if($product->is_manual_lead_time === 'manual')
                                           <span class="text-[9px] text-muted ml-1">({{ $product->min_lead_time_days }}-{{ $product->max_lead_time_days }})</span>
                                        @endif
                                    </span>

                                    <span class="flex items-center gap-1" title="Batch Size">
                                        📦 Batch: {{ $product->batch_size }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-3 py-2 text-center">{{ $product->current_stock }}</td>
                            <td class="px-3 py-2 text-center text-petronas font-medium">{{ $product->committed_stock }}</td>
                            <td class="px-3 py-2 text-center font-bold text-silver border-l border-carbon">
                                {{ $product->current_stock - $product->committed_stock }}
                            </td>
                            <td class="px-3 py-2 text-center text-muted text-xs">{{ $product->safety_stock }}</td>
                            <td class="px-3 py-2 text-center">{{ number_format($product->cost_price, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-center">{{ number_format($product->price, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-center space-x-2">
                                <a href="{{ route('products.show', $product->id) }}" class="inline-flex items-center justify-center w-8 h-8 rounded bg-petronas text-blackBase hover:bg-petronas/90 transition">👁️</a>
                                <button type="button" onclick='editProduct(@json($product))' class="inline-flex items-center justify-center w-8 h-8 rounded border border-petronas text-petronas hover:bg-petronas/10 transition">✏️</button>
                                <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="openDeleteModal(this)" class="inline-flex items-center justify-center w-8 h-8 rounded border border-danger text-danger hover:bg-danger hover:text-blackBase transition">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    @if ($products->count() === 0)
                        <tr><td colspan="9" class="px-3 py-4 text-center text-muted">No products available</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
            <div class="mt-4">{{ $products->links('pagination::tailwind') }}</div>
        @endif
    </section>
</main>

{{-- Delete Modal --}}
<div id="deleteModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="bg-carbonSoft rounded-xl p-6 w-full max-w-md border border-danger shadow-2xl">
        <h3 class="text-lg font-bold text-danger mb-2">Confirm Deletion</h3>
        <p class="text-sm text-muted mb-6">Are you sure? <span class="text-red-400 font-semibold">Cannot be undone.</span></p>
        <div class="flex justify-end gap-3">
            <button onclick="closeDeleteModal()" class="px-5 py-2 rounded-lg border border-muted text-muted hover:bg-carbon transition">Cancel</button>
            <button onclick="confirmDelete()" class="px-5 py-2 rounded-lg bg-danger text-blackBase font-bold hover:bg-red-600 transition">Delete</button>
        </div>
    </div>
</div>

{{-- JAVASCRIPT --}}
<script>
    // --- UI Logic: Show/Hide Lead Time Inputs ---
    function toggleLeadTimeInputs() {
        const isManual = document.querySelector('input[name="is_manual_lead_time"][value="manual"]').checked;
        const minInput = document.getElementById('min_lead_time_days');
        const maxInput = document.getElementById('max_lead_time_days');
        const infoContainer = document.getElementById('automaticInfo');

        if (isManual) {
            // Mode Manual: Input Bisa Diedit
            minInput.readOnly = false;
            maxInput.readOnly = false;
            
            // Style Active
            minInput.classList.remove('text-muted', 'cursor-not-allowed', 'bg-carbonSoft');
            maxInput.classList.remove('text-muted', 'cursor-not-allowed', 'bg-carbonSoft');
            minInput.classList.add('bg-carbon', 'text-silver');
            maxInput.classList.add('bg-carbon', 'text-silver');
            
            // Hide Info
            infoContainer.classList.add('hidden');
        } else {
            // Mode Automatic: Input Readonly
            minInput.readOnly = true;
            maxInput.readOnly = true;
            
            // Style Disabled
            minInput.classList.add('text-muted', 'cursor-not-allowed', 'bg-carbonSoft');
            maxInput.classList.add('text-muted', 'cursor-not-allowed', 'bg-carbonSoft');
            minInput.classList.remove('bg-carbon', 'text-silver');
            maxInput.classList.remove('bg-carbon', 'text-silver');
            
            // Show Info
            infoContainer.classList.remove('hidden');
        }
    }

    function editProduct(product) {
        document.getElementById('product_id').value = product.id;
        
        const codeInput = document.getElementById('code');
        codeInput.value = product.code;
        codeInput.readOnly = true;
        codeInput.classList.add('text-muted', 'cursor-not-allowed');

        document.getElementById('name').value = product.name;
        document.getElementById('packaging').value = product.packaging || ''; 

        // Hide Stock Opname Section on Edit
        document.getElementById('stockOpnameSection').style.display = 'none';

        // Readonly System Fields
        document.getElementById('committed_stock').value = product.committed_stock;
        document.getElementById('safety_stock').value = product.safety_stock;
        
        // --- Populate New Fields ---
        document.getElementById('min_lead_time_days').value = product.min_lead_time_days;
        document.getElementById('max_lead_time_days').value = product.max_lead_time_days;
        document.getElementById('batch_size').value = product.batch_size;
        
        // Populate Average Lead Time (Readonly)
        document.getElementById('lead_time_average').value = product.lead_time_average || 0;
        
        // Set Radio Button Value & Toggle UI
        const mode = product.is_manual_lead_time || 'manual';
        document.querySelector(`input[name="is_manual_lead_time"][value="${mode}"]`).checked = true;
        toggleLeadTimeInputs(); 

        document.getElementById('price').value = product.price;

        document.getElementById('submitBtn').innerText = 'Update Product';
        document.getElementById('cancelBtn').classList.remove('hidden');
        document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
    }

    function resetForm() {
        document.querySelector('form').reset();
        document.getElementById('product_id').value = '';
        document.getElementById('submitBtn').innerText = 'Save Product';
        document.getElementById('cancelBtn').classList.add('hidden');

        const codeInput = document.getElementById('code');
        codeInput.readOnly = false;
        codeInput.classList.remove('text-muted', 'cursor-not-allowed');

        document.getElementById('packaging').value = '';
        document.getElementById('stockOpnameSection').style.display = 'block';

        // Reset default values new fields
        document.getElementById('current_stock').value = 0;
        document.getElementById('cost_price').value = 0;
        document.getElementById('committed_stock').value = 0;
        document.getElementById('safety_stock').value = 0;
        
        // Defaults
        document.getElementById('min_lead_time_days').value = 1;
        document.getElementById('max_lead_time_days').value = 3;
        document.getElementById('batch_size').value = 50;
        document.getElementById('lead_time_average').value = 0;
        
        // Reset Radio & UI
        document.querySelector('input[name="is_manual_lead_time"][value="manual"]').checked = true;
        toggleLeadTimeInputs();
        
        checkStockRequirement();
    }

    // Modal & Stock Logic remains unchanged
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

    function checkStockRequirement() {
        const stockInput = document.getElementById('current_stock');
        const priceInput = document.getElementById('cost_price');
        const submitBtn  = document.getElementById('submitBtn');
        const asterisk = document.getElementById('hppAsterisk');
        const hppLabel = document.getElementById('hppLabel');
        const errorMsg = document.getElementById('hppErrorMsg');

        const stockValue = parseFloat(stockInput.value) || 0;
        const priceValue = parseFloat(priceInput.value) || 0;
        const isEditMode = document.getElementById('stockOpnameSection').style.display === 'none';

        if (isEditMode) {
            enableSubmitButton();
            return;
        }

        if (stockValue > 0) {
            priceInput.required = true; 
            asterisk.classList.remove('hidden');
            hppLabel.classList.remove('text-muted');
            hppLabel.classList.add('text-silver', 'font-bold');
            priceInput.classList.add('border-petronas'); 
            priceInput.classList.remove('border-carbon');

            if (priceValue <= 0) {
                disableSubmitButton();
                errorMsg.classList.remove('hidden'); 
                priceInput.classList.add('border-danger', 'ring-1', 'ring-danger');
                priceInput.classList.remove('border-petronas');
            } else {
                enableSubmitButton();
                errorMsg.classList.add('hidden');
                priceInput.classList.remove('border-danger', 'ring-1', 'ring-danger');
                priceInput.classList.add('border-petronas');
            }
        } else {
            enableSubmitButton();
            priceInput.required = false;
            asterisk.classList.add('hidden');
            hppLabel.classList.add('text-muted');
            hppLabel.classList.remove('text-silver', 'font-bold');
            priceInput.classList.remove('border-petronas', 'border-danger', 'ring-1', 'ring-danger');
            priceInput.classList.add('border-carbon');
            errorMsg.classList.add('hidden');
        }
    }

    function disableSubmitButton() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = 'Lengkapi HPP Dulu ⚠'; 
    }

    function enableSubmitButton() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = false;
        const isEdit = document.getElementById('product_id').value !== '';
        btn.innerText = isEdit ? 'Update Product' : 'Save Product';
    }

    document.addEventListener('DOMContentLoaded', function() {
        checkStockRequirement();
        toggleLeadTimeInputs(); // Init state
    });
</script>
</body>
</html>