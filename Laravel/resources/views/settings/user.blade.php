<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Management | Production Planning System</title>
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
            danger: '#EF4444',
            warning: '#F59E0B'
          }
        }
      }
    }
  </script>
  {{-- Bootstrap Icons --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>

<body class="bg-blackBase text-silver min-h-screen">

<main class="max-w-7xl mx-auto px-6 py-6 space-y-8">

  {{-- Breadcrumb --}}
  <nav aria-label="breadcrumb" class="text-xs text-muted">
    <ol class="flex items-center space-x-2">
      <li><a href="{{ route('home.index') }}" class="hover:text-blue-600 transition-colors">Home</a></li>
      <li class="opacity-40">/</li>
      <li>Settings</li>
      <li class="opacity-40">/</li>
      <li class="text-slate-800 font-semibold">User Management</li>
    </ol>
  </nav>

  <x-alert-messages />

  <header>
    <p class="text-xs uppercase tracking-widest text-muted">System Administration</p>
    <h1 class="text-3xl font-extrabold text-slate-800">User Management</h1>
    <p class="text-sm text-muted mt-1">Kelola akses pengguna, peran (role), dan keamanan akun.</p>
  </header>

  {{-- Form Section: Add/Edit User --}}
  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-lg font-bold text-slate-800" id="formTitle">Add New User</h2>
    </div>

    <form action="{{ route('settings.storeUser') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6"> 
      @csrf
      {{-- Hidden ID for Edit Mode --}}
      <input id="user_id" type="hidden" name="user_id">

      {{-- Name --}}
      <div>
        <label class="text-xs text-muted uppercase tracking-wide">Full Name</label>
        <input id="name" type="text" name="name" required
          value="{{ old('name') }}"
          class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas focus:ring-1 focus:ring-petronas transition placeholder-muted/50"
          placeholder="e.g. John Doe">
        @error('name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
      </div>

      {{-- Email --}}
      <div>
        <label class="text-xs text-muted uppercase tracking-wide">Email Address</label>
        <input id="email" type="email" name="email" required
          value="{{ old('email') }}"
          class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas focus:ring-1 focus:ring-petronas transition placeholder-muted/50"
          placeholder="name@company.com">
        @error('email') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
      </div>

      {{-- Role Selection --}}
      <div>
        <label class="text-xs text-muted uppercase tracking-wide">Role / Division</label>
        <div class="relative mt-1">
          <select id="role" name="role" required
            class="w-full px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas focus:ring-1 focus:ring-petronas transition appearance-none cursor-pointer">
            <option value="none" class="text-muted">-- Select Role --</option>
            <option value="admin">Admin (Full Access)</option>
            <option value="sales">Sales</option>
            <option value="purchase">Purchase (Procurement)</option>
            <option value="inventory">Inventory / Warehouse</option>
            <option value="production">Production</option>
            <option value="accounting">Accounting</option>
          </select>
          <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-800">
            <i class="bi bi-chevron-down text-xs"></i>
          </div>
        </div>
        @error('role') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
      </div>

      {{-- Password --}}
      <div>
        <label class="text-xs text-muted uppercase tracking-wide flex justify-between">
          Password 
          <span id="passwordHint" class="text-[10px] text-slate-800 hidden italic">* Leave blank to keep current</span>
        </label>
        <input id="password" type="password" name="password" required
          class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver focus:outline-none focus:border-petronas focus:ring-1 focus:ring-petronas transition placeholder-muted/50"
          placeholder="••••••••">
        @error('password') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
      </div>

      {{-- Action Buttons --}}
      <div class="md:col-span-2 flex justify-end gap-3 pt-2 border-t border-carbon mt-2">
        <button type="button" id="cancelBtn" onclick="resetForm()"
          class="hidden px-6 py-2 rounded-lg border border-muted text-slate-800 hover:bg-carbon transition">
          Cancel
        </button>
        
        <button type="submit" id="submitBtn"
          class="bg-petronas text-blackBase font-bold px-6 py-2 rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20">
          <i class="bi bi-person-plus-fill mr-2"></i> Save User
        </button>
      </div>
    </form>
  </section>

  {{-- User List --}}
  <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-lg font-bold text-slate-800">Registered Users</h2>
      
      {{-- Search Bar Optional --}}
      <div class="relative">
        <input type="text" placeholder="Search users..." 
          class="bg-carbon border border-carbonSoft text-xs rounded-full px-4 py-1.5 text-silver focus:outline-none focus:border-petronas w-64 placeholder-muted">
        <i class="bi bi-search absolute right-3 top-1.5 text-muted text-xs"></i>
      </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-carbon">
      <table class="w-full text-sm">
        <thead class="bg-carbon">
          <tr>
            <th class="px-4 py-3 text-left text-black text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">User Info</th>
            <th class="px-4 py-3 text-center text-black text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Role</th>
            <th class="px-4 py-3 text-center text-black text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Joined At</th>
            <th class="px-4 py-3 text-center text-black text-xs uppercase tracking-wide font-bold border-b border-carbonSoft">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-carbon/50">
          @foreach ($users as $user)
            <tr class="hover:bg-carbon transition-colors group">
              {{-- User Info --}}
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-blue-200 text-blue-800 flex items-center justify-center font-bold text-xs border border-petronas/30">
                    {{ substr($user->name, 0, 1) }}
                  </div>
                  <div>
                    <div class="font-bold text-silver">{{ $user->name }}</div>
                    <div class="text-xs text-muted ">{{ $user->email }}</div>
                  </div>
                </div>
              </td>

              {{-- Role Badge --}}
              <td class="px-4 py-3 text-center">
                @php
                  $roleColor = match($user->role) {
                    'admin'      => 'bg-blue-200 text-blue-800 border-blue-400',
                    'sales'      => 'bg-yellow-200 text-yellow-800 border-yellow-400',
                    'purchase'   => 'bg-green-200 text-green-800 border-green-400',
                    'inventory'  => 'bg-purple-200 text-purple-800 border-purple-400',
                    'production' => 'bg-indigo-200 text-indigo-800 border-indigo-400',
                    'accounting' => 'bg-pink-200 text-pink-800 border-pink-400',
                    'none'       => 'bg-carbon text-slate-800 border-carbon',
                    default      => 'bg-gray-200 text-gray-800 border-gray-400',
                  };
                @endphp
                <span class="px-2.5 py-1 rounded text-[10px] font-bold uppercase border {{ $roleColor }}">
                  {{ $user->role }}
                </span>
              </td>

              {{-- Joined At --}}
              <td class="px-4 py-3 text-center text-muted text-xs">
                {{ $user->created_at->format('d M Y') }}
              </td>

              {{-- Actions --}}
              <td class="px-4 py-3 text-center">
                <div class="flex justify-center gap-2">
                  <button type="button" onclick='editUser(@json($user))' 
                    class="inline-flex items-center justify-center w-8 h-8 rounded bg-yellow-500 text-white hover:bg-yellow-600 transition"
                    title="Edit User">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                  </button>
                  
                  {{-- Prevent deleting self if needed, handled in backend usually --}}
                  <form action="{{ route('settings.destroyUser', $user->id) }}" method="POST" class="inline">
                    @csrf @method('DELETE')
                    <button type="button" onclick="openDeleteModal(this)" 
                      class="inline-flex items-center justify-center w-8 h-8 rounded bg-danger text-white hover:bg-red-600 transition"
                      title="Delete User">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                      </svg>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach

          @if ($users->isEmpty())
            <tr><td colspan="4" class="px-4 py-8 text-center text-muted italic">No users found.</td></tr>
          @endif
        </tbody>
      </table>
    </div>
    
    {{-- Pagination --}}
    @if ($users instanceof \Illuminate\Pagination\LengthAwarePaginator && $users->hasPages())
      <div class="mt-4 border-t border-carbon pt-4">
        {{ $users->links('pagination::tailwind') }}
      </div>
    @endif
  </section>

</main>

{{-- Delete Modal (Sama persis dengan Product) --}}
<div id="deleteModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity opacity-0">
  <div class="bg-carbonSoft rounded-xl p-6 w-full max-w-md border border-danger shadow-2xl transform scale-95 transition-transform duration-200" id="modalContent">
    <div class="flex items-center gap-3 mb-4">
      <div class="w-10 h-10 rounded-full bg-danger/20 flex items-center justify-center text-danger border border-danger/30">
        <i class="bi bi-exclamation-triangle-fill text-lg"></i>
      </div>
      <h3 class="text-lg font-bold text-slate-800">Delete User?</h3>
    </div>
    <p class="text-sm text-muted mb-6 pl-13">
      Are you sure you want to delete this user? They will lose access to the system immediately. 
      <span class="text-danger font-semibold block mt-1">This action cannot be undone.</span>
    </p>
    <div class="flex justify-end gap-3">
      <button onclick="closeDeleteModal()" class="px-5 py-2 rounded-lg border border-muted/50 text-silver hover:bg-carbon transition text-sm font-medium">Cancel</button>
      <button onclick="confirmDelete()" class="px-5 py-2 rounded-lg bg-danger text-blackBase font-bold hover:bg-red-600 transition text-sm shadow-lg shadow-danger/20">Delete Account</button>
    </div>
  </div>
</div>

<script>
  // --- User Form Logic ---

  function editUser(user) {
    // 1. Populate Hidden ID
    document.getElementById('user_id').value = user.id;

    // 2. Populate Standard Fields
    document.getElementById('name').value = user.name;
    document.getElementById('email').value = user.email;
    document.getElementById('role').value = user.role;

    // 3. Handle Password Field (Optional on Edit)
    const passInput = document.getElementById('password');
    passInput.required = false; // Tidak wajib saat edit
    passInput.value = ''; // Kosongkan
    passInput.placeholder = "(Unchanged)";
    document.getElementById('passwordHint').classList.remove('hidden');

    // 4. Update UI Elements
    document.getElementById('formTitle').innerText = 'Edit User: ' + user.name;
    document.getElementById('formTitle').classList.add('text-warning'); // Visual feedback
    document.getElementById('formTitle').classList.remove('text-slate-800');

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.innerHTML = '<i class="bi bi-save mr-2"></i> Update User';
    submitBtn.classList.remove('bg-petronas', 'text-blackBase'); // Ganti style tombol
    submitBtn.classList.add('bg-warning', 'text-blackBase');
    
    document.getElementById('cancelBtn').classList.remove('hidden');

    // 5. Scroll to top
    document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
  }

  function resetForm() {
    // 1. Reset Form Tags
    document.querySelector('form').reset();
    document.getElementById('user_id').value = '';

    // 2. Reset Password Logic (Required on Create)
    const passInput = document.getElementById('password');
    passInput.required = true;
    passInput.placeholder = "••••••••";
    document.getElementById('passwordHint').classList.add('hidden');

    // 3. Reset UI Elements
    document.getElementById('formTitle').innerText = 'Add New User';
    document.getElementById('formTitle').classList.remove('text-warning');
    document.getElementById('formTitle').classList.add('text-slate-800');

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.innerHTML = '<i class="bi bi-person-plus-fill mr-2"></i> Save User';
    submitBtn.classList.remove('bg-warning');
    submitBtn.classList.add('bg-petronas');

    document.getElementById('cancelBtn').classList.add('hidden');
  }

  // --- Modal Logic (Sama dengan Product) ---
  let deleteForm = null;

  function openDeleteModal(button) {
    deleteForm = button.closest('form');
    const modal = document.getElementById('deleteModal');
    const content = document.getElementById('modalContent');
    
    modal.classList.remove('hidden');
    // Simple animation
    setTimeout(() => {
      modal.classList.remove('opacity-0');
      content.classList.remove('scale-95');
      content.classList.add('scale-100');
    }, 10);
    modal.classList.add('flex');
  }

  function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    const content = document.getElementById('modalContent');

    modal.classList.add('opacity-0');
    content.classList.remove('scale-100');
    content.classList.add('scale-95');

    setTimeout(() => {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
      deleteForm = null;
    }, 200);
  }

  function confirmDelete() {
    if (deleteForm) deleteForm.submit();
  }
</script>

</body>
</html>