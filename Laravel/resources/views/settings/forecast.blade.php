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
            <li><a href="{{ route('home.index') }}" class="hover:text-petronas transition-colors">Home</a></li>
            <li class="opacity-40">/</li>
            <li><a href="{{ route('settings.index') }}" class="hover:text-petronas transition-colors">Settings</a></li>
            <li class="opacity-40">/</li>
            <li class="text-petronas font-semibold" aria-current="page">Model Configuration</li>
        </ol>
    </nav>

    @if (session('success'))
        <div class="bg-carbonSoft border border-petronas rounded-xl p-4 flex justify-between items-center">
            <p class="text-sm text-petronas font-semibold">{{ session('success') }}</p>
            <button onclick="this.parentElement.remove()" class="text-muted hover:text-petronas transition">✕</button>
        </div>
    @endif

    <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-widest text-muted">System Configuration</p>
            <h1 class="text-3xl font-extrabold text-petronas">Model Configuration</h1>
            <p class="text-sm text-muted mt-1">Atur parameter SARIMA dan pantau akurasi model per produk</p>
        </div>

        <div>
            <form id="grid-all-form" action="{{ route('settings.gridSearchAll') }}" method="POST">
                @csrf
                <button type="submit" id="btn-tune-all"
                    @if($isGridSearchRunning) disabled @endif
                    onclick="return confirm('PERINGATAN: Proses ini akan memakan waktu lama tergantung jumlah produk. Lanjutkan?')"
                    class="group min-w-[200px] flex justify-center items-center gap-2 px-5 py-3 rounded-xl bg-carbonSoft border border-petronas/30 text-petronas font-bold hover:bg-petronas hover:text-blackBase transition-all shadow-lg hover:shadow-petronas/20 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-carbon">
                    
                    @if($isGridSearchRunning)
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Processing in Background...</span>
                        </div>
                    @else
                        <div id="btn-text-normal" class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:animate-pulse" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                            </svg>
                            <span>Auto-tune All Products</span>
                        </div>
                        <div id="btn-text-loading" class="hidden">
                            <span>Processing...</span>
                        </div>
                    @endif
                </button>
            </form>
        </div>
    </header>

    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4 gap-4">
            <h2 class="text-lg font-bold text-petronas">SARIMA Parameters & Performance</h2>
            <div class="text-xs text-muted flex gap-4 bg-carbon px-3 py-1.5 rounded-lg border border-carbon">
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-petronas"></span> Non-Seasonal</span>
                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-silver"></span> Seasonal</span>
            </div>
        </div>

        <div class="overflow-x-auto pb-4"> {{-- Added pb-4 for dropdown space --}}
            <table class="w-full text-sm">
                <thead class="bg-carbon">
                    <tr>
                        <th class="px-3 py-3 text-left text-muted">Product Info</th>
                        <th class="px-1 py-3 text-center text-petronas font-bold">p</th>
                        <th class="px-1 py-3 text-center text-petronas font-bold">d</th>
                        <th class="px-1 py-3 text-center text-petronas font-bold">q</th>
                        <th class="px-1 py-3 text-center text-silver font-bold border-l border-carbonSoft">P</th>
                        <th class="px-1 py-3 text-center text-silver font-bold">D</th>
                        <th class="px-1 py-3 text-center text-silver font-bold">Q</th>
                        {{-- KOLOM BARU S --}}
                        <th class="px-1 py-3 text-center text-silver font-bold">s</th> 
                        
                        <th class="px-3 py-3 text-right text-muted border-l border-carbonSoft">RMSE</th>
                        <th class="px-3 py-3 text-right text-muted">MAPE</th>
                        <th class="px-3 py-3 text-left text-muted">Last Trained</th>
                        <th class="px-3 py-3 text-center text-muted">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <form id="form-{{ $product->id }}" action="{{ route('settings.updateSarima') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                        </form>

                        <tr class="border-b border-carbon hover:bg-carbon transition-colors group">
                            {{-- Product Info --}}
                            <td class="px-3 py-2">
                                <div class="flex flex-col">
                                    <span class="font-semibold text-silver">{{ $product->code }}</span>
                                    <span class="text-xs text-muted truncate max-w-[150px]">{{ $product->name }}</span>
                                </div>
                            </td>

                            {{-- Non-Seasonal Params --}}
                            <td class="px-1 py-2 text-center"><input form="form-{{ $product->id }}" type="number" min="0" max="5" name="order_p" value="{{ $product->order_p }}" class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-petronas font-bold focus:outline-none focus:border-petronas transition-colors"></td>
                            <td class="px-1 py-2 text-center"><input form="form-{{ $product->id }}" type="number" min="0" max="5" name="order_d" value="{{ $product->order_d }}" class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-petronas font-bold focus:outline-none focus:border-petronas transition-colors"></td>
                            <td class="px-1 py-2 text-center"><input form="form-{{ $product->id }}" type="number" min="0" max="5" name="order_q" value="{{ $product->order_q }}" class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-petronas font-bold focus:outline-none focus:border-petronas transition-colors"></td>

                            {{-- Seasonal Params --}}
                            <td class="px-1 py-2 text-center border-l border-carbon"><input form="form-{{ $product->id }}" type="number" min="0" max="5" name="seasonal_P" value="{{ $product->seasonal_P }}" class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-silver font-medium focus:outline-none focus:border-petronas transition-colors"></td>
                            <td class="px-1 py-2 text-center"><input form="form-{{ $product->id }}" type="number" min="0" max="5" name="seasonal_D" value="{{ $product->seasonal_D }}" class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-silver font-medium focus:outline-none focus:border-petronas transition-colors"></td>
                            <td class="px-1 py-2 text-center"><input form="form-{{ $product->id }}" type="number" min="0" max="5" name="seasonal_Q" value="{{ $product->seasonal_Q }}" class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-silver font-medium focus:outline-none focus:border-petronas transition-colors"></td>

                            {{-- DROPDOWN S (SEASONALITY) --}}
                            <td class="px-1 py-2 text-center">
                                <select form="form-{{ $product->id }}" name="seasonal_s" 
                                        class="w-14 text-center bg-blackBase border border-carbon rounded-lg py-1 text-silver font-medium focus:outline-none focus:border-petronas transition-colors appearance-none cursor-pointer hover:bg-carbonSoft">
                                    @foreach([2, 3, 6, 9, 12] as $sVal)
                                        <option value="{{ $sVal }}" {{ $product->seasonal_s == $sVal ? 'selected' : '' }}>
                                            {{ $sVal }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            {{-- Metrics --}}
                            <td class="px-3 py-2 text-right border-l border-carbon font-mono text-xs text-silver">
                                {{ $product->rmse !== null ? number_format($product->rmse, 2) : '-' }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono text-xs text-silver">
                                {{ $product->mape !== null ? number_format($product->mape, 2) . '%' : '-' }}
                            </td> 

                            {{-- Last Trained --}}
                            <td class="px-3 py-2 text-xs text-muted">
                                @if($product->last_trained_at)
                                    <div>{{ \Carbon\Carbon::parse($product->last_trained_at)->format('d M Y') }}</div>
                                    <div class="opacity-50 text-[10px]">{{ \Carbon\Carbon::parse($product->last_trained_at)->format('H:i') }}</div>
                                @else
                                    <span class="opacity-50 italic">Not trained</span>
                                @endif
                            </td> 

                            {{-- Action Button --}}
                            <td class="px-3 py-2 text-center">
                                <button form="form-{{ $product->id }}" type="submit" disabled
                                        class="save-btn inline-flex items-center justify-center w-8 h-8 rounded 
                                            transition-all duration-200
                                            bg-carbon text-muted cursor-not-allowed opacity-50"
                                        title="Save Configuration">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @endforeach

                    @if($products->isEmpty())
                        <tr><td colspan="13" class="px-3 py-6 text-center text-muted italic">Tidak ada data konfigurasi yang tersedia.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>

</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- LOGIC TOMBOL AUTO TUNE ---
        const gridAllForm = document.getElementById('grid-all-form');
        const btnTuneAll = document.getElementById('btn-tune-all');
        const textNormal = document.getElementById('btn-text-normal');
        const textLoading = document.getElementById('btn-text-loading');

        if (gridAllForm) {
            gridAllForm.addEventListener('submit', function() {
                btnTuneAll.disabled = true;
                textNormal.classList.add('hidden');
                textLoading.classList.remove('hidden');
            });
        }

        // --- LOGIC MANUAL SAVE BUTTON ---
        const forms = document.querySelectorAll('form[id^="form-"]');

        forms.forEach(form => {
            const formId = form.id;
            
            // Ambil semua Input dan Select dalam form tersebut
            // Note: Selector ini mencari elemen input/select di seluruh dokumen yang punya atribut form="form-id"
            // Karena elemennya berada di luar tag <form>, kita pakai atribut 'form'
            const inputs = document.querySelectorAll(`input[form="${formId}"], select[form="${formId}"]`);
            const saveBtn = document.querySelector(`button[form="${formId}"]`);

            const activeClasses = ['bg-petronas', 'text-blackBase', 'hover:bg-petronas/90', 'shadow-lg', 'shadow-petronas/20', 'cursor-pointer', 'opacity-100'];
            const inactiveClasses = ['bg-carbon', 'text-muted', 'cursor-not-allowed', 'opacity-50'];

            // Simpan nilai asli
            inputs.forEach(input => {
                input.dataset.original = input.value;
                
                // Event listener untuk input (ketik) dan change (dropdown)
                input.addEventListener('input', () => checkDirtyState(inputs, saveBtn, activeClasses, inactiveClasses));
                input.addEventListener('change', () => checkDirtyState(inputs, saveBtn, activeClasses, inactiveClasses));
            });
        });

        function checkDirtyState(inputs, btn, activeClasses, inactiveClasses) {
            let isDirty = false;
            inputs.forEach(input => {
                if (input.value !== input.dataset.original) {
                    isDirty = true;
                }
            });

            if (isDirty) {
                btn.disabled = false;
                btn.classList.remove(...inactiveClasses);
                btn.classList.add(...activeClasses);
            } else {
                btn.disabled = true;
                btn.classList.remove(...activeClasses);
                btn.classList.add(...inactiveClasses);
            }
        }
    });
</script>

</body>
</html>