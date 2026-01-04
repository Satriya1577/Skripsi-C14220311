<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings | Production Planning System</title>
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

    <nav aria-label="breadcrumb" class="text-xs text-muted">
        <ol class="flex items-center space-x-2">
            <li>
                <a href="{{ route('home.index') }}" class="hover:text-petronas transition-colors">
                    Home
                </a>
            </li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold" aria-current="page">
                Settings
            </li>
        </ol>
    </nav>

    @if (session('success'))
        <div class="bg-carbonSoft border border-petronas rounded-xl p-4 flex justify-between items-center shadow-[0_0_15px_rgba(0,161,155,0.1)]">
            <p class="text-sm text-petronas font-semibold flex items-center gap-2">
                <span>✓</span> {{ session('success') }}
            </p>
            <button onclick="this.parentElement.remove()" class="text-muted hover:text-petronas transition">✕</button>
        </div>
    @endif

    <header>
        <p class="text-xs uppercase tracking-widest text-muted">
            System Configuration
        </p>
        <h1 class="text-3xl font-extrabold text-petronas">
            Settings
        </h1>
        <p class="text-sm text-muted mt-1">
            Pusat kendali untuk akun, data master, dan konfigurasi sistem.
        </p>
    </header>

    <section class="bg-carbonSoft rounded-2xl p-6 border border-carbon relative overflow-hidden">
        
        <div class="absolute top-0 right-0 w-64 h-64 bg-petronas/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>

        <div class="flex flex-col md:flex-row items-center justify-between gap-6 relative z-10">
            
            <div class="flex items-center gap-5 w-full">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-carbon to-blackBase border-2 border-petronas flex items-center justify-center shadow-lg shadow-petronas/20 shrink-0">
                    <span class="text-2xl font-bold text-petronas">
                        {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                    </span>
                </div>

                <div class="flex-1">
                    <h2 class="text-xl font-bold text-silver">{{ Auth::user()->name ?? 'Guest User' }}</h2>
                    <p class="text-sm text-muted">{{ Auth::user()->email ?? 'No Email' }}</p>
                    <div class="mt-2 flex gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-petronas/10 text-petronas border border-petronas/20 uppercase tracking-wide">
                            Administrator
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-carbon text-muted border border-carbon uppercase tracking-wide">
                            Active
                        </span>
                    </div>
                </div>
            </div>

            <form action="{{ route('logout') }}" method="POST" class="w-full md:w-auto">
                @csrf
                <button type="submit" 
                        class="w-full md:w-auto flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-red-500/10 border border-red-500/50 text-red-400 font-bold hover:bg-red-500 hover:text-white transition-all duration-300 group">
                    
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 group-hover:-translate-x-1 transition-transform">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                    </svg>
                    Logout System
                </button>
            </form>
        </div>
    </section>

    <div class="border-t border-carbon my-2"></div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

        <section class="space-y-4">
            <div class="flex items-center gap-3">
                <span class="w-1 h-6 bg-silver rounded-full"></span>
                <h2 class="text-xl font-bold text-silver">Data Management</h2>
            </div>

            <a href="{{ route('settings.import') }}" 
               class="group block bg-carbonSoft rounded-xl p-6 border border-carbon hover:border-petronas transition-all duration-300 hover:shadow-[0_0_20px_rgba(0,161,155,0.1)] relative overflow-hidden h-40">
                
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-silver/5 rounded-full blur-2xl group-hover:bg-petronas/10 transition"></div>

                <div class="flex flex-col h-full justify-between relative z-10">
                    <div class="flex items-start justify-between">
                        <div class="p-3 bg-carbon rounded-lg text-silver group-hover:text-petronas border border-carbon transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                            </svg>
                        </div>
                        
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" 
                             class="w-6 h-6 text-muted group-hover:text-petronas group-hover:translate-x-2 transition transform">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                        </svg>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-silver group-hover:text-petronas transition">Import Data Excel</h3>
                        <p class="text-xs text-muted mt-1">
                            Upload massal data Produk (Barang Jadi) dan Resep (BOM).
                        </p>
                    </div>
                </div>
            </a>
        </section>

        <section class="space-y-4">
            <div class="flex items-center gap-3">
                <span class="w-1 h-6 bg-petronas rounded-full"></span>
                <h2 class="text-xl font-bold text-petronas">Model Configuration</h2>
            </div>

            <a href="{{ route('settings.forecast') }}" 
               class="group block bg-carbonSoft rounded-xl p-6 border border-carbon hover:border-petronas transition-all duration-300 hover:shadow-[0_0_20px_rgba(0,161,155,0.1)] relative overflow-hidden h-40">
                
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-petronas/5 rounded-full blur-2xl group-hover:bg-petronas/15 transition"></div>

                <div class="flex flex-col h-full justify-between relative z-10">
                    <div class="flex items-start justify-between">
                        <div class="p-3 bg-carbon rounded-lg text-petronas border border-carbon transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                            </svg>                              
                        </div>
                        
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" 
                             class="w-6 h-6 text-muted group-hover:text-petronas group-hover:translate-x-2 transition transform">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                        </svg>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-silver group-hover:text-petronas transition">SARIMA Config</h3>
                        <p class="text-xs text-muted mt-1">
                            Atur parameter (p,d,q)(P,D,Q)s dan monitoring akurasi (RMSE/MAPE).
                        </p>
                    </div>
                </div>
            </a>
        </section>

    </div>

</main>

</body>
</html>