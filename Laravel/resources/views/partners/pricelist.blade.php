<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Pricelist - {{ $partner->company_name }} | Production Planning System</title>
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

  {{-- Breadcrumb --}}
  <nav aria-label="breadcrumb" class="text-xs text-muted">
    <ol class="flex items-center space-x-2">
      <li><a href="{{ route('home.index') }}" class="hover:text-blue-600 transition-colors">Home</a></li>
      <li class="opacity-40">/</li>
      <li><a href="{{ route('partners.index') }}" class="hover:text-blue-600 transition-colors">Partners</a></li>
      <li class="opacity-40">/</li>
      <li class="text-slate-800 font-semibold">Pricelist</li>
    </ol>
  </nav>

  <x-alert-messages />

  <header class="flex justify-between items-end">
    <div>
      <p class="text-xs uppercase tracking-widest text-muted">Supplier Pricelist</p>
      <h1 class="text-3xl font-extrabold text-slate-800">{{ $partner->company_name }}</h1>
      <p class="text-sm text-muted mt-1">PIC: {{ $partner->person_name ?? '-' }} | {{ $partner->phone ?? 'No Phone' }}</p>
    </div>
  </header>

  {{-- Form Tambah/Edit Pricelist --}}
  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon" id="formSection">
    <h2 class="text-lg font-bold text-slate-800 mb-4" id="formHeader">Add New Material Price</h2>
    
    {{-- UBAH: Gunakan grid layout persis seperti di partner.index --}}
    <form action="{{ route('partners.storePricelist', $partner->id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-6">
      @csrf
      
      {{-- Dropdown Material (Span 2) --}}
      <div class="md:col-span-2">
        <label class="text-xs text-muted font-bold uppercase tracking-wide block mb-1">Pilih Material</label>
        <div class="relative">
          <select name="material_id" id="materialSelect" required
            class="w-full appearance-none px-4 py-3 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas cursor-pointer transition">
            <option value="" disabled selected>-- Pilih Material --</option>
            @foreach($materials as $material)
              <option value="{{ $material->id }}">
                {{ $material->code }} - {{ $material->name }} (Satuan Beli: {{ $material->purchase_unit }})
              </option>
            @endforeach
          </select>
          <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-800" id="dropdownIcon">
            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
          </div>
        </div>
      </div>
      
      {{-- Input Harga --}}
      <div>
        <label class="text-xs text-muted font-bold uppercase tracking-wide block mb-1">Harga Beli Per Satuan</label>
        <div class="relative">
          <span class="absolute left-3 top-3 text-muted text-sm font-bold">Rp</span>
          <input type="number" name="price" id="priceInput" step="0.01" min="0" required
            class="w-full pl-10 pr-4 py-3 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas font-bold transition"
            placeholder="0">
        </div>
      </div>

      {{-- Action Buttons ditaruh di bawah dengan justify-end --}}
      <div class="md:col-span-3 flex flex-wrap justify-end gap-3 pt-2">
        <button type="button" id="cancelBtn" onclick="cancelEdit()"
          class="hidden px-6 py-2 rounded-lg border border-muted text-slate-800 hover:bg-carbon transition">
          Cancel
        </button>
        
        <button type="submit" id="submitBtn"
          class="bg-petronas text-white font-bold px-6 py-2 rounded-lg hover:bg-blue-600 transition shadow-lg shadow-petronas/20">
          <span id="submitBtnText">Save Price</span>
        </button>
      </div>

    </form>
  </section>

  {{-- Tabel List Pricelist --}}
  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-bold text-slate-800">Material Pricelist</h2>
      <span class="text-xs bg-carbon px-3 py-1 rounded-full text-slate-800 font-bold border border-carbon">Total: {{ $pricelists->count() }} Materials</span>
    </div>

    <div class="overflow-x-auto rounded-lg border border-carbon">
      <table class="w-full text-sm">
        <thead class="bg-carbon">
          <tr>
            <th class="px-4 py-3 text-left text-black text-xs uppercase tracking-wide font-bold">Kode</th>
            <th class="px-4 py-3 text-left text-black text-xs uppercase tracking-wide font-bold">Nama Material</th>
            <th class="px-4 py-3 text-center text-black text-xs uppercase tracking-wide font-bold">Satuan Beli</th>
            <th class="px-4 py-3 text-right text-black text-xs uppercase tracking-wide font-bold">Harga Saat Ini</th>
            <th class="px-4 py-3 text-center text-black text-xs uppercase tracking-wide font-bold">Terakhir Diupdate</th>
            <th class="px-4 py-3 text-center text-black text-xs uppercase tracking-wide font-bold">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-carbon/50">
          @forelse ($pricelists as $list)
            <tr class="hover:bg-carbon transition-colors">
              <td class="px-4 py-3 text-left text-muted font-mono">{{ $list->material->code }}</td>
              <td class="px-4 py-3 text-left font-bold text-silver">{{ $list->material->name }}</td>
              <td class="px-4 py-3 text-center text-muted">{{ $list->material->purchase_unit }}</td>
              <td class="px-4 py-3 text-right font-bold text-slate-800">
                Rp {{ number_format($list->price, 2, ',', '.') }}
              </td>
              <td class="px-4 py-3 text-center text-xs text-muted">
                {{ $list->updated_at->diffForHumans() }}
              </td>
              <td class="px-4 py-3 text-center space-x-2 whitespace-nowrap">
                
                {{-- Edit Button --}}
                <button type="button" onclick="editPricelist('{{ $list->material_id }}', {{ $list->price }})" 
                  class="inline-flex items-center justify-center w-8 h-8 rounded bg-yellow-500 text-white hover:bg-yellow-600 transition" title="Edit Price">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                  </svg>
                </button>

                {{-- Delete Form --}}
                <form action="{{ route('partners.destroyPricelist', ['partner' => $partner->id, 'id' => $list->id]) }}" method="POST" class="inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit" onclick="return confirm('Hapus material ini dari pricelist supplier?')" 
                    class="inline-flex items-center justify-center w-8 h-8 rounded bg-danger text-white hover:bg-red-600 transition" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-4 py-8 text-center text-muted">
                <div class="flex flex-col items-center justify-center gap-2">
                  <span class="text-2xl">📋</span>
                  <span>Belum ada material di pricelist supplier ini.</span>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

