<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Import Data | Production Planning System</title>
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
            <li><a href="{{ route('settings.index') }}" class="hover:text-petronas transition-colors">Settings</a></li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold" aria-current="page">Import Data</li>
        </ol>
    </nav>

    <x-alert-messages />

    <header class="space-y-2">
        <p class="text-xs uppercase tracking-widest text-muted">Data Import Tool</p>
        <h1 class="text-3xl font-extrabold text-petronas">Import Data Master</h1>
        <p class="text-sm text-muted mt-1">
            Silakan pilih jenis data yang ingin diimport dan sesuaikan format Excel dengan panduan.
        </p>
    </header>

    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon w-full transition-all duration-300">
        
        <div class="mb-6">
            <label class="text-xs text-muted uppercase tracking-wide block mb-2">Pilih Jenis Data</label>
            <select id="importType" onchange="switchImportType()" 
                    class="w-full md:w-1/2 px-4 py-3 rounded-lg bg-carbon border border-petronas text-silver focus:outline-none focus:ring-1 focus:ring-petronas cursor-pointer">
                
                <option value="product" 
                        data-route="{{ route('products.import.excel') }}" 
                        data-title="Import Produk (Finished Goods)"
                        selected>
                    📦 Import Produk (Finished Goods)
                </option>

                <option value="material" 
                        data-route="{{ route('materials.import.excel') }}" 
                        data-title="Import Bahan Baku (Raw Materials)">
                    🧱 Import Bahan Baku (Material)
                </option>

                <option value="recipe" 
                        data-route="{{ route('product_materials.import.excel') }}"
                        data-title="Import Resep (Bill of Materials)">
                    📜 Import Resep (Product Material)
                </option>

                <option value="sales" 
                        data-route="{{ route('sales.import.excel') }}"
                        data-title="Import Transaksi Penjualan (Sales)">
                    💰 Import Data Penjualan (Sales)
                </option>
            </select>
        </div>

        <div class="border-t border-carbon my-6"></div>

        <form id="importForm" action="{{ route('products.import.excel') }}" method="POST" enctype="multipart/form-data" class="space-y-4 w-full">
            @csrf

            <h3 id="formTitle" class="text-lg font-bold text-petronas">Import Produk</h3>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Upload Excel File (.xlsx, .xls)</label>
                <input type="file" name="file" required
                       class="block w-full text-sm mt-2
                              file:bg-carbon file:border-0
                              file:px-4 file:py-2
                              file:rounded-lg
                              file:text-silver
                              file:mr-4 file:hover:bg-carbon/80 file:cursor-pointer
                              text-silver focus:outline-none">
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('settings.index') }}"
                   class="px-6 py-2 rounded-lg border border-muted text-muted hover:bg-carbon transition">
                    Cancel
                </a>

                <button type="submit"
                        class="bg-petronas text-blackBase font-bold px-6 py-2 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20">
                    Upload File
                </button>
            </div>
        </form>
    </section>

    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon w-full">
        <div class="flex items-center gap-2 mb-4">
            <h2 class="text-lg font-bold text-petronas">Panduan Kolom Excel</h2>
            <span class="text-xs bg-carbon px-2 py-1 rounded text-muted">Format Wajib</span>
        </div>

        <p class="text-sm text-muted mb-6">
            Pastikan header file Excel Anda sesuai dengan tabel di bawah ini.
        </p>

        <div id="guide-product" class="overflow-x-auto rounded-lg border border-carbon">
            <table class="w-full text-sm">
                <thead class="bg-carbon">
                    <tr>
                        <th class="px-4 py-3 text-left text-muted text-xs uppercase">Nama Kolom</th>
                        <th class="px-4 py-3 text-left text-muted text-xs uppercase">Deskripsi</th>
                        <th class="px-4 py-3 text-left text-muted text-xs uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon/50">
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">product_code</code></td>
                        <td class="px-4 py-3 text-silver">Kode unik produk (SKU).</td>
                        <td class="px-4 py-3"><span class="text-xs bg-petronas/10 text-petronas px-2 py-0.5 rounded">Wajib</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">name</code></td>
                        <td class="px-4 py-3 text-silver">Nama produk jadi.</td>
                        <td class="px-4 py-3"><span class="text-xs bg-petronas/10 text-petronas px-2 py-0.5 rounded">Wajib</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">current_stock</code></td>
                        <td class="px-4 py-3 text-silver">Stok awal fisik.</td>
                        <td class="px-4 py-3"><span class="text-xs border border-carbon text-muted px-2 py-0.5 rounded">Opsional</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">safety_stock</code></td>
                        <td class="px-4 py-3 text-silver">Batas aman stok minimum.</td>
                        <td class="px-4 py-3"><span class="text-xs border border-carbon text-muted px-2 py-0.5 rounded">Opsional</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">price</code></td>
                        <td class="px-4 py-3 text-silver">Harga Jual Produk.</td>
                        <td class="px-4 py-3"><span class="text-xs border border-carbon text-muted px-2 py-0.5 rounded">Opsional</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="guide-material" class="overflow-x-auto rounded-lg border border-carbon hidden">
            <table class="w-full text-sm">
                <thead class="bg-carbon">
                    <tr>
                        <th class="px-4 py-3 text-left text-muted text-xs uppercase">Nama Kolom</th>
                        <th class="px-4 py-3 text-left text-muted text-xs uppercase">Deskripsi</th>
                        <th class="px-4 py-3 text-left text-muted text-xs uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon/50">
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">material_code</code></td>
                        <td class="px-4 py-3 text-silver">Kode unik material (Contoh: MAT-001).</td>
                        <td class="px-4 py-3"><span class="text-xs bg-petronas/10 text-petronas px-2 py-0.5 rounded">Wajib</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">name</code></td>
                        <td class="px-4 py-3 text-silver">Nama material.</td>
                        <td class="px-4 py-3"><span class="text-xs bg-petronas/10 text-petronas px-2 py-0.5 rounded">Wajib</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">category_type</code></td>
                        <td class="px-4 py-3 text-silver">Pilih: <strong>mass</strong>, <strong>volume</strong>, atau <strong>unit</strong>.</td>
                        <td class="px-4 py-3"><span class="text-xs bg-petronas/10 text-petronas px-2 py-0.5 rounded">Wajib</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">unit</code></td>
                        <td class="px-4 py-3 text-silver">Satuan Dasar (gram, ml, pcs).</td>
                        <td class="px-4 py-3"><span class="text-xs bg-petronas/10 text-petronas px-2 py-0.5 rounded">Wajib</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">purchase_unit</code></td>
                        <td class="px-4 py-3 text-silver">Satuan Beli (Sak @25kg, Botol).</td>
                        <td class="px-4 py-3"><span class="text-xs bg-petronas/10 text-petronas px-2 py-0.5 rounded">Wajib</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">packaging_size</code></td>
                        <td class="px-4 py-3 text-silver">Angka isi per kemasan (Contoh: 25).</td>
                        <td class="px-4 py-3"><span class="text-xs bg-petronas/10 text-petronas px-2 py-0.5 rounded">Wajib</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">packaging_unit</code></td>
                        <td class="px-4 py-3 text-silver">Satuan kemasan (kg, liter).</td>
                        <td class="px-4 py-3"><span class="text-xs bg-petronas/10 text-petronas px-2 py-0.5 rounded">Wajib</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">price_per_unit</code></td>
                        <td class="px-4 py-3 text-silver">Harga Beli per <strong>Kemasan</strong>.</td>
                        <td class="px-4 py-3"><span class="text-xs border border-carbon text-muted px-2 py-0.5 rounded">Opsional</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="guide-recipe" class="overflow-x-auto rounded-lg border border-carbon hidden">
            <table class="w-full text-sm">
                <thead class="bg-carbon">
                    <tr>
                        <th class="px-4 py-3 text-left text-muted text-xs uppercase">Nama Kolom</th>
                        <th class="px-4 py-3 text-left text-muted text-xs uppercase">Deskripsi</th>
                        <th class="px-4 py-3 text-left text-muted text-xs uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon/50">
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">Kode</code></td>
                        <td class="px-4 py-3 text-silver">Kode Produk Induk (Parent).</td>
                        <td class="px-4 py-3"><span class="text-xs bg-petronas/10 text-petronas px-2 py-0.5 rounded">Wajib</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">Material</code></td>
                        <td class="px-4 py-3 text-silver">Nama Bahan Baku (Sesuai Master Material).</td>
                        <td class="px-4 py-3"><span class="text-xs bg-petronas/10 text-petronas px-2 py-0.5 rounded">Wajib</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">Qty</code></td>
                        <td class="px-4 py-3 text-silver">Jumlah kebutuhan per 1 unit produk.</td>
                        <td class="px-4 py-3"><span class="text-xs bg-petronas/10 text-petronas px-2 py-0.5 rounded">Wajib</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="guide-sales" class="overflow-x-auto rounded-lg border border-carbon hidden">
            <table class="w-full text-sm">
                <thead class="bg-carbon">
                    <tr>
                        <th class="px-4 py-3 text-left text-muted text-xs uppercase">Nama Kolom</th>
                        <th class="px-4 py-3 text-left text-muted text-xs uppercase">Deskripsi</th>
                        <th class="px-4 py-3 text-left text-muted text-xs uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon/50">
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">Tanggal Nota</code></td>
                        <td class="px-4 py-3 text-silver">Tanggal transaksi (Format Excel Date).</td>
                        <td class="px-4 py-3"><span class="text-xs bg-petronas/10 text-petronas px-2 py-0.5 rounded">Wajib</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">
                            <code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">Kode Barang</code>
                        </td>
                        <td class="px-4 py-3 text-silver">Kode unik produk (SKU).</td>
                        <td class="px-4 py-3"><span class="text-xs bg-petronas/10 text-petronas px-2 py-0.5 rounded">Wajib</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><code class="text-petronas bg-blackBase/30 px-2 py-1 rounded">Quantity</code></td>
                        <td class="px-4 py-3 text-silver">Jumlah barang terjual (Angka).</td>
                        <td class="px-4 py-3"><span class="text-xs bg-petronas/10 text-petronas px-2 py-0.5 rounded">Wajib</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

    </section>

