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
                        muted: '#9DA3A6'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-blackBase text-silver min-h-screen">

<main class="max-w-7xl mx-auto px-6 py-5 flex flex-col justify-between min-h-screen">

    <!-- HEADER -->
    <header class="text-center mb-12 pt-6 pb-12">
        <h1 class="text-3xl font-extrabold text-petronas">Production Planning & Forecasting</h1>
        <p class="text-muted mt-2">Inventory control, forecasting, and production decisions</p>
    </header>

    <!-- DASHBOARD GRID -->
    <section class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 flex-1">

        <!-- PRODUCT -->
        <a href="{{ route('products.index') }}"
        class="text-center p-4 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center
                transform transition-transform transition-shadow transition-colors hover:-translate-y-1.5 hover:border-petronas hover:shadow-md">
            <div class="text-petronas text-5xl mb-3"><i class="bi bi-box-seam"></i></div>
            <h5 class="text-xl font-bold text-silver mb-1">Product</h5>
            <p class="text-xs text-muted">Product master data and stock levels.</p>
        </a>

        <!-- MATERIAL -->
        <a href="{{ route('materials.index') }}"
        class="text-center p-4 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center
                transform transition-transform transition-shadow transition-colors hover:-translate-y-1.5 hover:border-petronas hover:shadow-md">
            <div class="text-petronas text-5xl mb-3"><i class="bi bi-layers"></i></div>
            <h5 class="text-xl font-bold text-silver mb-1">Material</h5>
            <p class="text-xs text-muted">Raw materials and bill of materials.</p>
        </a>

        <!-- SALES -->
        <a href="{{ route('sales.index') }}"
        class="text-center p-4 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center
                transform transition-transform transition-shadow transition-colors hover:-translate-y-1.5 hover:border-petronas hover:shadow-md">
            <div class="text-petronas text-5xl mb-3"><i class="bi bi-cart-check"></i></div>
            <h5 class="text-xl font-bold text-silver mb-1">Sales</h5>
            <p class="text-xs text-muted">Historical demand data.</p>
        </a>

        <!-- FORECASTING -->
        <a href="{{ route('forecast.index') }}"
        class="text-center p-4 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center
                transform transition-transform transition-shadow transition-colors hover:-translate-y-1.5 hover:border-petronas hover:shadow-md">
            <div class="text-petronas text-5xl mb-3"><i class="bi bi-graph-up-arrow"></i></div>
            <h5 class="text-xl font-bold text-silver mb-1">Forecasting</h5>
            <p class="text-xs text-muted">SARIMA-based demand forecasting.</p>
        </a>

        <!-- PRODUCTION -->
        <a href="production.html"
        class="text-center p-4 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center
                transform transition-transform transition-shadow transition-colors hover:-translate-y-1.5 hover:border-petronas hover:shadow-md">
            <div class="text-petronas text-5xl mb-3"><i class="bi bi-gear-wide-connected"></i></div>
            <h5 class="text-xl font-bold text-silver mb-1">Production</h5>
            <p class="text-xs text-muted">Production plan recommendations.</p>
        </a>

        <!-- SETTINGS -->
        <a href="{{ route('settings.index') }}"
        class="text-center p-4 bg-carbonSoft rounded-xl border border-transparent flex flex-col justify-center items-center
                transform transition-transform transition-shadow transition-colors hover:-translate-y-1.5 hover:border-petronas hover:shadow-md">
            <div class="text-petronas text-5xl mb-3"><i class="bi bi-sliders"></i></div>
            <h5 class="text-xl font-bold text-silver mb-1">Settings</h5>
            <p class="text-xs text-muted">Upload data and model configuration.</p>
        </a>

    </section>

    <!-- FOOTER -->
    <footer class="text-center text-xs text-muted mt-12">
        © 2025 Production Planning System – Academic Prototype
    </footer>

</main>

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

</body>
</html>
