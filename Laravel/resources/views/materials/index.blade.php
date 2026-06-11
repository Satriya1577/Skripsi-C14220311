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
      <li class="text-slate-800 font-semibold">Materials</li>
    </ol>
  </nav>

  <x-alert-messages />

  <header class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 pb-4">
    <div>
      <p class="text-xs uppercase tracking-widest text-muted">Master Data</p>
      <h1 class="text-3xl font-extrabold text-slate-800">Material Management</h1>
      <p class="text-sm text-muted mt-1">Kelola bahan baku, satuan, stok, dan harga</p>
    </div>
    
    {{-- Group Tombol Action Utama --}}
    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
      {{-- Tombol Create Material --}}
      <a href="{{ route('materials.create') }}" class="bg-petronas text-blackBase font-bold px-6 py-2.5 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20 flex items-center justify-center gap-2 whitespace-nowrap">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        <span>Create Material</span>
      </a>
    </div>
  </header>

  {{-- TABLE SECTION --}}
  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-4 gap-4">
      <h2 class="text-lg font-bold text-slate-800 whitespace-nowrap">Material List</h2>
      
      {{-- Tombol Update Dipindahkan Ke Sini --}}
      <a href="{{ route('materials.updateMaterialLeadTimeSafetyStockROP') }}" class="bg-carbonSoft border border-blue-600 text-blue-600 text-sm font-bold px-4 py-3 rounded-lg hover:bg-blue-50 transition shadow-sm flex items-center justify-center gap-2 whitespace-nowrap">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
        <span>Update Lead Time, Safety Stock & ROP</span>
      </a>
    </div>
    
    <div class="overflow-x-auto rounded-lg border border-carbon">
      <table class="w-full text-sm">
        <thead class="bg-carbon">
          <tr>
            <th class="px-3 py-3 text-center text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Code</th>
            <th class="px-3 py-3 text-left text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Material Name</th>
            <th class="px-3 py-3 text-center text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Status</th> 
            <th class="px-3 py-3 text-center text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">On Hand Stock</th> 
            <th class="px-3 py-3 text-center text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Ordered Stock</th>
            <th class="px-3 py-3 text-center text-black border-l border-carbon whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Safety Stock</th>
            <th class="px-3 py-3 text-center text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">ROP</th>
            <th class="px-3 py-3 text-center text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Purchase Unit</th>
            <th class="px-3 py-3 text-center text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Base Unit</th>
            <th class="px-3 py-3 text-center text-black border-l border-carbon whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Buy Price / Unit</th> 
            <th class="px-3 py-3 text-center text-black whitespace-nowrap text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($materials as $material)
            @php
              $factor = $material->conversion_factor > 0 ? $material->conversion_factor : 1;
            @endphp
            <tr class="border-b border-carbon hover:bg-carbon transition-colors group">
              
              {{-- CLICKABLE CODE --}}
              <td class="px-3 py-3 text-center font-semibold whitespace-nowrap">
                <a href="{{ route('materials.show', $material->id) }}" class="text-blue-600 hover:text-blue-800 hover:underline transition-colors block">
                  {{ $material->code }}
                </a>
              </td>
              
              {{-- CLICKABLE NAME --}}
              <td class="px-3 py-3 text-left whitespace-nowrap">
                <a href="{{ route('materials.show', $material->id) }}" class="block">
                  <div class="font-bold text-silver group-hover:text-blue-600 transition-colors">{{ $material->name }}</div>
                </a>
              </td>

              <td class="px-3 py-3 text-center whitespace-nowrap">
                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase
                  {{ $material->is_active ? 'bg-green-300/50 text-green-600' : 'bg-red-300/50 text-red-600' }}">
                  {{ $material->is_active ? 'Active' : 'Non-Active' }}
                </span>
              </td>

              <td class="px-3 py-3 text-center font-semibold text-silver whitespace-nowrap">
                {{ number_format($material->current_stock / $factor, 0) }} 
              </td>

              <td class="px-3 py-3 text-center font-semibold text-silver whitespace-nowrap">
                {{ number_format($material->ordered_stock / $factor, 0) }} 
              </td>

              <td class="px-3 py-3 text-center text-muted border-l border-carbon whitespace-nowrap">
                {{ number_format($material->safety_stock / $factor) }}
              </td>
              <td class="px-3 py-3 text-center text-slate-800 font-bold whitespace-nowrap">
                {{ number_format($material->reorder_point / $factor) }}
              </td>

              <td class="px-3 py-3 text-center text-muted whitespace-nowrap">{{ $material->purchase_unit }}</td>

              <td class="px-3 py-3 text-center text-muted whitespace-nowrap">{{ $material->unit }}</td>
              
              {{-- BUY PRICE (Rata Kiri Kanan) --}}
              <td class="px-3 py-3 border-l border-carbon whitespace-nowrap">
                <div class="flex justify-between items-center min-w-[90px]">
                  <span class="text-muted text-xs">Rp</span>
                  <span class="text-right font-semibold text-silver ml-2">{{ number_format($material->price_per_unit * $factor, 2, ',', '.') }}</span>
                </div>
              </td>

              {{-- ACTIONS --}}
              <td class="px-3 py-3 text-center space-x-1 whitespace-nowrap">
                {{-- View / Show Icon --}}
                <a href="{{ route('materials.show', $material->id) }}" class="inline-flex items-center justify-center w-8 h-8 rounded bg-petronas text-white hover:bg-blue-700 transition" title="View Details">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                  </svg>
                </a>

                {{-- Edit Icon --}}
                <a href="{{ route('materials.edit', $material->id) }}" class="inline-flex items-center justify-center w-8 h-8 rounded bg-yellow-500 text-white hover:bg-yellow-600 transition" title="Edit">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                  </svg>
                </a>
                
                {{-- Delete Icon --}}
                <form action="{{ route('materials.destroy', $material->id) }}" method="POST" class="inline">
                  @csrf @method('DELETE')
                  <button type="button" onclick="openDeleteModal(this)" class="inline-flex items-center justify-center w-8 h-8 rounded bg-danger text-white hover:bg-red-600 transition" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="11" class="text-center text-muted py-8 italic bg-slate-100 whitespace-nowrap">No materials available</td>
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
</script>

</body>
</html>