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

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="text-xs text-muted">
        <ol class="flex items-center space-x-2">
            <li><a href="{{ route('home.index') }}" class="hover:text-petronas transition-colors">Home</a></li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold">Partners</li>
        </ol>
    </nav>

    <x-alert-messages />

    <header>
        <p class="text-xs uppercase tracking-widest text-muted">Master Data</p>
        <h1 class="text-3xl font-extrabold text-petronas">Partner Management</h1>
        <p class="text-sm text-muted mt-1">Kelola data Distributor, Supplier, dan Mitra Bisnis</p>
    </header>

    {{-- Form Section --}}
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
        <h2 class="text-lg font-bold text-petronas" id="formHeader">Add New Partner</h2>
        <form action="{{ route('partners.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-6"> 
            @csrf
            {{-- Hidden ID untuk Mode Edit --}}
            <input id="partner_id" type="hidden" name="partner_id">

            {{-- Company Name (Span 2) --}}
            <div class="md:col-span-2">
                <label class="text-xs text-muted uppercase tracking-wide">Company Name</label>
                <input id="company_name" type="text" name="company_name" required
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas placeholder-gray-600"
                    placeholder="Contoh: PT. Sumber Makmur">
            </div>

            {{-- Contact Person --}}
            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Contact Person (PIC)</label>
                <input id="person_name" type="text" name="person_name" 
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas"
                    placeholder="Nama Kontak">
            </div>

            {{-- Type (Dropdown) --}}
            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Partner Type</label>
                <div class="relative mt-1">
                    <select id="type" name="type" required
                        class="w-full px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas appearance-none cursor-pointer">
                        <option value="distributor">Distributor</option>
                        <option value="supplier">Supplier</option>
                        <option value="both">Both (Dist & Supp)</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-petronas">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>
            </div>

            {{-- Phone --}}
            <div class="md:col-span-2">
                <label class="text-xs text-muted uppercase tracking-wide">Phone Number</label>
                <input id="phone" type="text" name="phone" 
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas"
                    placeholder="081xxx">
            </div>

            {{-- Email --}}
            <div class="md:col-span-2">
                <label class="text-xs text-muted uppercase tracking-wide">Email Address</label>
                <input id="email" type="email" name="email" 
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas"
                    placeholder="email@company.com">
            </div>

            {{-- Address (Full Width) --}}
            <div class="md:col-span-4">
                <label class="text-xs text-muted uppercase tracking-wide">Full Address</label>
                <textarea id="address" name="address" rows="2"
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas"
                    placeholder="Alamat lengkap perusahaan..."></textarea>
            </div>

            {{-- Action Buttons --}}
            <div class="md:col-span-4 flex flex-wrap justify-end gap-3 pt-2"> 
                <button type="button" id="cancelBtn" onclick="resetForm()"
                    class="hidden px-6 py-2 rounded-lg border border-muted text-muted hover:bg-carbon transition">
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
    <section class="bg-carbonSoft rounded-xl p-6">
        <h2 class="text-lg font-bold text-petronas mb-4">Partner List</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-carbon">
                    <tr>
                        <th class="px-3 py-2 text-left text-muted">Company</th>
                        <th class="px-3 py-2 text-left text-muted">Contact Person</th>
                        <th class="px-3 py-2 text-center text-muted">Type</th> 
                        <th class="px-3 py-2 text-left text-muted">Contact Info</th> 
                        <th class="px-3 py-2 text-left text-muted">Address</th>
                        <th class="px-3 py-2 text-center text-muted">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($partners as $partner)
                        <tr class="border-b border-carbon hover:bg-carbon transition-colors group">
                            {{-- Company --}}
                            <td class="px-3 py-3 text-left font-semibold text-silver">
                                {{ $partner->company_name }}
                            </td>
                            
                            {{-- PIC --}}
                            <td class="px-3 py-3 text-left text-muted">
                                {{ $partner->person_name ?? '-' }}
                            </td>

                            {{-- Type Badge --}}
                            <td class="px-3 py-3 text-center">
                                @if($partner->type === 'distributor')
                                    <span class="px-2 py-1 rounded bg-petronas/10 text-petronas text-xs font-bold border border-petronas/20">Distributor</span>
                                @elseif($partner->type === 'supplier')
                                    <span class="px-2 py-1 rounded bg-yellow-500/10 text-yellow-500 text-xs font-bold border border-yellow-500/20">Supplier</span>
                                @else
                                    <span class="px-2 py-1 rounded bg-purple-500/10 text-purple-400 text-xs font-bold border border-purple-500/20">Both</span>
                                @endif
                            </td>

                            {{-- Contact Info (Phone/Email) --}}
                            <td class="px-3 py-3 text-left">
                                <div class="flex flex-col gap-1">
                                    @if($partner->phone)
                                        <div class="text-xs text-silver flex items-center gap-1">
                                            <span class="opacity-50">📞</span> {{ $partner->phone }}
                                        </div>
                                    @endif
                                    @if($partner->email)
                                        <div class="text-xs text-petronas flex items-center gap-1">
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
                            <td class="px-3 py-3 text-center space-x-2">
                                <button type="button" onclick='editPartner(@json($partner))' 
                                    class="inline-flex items-center justify-center w-8 h-8 rounded border border-petronas text-petronas hover:bg-petronas/10 transition">
                                    ✏️
                                </button>
                                <form action="{{ route('partners.destroy', $partner->id) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="openDeleteModal(this)" 
                                        class="inline-flex items-center justify-center w-8 h-8 rounded border border-danger text-danger hover:bg-danger hover:text-blackBase transition">
                                        🗑️
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    
                    @if ($partners->count() === 0)
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-muted">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="text-2xl">📭</span>
                                    <span>No partners data available.</span>
                                </div>
                            </td>
                        </tr>
                    @endif
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
            <button onclick="closeDeleteModal()" class="px-5 py-2 rounded-lg border border-muted text-muted hover:bg-carbon transition">Cancel</button>
            <button onclick="confirmDelete()" class="px-5 py-2 rounded-lg bg-danger text-blackBase font-bold hover:bg-red-600 transition">Delete</button>
        </div>
    </div>
</div>

<script>
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

    // --- Modal Logic (Sama seperti product.index) ---
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