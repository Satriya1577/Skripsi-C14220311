<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Materials | Production Planning System</title>
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
            <li class="text-petronas font-semibold">Materials</li>
        </ol>
    </nav>

    <x-alert-messages />

    <header>
        <p class="text-xs uppercase tracking-widest text-muted">Master Data</p>
        <h1 class="text-3xl font-extrabold text-petronas">Material Management</h1>
        <p class="text-sm text-muted mt-1">Kelola bahan baku, satuan, stok, dan harga</p>
    </header>

    {{-- FORM SECTION --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
        <h2 class="text-lg font-bold text-petronas">Add New Material</h2>
        
        <form action="{{ route('materials.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-6">
            @csrf
            <input type="hidden" name="material_id" id="material_id">
            
            {{-- Row 1: Identity --}}
            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Kode Barang</label>
                <input type="text" name="code" id="code" required
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas">
            </div>

            <div class="md:col-span-2">
                <label class="text-xs text-muted uppercase tracking-wide">Nama Barang</label>
                <input type="text" name="name" id="name" required
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas"
                    placeholder="Cth: Tepung Terigu Protein Tinggi">
            </div>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Status</label>
                <select name="is_active" id="is_active" required class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas">
                    <option value="1">Active</option>
                    <option value="0">Non-Active</option>
                </select>
            </div>

            {{-- Row 2: Category & System Info --}}
            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Category Type</label>
                <select name="category_type" id="category_type" required class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas">
                    <option value="mass">Mass (Berat)</option>
                    <option value="volume">Volume (Cairan)</option>
                    <option value="unit">Unit (Pcs)</option>
                </select>
            </div>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Safety Stock</label>
                <input type="number" name="safety_stock" id="safety_stock" value="0" readonly
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-muted cursor-not-allowed focus:outline-none"
                    title="Dihitung otomatis oleh sistem">
            </div>

            <div>
                <label class="text-xs text-petronas uppercase tracking-wide">Reorder Point (ROP)</label>
                <input type="number" name="reorder_point" id="reorder_point" value="0" readonly
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-petronas/30 text-petronas cursor-not-allowed focus:outline-none"
                    title="Titik pemesanan ulang otomatis">
            </div>
            
            <div>
                {{-- Empty column or used for something else --}}
            </div>

            {{-- Row 3: Unit Configuration (Special for Material) --}}
            <div class="md:col-span-4 bg-carbon p-4 rounded-xl border border-carbonSoft">
                <h4 class="text-xs font-bold text-silver uppercase mb-3 border-b border-carbonSoft pb-2">Konfigurasi Satuan & Kemasan</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide">Satuan Pemakaian (Base Unit)</label>
                        <select name="unit" id="baseUnitSelect" class="w-full mt-1 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon text-silver focus:border-petronas focus:outline-none">
                            <option value="gram" class="opt-mass">Gram (Berat)</option>
                            <option value="ml"   class="opt-volume">Mililiter (Cairan)</option>
                            <option value="pcs"  class="opt-unit">Pcs (Unit)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide">Satuan Pembelian</label>
                        <input type="text" name="purchase_unit" id="purchase_unit" placeholder="Cth: Karung @50kg"
                            class="w-full mt-1 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon text-silver focus:border-petronas focus:outline-none">
                    </div>

                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide">Isi per Kemasan (Netto)</label>
                        <div class="flex gap-2 mt-1">
                            <input type="number" step="0.01" name="packaging_size" id="packaging_size" placeholder="Cth: 50" required
                                class="w-2/3 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon text-silver focus:border-petronas focus:outline-none">
                            
                            <select name="packaging_unit" id="packaging_unit" class="w-1/3 px-2 py-2 rounded-lg bg-carbonSoft border border-carbon text-silver text-sm focus:border-petronas focus:outline-none">
                                <optgroup label="Berat">
                                    <option value="kg">KG</option>
                                    <option value="gram">Gram</option>
                                    <option value="ons">Ons</option>
                                </optgroup>
                                <optgroup label="Cairan">
                                    <option value="liter">Liter</option>
                                    <option value="ml">ML</option>
                                </optgroup>
                                <optgroup label="Lainnya">
                                    <option value="pcs">Pcs</option>
                                    <option value="dozen">Lusin</option>
                                </optgroup>
                            </select>
                        </div>
                        <p class="text-[10px] text-muted mt-1">Sistem otomatis menghitung konversi.</p>
                    </div>
                </div>
            </div>

            {{-- Row 4: Lead Time Management (SAMA PERSIS PRODUCT) --}}
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
                            <span class="text-muted group-hover:text-silver transition">Automatic (Purchase History)</span>
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
                        <p class="text-[10px] text-muted mt-1">Waktu pengiriman tercepat.</p>
                    </div>
                    
                    {{-- Max --}}
                    <div>
                        <span class="text-xs text-muted uppercase block mb-1">Max Lead Time (Days)</span>
                        <input id="max_lead_time_days" type="number" name="max_lead_time_days" value="7" min="1"
                            class="w-full px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:border-petronas focus:outline-none transition-colors">
                        <p class="text-[10px] text-muted mt-1">Waktu pengiriman terlama (buffer).</p>
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
                                Sistem akan otomatis mengupdate Min & Max Lead Time berdasarkan rata-rata historis kedatangan barang. 
                                Kolom input manual dikunci untuk menjaga integritas data.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Row 5: Stock Opname (SAMA PERSIS PRODUCT) --}}
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
                        <input type="number" step="0.01" name="initial_qty_purchase_unit" id="initialQty" value="0"
                            oninput="checkStockRequirement()"
                            class="w-full mt-1 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon text-silver focus:border-petronas focus:outline-none transition">
                    </div>

                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide transition-colors" id="priceLabel">
                            Harga Beli (Per Satuan Pembelian)
                            <span id="priceAsterisk" class="text-danger hidden">* (Wajib)</span>
                        </label>
                        
                        <input type="number" step="0.01" name="initial_price_purchase_unit" id="initialPrice" value="0"
                            oninput="checkStockRequirement()"
                            class="w-full mt-1 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon text-silver focus:outline-none transition
                            @error('initial_price_purchase_unit') border-danger ring-1 ring-danger @enderror"
                            placeholder="0">

                        <p id="priceErrorMsg" class="text-xs text-danger mt-1 font-bold hidden">
                            ⚠ Harga Beli tidak boleh 0 jika ada stok awal.
                        </p>
                        @error('initial_price_purchase_unit')
                            <p class="text-xs text-danger mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="md:col-span-4 flex justify-end gap-3 pt-2">
                <button type="button" id="cancelBtn" onclick="resetMaterialForm()" 
                    class="hidden px-6 py-2 rounded-lg border border-muted text-muted hover:bg-carbon transition">
                    Cancel
                </button>
                
                <button type="submit" id="submitBtn" 
                    class="bg-petronas text-blackBase font-bold px-6 py-2 rounded-lg hover:bg-petronas/90 transition shadow-lg 
                    disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-600 disabled:text-gray-400">
                    Simpan Material
                </button>
            </div>
        </form>
    </section>

    {{-- TABLE SECTION --}}
    <section class="bg-carbonSoft rounded-xl p-6">
        <h2 class="text-lg font-bold text-petronas mb-4">Material List</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-carbon">
                    <tr>
                        <th class="px-3 py-2 text-left text-muted">Code</th>
                        <th class="px-3 py-2 text-left text-muted">Name / Specs</th>
                        <th class="px-3 py-2 text-center text-muted">Status</th> 
                        <th class="px-3 py-2 text-right text-muted">Stock</th> 
                        <th class="px-3 py-2 text-center text-muted">Base Unit</th>
                        <th class="px-3 py-2 text-right text-muted border-l border-carbon">Safety Stock</th>
                        <th class="px-3 py-2 text-right text-petronas font-bold">ROP</th>
                        <th class="px-3 py-2 text-right text-muted border-l border-carbon">Price / Unit</th> 
                        <th class="px-3 py-2 text-center text-muted">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($materials as $material)
                        @php
                            $factor = $material->conversion_factor > 0 ? $material->conversion_factor : 1;
                        @endphp
                        <tr class="border-b border-carbon hover:bg-carbon transition-colors">
                            <td class="px-3 py-2 font-semibold">{{ $material->code }}</td>
                            
                            <td class="px-3 py-2">
                                <div class="font-medium text-silver">{{ $material->name }}</div>
                                <div class="flex flex-wrap gap-2 text-[10px] text-muted mt-1">
                                    {{-- Lead Time Info --}}
                                    <span class="flex items-center gap-1 border border-carbon/50 px-1.5 py-0.5 rounded" title="Lead Time Settings">
                                        @if($material->is_manual_lead_time === 'manual')
                                            <span class="text-silver">⏱ Manual</span>
                                        @else
                                            <span class="text-petronas">⏱ Auto</span>
                                        @endif
                                        <span class="text-muted mx-1">|</span>
                                        <span class="{{ $material->lead_time_average ? 'text-silver' : 'text-muted' }} font-bold">
                                            Avg {{ number_format($material->lead_time_average ?? 0, 1) }}d
                                        </span>
                                        @if($material->is_manual_lead_time === 'manual')
                                           <span class="text-[9px] text-muted ml-1">({{ $material->min_lead_time_days }}-{{ $material->max_lead_time_days }})</span>
                                        @endif
                                    </span>

                                    {{-- Packaging Info --}}
                                    @if($material->packaging_size)
                                        <span class="flex items-center gap-1" title="Packaging">
                                            📦 {{ (float)$material->packaging_size }} {{ $material->packaging_unit }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-3 py-2 text-center">
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase
                                    {{ $material->is_active ? 'bg-green-900/30 text-green-400' : 'bg-red-900/30 text-red-400' }}">
                                    {{ $material->is_active ? 'Active' : 'Non-Active' }}
                                </span>
                            </td>

                            <td class="px-3 py-2 text-right font-semibold text-silver">
                                {{ number_format($material->current_stock / $factor, 2) }} 
                                <span class="ml-1 text-xs text-muted">{{ $material->purchase_unit }}</span>
                            </td>

                            <td class="px-3 py-2 text-center text-muted">{{ $material->unit }}</td>

                            <td class="px-3 py-2 text-right text-muted border-l border-carbon">
                                {{ number_format($material->safety_stock) }}
                            </td>
                            <td class="px-3 py-2 text-right text-petronas font-bold">
                                {{ number_format($material->reorder_point) }}
                            </td>

                            <td class="px-3 py-2 text-right font-semibold text-silver border-l border-carbon">
                                {{ number_format($material->price_per_unit * $factor, 2) }}
                            </td>

                            <td class="px-3 py-2 text-center space-x-2">
                                <a href="{{ route('materials.show', $material->id) }}" class="inline-flex w-8 h-8 items-center justify-center bg-petronas text-blackBase rounded hover:bg-petronas/90 transition">👁️</a>
                                <button type="button" onclick='editMaterial(@json($material))' class="inline-flex items-center justify-center w-8 h-8 rounded border border-petronas text-petronas hover:bg-petronas/10 transition">✏️</button>
                                <form action="{{ route('materials.destroy', $material->id) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="openDeleteModal(this)" class="inline-flex w-8 h-8 items-center justify-center border border-red-500 text-red-500 rounded hover:bg-red-500 hover:text-blackBase transition">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No materials available</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if ($materials->hasPages())
            <div class="mt-4 flex justify-between text-sm text-muted">
                <div>Showing {{ $materials->firstItem() }} to {{ $materials->lastItem() }} of {{ $materials->total() }} materials</div>
                {{ $materials->links('pagination::tailwind') }}
            </div>
        @endif
    </section>
</main>

{{-- Delete Modal --}}
<div id="deleteModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50">
    <div class="bg-carbonSoft rounded-xl p-6 w-full max-w-md border border-red-500">
        <h3 class="text-lg font-bold text-red-500 mb-2">Confirm Deletion</h3>
        <p class="text-sm text-muted mb-6">Are you sure? <span class="text-red-400 font-semibold">Cannot be undone.</span></p>
        <div class="flex justify-end gap-3">
            <button onclick="closeDeleteModal()" class="px-5 py-2 rounded-lg border border-muted text-muted hover:bg-carbon transition">Cancel</button>
            <button onclick="confirmDelete()" class="px-5 py-2 rounded-lg bg-red-500 text-blackBase font-bold hover:bg-red-600 transition">Delete</button>
        </div>
    </div>
</div>

<script>
    // --- UI Logic: Lead Time Inputs (Copied from Products) ---
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

        if (firstVisible && unitSelect.value !== firstVisible.value && !unitSelect.querySelector(`option[value="${unitSelect.value}"]`).classList.contains(allowedClass)) {
            unitSelect.value = firstVisible.value;
        }
    });
    // Init Category
    document.querySelector('select[name="category_type"]').dispatchEvent(new Event('change'));


    // --- FUNGSI EDIT MATERIAL ---
    function editMaterial(material) {
        document.getElementById('material_id').value = material.id;
        
        const codeInput = document.getElementById('code');
        codeInput.value = material.code;
        codeInput.readOnly = true;
        codeInput.classList.add('text-muted', 'cursor-not-allowed');
        
        // Hide Section Saldo Awal saat Edit
        document.getElementById('initialStockSection').style.display = 'none';
        
        document.getElementById('name').value = material.name;
        document.getElementById('category_type').value = material.category_type;
        document.getElementById('category_type').dispatchEvent(new Event('change'));

        // Populate Lead Time Fields
        document.getElementById('min_lead_time_days').value = material.min_lead_time_days;
        document.getElementById('max_lead_time_days').value = material.max_lead_time_days;
        document.getElementById('lead_time_average').value = material.lead_time_average || 0;
        
        // Set Radio & Toggle UI
        const mode = material.is_manual_lead_time || 'manual';
        document.querySelector(`input[name="is_manual_lead_time"][value="${mode}"]`).checked = true;
        toggleLeadTimeInputs();

        document.getElementById('safety_stock').value = material.safety_stock;
        document.getElementById('reorder_point').value = material.reorder_point;
        
        document.getElementById('is_active').value = material.is_active ? "1" : "0";
        document.getElementById('baseUnitSelect').value = material.unit;
        document.getElementById('purchase_unit').value = material.purchase_unit;

        // Logika Konversi Reverse
        if (material.packaging_size && material.packaging_unit) {
            document.getElementById('packaging_size').value = parseFloat(material.packaging_size);
            document.getElementById('packaging_unit').value = material.packaging_unit;
        } else {
            // Fallback Logic (bisa disesuaikan atau dikosongkan)
            document.getElementById('packaging_size').value = 1;
        }

        document.getElementById('submitBtn').innerText = 'Update Material';
        document.getElementById('cancelBtn').classList.remove('hidden');
        document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
        
        checkStockRequirement(); // Ensure button state
    }

    // --- FUNGSI RESET FORM ---
    function resetMaterialForm() {
        document.querySelector('form').reset();
        document.getElementById('material_id').value = '';
        document.getElementById('submitBtn').innerText = 'Simpan Material';
        document.getElementById('cancelBtn').classList.add('hidden');
        
        const codeInput = document.getElementById('code');
        codeInput.readOnly = false;
        codeInput.classList.remove('text-muted', 'cursor-not-allowed');
        
        // Show Stock Section
        document.getElementById('initialStockSection').style.display = 'block';
        
        // Reset Defaults
        document.getElementById('min_lead_time_days').value = 1;
        document.getElementById('max_lead_time_days').value = 7;
        document.getElementById('lead_time_average').value = 0;
        document.querySelector('input[name="is_manual_lead_time"][value="manual"]').checked = true;
        toggleLeadTimeInputs();

        document.getElementById('is_active').value = "1";
        document.getElementById('safety_stock').value = "0";
        document.getElementById('reorder_point').value = "0";
        document.getElementById('initialQty').value = "0";
        document.getElementById('initialPrice').value = "0";

        checkStockRequirement();
    }

    // --- LOGIKA VALIDASI STOCK OPNAME ---
    function checkStockRequirement() {
        const qtyInput = document.getElementById('initialQty');
        const priceInput = document.getElementById('initialPrice');
        const submitBtn = document.getElementById('submitBtn');
        const asterisk = document.getElementById('priceAsterisk');
        const priceLabel = document.getElementById('priceLabel');
        const errorMsg = document.getElementById('priceErrorMsg');
        const section = document.getElementById('initialStockSection');

        const qtyValue = parseFloat(qtyInput.value) || 0;
        const priceValue = parseFloat(priceInput.value) || 0;

        // Jika section hidden (mode edit), skip validasi
        if (section.style.display === 'none') {
            enableSubmitButton();
            return;
        }

        if (qtyValue > 0) {
            priceInput.required = true; 
            asterisk.classList.remove('hidden');
            priceLabel.classList.remove('text-muted');
            priceLabel.classList.add('text-silver', 'font-bold');
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
            priceLabel.classList.add('text-muted');
            priceLabel.classList.remove('text-silver', 'font-bold');
            priceInput.classList.remove('border-petronas', 'border-danger', 'ring-1', 'ring-danger');
            priceInput.classList.add('border-carbon');
            errorMsg.classList.add('hidden');
        }
    }

    function disableSubmitButton() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = 'Lengkapi Harga ⚠'; 
    }

    function enableSubmitButton() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = false;
        const isEdit = document.getElementById('material_id').value !== '';
        btn.innerText = isEdit ? 'Update Material' : 'Simpan Material';
    }

    // --- Modal Logic ---
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

    // Listener saat load
    document.addEventListener('DOMContentLoaded', function() {
        checkStockRequirement();
        toggleLeadTimeInputs(); // Init state
    });
</script>

</body>
</html>