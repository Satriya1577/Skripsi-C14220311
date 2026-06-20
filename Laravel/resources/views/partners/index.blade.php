<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Partners | Production Planning System</title>
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
      <li class="text-slate-800 font-semibold">Partners</li>
    </ol>
  </nav>

  <x-alert-messages />

  <header>
    <p class="text-xs uppercase tracking-widest text-muted">Master Data</p>
    <h1 class="text-3xl font-extrabold text-slate-800">Partner Management</h1>
    <p class="text-sm text-muted mt-1">Kelola data Distributor, Supplier, dan Mitra Bisnis</p>
  </header>

  {{-- Form Section --}}
  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
    <h2 class="text-lg font-bold text-slate-800" id="formHeader">Add New Partner</h2>
    <form action="{{ route('partners.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-6"> 
      @csrf
      {{-- Hidden ID untuk Mode Edit --}}
      <input id="partner_id" type="hidden" name="partner_id">

      {{-- Company Name (Span 2) --}}
      <div class="md:col-span-2">
        <label class="text-xs text-muted uppercase tracking-wide">Company Name</label>
        <input id="company_name" type="text" name="company_name" required
          class="w-full mt-1 px-4 py-3 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas placeholder-gray-600"
          placeholder="Contoh: PT. Sumber Makmur">
      </div>

      {{-- Contact Person --}}
      <div>
        <label class="text-xs text-muted uppercase tracking-wide">Contact Person (PIC)</label>
        <input id="person_name" type="text" name="person_name" 
          class="w-full mt-1 px-4 py-3 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas"
          placeholder="Nama Kontak">
      </div>

      {{-- Type (Dropdown) --}}
      <div>
        <label class="text-xs text-muted uppercase tracking-wide">Partner Type</label>
        <div class="relative mt-1">
          <select id="type" name="type" required
            class="w-full px-4 py-3 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas appearance-none cursor-pointer">
            <option value="distributor">Distributor</option>
            <option value="supplier">Supplier</option>
            {{-- <option value="both">Both (Dist & Supp)</option> --}}
          </select>
          <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-800">
            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
          </div>
        </div>
      </div>

      {{-- Phone --}}
      <div class="md:col-span-2">
        <label class="text-xs text-muted uppercase tracking-wide">Phone Number</label>
        <input id="phone" type="text" name="phone" 
          class="w-full mt-1 px-4 py-3 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas"
          placeholder="081xxx">
      </div>

      {{-- Email --}}
      <div class="md:col-span-2">
        <label class="text-xs text-muted uppercase tracking-wide">Email Address</label>
        <input id="email" type="email" name="email" 
          class="w-full mt-1 px-4 py-3 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas"
          placeholder="email@company.com">
      </div>

      {{-- Address (Full Width) --}}
      <div class="md:col-span-4">
        <label class="text-xs text-muted uppercase tracking-wide">Full Address</label>
        <textarea id="address" name="address" rows="2"
          class="w-full mt-1 px-4 py-3 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas"
          placeholder="Alamat lengkap perusahaan..."></textarea>
      </div>

      {{-- Action Buttons --}}
      <div class="md:col-span-4 flex flex-wrap justify-end gap-3 pt-2"> 
        <button type="button" id="cancelBtn" onclick="resetForm()"
          class="hidden px-6 py-2 rounded-lg border border-muted text-slate-800 hover:bg-carbon transition">
          Cancel
        </button>
        
        <button type="submit" id="submitBtn"
          class="bg-petronas text-blackBase font-bold px-6 py-2 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20">
          Save Partner
        </button>
      </div>
    </form>
  </section>

  {{-- List Section --}}
  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
    <h2 class="text-lg font-bold text-slate-800 mb-4">Partner List</h2>
    
    {{-- FRONTEND TABS FILTER --}}
    <div class="flex space-x-6 border-b border-carbon/50 mb-6 overflow-x-auto" id="frontendTabs">
      <button type="button" onclick="filterTable('all', this)" class="tab-btn pb-3 text-sm font-bold border-b-2 whitespace-nowrap transition-colors border-petronas text-petronas">
        All Partners
      </button>
      <button type="button" onclick="filterTable('supplier', this)" class="tab-btn pb-3 text-sm font-bold border-b-2 whitespace-nowrap transition-colors border-transparent text-muted hover:text-silver">
        Supplier
      </button>
      <button type="button" onclick="filterTable('distributor', this)" class="tab-btn pb-3 text-sm font-bold border-b-2 whitespace-nowrap transition-colors border-transparent text-muted hover:text-silver">
        Distributor
      </button>
    </div>

    <div class="overflow-x-auto rounded-lg border border-carbon">
      <table class="w-full text-sm">
        <thead class="bg-carbon">
          <tr>
            <th class="px-3 py-3 text-left text-black text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Company</th>
            <th class="px-3 py-3 text-left text-black text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Contact Person</th>
            <th class="px-3 py-3 text-center text-black text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Type</th> 
            <th class="px-3 py-3 text-left text-black text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Contact Info</th> 
            <th class="px-3 py-3 text-left text-black text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Address</th>
            <th class="px-3 py-3 text-center text-black text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Actions</th>
          </tr>
        </thead>
        <tbody id="tableBody">
          @forelse ($partners as $partner)
            {{-- Tambahkan filterable-row dan data-status --}}
            <tr class="border-b border-carbon hover:bg-carbon transition-colors group filterable-row" data-status="{{ strtolower($partner->type) }}">
              {{-- Company --}}
              <td class="px-3 py-3 text-left font-semibold text-silver whitespace-nowrap">
                {{ $partner->company_name }}
              </td>
              
              {{-- PIC --}}
              <td class="px-3 py-3 text-left text-muted whitespace-nowrap">
                {{ $partner->person_name ?? '-' }}
              </td>

              {{-- Type Badge --}}
              <td class="px-3 py-3 text-center whitespace-nowrap">
                @if($partner->type === 'distributor')
                  <span class="px-2 py-1 rounded bg-blue-100 text-blue-700 text-xs font-bold border border-petronas/20">Distributor</span>
                @elseif($partner->type === 'supplier')
                  <span class="px-2 py-1 rounded bg-yellow-500/10 text-yellow-500 text-xs font-bold border border-yellow-500/20">Supplier</span>
                @else
                  <span class="px-2 py-1 rounded bg-purple-500/10 text-purple-400 text-xs font-bold border border-purple-500/20">Both</span>
                @endif
              </td>

              {{-- Contact Info (Phone/Email) --}}
              <td class="px-3 py-3 text-left whitespace-nowrap">
                <div class="flex flex-col gap-1">
                  @if($partner->phone)
                    <div class="text-xs text-silver flex items-center gap-1">
                      <span class="opacity-50">📞</span> {{ $partner->phone }}
                    </div>
                  @endif
                  @if($partner->email)
                    <div class="text-xs text-slate-800 flex items-center gap-1">
                      <span class="opacity-50">✉️</span> {{ $partner->email }}
                    </div>
                  @endif
                  @if(!$partner->phone && !$partner->email)
                    <span class="text-muted text-xs">-</span>
                  @endif
                </div>
              </td>

              {{-- Address --}}
              <td class="px-3 py-3 text-left text-muted text-xs max-w-[200px] truncate" title="{{ $partner->address }}">
                {{ $partner->address ?? '-' }}
              </td>

              {{-- Actions --}}
              <td class="px-3 py-3 text-center space-x-2 whitespace-nowrap">
                {{-- Edit Icon --}}
                <button type="button" onclick='editPartner(@json($partner))' 
                  class="inline-flex items-center justify-center w-8 h-8 rounded bg-yellow-500 text-white hover:bg-yellow-600 transition" title="Edit">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                  </svg>
                </button>
                
                {{-- Delete Icon --}}
                <form action="{{ route('partners.destroy', $partner->id) }}" method="POST" class="inline">
                  @csrf @method('DELETE')
                  <button type="button" onclick="openDeleteModal(this)" 
                    class="inline-flex items-center justify-center w-8 h-8 rounded bg-danger text-white hover:bg-red-600 transition" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                  </button>
                </form>
              </td>
            </tr>
          @empty
            {{-- Sengaja dikosongkan agar ditangani table row fallback --}}
          @endforelse
          
          {{-- Baris Empty State Filter --}}
          <tr id="emptyRow" style="{{ count($partners) === 0 ? '' : 'display: none;' }}">
            <td colspan="6" class="px-3 py-8 text-center text-muted">
              <div class="flex flex-col items-center justify-center gap-2">
                <span class="text-2xl">📭</span>
                <span>No partners found.</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    {{-- Pagination --}}
    @if ($partners->hasPages())
      <div class="mt-4">
        {{ $partners->links('pagination::tailwind') }}
      </div>
    @endif
  </section>