</main>

<script>
  function editPricelist(materialId, currentPrice) {
    const select = document.getElementById('materialSelect');
    const priceInput = document.getElementById('priceInput');
    const submitBtnText = document.getElementById('submitBtnText');
    const cancelBtn = document.getElementById('cancelBtn');
    const formHeader = document.getElementById('formHeader');
    const dropdownIcon = document.getElementById('dropdownIcon');

    // Set Data
    select.value = materialId;
    priceInput.value = currentPrice;

    // Kunci Select agar tidak bisa diubah (Hanya bisa ganti harga)
    select.classList.add('pointer-events-none', 'bg-blackBase/50', 'text-muted');
    select.classList.remove('bg-carbon', 'text-silver');
    dropdownIcon.classList.add('hidden'); // Sembunyikan panah dropdown

    // Ubah UI Form
    formHeader.innerText = "Update Material Price";
    submitBtnText.innerText = "Update Price";
    cancelBtn.classList.remove('hidden');

    // Scroll otomatis ke form
    document.getElementById('formSection').scrollIntoView({ behavior: 'smooth' });
    
    // Focus otomatis ke input harga
    setTimeout(() => priceInput.focus(), 300);
  }

  function cancelEdit() {
    const select = document.getElementById('materialSelect');
    const priceInput = document.getElementById('priceInput');
    const submitBtnText = document.getElementById('submitBtnText');
    const cancelBtn = document.getElementById('cancelBtn');
    const formHeader = document.getElementById('formHeader');
    const dropdownIcon = document.getElementById('dropdownIcon');

    // Reset Data
    select.value = "";
    priceInput.value = "";

    // Buka kembali Select
    select.classList.remove('pointer-events-none', 'bg-blackBase/50', 'text-muted');
    select.classList.add('bg-carbon', 'text-silver');
    dropdownIcon.classList.remove('hidden');

    // Kembalikan UI Form seperti semula
    formHeader.innerText = "Add New Material Price";
    submitBtnText.innerText = "Save Price";
    cancelBtn.classList.add('hidden');
  }
</script>

</body>
</html>