</main>

<script>
    function switchImportType() {
        const select = document.getElementById('importType');
        const selectedOption = select.options[select.selectedIndex];
        
        // 1. Ambil data dari option yang dipilih
        const targetRoute = selectedOption.getAttribute('data-route');
        const titleText = selectedOption.getAttribute('data-title');
        const typeValue = select.value;

        // 2. Ubah Action Form & Judul
        document.getElementById('importForm').action = targetRoute;
        document.getElementById('formTitle').innerText = titleText;

        // 3. Toggle Tampilan Tabel Panduan
        const guideProduct = document.getElementById('guide-product');
        const guideMaterial = document.getElementById('guide-material');
        const guideRecipe = document.getElementById('guide-recipe');
        const guideSales = document.getElementById('guide-sales');

        // Reset semua ke hidden dulu
        guideProduct.classList.add('hidden');
        guideMaterial.classList.add('hidden');
        guideRecipe.classList.add('hidden');
        guideSales.classList.add('hidden');

        // Munculkan yang dipilih
        if (typeValue === 'product') {
            guideProduct.classList.remove('hidden');
        } else if (typeValue === 'material') {
            guideMaterial.classList.remove('hidden');
        } else if (typeValue === 'recipe') {
            guideRecipe.classList.remove('hidden');
        } else if (typeValue === 'sales') {
            guideSales.classList.remove('hidden');
        }
    }
</script>

</body>
</html>