</main>

{{-- Delete Modal --}}
<div id="deleteModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50">
  <div class="bg-carbonSoft rounded-xl p-6 w-full max-w-md border border-danger shadow-2xl">
    <h3 class="text-lg font-bold text-danger mb-2">Confirm Deletion</h3>
    <p class="text-sm text-muted mb-6">Are you sure you want to delete this partner? <br><span class="text-red-400 font-semibold">Related transactions might be affected.</span></p>
    <div class="flex justify-end gap-3">
      <button onclick="closeDeleteModal()" class="px-5 py-2 rounded-lg border border-muted text-slate-800 hover:bg-carbon transition">Cancel</button>
      <button onclick="confirmDelete()" class="px-5 py-2 rounded-lg bg-danger text-blackBase font-bold hover:bg-red-600 transition">Delete</button>
    </div>
  </div>
</div>

<script>
  /**
   * Logic Filter Frontend
   */
  function filterTable(status, btn) {
    // 1. Reset gaya semua tombol tab
    const tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach(tab => {
      tab.classList.remove('border-petronas', 'text-petronas');
      tab.classList.add('border-transparent', 'text-muted');
    });

    // 2. Terapkan gaya ke tombol yang aktif
    btn.classList.remove('border-transparent', 'text-muted');
    btn.classList.add('border-petronas', 'text-petronas');

    // 3. Filter Baris Tabel
    const rows = document.querySelectorAll('.filterable-row');
    let visibleCount = 0;

    rows.forEach(row => {
      const rowType = row.dataset.status; // Berisi: 'distributor', 'supplier', atau 'both'
      
      // Tampilkan jika:
      // Tab 'all' dipilih, ATAU tipenya cocok persis, ATAU tipenya 'both'
      if (status === 'all' || rowType === status || rowType === 'both') {
        row.style.display = ''; 
        visibleCount++;
      } else {
        row.style.display = 'none'; 
      }
    });

    // 4. Handle Empty State
    const emptyRow = document.getElementById('emptyRow');
    if (emptyRow) {
      if (visibleCount === 0) {
        emptyRow.style.display = '';
      } else {
        emptyRow.style.display = 'none';
      }
    }
  }

  /**
   * Logic Edit Partner
   * Mengisi form dengan data dari JSON row
   */
  function editPartner(partner) {
    // 1. Isi Hidden ID
    document.getElementById('partner_id').value = partner.id;

    // 2. Isi Field-Field
    document.getElementById('company_name').value = partner.company_name;
    document.getElementById('person_name').value = partner.person_name || '';
    document.getElementById('phone').value = partner.phone || '';
    document.getElementById('email').value = partner.email || '';
    document.getElementById('address').value = partner.address || '';
    
    // 3. Set Dropdown
    const typeSelect = document.getElementById('type');
    typeSelect.value = partner.type;

    // 4. Ubah Tampilan Tombol
    document.getElementById('formHeader').innerText = 'Edit Partner';
    document.getElementById('submitBtn').innerText = 'Update Partner';
    document.getElementById('cancelBtn').classList.remove('hidden');

    // 5. Scroll ke atas
    document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
  }

  /**
   * Logic Reset Form
   * Mengembalikan form ke mode "Add New"
   */
  function resetForm() {
    // 1. Reset Form Native
    document.querySelector('form').reset();
    
    // 2. Kosongkan Hidden ID
    document.getElementById('partner_id').value = '';

    // 3. Kembalikan Tampilan Tombol
    document.getElementById('formHeader').innerText = 'Add New Partner';
    document.getElementById('submitBtn').innerText = 'Save Partner';
    document.getElementById('cancelBtn').classList.add('hidden');
    
    // 4. Reset Dropdown ke Default
    document.getElementById('type').value = 'distributor';
  }

  // --- Modal Logic ---
  let deleteForm = null;

  function openDeleteModal(button) {
    deleteForm = button.closest('form');
    const modal = document.getElementById('deleteModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }

  function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    deleteForm = null;
  }

  function confirmDelete() {
    if (deleteForm) deleteForm.submit();
  }
</script>

</body>
</html>