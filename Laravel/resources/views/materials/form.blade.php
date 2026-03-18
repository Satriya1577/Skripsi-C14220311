<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ isset($material) ? 'Edit Material' : 'Create Material' }} | Production Planning System</title>
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
            <li><a href="{{ route('materials.index') }}" class="hover:text-petronas transition-colors">Materials</a></li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold">{{ isset($material) ? 'Edit' : 'Create' }}</li>
        </ol>
    </nav>

    <x-alert-messages />

    <header class="pb-4">
        <p class="text-xs uppercase tracking-widest text-muted">Master Data</p>
        <h1 class="text-3xl font-extrabold text-petronas">
            {{ isset($material) ? 'Edit Material: ' . $material->code : 'Create New Material' }}
        </h1>
        <p class="text-sm text-muted mt-1">
            {{ isset($material) ? 'Perbarui informasi dan parameter bahan baku ini.' : 'Tambahkan bahan baku baru ke dalam sistem.' }}
        </p>
    </header>

    {{-- FORM SECTION --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
        <form action="{{ isset($material) ? route('materials.update', $material->id) : route('materials.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-6">
            @csrf
            
            @if(isset($material))
                @method('PATCH')
            @endif
            
            {{-- Row 1: Identity --}}
            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Kode Barang</label>
                <input type="text" name="code" id="code" value="{{ old('code', $material->code ?? '') }}" required
                    {{ isset($material) ? 'readonly' : '' }}
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas {{ isset($material) ? 'opacity-50 cursor-not-allowed' : '' }}">
            </div>

            <div class="md:col-span-2">
                <label class="text-xs text-muted uppercase tracking-wide">Nama Barang</label>
                <input type="text" name="name" id="name" value="{{ old('name', $material->name ?? '') }}" required
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas"
                    placeholder="Cth: Tepung Terigu Protein Tinggi">
            </div>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Status</label>
                <select name="is_active" id="is_active" required class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas">
                    <option value="1" {{ old('is_active', $material->is_active ?? 1) == 1 ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('is_active', $material->is_active ?? 1) == 0 ? 'selected' : '' }}>Non-Active</option>
                </select>
            </div>

            {{-- Row 2: Category & System Info --}}
            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Category Type</label>
                <select name="category_type" id="category_type" required class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas">
                    <option value="mass" {{ old('category_type', $material->category_type ?? '') == 'mass' ? 'selected' : '' }}>Mass (Berat)</option>
                    <option value="volume" {{ old('category_type', $material->category_type ?? '') == 'volume' ? 'selected' : '' }}>Volume (Cairan)</option>
                    <option value="unit" {{ old('category_type', $material->category_type ?? '') == 'unit' ? 'selected' : '' }}>Unit (Pcs)</option>
                </select>
            </div>

            {{-- <div>
                <label class="text-xs text-muted uppercase tracking-wide">Safety Stock</label>
                <input type="number" name="safety_stock" id="safety_stock" value="{{ $material->safety_stock ?? 0 }}" readonly
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-muted cursor-not-allowed focus:outline-none"
                    title="Dihitung otomatis oleh sistem">
            </div>

            <div>
                <label class="text-xs text-petronas uppercase tracking-wide">Reorder Point (ROP)</label>
                <input type="number" name="reorder_point" id="reorder_point" value="{{ $material->reorder_point ?? 0 }}" readonly
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-petronas/30 text-petronas cursor-not-allowed focus:outline-none"
                    title="Titik pemesanan ulang otomatis">
            </div> --}}
            
            <div>
                {{-- Empty column --}}
            </div>

            {{-- Row 3: Unit Configuration (Special for Material) --}}
            <div class="md:col-span-4 bg-carbon p-4 rounded-xl border border-carbonSoft">
                <h4 class="text-xs font-bold text-silver uppercase mb-3 border-b border-carbonSoft pb-2">Konfigurasi Satuan & Kemasan</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide">Satuan Pemakaian (Base Unit)</label>
                        <select name="unit" id="baseUnitSelect" class="w-full mt-1 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon text-silver focus:border-petronas focus:outline-none">
                            <option value="gram" class="opt-mass" {{ old('unit', $material->unit ?? '') == 'gram' ? 'selected' : '' }}>Gram (Berat)</option>
                            <option value="ml"   class="opt-volume" {{ old('unit', $material->unit ?? '') == 'ml' ? 'selected' : '' }}>Mililiter (Cairan)</option>
                            <option value="pcs"  class="opt-unit" {{ old('unit', $material->unit ?? '') == 'pcs' ? 'selected' : '' }}>Pcs (Unit)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide">Satuan Pembelian</label>
                        <input type="text" name="purchase_unit" id="purchase_unit" value="{{ old('purchase_unit', $material->purchase_unit ?? '') }}" placeholder="Cth: Karung @50kg"
                            class="w-full mt-1 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon text-silver focus:border-petronas focus:outline-none">
                    </div>

                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide">Isi per Kemasan (Netto)</label>
                        <div class="flex gap-2 mt-1">
                            <input type="number" step="0.01" name="packaging_size" id="packaging_size" value="{{ old('packaging_size', $material->packaging_size ?? '') }}" placeholder="Cth: 50" required
                                class="w-2/3 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon text-silver focus:border-petronas focus:outline-none">
                            
                            <select name="packaging_unit" id="packaging_unit" class="w-1/3 px-2 py-2 rounded-lg bg-carbonSoft border border-carbon text-silver text-sm focus:border-petronas focus:outline-none">
                                <optgroup label="Berat">
                                    <option value="kg" {{ old('packaging_unit', $material->packaging_unit ?? '') == 'kg' ? 'selected' : '' }}>KG</option>
                                    <option value="gram" {{ old('packaging_unit', $material->packaging_unit ?? '') == 'gram' ? 'selected' : '' }}>Gram</option>
                                    <option value="ons" {{ old('packaging_unit', $material->packaging_unit ?? '') == 'ons' ? 'selected' : '' }}>Ons</option>
                                </optgroup>
                                <optgroup label="Cairan">
                                    <option value="liter" {{ old('packaging_unit', $material->packaging_unit ?? '') == 'liter' ? 'selected' : '' }}>Liter</option>
                                    <option value="ml" {{ old('packaging_unit', $material->packaging_unit ?? '') == 'ml' ? 'selected' : '' }}>ML</option>
                                </optgroup>
                                <optgroup label="Lainnya">
                                    <option value="pcs" {{ old('packaging_unit', $material->packaging_unit ?? '') == 'pcs' ? 'selected' : '' }}>Pcs</option>
                                    <option value="dozen" {{ old('packaging_unit', $material->packaging_unit ?? '') == 'dozen' ? 'selected' : '' }}>Lusin</option>
                                </optgroup>
                            </select>
                        </div>
                        <p class="text-[10px] text-muted mt-1">Sistem otomatis menghitung konversi.</p>
                    </div>
                </div>
            </div>

            {{-- Row 4: Lead Time Management --}}
            <div class="md:col-span-4 bg-carbon/50 p-4 rounded-xl border border-carbon">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-2">
                    <label class="text-sm font-bold text-petronas uppercase tracking-wide">
                        <i class="bi bi-clock-history mr-1"></i> Lead Time Average Calculation
                    </label>
                    
                    {{-- Radio Button Mode --}}
                    <div class="flex items-center gap-4 text-xs bg-carbon px-3 py-1.5 rounded-lg border border-carbonSoft">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="radio" name="is_manual_lead_time" value="manual" 
                                {{ old('is_manual_lead_time', $material->is_manual_lead_time ?? 'manual') == 'manual' ? 'checked' : '' }} 
                                class="accent-petronas cursor-pointer" onchange="toggleLeadTimeInputs()">
                            <span class="text-silver group-hover:text-white transition">Manual Setting</span>
                        </label>
                        <div class="w-px h-4 bg-muted/30"></div>
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="radio" name="is_manual_lead_time" value="automatic" 
                                {{ old('is_manual_lead_time', $material->is_manual_lead_time ?? '') == 'automatic' ? 'checked' : '' }}
                                class="accent-petronas cursor-pointer" onchange="toggleLeadTimeInputs()">
                            <span class="text-muted group-hover:text-silver transition">Automatic (Purchase History)</span>
                        </label>
                    </div>
                </div>

                {{-- Container Input (3 Kolom) --}}
                <div id="leadTimeInputs" class="grid grid-cols-1 md:grid-cols-3 gap-6 transition-all duration-300">
                    <div>
                        <span class="text-xs text-muted uppercase block mb-1">Min Lead Time (Days)</span>
                        <input id="min_lead_time_days" type="number" name="min_lead_time_days" value="{{ old('min_lead_time_days', $material->min_lead_time_days ?? 1) }}" min="1"
                            class="w-full px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:border-petronas focus:outline-none transition-colors">
                        <p class="text-[10px] text-muted mt-1">Waktu pengiriman tercepat.</p>
                    </div>
                    
                    <div>
                        <span class="text-xs text-muted uppercase block mb-1">Max Lead Time (Days)</span>
                        <input id="max_lead_time_days" type="number" name="max_lead_time_days" value="{{ old('max_lead_time_days', $material->max_lead_time_days ?? 7) }}" min="1"
                            class="w-full px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:border-petronas focus:outline-none transition-colors">
                        <p class="text-[10px] text-muted mt-1">Waktu pengiriman terlama (buffer).</p>
                    </div>

                    <div>
                        <span class="text-xs text-petronas uppercase block mb-1 font-bold">Avg Actual (Days)</span>
                        <input id="lead_time_average" type="text" value="{{ $material->lead_time_average ?? 0 }}" readonly
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
                                Sistem akan otomatis mengupdate Min & Max Lead Time berdasarkan rata-rata historis kedatangan barang. 
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Row 5: Stock Opname (HANYA MUNCUL SAAT CREATE) --}}
            {{-- @if(!isset($material))
                <div id="initialStockSection" class="md:col-span-4 bg-carbon rounded-xl p-4 border border-carbonSoft mt-2">
                    <div class="flex items-center gap-2 mb-4 border-b border-carbon pb-2">
                        <h3 class="text-sm font-bold text-petronas uppercase tracking-wide">
                            <i class="bi bi-box-seam mr-1"></i> Stock Opname Management
                        </h3>
                    </div>
                    <p class="text-xs text-muted mb-4">Isi hanya jika di awal entry data, material sudah tersedia di gudang.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-muted uppercase tracking-wide">Stok Awal (Satuan Pembelian)</label>
                            <input type="number" step="0.01" name="initial_qty_purchase_unit" id="initialQty" value="{{ old('initial_qty_purchase_unit', 0) }}"
                                oninput="checkStockRequirement()"
                                class="w-full mt-1 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon text-silver focus:border-petronas focus:outline-none transition">
                        </div>

                        <div>
                            <label class="text-xs text-muted uppercase tracking-wide transition-colors" id="priceLabel">
                                Harga Beli (Per Satuan Pembelian)
                                <span id="priceAsterisk" class="text-danger hidden">* (Wajib)</span>
                            </label>
                            
                            <input type="number" step="0.01" name="initial_price_purchase_unit" id="initialPrice" value="{{ old('initial_price_purchase_unit', 0) }}"
                                oninput="checkStockRequirement()"
                                class="w-full mt-1 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon text-silver focus:outline-none transition
                                @error('initial_price_purchase_unit') border-danger ring-1 ring-danger @enderror"
                                placeholder="0">

                            <p id="priceErrorMsg" class="text-xs text-danger mt-1 font-bold hidden">
                                ⚠ Harga Beli tidak boleh 0 jika ada stok awal.
                            </p>
                        </div>
                    </div>
                </div>
            @endif --}}

            {{-- Action Buttons --}}
            <div class="md:col-span-4 flex justify-end gap-3 pt-4 border-t border-carbon">
                <a href="{{ route('materials.index') }}" 
                    class="px-6 py-2 rounded-lg border border-muted text-muted hover:bg-carbon transition inline-block text-center">
                    Cancel
                </a>
                
                <button type="submit" id="submitBtn" 
                    class="bg-petronas text-blackBase font-bold px-6 py-2 rounded-lg hover:bg-petronas/90 transition shadow-lg 
                    disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-600 disabled:text-gray-400">
                    {{ isset($material) ? 'Update Material' : 'Simpan Material' }}
                </button>
            </div>
        </form>
    </section>

