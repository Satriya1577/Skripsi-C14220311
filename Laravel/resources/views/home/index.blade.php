<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home | Production Planning System</title>
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

    {{-- USER PROFILE DROPDOWN (FIXED POSITION) --}}
    {{-- Menggunakan 'fixed' agar selalu menempel di pojok kanan layar --}}
    <div class="fixed top-6 right-6 group z-50">
        
        {{-- Trigger Button --}}
        <button class="flex items-center gap-3 text-muted hover:text-silver transition-colors focus:outline-none bg-blackBase/50 backdrop-blur-sm p-2 rounded-xl border border-transparent hover:border-carbonSoft">
            
            {{-- Text Info (Nama & Role) --}}
            <div class="text-right hidden sm:block">
                <p class="text-sm font-bold text-silver leading-tight">{{ Auth::user()->name ?? 'Administrator' }}</p>
                <p class="text-[10px] text-muted uppercase tracking-wider leading-tight">{{ Auth::user()->role ?? 'User' }}</p>
            </div>
            
            {{-- Icon User --}}
            <div class="w-10 h-10 rounded-full bg-carbon border border-carbonSoft flex items-center justify-center text-petronas shadow-lg group-hover:border-petronas transition-all">
                <i class="bi bi-person-fill text-xl"></i>
            </div>

            {{-- Chevron Icon --}}
            <i class="bi bi-chevron-down text-xs transition-transform duration-300 group-hover:rotate-180 mr-1"></i>
        </button>

        {{-- Dropdown Menu --}}
        <div class="absolute right-0 mt-2 w-56 bg-carbonSoft border border-carbon rounded-xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 transform origin-top-right scale-95 group-hover:scale-100">
            <div class="p-2">
                {{-- User Email --}}
                <div class="px-4 py-3 border-b border-carbon mb-2">
                    <p class="text-[10px] text-muted uppercase tracking-wide">Signed in as</p>
                    <p class="text-xs font-bold text-white truncate">{{ Auth::user()->email ?? 'user@example.com' }}</p>
                </div>
                
                {{-- Settings (Optional shortcut) --}}
                <a href="{{ route('settings.index') }}" class="w-full text-left px-4 py-2.5 rounded-lg text-sm text-silver hover:bg-carbon hover:text-petronas transition-colors flex items-center gap-3 mb-1">
                    <i class="bi bi-sliders"></i> Settings
                </a>

                {{-- Logout Button --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2.5 rounded-lg text-sm text-silver hover:bg-carbon hover:text-danger transition-colors flex items-center gap-3 group/item">
                        <i class="bi bi-box-arrow-right text-muted group-hover/item:text-danger transition-colors"></i> 
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- KONTEN UTAMA --}}
    <main class="max-w-6xl mx-auto px-6 py-8 flex flex-col justify-between min-h-screen relative">

        <header class="text-center mb-10 pt-12 pb-4">
            <h1 class="text-3xl font-extrabold text-petronas">Production Planning System</h1>
            <p class="text-muted mt-2 text-sm">Integrated Inventory, Procurement, and Forecasting System</p>
        </header>

        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 flex-1 content-start">

            {{-- 1. Product --}}
            <a href="{{ route('products.index') }}"
            class="text-center p-6 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center
                    transform transition-all duration-300 hover:-translate-y-1 hover:border-petronas hover:shadow-lg hover:shadow-petronas/10 group">
                <div class="text-petronas group-hover:text-white transition-colors text-4xl mb-3">
                    <i class="bi bi-box-seam"></i>
                </div>
                <h5 class="text-lg font-bold text-silver mb-1">Product</h5>
                <p class="text-xs text-muted">Master Data & Resep</p>
            </a>

            {{-- 2. Material --}}
            <a href="{{ route('materials.index') }}"
            class="text-center p-6 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center
                    transform transition-all duration-300 hover:-translate-y-1 hover:border-petronas hover:shadow-lg hover:shadow-petronas/10 group">
                <div class="text-petronas group-hover:text-white transition-colors text-4xl mb-3">
                    <i class="bi bi-layers-fill"></i>
                </div>
                <h5 class="text-lg font-bold text-silver mb-1">Material</h5>
                <p class="text-xs text-muted">Bahan Baku & Stok</p>
            </a>

            {{-- 3. Partners --}}
            <a href="{{ route('partners.index') }}" 
            class="text-center p-6 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center
                    transform transition-all duration-300 hover:-translate-y-1 hover:border-petronas hover:shadow-lg hover:shadow-petronas/10 group">
                <div class="text-petronas group-hover:text-white transition-colors text-4xl mb-3">
                    <i class="bi bi-people-fill"></i>
                </div>
                <h5 class="text-lg font-bold text-silver mb-1">Partners</h5>
                <p class="text-xs text-muted">Distributor & Supplier</p>
            </a>

            {{-- 4. Sales --}}
            <a href="{{ route('sales.index') }}"
            class="text-center p-6 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center
                    transform transition-all duration-300 hover:-translate-y-1 hover:border-petronas hover:shadow-lg hover:shadow-petronas/10 group">
                <div class="text-petronas group-hover:text-white transition-colors text-4xl mb-3">
                    <i class="bi bi-cart-check-fill"></i>
                </div>
                <h5 class="text-lg font-bold text-silver mb-1">Sales (SO)</h5>
                <p class="text-xs text-muted">Order Penjualan</p>
            </a>

            {{-- 5. Purchase --}}
            <a href="{{ route('purchases.index') }}" 
            class="text-center p-6 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center
                    transform transition-all duration-300 hover:-translate-y-1 hover:border-petronas hover:shadow-lg hover:shadow-petronas/10 group">
                <div class="text-petronas group-hover:text-white transition-colors text-4xl mb-3">
                    <i class="bi bi-bag-check-fill"></i>
                </div>
                <h5 class="text-lg font-bold text-silver mb-1">Purchase (PO)</h5>
                <p class="text-xs text-muted">Order Pembelian Bahan</p>
            </a>

            {{-- 6. Production --}}
            <a href="#" 
            class="text-center p-6 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center
                    transform transition-all duration-300 hover:-translate-y-1 hover:border-petronas hover:shadow-lg hover:shadow-petronas/10 group">
                <div class="text-petronas group-hover:text-white transition-colors text-4xl mb-3">
                    <i class="bi bi-gear-wide-connected"></i>
                </div>
                <h5 class="text-lg font-bold text-silver mb-1">Production</h5>
                <p class="text-xs text-muted">Realisasi Produksi & Batch</p>
            </a>

            {{-- 7. Reports --}}
            <a href="{{ route('reports.index') }}" 
            class="text-center p-6 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center
                    transform transition-all duration-300 hover:-translate-y-1 hover:border-petronas hover:shadow-lg hover:shadow-petronas/10 group">
                <div class="text-petronas group-hover:text-white transition-colors text-4xl mb-3">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i>
                </div>
                <h5 class="text-lg font-bold text-silver mb-1">Reports</h5>
                <p class="text-xs text-muted">Kartu Stok & Laporan</p>
            </a>

            {{-- 8. Forecasting --}}
            <a href="{{ route('forecast.index') }}"
            class="text-center p-6 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center
                    transform transition-all duration-300 hover:-translate-y-1 hover:border-petronas hover:shadow-lg hover:shadow-petronas/10 group">
                <div class="text-petronas group-hover:text-white transition-colors text-4xl mb-3">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <h5 class="text-lg font-bold text-silver mb-1">Forecasting</h5>
                <p class="text-xs text-muted">Peramalan Permintaan & Production Plan</p>
            </a>

            {{-- 9. Settings --}}
            <a href="{{ route('settings.index') }}"
            class="text-center p-6 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center
                    transform transition-all duration-300 hover:-translate-y-1 hover:border-petronas hover:shadow-lg hover:shadow-petronas/10 group">
                <div class="text-petronas group-hover:text-white transition-colors text-4xl mb-3">
                    <i class="bi bi-sliders"></i>
                </div>
                <h5 class="text-lg font-bold text-silver mb-1">Settings</h5>
                <p class="text-xs text-muted">Konfigurasi Sistem</p>
            </a>

        </section>

        <footer class="text-center text-xs text-muted mt-8 mb-4">
            © 2025 Production Planning System – Academic Prototype
        </footer>

    </main>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

</body>
</html>