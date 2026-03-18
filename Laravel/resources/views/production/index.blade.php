<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Production Planning | Production Planning System</title>
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
                Production
            </li>
        </ol>
    </nav>

    <x-alert-messages />

    <header>
        <p class="text-xs uppercase tracking-widest text-muted">
            Manufacturing
        </p>
        <h1 class="text-3xl font-extrabold text-petronas">
            Production Planning
        </h1>
        <p class="text-sm text-muted mt-1">
            Pilih produk jadi untuk melihat dan mengelola rencana produksi berdasarkan target forecast.
        </p>
    </header>

    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon shadow-lg shadow-black/50">
        <h2 class="text-lg font-bold text-petronas mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            Manufactured Products
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
                                {{-- Pastikan route ini sesuai dengan yang Anda inginkan (misal: production.plans.show) --}}
                                <a href="{{ route('production.showPlan', $product->id) }}" 
                                   class="inline-flex items-center gap-2 px-4 py-1.5 rounded-lg 
                                          bg-petronas text-blackBase text-xs font-bold uppercase tracking-wide
                                          hover:bg-petronas/90 hover:scale-105 transition-all shadow-lg shadow-petronas/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                    View Plans
                                </a>
                            </td>
                        </tr>
                    @endforeach

                    @if ($products->count() === 0)
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-muted italic">
                                Belum ada data produk jadi. Silakan tambahkan produk di Master Data terlebih dahulu.
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