<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ isset($product) ? 'Edit Product' : 'Create Product' }} | Production Planning</title>
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
    <style>
        /* Chrome, Safari, Edge, Opera */
        input[type=number]::-webkit-outer-spin-button,
        input[type=number]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Firefox */
        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
</head>

<body class="bg-blackBase text-silver min-h-screen">

<main class="max-w-7xl mx-auto px-6 py-6 space-y-8">

    <nav aria-label="breadcrumb" class="text-xs text-muted">
        <ol class="flex items-center space-x-2">
            <li><a href="{{ route('home.index') }}" class="hover:text-petronas transition-colors">Home</a></li>
            <li class="opacity-40">/</li>
            <li><a href="{{ route('products.index') }}" class="hover:text-petronas transition-colors">Products</a></li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold">{{ isset($product) ? 'Edit' : 'Create' }}</li>
        </ol>
    </nav>
    
    <x-alert-messages />
    
    <header class="pb-4">
        <p class="text-xs uppercase tracking-widest text-muted">Master Data</p>
        <h1 class="text-3xl font-extrabold text-petronas">
            {{ isset($product) ? 'Edit Product: ' . $product->code : 'Create New Product' }}
        </h1>
        <p class="text-sm text-muted mt-1">
            {{ isset($product) ? 'Perbarui informasi dan parameter produk ini.' : 'Tambahkan produk baru ke dalam sistem.' }}
        </p>
    </header>

    {{-- Form Section --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
        <form action="{{ isset($product) ? route('products.update', $product->id) : route('products.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-6"> 
            @csrf
            
            {{-- Tambahkan @method('PUT') secara otomatis jika mode EDIT --}}
            @if(isset($product))
                @method('PATCH')
            @endif

            {{-- Row 1: Identity --}}
            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Product Code</label>
                <input id="code" type="text" name="code" value="{{ old('code', $product->code ?? '') }}" required
                    {{ isset($product) ? 'readonly' : '' }}
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas {{ isset($product) ? 'opacity-50 cursor-not-allowed' : '' }}">
            </div>

            <div class="md:col-span-2"> 
                <label class="text-xs text-muted uppercase tracking-wide">Product Name</label>
                <input id="name" type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas">
            </div>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Packaging (Kemasan)</label>
                <input id="packaging" type="text" name="packaging" value="{{ old('packaging', $product->packaging ?? '') }}"
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas"
                    placeholder="Cth: 24 Pak x 180 Gr">
            </div>


            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Batch Size (Pcs)</label>
                <input id="batch_size" type="number" name="batch_size" value="{{ old('batch_size', $product->batch_size ?? 50) }}"
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas"
                    placeholder="Lot Size">
            </div>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Selling Price</label>
                <input id="price" type="number" step="0.01" name="price" value="{{ old('price', $product->price ?? 0) }}"
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
                            <input type="radio" name="is_manual_lead_time" value="manual" 
                                {{ old('is_manual_lead_time', $product->is_manual_lead_time ?? 'manual') == 'manual' ? 'checked' : '' }} 
                                class="accent-petronas cursor-pointer" onchange="toggleLeadTimeInputs()">
                            <span class="text-silver group-hover:text-white transition">Manual Setting</span>
                        </label>
                        <div class="w-px h-4 bg-muted/30"></div>
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="radio" name="is_manual_lead_time" value="automatic" 
                                {{ old('is_manual_lead_time', $product->is_manual_lead_time ?? '') == 'automatic' ? 'checked' : '' }}
                                class="accent-petronas cursor-pointer" onchange="toggleLeadTimeInputs()">
                            <span class="text-muted group-hover:text-silver transition">Automatic (Production Batch)</span>
                        </label>
                    </div>
                </div>

                {{-- Container Input (3 Kolom) --}}
                <div id="leadTimeInputs" class="grid grid-cols-1 md:grid-cols-3 gap-6 transition-all duration-300">
                    <div>
                        <span class="text-xs text-muted uppercase block mb-1">Min Lead Time (Days)</span>
                        <input id="min_lead_time_days" type="number" name="min_lead_time_days" value="{{ old('min_lead_time_days', $product->min_lead_time_days ?? 1) }}" min="1"
                            class="w-full px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:border-petronas focus:outline-none transition-colors">
                        <p class="text-[10px] text-muted mt-1">Waktu produksi tercepat.</p>
                    </div>
                    
                    <div>
                        <span class="text-xs text-muted uppercase block mb-1">Max Lead Time (Days)</span>
                        <input id="max_lead_time_days" type="number" name="max_lead_time_days" value="{{ old('max_lead_time_days', $product->max_lead_time_days ?? 3) }}" min="1"
                            class="w-full px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:border-petronas focus:outline-none transition-colors">
                        <p class="text-[10px] text-muted mt-1">Waktu produksi terlama (buffer).</p>
                    </div>

                    <div>
                        <span class="text-xs text-petronas uppercase block mb-1 font-bold">Avg Actual (Days)</span>
                        <input id="lead_time_average" type="text" value="{{ $product->lead_time_average ?? 0 }}" readonly
                            class="w-full px-4 py-2 rounded-lg bg-blackBase border border-carbonSoft text-petronas font-extrabold cursor-not-allowed focus:outline-none tracking-wider">
                        <p class="text-[10px] text-muted mt-1">Rata-rata aktual (System Calculated).</p>
                    </div>
                </div>
                
                <div id="automaticInfo" class="hidden mt-4 p-3 bg-petronas/10 border border-petronas/20 rounded-lg text-left text-xs">
                    <div class="flex items-start gap-2">
                        <i class="bi bi-info-circle-fill text-petronas mt-0.5"></i>
                        <div class="text-silver">
                            <strong class="text-petronas">Mode Automatic Aktif:</strong>
                            <p class="mt-1 opacity-80">
                                Sistem akan otomatis mengupdate Min & Max Lead Time berdasarkan rata-rata <strong>30 Batch Produksi terakhir</strong>. 
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Row 4: Stock Opname Logic (TAMPIL HANYA JIKA MODE CREATE)
            @if(!isset($product))
                <div id="stockOpnameSection" class="md:col-span-4 bg-carbon rounded-xl p-4 border border-carbonSoft mt-2">
                    <div class="flex items-center gap-2 mb-4 border-b border-carbon pb-2">
                        <h3 class="text-sm font-bold text-petronas uppercase tracking-wide">
                            <i class="bi bi-box-seam mr-1"></i> Stock Opname (Initial Stock)
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
                        </div>
                    </div>
                </div>
            @endif --}}

            {{-- Action Buttons --}}
            <div class="md:col-span-4 flex flex-wrap justify-end gap-3 pt-4 border-t border-carbon"> 
                <a href="{{ route('products.index') }}" 
                    class="px-6 py-2 rounded-lg border border-muted text-muted hover:bg-carbon transition text-center inline-block">
                    Cancel
                </a>
                
                <button type="submit" id="submitBtn"
                    class="bg-petronas text-blackBase font-bold px-6 py-2 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20 
                    disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ isset($product) ? 'Update Product' : 'Save Product' }}
                </button>
            </div>
        </form>
    </section>
