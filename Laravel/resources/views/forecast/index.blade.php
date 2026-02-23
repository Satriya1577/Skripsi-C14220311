<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forecasting | Production Planning System</title>
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
                Forecasting
            </li>
        </ol>
    </nav>

    <header>
        <p class="text-xs uppercase tracking-widest text-muted">
            Predictive Analytics
        </p>
        <h1 class="text-3xl font-extrabold text-petronas">
            Sales Forecasting
        </h1>
        <p class="text-sm text-muted mt-1">
            Pilih produk untuk melihat prediksi penjualan bulan depan menggunakan metode SARIMA.
        </p>
    </header>

    <div class="flex justify-end">
        <div class="relative w-full md:w-64">
            <input type="text" placeholder="Search Product..." 
                   class="w-full bg-carbonSoft border border-carbon rounded-lg pl-4 pr-10 py-2 text-sm text-silver focus:outline-none focus:border-petronas transition">
            <svg class="w-4 h-4 absolute right-3 top-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
    </div>

    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon shadow-lg shadow-black/50">
        <h2 class="text-lg font-bold text-petronas mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Product Forecast List
        </h2>

        <div class="overflow-x-auto rounded-lg border border-carbon">
            <table class="w-full text-sm">
                <thead class="bg-carbon">
                    <tr>
                        <th class="px-4 py-3 text-left text-muted font-medium uppercase text-xs tracking-wider">Product Code</th>
                        <th class="px-4 py-3 text-left text-muted font-medium uppercase text-xs tracking-wider">Product Name</th>
                        <th class="px-4 py-3 text-center text-muted font-medium uppercase text-xs tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon/50 bg-carbonSoft">
                    @foreach ($products as $product)
                        <tr class="hover:bg-carbon transition-colors group">
                            <td class="px-4 py-3 font-mono text-petronas font-bold">
                                {{ $product->code }}
                            </td>
                            
                            <td class="px-4 py-3 text-silver font-medium">
                                {{ $product->name }}
                            </td>

                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('forecast.show', $product->id) }}" 
                                   class="inline-flex items-center gap-2 px-4 py-1.5 rounded-lg 
                                          bg-petronas text-blackBase text-xs font-bold uppercase tracking-wide
                                          hover:bg-petronas/90 hover:scale-105 transition-all shadow-lg shadow-petronas/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                    View Forecast
                                </a>
                            </td>
                        </tr>
                    @endforeach

                    @if ($products->count() === 0)
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-muted italic">
                                Belum ada data produk. Silakan import atau tambah produk terlebih dahulu.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="mt-6 flex justify-between items-center text-sm text-muted border-t border-carbon pt-4">
                <div>
                    Showing <span class="font-bold text-silver">{{ $products->firstItem() }}</span> 
                    to <span class="font-bold text-silver">{{ $products->lastItem() }}</span> 
                    of <span class="font-bold text-silver">{{ $products->total() }}</span> products
                </div>
                <div>
                    {{ $products->links('pagination::tailwind') }}
                </div>
            </div>
        @endif
    </section>

</main>

</body>
</html>