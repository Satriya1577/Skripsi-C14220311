<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $product->name }} - Recipe | Production Planning System</title>
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
            <p class="text-sm text-muted mt-1">Kode: <span class="font-mono text-silver">{{ $product->code }}</span></p>
        </div>
    </header>

    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
        <h2 class="text-lg font-bold text-petronas mb-4">
            Product Specification (Read Only)
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <label class="text-xs text-muted uppercase tracking-wide block mb-1">Product Code</label>
                <div class="w-full px-4 py-2 rounded-lg bg-blackBase/50 border border-carbon text-muted cursor-not-allowed font-mono">
                    {{ $product->code }}
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="text-xs text-muted uppercase tracking-wide block mb-1">Product Name</label>
                <div class="w-full px-4 py-2 rounded-lg bg-blackBase/50 border border-carbon text-muted cursor-not-allowed">
                    {{ $product->name }}
                </div>
            </div>

             <div>
                <label class="text-xs text-muted uppercase tracking-wide block mb-1">Selling Price</label>
                <div class="w-full px-4 py-2 rounded-lg bg-blackBase/50 border border-carbon text-muted cursor-not-allowed font-mono text-right">
                    {{ number_format($product->price, 2) }}
                </div>
            </div>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide block mb-1">Current Stock</label>
                <div class="w-full px-4 py-2 rounded-lg bg-blackBase/50 border border-carbon text-muted cursor-not-allowed font-mono">
                    {{ $product->current_stock }}
                </div>
            </div>

            <div>
                <label class="text-xs text-muted uppercase tracking-wide block mb-1">Safety Stock</label>
                <div class="w-full px-4 py-2 rounded-lg bg-blackBase/50 border border-carbon text-muted cursor-not-allowed font-mono">
                    {{ $product->safety_stock }}
                </div>
            </div>
            
             <div>
                <label class="text-xs text-muted uppercase tracking-wide block mb-1">Cost Price (HPP)</label>
                <div class="w-full px-4 py-2 rounded-lg bg-blackBase/50 border border-carbon text-muted cursor-not-allowed font-mono text-right">
                    {{ number_format($product->cost_price, 2) }}
                </div>
            </div>
        </div>
    </section>

    <section class="bg-carbonSoft rounded-xl p-6 border border-petronas/30 shadow-lg shadow-petronas/5 relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-petronas/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10">
            <h2 class="text-lg font-bold text-petronas mb-1">
                Add Material Ingredient
            </h2>
            <p class="text-xs text-muted mb-6">Tambahkan bahan baku untuk menyusun resep 1 unit produk ini.</p>
            <form action="{{ route('product_materials.store', $product->id) }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                <div class="flex flex-col md:flex-row gap-4 items-end">
                    
                    <div class="flex-grow w-full md:w-3/5">
                        <label class="text-xs text-silver font-semibold uppercase tracking-wide mb-2 block">
                            Select Material (Bahan Baku)
                        </label>
                        <div class="relative">
                            <select name="material_id" id="materialSelect" required
                                class="w-full appearance-none bg-carbon border border-muted/30 text-silver text-sm rounded-lg focus:ring-petronas focus:border-petronas block w-full p-3 pr-10 hover:border-petronas/50 transition">
                                <option value="" disabled selected data-unit="Qty">-- Choose Material --</option>
                                @foreach($materials as $material)
                                    <option value="{{ $material->id }}" data-unit="{{ $material->unit }}">
                                        {{ $material->code }} - {{ $material->name }} (Unit: {{ $material->unit }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-muted">
                                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                    </div>

                    <div class="w-full md:w-1/4">
                        <label class="text-xs text-silver font-semibold uppercase tracking-wide mb-2 block">
                            Usage Quantity
                        </label>
                        <div class="relative group">
                            <input type="number" step="0.001" name="amount_needed" placeholder="0.00" required
                                class="block w-full p-3 pr-16 bg-carbon border border-muted/30 rounded-lg text-white font-mono placeholder-muted/50 focus:ring-1 focus:ring-petronas focus:border-petronas hover:border-petronas/50 transition"
                            >
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <span id="unitLabel" class="text-xs text-petronas font-bold bg-petronas/10 px-2 py-1 rounded transition-all duration-300">
                                    
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="w-full md:w-auto">
                        <button type="submit" 
                            class="w-full md:w-auto bg-petronas text-blackBase font-bold px-6 py-3 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20 flex items-center justify-center gap-2">
                            <span>+ Add</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-petronas">
                Current Recipe List
            </h2>
            <span class="text-xs bg-carbon px-3 py-1 rounded-full text-muted border border-carbon">
                Total Items: {{ $product->productMaterials->count() }}
            </span>
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
                            <td class="px-4 py-3 font-mono text-petronas">
                                {{ $pm->material->code }}
                            </td>
                            <td class="px-4 py-3 text-silver">
                                {{ $pm->material->name }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-white">
                                {{ number_format($pm->amount_needed, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center text-muted">
                                {{ $pm->material->unit }}
                            </td>
                            <td class="px-4 py-3 text-right text-muted font-mono text-xs">
                                {{ number_format($pm->amount_needed * $pm->material->price_per_unit, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{-- {{ route('product-materials.destroy', $pm->id) }} --}}
                                <form action="#" method="POST" onsubmit="return confirm('Remove ingredient?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-500 transition" title="Remove Ingredient">
                                        ✕
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-muted italic">
                                No ingredients added yet. Add materials above to create the recipe.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($product->productMaterials->count() > 0)
                <tfoot class="bg-carbon/50">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right text-muted font-bold text-xs uppercase">Total Recipe Cost:</td>
                        <td class="px-4 py-3 text-right text-petronas font-bold font-mono">
                            {{ number_format($product->productMaterials->sum(function($pm){ 
                                return $pm->amount_needed * $pm->material->price_per_unit; 
                            }), 2) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </section>

</main>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const materialSelect = document.getElementById('materialSelect');
        const unitLabel = document.getElementById('unitLabel');

        // Fungsi untuk update label
        function updateUnitLabel() {
            // Ambil option yang sedang dipilih
            const selectedOption = materialSelect.options[materialSelect.selectedIndex];
            
            // Ambil attribut data-unit
            const unit = selectedOption.getAttribute('data-unit');

            // Update text label, jika kosong default ke 'Qty'
            unitLabel.innerText = unit ? unit : 'Qty';
        }

        // Jalankan saat dropdown berubah
        materialSelect.addEventListener('change', updateUnitLabel);
    });
</script>

</body>
</html>