</main>

<script>
    function toggleLeadTimeInputs() {
        const isManual = document.querySelector('input[name="is_manual_lead_time"][value="manual"]').checked;
        const minInput = document.getElementById('min_lead_time_days');
        const maxInput = document.getElementById('max_lead_time_days');
        const infoContainer = document.getElementById('automaticInfo');

        if (isManual) {
            minInput.readOnly = false;
            maxInput.readOnly = false;
            
            minInput.classList.remove('text-muted', 'cursor-not-allowed', 'bg-carbonSoft');
            maxInput.classList.remove('text-muted', 'cursor-not-allowed', 'bg-carbonSoft');
            minInput.classList.add('bg-carbon', 'text-silver');
            maxInput.classList.add('bg-carbon', 'text-silver');
            
            infoContainer.classList.add('hidden');
        } else {
            minInput.readOnly = true;
            maxInput.readOnly = true;
            
            minInput.classList.add('text-muted', 'cursor-not-allowed', 'bg-carbonSoft');
            maxInput.classList.add('text-muted', 'cursor-not-allowed', 'bg-carbonSoft');
            minInput.classList.remove('bg-carbon', 'text-silver');
            maxInput.classList.remove('bg-carbon', 'text-silver');
            
            infoContainer.classList.remove('hidden');
        }
    }

    // Logic untuk memastikan form HPP terisi hanya dijalankan saat Create Mode
    @if(!isset($product))
    function checkStockRequirement() {
        const stockInput = document.getElementById('current_stock');
        const priceInput = document.getElementById('cost_price');
        const submitBtn  = document.getElementById('submitBtn');
        const asterisk = document.getElementById('hppAsterisk');
        const hppLabel = document.getElementById('hppLabel');
        const errorMsg = document.getElementById('hppErrorMsg');

        const stockValue = parseFloat(stockInput.value) || 0;
        const priceValue = parseFloat(priceInput.value) || 0;

        if (stockValue > 0) {
            priceInput.required = true; 
            asterisk.classList.remove('hidden');
            hppLabel.classList.remove('text-muted');
            hppLabel.classList.add('text-silver', 'font-bold');
            priceInput.classList.add('border-petronas'); 
            priceInput.classList.remove('border-carbon');

            if (priceValue <= 0) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Lengkapi HPP Dulu ⚠'; 
                errorMsg.classList.remove('hidden'); 
                priceInput.classList.add('border-danger', 'ring-1', 'ring-danger');
                priceInput.classList.remove('border-petronas');
            } else {
                submitBtn.disabled = false;
                submitBtn.innerText = 'Save Product';
                errorMsg.classList.add('hidden');
                priceInput.classList.remove('border-danger', 'ring-1', 'ring-danger');
                priceInput.classList.add('border-petronas');
            }
        } else {
            submitBtn.disabled = false;
            submitBtn.innerText = 'Save Product';
            priceInput.required = false;
            asterisk.classList.add('hidden');
            hppLabel.classList.add('text-muted');
            hppLabel.classList.remove('text-silver', 'font-bold');
            priceInput.classList.remove('border-petronas', 'border-danger', 'ring-1', 'ring-danger');
            priceInput.classList.add('border-carbon');
            errorMsg.classList.add('hidden');
        }
    }
    @endif

    document.addEventListener('DOMContentLoaded', function() {
        toggleLeadTimeInputs(); 
        @if(!isset($product))
            checkStockRequirement();
        @endif
    });
</script>
</body>
</html>