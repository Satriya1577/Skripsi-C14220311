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

            <li>
                <a href="{{ route('settings.index') }}" class="hover:text-petronas transition-colors">
                    Settings
                </a>
            </li>

            <li class="opacity-40">/</li>

            <li class="text-petronas font-semibold" aria-current="page">
                Model Configuration
            </li>
        </ol>
    </nav>

    @if (session('success'))
        <div class="bg-carbonSoft border border-petronas rounded-xl p-4 flex justify-between items-center">
            <p class="text-sm text-petronas font-semibold">{{ session('success') }}</p>
            <button onclick="this.parentElement.remove()" class="text-muted hover:text-petronas transition">✕</button>
        </div>
    @endif

    <header>
        <p class="text-xs uppercase tracking-widest text-muted">
            System Configuration
        </p>
        <h1 class="text-3xl font-extrabold text-petronas">
            Model Configuration
        </h1>
        <p class="text-sm text-muted mt-1">
            Atur parameter SARIMA dan pantau akurasi model per produk
        </p>
    </header>

    <section class="bg-carbonSoft rounded-xl p-6 border border-carbon">
        
        <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4 gap-4">
            <h2 class="text-lg font-bold text-petronas">
                SARIMA Parameters & Performance
            </h2>
            
            <div class="text-xs text-muted flex gap-4 bg-carbon px-3 py-1.5 rounded-lg border border-carbon">
                <span class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-petronas"></span> Non-Seasonal
                </span>
                <span class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-silver"></span> Seasonal
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
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
                        <th class="px-1 py-3 text-center text-muted font-normal border-l border-carbonSoft">Season (m)</th>

                        <th class="px-3 py-3 text-right text-muted border-l border-carbonSoft">RMSE</th>
                        <th class="px-3 py-3 text-right text-muted">MAPE</th>
                        <th class="px-3 py-3 text-left text-muted">Last Trained</th>
                        <th class="px-3 py-3 text-center text-muted">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sarimaParameters as $param)
                        <form id="form-{{ $param->id }}" action="{{ route('settings.updateSarima') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="product_id" value="{{ $param->product_id }}">
                        </form>

                        <tr class="border-b border-carbon hover:bg-carbon transition-colors group">
                            <td class="px-3 py-2">
                                <div class="flex flex-col">
                                    <span class="font-semibold text-silver">{{ $param->product->code }}</span>
                                    <span class="text-xs text-muted truncate max-w-[150px]">{{ $param->product->name }}</span>
                                </div>
                            </td>

                            <td class="px-1 py-2 text-center">
                                <input form="form-{{ $param->id }}" type="number" min="0" max="5" 
                                       name="order_p" value="{{ $param->order_p }}"
                                       class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-petronas font-bold focus:outline-none focus:border-petronas transition-colors">
                            </td>
                            <td class="px-1 py-2 text-center">
                                <input form="form-{{ $param->id }}" type="number" min="0" max="5" 
                                       name="order_d" value="{{ $param->order_d }}"
                                       class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-petronas font-bold focus:outline-none focus:border-petronas transition-colors">
                            </td>
                            <td class="px-1 py-2 text-center">
                                <input form="form-{{ $param->id }}" type="number" min="0" max="5" 
                                       name="order_q" value="{{ $param->order_q }}"
                                       class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-petronas font-bold focus:outline-none focus:border-petronas transition-colors">
                            </td>

                            <td class="px-1 py-2 text-center border-l border-carbon">
                                <input form="form-{{ $param->id }}" type="number" min="0" max="5" 
                                       name="seasonal_P" value="{{ $param->seasonal_P }}"
                                       class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-silver font-medium focus:outline-none focus:border-petronas transition-colors">
                            </td>
                            <td class="px-1 py-2 text-center">
                                <input form="form-{{ $param->id }}" type="number" min="0" max="5" 
                                       name="seasonal_D" value="{{ $param->seasonal_D }}"
                                       class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-silver font-medium focus:outline-none focus:border-petronas transition-colors">
                            </td>
                            <td class="px-1 py-2 text-center">
                                <input form="form-{{ $param->id }}" type="number" min="0" max="5" 
                                       name="seasonal_Q" value="{{ $param->seasonal_Q }}"
                                       class="w-10 text-center bg-blackBase border border-carbon rounded-lg py-1 text-silver font-medium focus:outline-none focus:border-petronas transition-colors">
                            </td>

                            <td class="px-1 py-2 text-center border-l border-carbon">
                                <input form="form-{{ $param->id }}" type="number" 
                                       name="seasonal_s" value="{{ $param->seasonal_s }}"
                                       class="w-12 text-center bg-blackBase border border-carbon rounded-lg py-1 text-muted focus:text-silver focus:outline-none focus:border-petronas transition-colors">
                            </td>

                            <td class="px-3 py-2 text-right border-l border-carbon font-mono text-xs text-silver">
                                {{ $param->rmse !== null ? number_format($param->rmse, 2) : '-' }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono text-xs text-silver">
                                {{ $param->mape !== null ? number_format($param->mape, 2) . '%' : '-' }}
                            </td>

                            <td class="px-3 py-2 text-xs text-muted">
                                @if($param->last_trained_at)
                                    <div>{{ \Carbon\Carbon::parse($param->last_trained_at)->format('d M Y') }}</div>
                                    <div class="opacity-50 text-[10px]">{{ \Carbon\Carbon::parse($param->last_trained_at)->format('H:i') }}</div>
                                @else
                                    <span class="opacity-50 italic">Not trained</span>
                                @endif
                            </td>

                            <td class="px-3 py-2 text-center">
                                <button form="form-{{ $param->id }}" type="submit" disabled
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

                    @if($sarimaParameters->isEmpty())
                        <tr>
                            <td colspan="12" class="px-3 py-6 text-center text-muted italic">
                                Tidak ada data konfigurasi yang tersedia.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>

</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Ambil semua form parameter yang ada di halaman
        const forms = document.querySelectorAll('form[id^="form-"]');

        forms.forEach(form => {
            const formId = form.id;
            
            // Karena input dan button berada DI LUAR tag <form>, kita cari berdasarkan atribut 'form'
            const inputs = document.querySelectorAll(`input[form="${formId}"]`);
            const saveBtn = document.querySelector(`button[form="${formId}"]`);

            // Definisi Style Tailwind
            const activeClasses = ['bg-petronas', 'text-blackBase', 'hover:bg-petronas/90', 'shadow-lg', 'shadow-petronas/20'];
            const inactiveClasses = ['bg-carbon', 'text-muted', 'cursor-not-allowed', 'opacity-50'];

            // 2. Simpan nilai awal (original value) ke data-attribute
            inputs.forEach(input => {
                input.dataset.original = input.value;

                // 3. Pasang Event Listener 'input' (mendeteksi ketikan/perubahan)
                input.addEventListener('input', () => {
                    checkDirtyState(inputs, saveBtn, activeClasses, inactiveClasses);
                });
            });
        });

        // Fungsi untuk mengecek apakah ada perubahan
        function checkDirtyState(inputs, btn, activeClasses, inactiveClasses) {
            let isDirty = false;

            // Cek satu per satu input dalam baris tersebut
            inputs.forEach(input => {
                if (input.value !== input.dataset.original) {
                    isDirty = true;
                }
            });

            // Update status tombol
            if (isDirty) {
                // Aktifkan Tombol
                btn.disabled = false;
                btn.classList.remove(...inactiveClasses);
                btn.classList.add(...activeClasses);
            } else {
                // Matikan Tombol (Kembali ke Original)
                btn.disabled = true;
                btn.classList.remove(...activeClasses);
                btn.classList.add(...inactiveClasses);
            }
        }
    });
</script>

</body>
</html>