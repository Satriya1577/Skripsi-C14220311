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
            <li class="text-petronas font-semibold">Materials</li>
        </ol>
    </nav>

    <x-alert-messages />

    <header>
        <p class="text-xs uppercase tracking-widest text-muted">Master Data</p>
        <h1 class="text-3xl font-extrabold text-petronas">Material Management</h1>
        <p class="text-sm text-muted mt-1">Kelola bahan baku, satuan, stok, dan harga</p>
    </header>

    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
        <h2 class="text-lg font-bold text-petronas">Add New Material</h2>
        <form action="{{ route('materials.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @csrf
            <input type="hidden" name="material_id" id="material_id">
            
            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Kode Barang</label>
                <input type="text" name="code" value="" required
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none">
            </div>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Nama Barang</label>
                <input type="text" name="name" value="" required
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none"
                    placeholder="Cth: Tepung Terigu Protein Tinggi">
            </div>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Category Type</label>
                <select name="category_type" required class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none">
                    <option value="mass">Mass (Berat)</option>
                    <option value="volume">Volume (Cairan)</option>
                    <option value="unit">Unit (Pcs)</option>
                </select>
            </div>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Lead Time (Days)</label>
                <input type="number" name="lead_time_days" value="1" min="0" required
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none">
            </div>

            <div class="md:col-span-2">
                <label class="text-xs text-muted uppercase tracking-wide">Status Material</label>
                <select name="is_active" required class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none">
                    <option value="1">Active (Aktif & Bisa Digunakan)</option>
                    <option value="0">Non-Active (Arsip / Tidak Digunakan)</option>
                </select>
                <p class="text-xs text-muted mt-1">Material non-aktif tidak akan muncul di pilihan pembelian atau produksi.</p>
            </div>

            <div class="md:col-span-2 bg-carbon rounded-xl p-4 border border-carbonSoft">
                <h4 class="text-sm font-bold text-petronas mb-4">Konfigurasi Satuan</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide">Satuan Pemakaian (Base Unit)</label>
                        {{-- <select name="unit" class="w-full mt-1 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon focus:border-petronas">
                            <option value="gram">Gram (Berat)</option>
                            <option value="ml">Mililiter (Cairan)</option>
                            <option value="pcs">Pcs (Unit)</option>
                        </select> --}}
                        <select name="unit" id="baseUnitSelect" class="w-full mt-1 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon focus:border-petronas">
                            <option value="gram" class="opt-mass">Gram (Berat)</option>
                            <option value="ml"   class="opt-volume">Mililiter (Cairan)</option>
                            <option value="pcs"  class="opt-unit">Pcs (Unit)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide">Satuan Pembelian</label>
                        <input type="text" name="purchase_unit" placeholder="Cth: Karung @50kg"
                            class="w-full mt-1 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon focus:border-petronas">
                    </div>
                    {{-- <div>
                        <label class="text-xs text-muted uppercase tracking-wide">Isi per Satuan Beli</label>
                        <input type="number" step="0.0001" name="conversion_factor" placeholder="Contoh: 50000"
                            class="w-full mt-1 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon focus:border-petronas">
                        <p class="text-xs text-muted mt-1">Berapa gram / ml / pcs dalam 1 kemasan beli</p>
                    </div> --}}

                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide">Isi per Kemasan (Netto)</label>
                        <div class="flex gap-2 mt-1">
                            <input type="number" step="0.01" name="packaging_size" placeholder="Cth: 50" required
                                class="w-2/3 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon focus:border-petronas focus:outline-none text-silver">
                            
                            <select name="packaging_unit" class="w-1/3 px-2 py-2 rounded-lg bg-carbonSoft border border-carbon focus:border-petronas text-silver text-sm">
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
                                    <option value="pcs">Pcs/Buah</option>
                                    <option value="dozen">Lusin (12)</option>
                                </optgroup>
                            </select>
                        </div>
                        <p class="text-xs text-muted mt-1">Sistem akan otomatis menghitung konversi ke Satuan Pemakaian.</p>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2 bg-carbon rounded-xl p-4 border border-carbonSoft">
                <h4 class="text-sm font-bold text-petronas mb-1">Saldo Awal (Stock Opname)</h4>
                <p class="text-xs text-muted mb-4">Isi hanya jika diawal entry data material sudah tersedia di gudang</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide">Jumlah Stok Di Gudang (Per Satuan Pembelian)</label>
                        <input type="number" step="0.01" name="initial_qty_purchase_unit" value="0"
                            class="w-full mt-1 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon focus:border-petronas">
                    </div>
                    <div>
                        <label class="text-xs text-muted uppercase tracking-wide">Harga Beli (Per Satuan Pembelian)</label>
                        <input type="number" step="0.01" name="initial_price_purchase_unit" value="0"
                            class="w-full mt-1 px-4 py-2 rounded-lg bg-carbonSoft border border-carbon focus:border-petronas">
                    </div>
                </div>
            </div>

            <div class="md:col-span-2 flex justify-end gap-3">
                <button type="button" id="cancelBtn" onclick="resetMaterialForm()" class="hidden px-6 py-2 rounded-lg border border-muted text-muted hover:bg-carbon transition">Cancel</button>
                
                <button type="submit" id="submitBtn" class="bg-petronas text-blackBase font-bold px-6 py-2 rounded-lg hover:bg-petronas/90 transition">Simpan Material</button>
            </div>
        </form>
    </section>

    <section class="bg-carbonSoft rounded-xl p-6">
        <h2 class="text-lg font-bold text-petronas mb-4">Material List</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-carbon">
                    <tr>
                        <th class="px-3 py-2 text-left text-muted">Code</th>
                        <th class="px-3 py-2 text-left text-muted">Name</th>
                        <th class="px-3 py-2 text-center text-muted">Status</th> 
                        <th class="px-3 py-2 text-right text-muted">Stock (Purchase Unit)</th> 
                        <th class="px-3 py-2 text-center text-muted">Base Unit</th>
                        <th class="px-3 py-2 text-right text-muted">Price / Purch. Unit</th> 
                        <th class="px-3 py-2 text-center text-muted">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($materials as $material)
                        @php
                            $factor = $material->conversion_factor > 0 ? $material->conversion_factor : 1;
                        @endphp
                        <tr class="border-b border-carbon hover:bg-carbon">
                            <td class="px-3 py-2 font-semibold">{{ $material->code }}</td>
                            <td class="px-3 py-2">{{ $material->name }}</td>

                            <td class="px-3 py-2 text-center">
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase
                                    {{ $material->is_active ? 'bg-green-900/30 text-green-400' : 'bg-red-900/30 text-red-400' }}">
                                    {{ $material->is_active ? 'Active' : 'Non-Active' }}
                                </span>
                            </td>

                            <td class="px-3 py-2 text-right font-semibold text-silver">
                                {{ number_format($material->current_stock / $factor, 2) }} 
                                <span class="ml-1">{{ $material->purchase_unit }}</span>
                            </td>

                            <td class="px-3 py-2 text-center text-muted">{{ $material->unit }}</td>

                            <td class="px-3 py-2 text-right font-semibold text-silver">
                                {{ number_format($material->price_per_unit * $factor, 2) }}
                            </td>

                            <td class="px-3 py-2 text-center space-x-2">
                                <a href="{{ route('materials.show', $material->id) }}" class="inline-flex w-8 h-8 items-center justify-center bg-petronas text-blackBase rounded hover:bg-petronas/80 transition">👁️</a>
                                <button type="button" onclick='editMaterial(@json($material))' class="inline-flex items-center justify-center w-8 h-8 rounded border border-petronas text-petronas hover:bg-petronas/10 transition">✏️</button>
                                <form action="{{ route('materials.destroy', $material->id) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="openDeleteModal(this)" class="inline-flex w-8 h-8 items-center justify-center border border-red-500 text-red-500 rounded hover:bg-red-500 hover:text-blackBase transition">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No materials available</td>
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

