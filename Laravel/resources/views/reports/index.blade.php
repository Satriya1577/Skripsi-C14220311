<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports | Production Planning System</title>
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
                        muted: '#9DA3A6'
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-blackBase text-silver min-h-screen">

<main class="max-w-7xl mx-auto px-6 py-6 space-y-8">

    {{-- BREADCRUMB --}}
    <nav aria-label="breadcrumb" class="text-xs text-muted">
        <ol class="flex items-center space-x-2">
            <li>
                <a href="{{ route('home.index') }}" class="hover:text-petronas transition-colors">
                    Home
                </a>
            </li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold" aria-current="page">
                Reports Center
            </li>
        </ol>
    </nav>

    {{-- HEADER --}}
    <header>
        <p class="text-xs uppercase tracking-widest text-muted">
            Analytics & History
        </p>
        <h1 class="text-3xl font-extrabold text-petronas">
            Reports
        </h1>
        <p class="text-sm text-muted mt-1">
            Pantau pergerakan stok dan performa keuangan perusahaan.
        </p>
    </header>

    <div class="border-t border-carbon"></div>

    {{-- REPORT GRID SECTION --}}
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        {{-- 1. PRODUCT STOCK CARD --}}
        <a href="{{ route('reports.product') }}" 
           class="group relative bg-carbonSoft rounded-2xl p-6 border border-carbon hover:border-petronas transition-all duration-300 hover:shadow-[0_0_20px_rgba(0,161,155,0.1)] hover:-translate-y-1 overflow-hidden h-full flex flex-col">
            
            {{-- Background Glow Effect --}}
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-petronas/5 rounded-full blur-3xl group-hover:bg-petronas/15 transition duration-500"></div>

            <div class="relative z-10 flex flex-col h-full justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-carbon border border-carbonSoft flex items-center justify-center text-petronas mb-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <i class="bi bi-box-seam text-2xl"></i>
                    </div>
                    
                    <h3 class="text-lg font-bold text-silver group-hover:text-petronas transition-colors">
                        Product Stock Card
                    </h3>
                    <p class="text-xs text-muted mt-2 leading-relaxed">
                        Laporan mutasi stok barang jadi (Finish Good). Melacak riwayat produksi masuk dan penjualan keluar.
                    </p>
                </div>

                <div class="mt-6 flex items-center text-xs font-bold text-muted group-hover:text-petronas transition-colors">
                    <span>View Report</span>
                    <i class="bi bi-arrow-right ml-2 transition-transform group-hover:translate-x-1"></i>
                </div>
            </div>
        </a>

        {{-- 2. MATERIAL STOCK CARD --}}
        {{-- Ganti route('material_stock_card.index') sesuai route asli Anda --}}
        <a href="{{ route('reports.material') }}" 
           class="group relative bg-carbonSoft rounded-2xl p-6 border border-carbon hover:border-blue-500 transition-all duration-300 hover:shadow-[0_0_20px_rgba(59,130,246,0.1)] hover:-translate-y-1 overflow-hidden h-full flex flex-col">
            
            {{-- Background Glow Effect --}}
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl group-hover:bg-blue-500/15 transition duration-500"></div>

            <div class="relative z-10 flex flex-col h-full justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-carbon border border-carbonSoft flex items-center justify-center text-blue-400 mb-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <i class="bi bi-layers-half text-2xl"></i>
                    </div>
                    
                    <h3 class="text-lg font-bold text-silver group-hover:text-blue-400 transition-colors">
                        Material Stock Card
                    </h3>
                    <p class="text-xs text-muted mt-2 leading-relaxed">
                        Laporan mutasi bahan baku (Raw Material). Memonitor pembelian masuk dan pemakaian produksi.
                    </p>
                </div>

                <div class="mt-6 flex items-center text-xs font-bold text-muted group-hover:text-blue-400 transition-colors">
                    <span>View Report</span>
                    <i class="bi bi-arrow-right ml-2 transition-transform group-hover:translate-x-1"></i>
                </div>
            </div>
        </a>

        {{-- 3. INCOME STATEMENT (LABA RUGI) --}}
        {{-- Ganti route sesuai route asli Anda --}}
        <a href="#" 
           class="group relative bg-carbonSoft rounded-2xl p-6 border border-carbon hover:border-green-500 transition-all duration-300 hover:shadow-[0_0_20px_rgba(34,197,94,0.1)] hover:-translate-y-1 overflow-hidden h-full flex flex-col">
            
            {{-- Background Glow Effect --}}
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-green-500/5 rounded-full blur-3xl group-hover:bg-green-500/15 transition duration-500"></div>

            <div class="relative z-10 flex flex-col h-full justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-carbon border border-carbonSoft flex items-center justify-center text-green-400 mb-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <i class="bi bi-graph-up text-2xl"></i>
                    </div>
                    
                    <h3 class="text-lg font-bold text-silver group-hover:text-green-400 transition-colors">
                        Income Statement
                    </h3>
                    <p class="text-xs text-muted mt-2 leading-relaxed">
                        Laporan Laba Rugi sederhana. Menghitung selisih pendapatan penjualan dengan biaya HPP dan operasional.
                    </p>
                </div>

                <div class="mt-6 flex items-center text-xs font-bold text-muted group-hover:text-green-400 transition-colors">
                    <span>View Report</span>
                    <i class="bi bi-arrow-right ml-2 transition-transform group-hover:translate-x-1"></i>
                </div>
            </div>
        </a>

    </section>

</main>

{{-- Bootstrap Icons --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

</body>
</html>