</main>

<script>
    // --- UI Logic: Lead Time Inputs ---
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

    // --- Script Category Type Logic ---
    document.querySelector('select[name="category_type"]').addEventListener('change', function() {
        const selectedCategory = this.value; 
        const unitSelect = document.getElementById('baseUnitSelect');
        const options = unitSelect.querySelectorAll('option');
        const allowedClass = 'opt-' + selectedCategory; 
        let firstVisible = null;

        options.forEach(opt => {
            if (opt.classList.contains(allowedClass)) {
                opt.style.display = 'block'; 
                if (!firstVisible) firstVisible = opt;
            } else {
                opt.style.display = 'none'; 
            }
        });

        // Set value jika unit sekarang tidak sesuai dengan kategori
        if (firstVisible && !unitSelect.querySelector(`option[value="${unitSelect.value}"]`).classList.contains(allowedClass)) {
            unitSelect.value = firstVisible.value;
        }
    });

    // --- LOGIKA VALIDASI STOCK OPNAME (HANYA CREATE) ---
    @if(!isset($material))
    function checkStockRequirement() {
        const qtyInput = document.getElementById('initialQty');
        const priceInput = document.getElementById('initialPrice');
        const submitBtn = document.getElementById('submitBtn');
        const asterisk = document.getElementById('priceAsterisk');
        const priceLabel = document.getElementById('priceLabel');
        const errorMsg = document.getElementById('priceErrorMsg');

        const qtyValue = parseFloat(qtyInput.value) || 0;
        const priceValue = parseFloat(priceInput.value) || 0;

        if (qtyValue > 0) {
            priceInput.required = true; 
            asterisk.classList.remove('hidden');
            priceLabel.classList.remove('text-muted');
            priceLabel.classList.add('text-silver', 'font-bold');
            priceInput.classList.add('border-petronas'); 
            priceInput.classList.remove('border-carbon');

            if (priceValue <= 0) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Lengkapi Harga ⚠'; 
                errorMsg.classList.remove('hidden'); 
                priceInput.classList.add('border-danger', 'ring-1', 'ring-danger');
                priceInput.classList.remove('border-petronas');
            } else {
                submitBtn.disabled = false;
                submitBtn.innerText = 'Simpan Material';
                errorMsg.classList.add('hidden');
                priceInput.classList.remove('border-danger', 'ring-1', 'ring-danger');
                priceInput.classList.add('border-petronas');
            }
        } else {
            submitBtn.disabled = false;
            submitBtn.innerText = 'Simpan Material';
            priceInput.required = false;
            asterisk.classList.add('hidden');
            priceLabel.classList.add('text-muted');
            priceLabel.classList.remove('text-silver', 'font-bold');
            priceInput.classList.remove('border-petronas', 'border-danger', 'ring-1', 'ring-danger');
            priceInput.classList.add('border-carbon');
            errorMsg.classList.add('hidden');
        }
    }
    @endif

    // Listener saat load
    document.addEventListener('DOMContentLoaded', function() {
        toggleLeadTimeInputs();
        
        // Panggil event change secara manual saat load agar filter dropdown unit sesuai kategori awal
        document.querySelector('select[name="category_type"]').dispatchEvent(new Event('change'));

        @if(!isset($material))
            checkStockRequirement();
        @endif
    });
</script>

</body>
</html>