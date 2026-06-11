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
            blackBase: '#E2E8F0',
            carbon: '#CBD5E1',
            carbonSoft: '#F8FAFC',
            silver: '#334155',
            petronas: '#2563EB',
            muted: '#64748B',
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
      <li><a href="{{ route('home.index') }}" class="hover:text-blue-600 transition-colors">Home</a></li>
      <li class="opacity-40">/</li>
      <li class="text-slate-800 font-semibold">Products</li>
    </ol>
  </nav>
  
  <x-alert-messages />
  
  {{-- Header with Create Button --}}
  <header class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 pb-4">
    <div>
      <p class="text-xs uppercase tracking-widest text-muted">Master Data</p>
      <h1 class="text-3xl font-extrabold text-slate-800">Product Management</h1>
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
    
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-4 gap-4">
      <h2 class="text-lg font-bold text-slate-800">Product List</h2>
      
      <form action="{{ route('products.updateProductLeadTimeSafetyStock') }}" method="POST" onsubmit="return confirm('Kalkulasi ulang Lead Time & Safety Stock seluruh produk?');">
        @csrf
        <button type="submit" class="bg-carbonSoft border border-blue-600 text-blue-600 text-sm font-bold px-4 py-2 rounded-lg hover:bg-blue-50 transition shadow-sm flex items-center justify-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
          <span class="whitespace-nowrap">Update Lead Time & Safety Stock</span>
        </button>
      </form>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-carbon">
          <tr>
            <th class="px-3 py-2 text-center text-black">Code</th>
            <th class="px-3 py-2 text-left text-black">Product Name</th> 
            <th class="px-3 py-2 text-center text-black">On Hand Stock</th> 
            <th class="px-3 py-2 text-center text-black font-semibold">Reserved Stock</th> 
            <th class="px-3 py-2 text-center text-black font-bold border-l border-carbon">Available Stock</th> 
            <th class="px-3 py-2 text-center text-black">Safety Stock</th>
            <th class="px-3 py-2 text-center text-black">HPP</th>
            <th class="px-3 py-2 text-center text-black">Selling Price</th>
            <th class="px-3 py-2 text-center text-black">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($products as $product)
            <tr class="border-b border-carbon hover:bg-carbon transition-colors group">
              
              {{-- CLICKABLE CODE --}}
              <td class="px-3 py-2 text-center font-semibold">
                <a href="{{ route('products.show', $product->id) }}" class="text-blue-600 hover:text-blue-800 hover:underline transition-colors block">
                  {{ $product->code }}
                </a>
              </td>
              
              {{-- CLICKABLE NAME --}}
              <td class="px-3 py-2 text-left">
                <a href="{{ route('products.show', $product->id) }}" class="block">
                  <div class="font-bold text-silver group-hover:text-blue-600 transition-colors">{{ $product->name }}</div>
                  <div class="flex flex-col gap-1 text-[10px] text-muted mt-1">
                    
                    {{-- Baris 1: Kemasan (Lead Time Dihapus) --}}
                    <div class="flex flex-wrap items-center gap-2">
                      @if($product->packaging)
                        <span class="bg-carbon px-1.5 py-0.5 rounded border border-carbon/50 text-slate-800">{{ $product->packaging }}</span>
                      @endif
                    </div>

                    {{-- Baris 2: Qty per Batch Dihapus --}}

                  </div>
                </a>
              </td>

              <td class="px-3 py-2 text-center">{{ $product->current_stock }}</td>
              <td class="px-3 py-2 text-center text-slate-800 font-medium">{{ $product->committed_stock }}</td>
              <td class="px-3 py-2 text-center font-bold text-silver border-l border-carbon">
                {{ $product->current_stock - $product->committed_stock }}
              </td>
              <td class="px-3 py-2 text-center text-muted text-xs">{{ $product->safety_stock }}</td>
              
              {{-- HPP (Rata Kiri Kanan) --}}
              <td class="px-3 py-2">
                <div class="flex justify-between items-center min-w-[90px]">
                  <span class="text-muted text-xs">Rp</span>
                  <span class="text-right">{{ number_format($product->cost_price, 2, ',', '.') }}</span>
                </div>
              </td>
              
              {{-- Selling Price (Rata Kiri Kanan) --}}
              <td class="px-3 py-2">
                <div class="flex justify-between items-center min-w-[90px]">
                  <span class="text-muted text-xs">Rp</span>
                  <span class="text-right">{{ number_format($product->price, 2, ',', '.') }}</span>
                </div>
              </td>
              
              {{-- ACTIONS --}}
              <td class="px-3 py-2 text-center space-x-1 whitespace-nowrap">
                <a href="{{ route('products.edit', $product->id) }}" class="inline-block px-3 py-1 rounded border border-yellow-500 text-yellow-600 hover:bg-yellow-50 hover:text-yellow-700 transition font-semibold text-xs">
                  Edit
                </a>
                
                <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="inline">
                  @csrf @method('DELETE')
                  <button type="button" onclick="openDeleteModal(this)" class="inline-block px-3 py-1 rounded border border-danger text-danger hover:bg-red-50 hover:text-red-700 transition font-semibold text-xs">
                    Delete
                  </button>
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
      <button onclick="closeDeleteModal()" class="px-5 py-2 rounded-lg border border-muted text-slate-800 hover:bg-carbon transition font-bold">Cancel</button>
      <button onclick="confirmDelete()" class="px-5 py-2 rounded-lg bg-danger text-white font-bold hover:bg-red-600 transition shadow-lg shadow-red-500/30">Delete</button>
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