<script>
    // Script untuk filter Base Unit berdasarkan Category Type
    document.querySelector('select[name="category_type"]').addEventListener('change', function() {
        const selectedCategory = this.value; // mass, volume, atau unit
        const unitSelect = document.getElementById('baseUnitSelect');
        const options = unitSelect.querySelectorAll('option');

        // Mapping class option yang boleh muncul
        const allowedClass = 'opt-' + selectedCategory; 
        
        let firstVisible = null;

        options.forEach(opt => {
            if (opt.classList.contains(allowedClass)) {
                opt.style.display = 'block'; // Munculkan yang sesuai pasangan
                if (!firstVisible) firstVisible = opt;
            } else {
                opt.style.display = 'none';  // Sembunyikan yang tidak cocok
            }
        });

        // Otomatis pilih opsi pertama yang valid agar tidak kosong
        if (firstVisible) {
            unitSelect.value = firstVisible.value;
        }
    });

    // Trigger saat halaman pertama kali load (untuk handle old input jika validasi error)
    document.querySelector('select[name="category_type"]').dispatchEvent(new Event('change'));


    function editMaterial(material) {
        document.getElementById('material_id').value = material.id;
        document.querySelector('input[name="code"]').value = material.code;
        document.querySelector('input[name="code"]').readOnly = true;
        
        // Sembunyikan bagian saldo awal saat edit
        const initialStockDiv = document.querySelector('input[name="initial_qty_purchase_unit"]');
        if(initialStockDiv) {
             initialStockDiv.closest('.bg-carbon').style.display = 'none';
        }
        
        document.querySelector('input[name="name"]').value = material.name;
        document.querySelector('select[name="category_type"]').value = material.category_type;
        // Trigger change event agar unit select terfilter ulang sesuai kategori
        document.querySelector('select[name="category_type"]').dispatchEvent(new Event('change'));

        document.querySelector('input[name="lead_time_days"]').value = material.lead_time_days;
        document.querySelector('select[name="is_active"]').value = material.is_active ? "1" : "0";
        document.querySelector('select[name="unit"]').value = material.unit;
        document.querySelector('input[name="purchase_unit"]').value = material.purchase_unit;

        // --- PERBAIKAN LOGIKA DI SINI ---
        if (material.packaging_size && material.packaging_unit) {
            // JIKA DATA PACKAGING ADA DI DB
            document.querySelector('input[name="packaging_size"]').value = parseFloat(material.packaging_size);
            document.querySelector('select[name="packaging_unit"]').value = material.packaging_unit;
        } else {
            // JIKA TIDAK ADA, HITUNG MUNDUR (REVERSE CONVERSION)
            let factor = parseFloat(material.conversion_factor);
            let baseUnit = material.unit;
            
            let displaySize = factor;
            let displayUnit = baseUnit; 

            // Cek Konversi KG/Liter (1000)
            if (factor >= 1000 && factor % 1000 === 0) {
                if (baseUnit === 'gram') {
                    displaySize = factor / 1000;
                    displayUnit = 'kg';
                } else if (baseUnit === 'ml') {
                    displaySize = factor / 1000;
                    displayUnit = 'liter';
                }
            } 
            // Cek Konversi Ons (100)
            else if (baseUnit === 'gram' && factor >= 100 && factor % 100 === 0) {
                displaySize = factor / 100;
                displayUnit = 'ons';
            }

            // Set nilai input (Hanya dijalankan di blok ini)
            document.querySelector('input[name="packaging_size"]').value = displaySize;
            document.querySelector('select[name="packaging_unit"]').value = displayUnit;
        }
        // --- AKHIR PERBAIKAN ---

        // Ubah Teks Tombol & Munculkan Cancel
        document.getElementById('submitBtn').innerText = 'Update Material';
        
        // Hapus class hidden agar tombol muncul
        document.getElementById('cancelBtn').classList.remove('hidden');

        // Disable Upload Excel button while editing
        const uploadBtn = document.getElementById('uploadBtn');
        if(uploadBtn) uploadBtn.classList.add('opacity-50', 'pointer-events-none'); 
    }

    function resetMaterialForm() {
        document.querySelector('form').reset();
        document.getElementById('material_id').value = '';
        document.getElementById('submitBtn').innerText = 'Simpan Material';
        document.getElementById('cancelBtn').classList.add('hidden');
        document.querySelector('input[name="code"]').readOnly = false;
        document.querySelector('input[name="initial_qty_purchase_unit"]').closest('div').parentElement.parentElement.style.display = 'block';
        document.querySelector('select[name="is_active"]').value = "1";

        // [NEW] Enable Upload Excel button again
        const uploadBtn = document.getElementById('uploadBtn');
        uploadBtn.classList.remove('opacity-50', 'pointer-events-none');
    }

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

</body>
</html>