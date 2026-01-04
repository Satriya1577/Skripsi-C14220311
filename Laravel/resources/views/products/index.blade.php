<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products | Production Planning System</title>

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

    <!-- BREADCRUMB -->
    <nav aria-label="breadcrumb" class="text-xs text-muted">
        <ol class="flex items-center space-x-2">
            <li>
                <a href="{{ route('home.index') }}" class="hover:text-petronas transition-colors">
                    Home
                </a>
            </li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold" aria-current="page">
                Products
            </li>
        </ol>
    </nav>

    <!-- ALERT MESSAGES COMPONENT -->
    <x-alert-messages />

    <!-- HEADER -->
    <header>
        <p class="text-xs uppercase tracking-widest text-muted">
            Master Data
        </p>
        <h1 class="text-3xl font-extrabold text-petronas">
            Product Management
        </h1>
        <p class="text-sm text-muted mt-1">
            Kelola data produk, stok, dan parameter produksi
        </p>
    </header>

    <!-- PRODUCT FORM -->
    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon space-y-6">
        <h2 class="text-lg font-bold text-petronas">
            Add New Product
        </h2>

        <form action="{{ route('products.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @csrf
            <input id="product_id" type="hidden" name="product_id">

            <!-- CODE -->
            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Product Code</label>
                <input id="code" type="text" name="code" required
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver
                           focus:outline-none focus:border-petronas">
            </div>

            <!-- NAME -->
            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Product Name</label>
                <input id="name" type="text" name="name" required
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver
                           focus:outline-none focus:border-petronas">
            </div>

            <!-- CURRENT STOCK -->
            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Current Stock</label>
                <input id="current_stock" type="number" name="current_stock" value="0"
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver
                           focus:outline-none focus:border-petronas">
            </div>

            <!-- SAFETY STOCK -->
            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Safety Stock</label>
                <input id="safety_stock" type="number" name="safety_stock" value="0"
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver
                           focus:outline-none focus:border-petronas">
            </div>

            <!-- PRICE -->
            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Selling Price</label>
                <input id="price" type="number" step="0.01" name="price" value="0"
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-silver
                           focus:outline-none focus:border-petronas">
            </div>

            <!-- COST PRICE -->
            <div>
                <label class="text-xs text-muted uppercase tracking-wide">Cost Price (Avg)</label>
                <input id="cost_price" type="number" step="0.01" name="cost_price" value="0" readonly
                    class="w-full mt-1 px-4 py-2 rounded-lg bg-carbon border border-carbon text-muted
                           cursor-not-allowed">
            </div>

            <!-- BUTTON -->
            <div class="md:col-span-3 flex flex-wrap justify-end gap-3">
                
                <button type="button" id="cancelBtn"
                    onclick="resetForm()"
                    class="hidden px-6 py-2 rounded-lg border border-muted text-muted
                        hover:bg-carbon transition">
                    Cancel
                </button>

                <button type="submit" id="submitBtn"
                    class="bg-petronas text-blackBase font-bold px-6 py-2 rounded-lg
                        hover:bg-petronas/90 transition shadow-[0_0_15px_rgba(0,161,155,0.3)]">
                    Save Product
                </button>
            </div>
        </form>
    </section>

    <!-- PRODUCT TABLE -->
    <section class="bg-carbonSoft rounded-xl p-6">
        <h2 class="text-lg font-bold text-petronas mb-4">
            Product List & Recipe
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-carbon">
                    <tr>
                        <th class="px-3 py-2 text-left text-muted">Code</th>
                        <th class="px-3 py-2 text-left text-muted">Name</th>
                        <th class="px-3 py-2 text-right text-muted">Stock</th>
                        <th class="px-3 py-2 text-right text-muted">Safety</th>
                        <th class="px-3 py-2 text-right text-muted">Selling Price</th>
                        <th class="px-3 py-2 text-center text-muted">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr class="border-b border-carbon hover:bg-carbon transition-colors">
                            <td class="px-3 py-2 font-semibold">
                                {{ $product->code }}
                            </td>
                            <td class="px-3 py-2">
                                {{ $product->name }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                {{ $product->current_stock }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                {{ $product->safety_stock }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                {{ number_format($product->price, 2) }}
                            </td>
                            <td class="px-3 py-2 text-center space-x-2">

                                <!-- VIEW ICON -->
                                <a href="{{ route('products.show', $product->id) }}"
                                title="View Details"
                                class="inline-flex items-center justify-center w-8 h-8 rounded
                                        bg-petronas text-blackBase
                                        hover:bg-petronas/90 transition">
                                    👁️
                                </a>

                                <!-- EDIT ICON -->
                                <button
                                    type="button"
                                    title="Edit Product"
                                    onclick="editProduct({{ $product }})"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded
                                        border border-petronas text-petronas
                                        hover:bg-petronas/10 transition">
                                    ✏️
                                </button>

                                <!-- DELETE -->
                                <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                        onclick="openDeleteModal(this)"
                                        title="Delete"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded
                                            border border-red-500 text-red-500
                                            hover:bg-red-500 hover:text-blackBase transition">
                                        🗑️
                                    </button>

                                </form>

                            </td>
                        </tr>
                    @endforeach

                    @if ($products->count() === 0)
                        <tr>
                            <td colspan="6" class="px-3 py-4 text-center text-muted">
                                No products available
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        @if ($products->hasPages())
            <div class="mt-4 flex justify-between items-center text-sm text-muted">
                <div>
                    Showing
                    <span class="font-semibold text-silver">{{ $products->firstItem() }}</span>
                    to
                    <span class="font-semibold text-silver">{{ $products->lastItem() }}</span>
                    of
                    <span class="font-semibold text-silver">{{ $products->total() }}</span>
                    products
                </div>

                <div>
                    {{ $products->links('pagination::tailwind') }}
                </div>
            </div>
        @endif
    </section>
</main>

<!-- DELETE CONFIRM MODAL -->
<div id="deleteModal"
    class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50">

    <div class="bg-carbonSoft rounded-xl p-6 w-full max-w-md border border-red-500 shadow-2xl">
        <h3 class="text-lg font-bold text-red-500 mb-2">
            Confirm Deletion
        </h3>

        <p class="text-sm text-muted mb-6">
            Are you sure you want to delete this product?
            <br>
            <span class="text-red-400 font-semibold">This action cannot be undone.</span>
        </p>

        <div class="flex justify-end gap-3">
            <button
                onclick="closeDeleteModal()"
                class="px-5 py-2 rounded-lg border border-muted text-muted
                    hover:bg-carbon transition">
                Cancel
            </button>

            <button
                onclick="confirmDelete()"
                class="px-5 py-2 rounded-lg bg-red-500 text-blackBase font-bold
                    hover:bg-red-600 transition shadow-lg shadow-red-500/20">
                Delete
            </button>
        </div>
    </div>
</div>

<script>
    function editProduct(product) {
        // Fill form
        document.getElementById('product_id').value = product.id;
        document.getElementById('code').value = product.code;
        document.getElementById('code').readOnly = true;

        document.getElementById('name').value = product.name;
        document.getElementById('current_stock').value = product.current_stock;
        document.getElementById('current_stock').readOnly = true;
        document.getElementById('safety_stock').value = product.safety_stock;
        document.getElementById('price').value = product.price;
        document.getElementById('cost_price').value = product.cost_price;

        // Update button text
        document.getElementById('submitBtn').innerText = 'Update';

        // Show cancel button
        document.getElementById('cancelBtn').classList.remove('hidden');

        // DISABLE BOTH UPLOAD BUTTONS WHILE EDITING
        const uploadProductBtn = document.getElementById('uploadProductBtn');
        const uploadRecipeBtn = document.getElementById('uploadRecipeBtn');
        
        // Add styling for disabled state
        [uploadProductBtn, uploadRecipeBtn].forEach(btn => {
            if(btn) btn.classList.add('opacity-30', 'pointer-events-none', 'grayscale');
        });
    }

    function resetForm() {
        document.querySelector('form').reset();
        document.getElementById('product_id').value = '';
        document.getElementById('submitBtn').innerText = 'Save Product';
        document.getElementById('cancelBtn').classList.add('hidden');

        document.getElementById('code').readOnly = false;
        document.getElementById('current_stock').readOnly = false;

        // ENABLE BOTH UPLOAD BUTTONS AGAIN
        const uploadProductBtn = document.getElementById('uploadProductBtn');
        const uploadRecipeBtn = document.getElementById('uploadRecipeBtn');

        [uploadProductBtn, uploadRecipeBtn].forEach(btn => {
            if(btn) btn.classList.remove('opacity-30', 'pointer-events-none', 'grayscale');
        });
    }



    let deleteForm = null;

    function openDeleteModal(button) {
        deleteForm = button.closest('form');
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deleteModal').classList.remove('flex');
        deleteForm = null;
    }

    function confirmDelete() {
        if (deleteForm) {
            deleteForm.submit();
        }
    }
</script>

</body>
</html>
