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

    <header class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 pb-4">
        <div>
            <p class="text-xs uppercase tracking-widest text-muted">Master Data</p>
            <h1 class="text-3xl font-extrabold text-petronas">Material Management</h1>
            <p class="text-sm text-muted mt-1">Kelola bahan baku, satuan, stok, dan harga</p>
        </div>
        
        {{-- Group Tombol Action Utama --}}
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            {{-- Tombol Create Material --}}
            <a href="{{ route('materials.create') }}" class="bg-petronas text-blackBase font-bold px-6 py-2.5 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20 flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span class="whitespace-nowrap">Create Material</span>
            </a>
        </div>
    </header>

    {{-- TABLE SECTION --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-4 gap-4">
            <h2 class="text-lg font-bold text-petronas">Material List</h2>
            
            {{-- Tombol Update Dipindahkan Ke Sini --}}
            <a href="{{ route('materials.updateMaterialLeadTimeSafetyStockROP') }}" class="bg-carbonSoft border border-petronas text-petronas text-sm font-bold px-4 py-2 rounded-lg hover:bg-petronas/10 transition shadow-sm flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span class="whitespace-nowrap">Update Lead Time, Safety Stock & ROP</span>
            </a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-carbon">
                    <tr>
                        <th class="px-3 py-2 text-left text-muted">Code</th>
                        <th class="px-3 py-2 text-left text-muted">Name</th>
                        <th class="px-3 py-2 text-center text-muted">Status</th> 
                        <th class="px-3 py-2 text-right text-muted">On Hand</th> 
                        <th class="px-3 py-2 text-center text-muted">Ordered Stock</th>
                        <th class="px-3 py-2 text-right text-muted border-l border-carbon">Safety Stock</th>
                        <th class="px-3 py-2 text-right text-petronas font-bold">ROP</th>
                        <th class="px-3 py-2 text-center text-muted">Purchase Unit</th>
                        <th class="px-3 py-2 text-center text-muted">Base Unit</th>
                        <th class="px-3 py-2 text-right text-muted border-l border-carbon">Buy Price / Unit</th> 
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
                                </div>
                            </td>

                            <td class="px-3 py-2 text-center">
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase
                                    {{ $material->is_active ? 'bg-green-900/30 text-green-400' : 'bg-red-900/30 text-red-400' }}">
                                    {{ $material->is_active ? 'Active' : 'Non-Active' }}
                                </span>
                            </td>

                            <td class="px-3 py-2 text-right font-semibold text-silver">
                                {{ number_format($material->current_stock / $factor, 0) }} 
                            </td>

                            <td class="px-3 py-2 text-right font-semibold text-silver">
                                {{ number_format($material->ordered_stock / $factor, 0) }} 
                            </td>

                            <td class="px-3 py-2 text-right text-muted border-l border-carbon">
                                {{ number_format($material->safety_stock / $factor) }}
                            </td>
                            <td class="px-3 py-2 text-right text-petronas font-bold">
                                {{ number_format($material->reorder_point / $factor) }}
                            </td>

                            <td class="px-3 py-2 text-center text-muted">{{ $material->purchase_unit }}</td>

                            <td class="px-3 py-2 text-center text-muted">{{ $material->unit }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-silver border-l border-carbon">
                                Rp {{ number_format($material->price_per_unit * $factor, 2, ',', '.') }}
                            </td>

                            <td class="px-3 py-2 text-center space-x-2">
                                <a href="{{ route('materials.show', $material->id) }}" class="inline-flex w-8 h-8 items-center justify-center bg-petronas text-blackBase rounded hover:bg-petronas/90 transition">👁️</a>
                                
                                {{-- Tombol Edit mengarah ke route baru --}}
                                <a href="{{ route('materials.edit', $material->id) }}" class="inline-flex items-center justify-center w-8 h-8 rounded border border-petronas text-petronas hover:bg-petronas/10 transition">✏️</a>
                                
                                <form action="{{ route('materials.destroy', $material->id) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="openDeleteModal(this)" class="inline-flex w-8 h-8 items-center justify-center border border-red-500 text-red-500 rounded hover:bg-red-500 hover:text-blackBase transition">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-8 italic bg-carbon/20">No materials available</td>
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