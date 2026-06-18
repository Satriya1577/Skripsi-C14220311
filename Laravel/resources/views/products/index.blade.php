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
        <button type="submit" class="bg-carbonSoft border border-blue-600 text-blue-600 text-sm font-bold px-4 py-3 rounded-lg hover:bg-blue-50 transition shadow-sm flex items-center justify-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
          <span class="whitespace-nowrap">Update Lead Time & Safety Stock</span>
        </button>
      </form>
    </div>

    <div class="overflow-x-auto rounded-lg border border-carbon">
      <table class="w-full text-sm">
        <thead class="bg-carbon">
          <tr>
            <th class="px-3 py-3 text-center text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Code</th>
            <th class="px-3 py-3 text-left text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Product Name</th> 
            <th class="px-3 py-3 text-center text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">On Production Stock</th> 
            <th class="px-3 py-3 text-center text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">On Hand Stock</th> 
            <th class="px-3 py-3 text-center text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Reserved Stock</th> 
            <th class="px-3 py-3 text-center text-black border-l border-carbon whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Available Stock</th> 
            <th class="px-3 py-3 text-center text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Safety Stock</th>
            <th class="px-3 py-3 text-center text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">HPP</th>
            <th class="px-3 py-3 text-center text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Selling Price</th>
            <th class="px-3 py-3 text-center text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($products as $product)
            <tr class="border-b border-carbon hover:bg-carbon transition-colors group">
              
              {{-- CODE --}}
              <td class="px-3 py-3 text-center font-semibold text-slate-800 whitespace-nowrap">
                {{ $product->code }}
              </td>
              
              {{-- NAME --}}
              <td class="px-3 py-3 text-left whitespace-nowrap">
                <div class="font-bold text-slate-800">{{ $product->name }}</div>
                <div class="flex flex-col gap-1 text-[10px] text-muted mt-1">
                  <div class="flex flex-wrap items-center gap-2">
                    @if($product->packaging)
                      <span class="bg-carbon px-1.5 py-0.5 rounded border border-carbon/50 text-slate-800">{{ $product->packaging }}</span>
                    @endif
                  </div>
                </div>
              </td>
              <td class="px-3 py-3 text-center whitespace-nowrap">{{ $product->on_production_stock }}</td>
              <td class="px-3 py-3 text-center whitespace-nowrap">{{ $product->current_stock }}</td>
              <td class="px-3 py-3 text-center text-slate-800 font-medium whitespace-nowrap">{{ $product->committed_stock }}</td>
              <td class="px-3 py-3 text-center font-bold text-slate-800 border-l border-carbon whitespace-nowrap">
                {{ $product->current_stock - $product->committed_stock }}
              </td>
              <td class="px-3 py-3 text-center text-muted text-xs whitespace-nowrap">{{ $product->safety_stock }}</td>
              
              {{-- HPP --}}
              <td class="px-3 py-3 whitespace-nowrap">
                <div class="flex justify-between items-center min-w-[90px]">
                  <span class="text-muted text-xs">Rp</span>
                  <span class="text-right text-slate-800 font-medium ml-2">{{ number_format($product->cost_price, 2, ',', '.') }}</span>
                </div>
              </td>
              
              {{-- Selling Price --}}
              <td class="px-3 py-3 whitespace-nowrap">
                <div class="flex justify-between items-center min-w-[90px]">
                  <span class="text-muted text-xs">Rp</span>
                  <span class="text-right text-slate-800 font-medium ml-2">{{ number_format($product->price, 2, ',', '.') }}</span>
                </div>
              </td>
              
              {{-- ACTIONS WITH SVG ICONS --}}
              <td class="px-3 py-3 text-center space-x-1 whitespace-nowrap">
                
                {{-- View / Show Icon --}}
                <a href="{{ route('products.show', $product->id) }}" class="inline-flex items-center justify-center w-8 h-8 rounded bg-petronas text-white hover:bg-blue-700 transition" title="View Details">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                  </svg>
                </a>

                {{-- Edit Icon --}}
                <a href="{{ route('products.edit', $product->id) }}" class="inline-flex items-center justify-center w-8 h-8 rounded bg-yellow-500 text-white hover:bg-yellow-600 transition" title="Edit">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                  </svg>
                </a>
                
                {{-- Delete Icon --}}
                <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="inline">
                  @csrf @method('DELETE')
                  <button type="button" onclick="openDeleteModal(this)" class="inline-flex items-center justify-center w-8 h-8 rounded bg-danger text-white hover:bg-red-600 transition" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                  </button>
                </form>

              </td>
            </tr>
          @endforeach

          @if ($products->count() === 0)
            <tr><td colspan="9" class="px-3 py-8 text-center text-muted italic whitespace-nowrap">No products available</td></tr>
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