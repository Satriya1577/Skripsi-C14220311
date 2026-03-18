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
    
    {{-- Header with Create Button --}}
    <header class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 pb-4">
        <div>
            <p class="text-xs uppercase tracking-widest text-muted">Master Data</p>
            <h1 class="text-3xl font-extrabold text-petronas">Product Management</h1>
            <p class="text-sm text-muted mt-1">Kelola data produk, stok, dan parameter produksi</p>
        </div>
        <a href="{{ route('products.create') }}" class="bg-petronas text-blackBase font-bold px-6 py-2.5 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span>Add New Product</span>
        </a>
    </header>

    {{-- Product List --}}
    <section class="bg-carbonSoft rounded-xl p-6">
        
        {{-- BUNGKUSAN FLEXBOX BARU UNTUK JUDUL & TOMBOL UPDATE --}}
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-4 gap-4">
            <h2 class="text-lg font-bold text-petronas">Product List</h2>
            
            {{-- TOMBOL UPDATE BARU --}}
            <a href="#" class="bg-carbonSoft border border-petronas text-petronas text-sm font-bold px-4 py-2 rounded-lg hover:bg-petronas/10 transition shadow-sm flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span class="whitespace-nowrap">Update Lead Time & Safety Stock</span>
            </a>
        </div>

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
                        <th class="px-3 py-2 text-center text-muted">Selling Price</th>
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
                                    <span class="flex items-center gap-1 border border-carbon/50 px-1.5 py-0.5 rounded" title="Lead Time Settings">
                                        @if($product->is_manual_lead_time === 'manual')
                                            <span class="text-silver">⏱ Manual</span>
                                        @else
                                            <span class="text-petronas">⏱ Auto</span>
                                        @endif
                                        
                                        <span class="text-muted mx-1">|</span>
                                        
                                        <span class="{{ $product->lead_time_average ? 'text-silver' : 'text-muted' }} font-bold">
                                            Avg {{ number_format($product->lead_time_average ?? 0, 1) }}d
                                        </span>

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
                            <td class="px-3 py-2 text-center">Rp {{ number_format($product->cost_price, 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-center">Rp {{ number_format($product->price, 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-center space-x-2">
                                <a href="{{ route('products.show', $product->id) }}" class="inline-flex items-center justify-center w-8 h-8 rounded bg-petronas text-blackBase hover:bg-petronas/90 transition">👁️</a>
                                
                                {{-- Link ke halaman edit --}}
                               
                                <a href="{{ route('products.edit', $product->id) }}" class="inline-flex items-center justify-center w-8 h-8 rounded border border-petronas text-petronas hover:bg-petronas/10 transition">✏️</a>
                                
                                <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="openDeleteModal(this)" class="inline-flex items-center justify-center w-8 h-8 rounded border border-danger text-danger hover:bg-danger hover:text-blackBase transition">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    @if ($products->count() === 0)
                        <tr><td colspan="9" class="px-3 py-8 text-center text-muted italic">No products available</td></tr>
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

<script>
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