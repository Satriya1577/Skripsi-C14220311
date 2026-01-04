<div class="space-y-4 mb-6">
    
    @if (session('success'))
        <div class="bg-carbonSoft border border-petronas rounded-xl p-4 flex justify-between items-center shadow-[0_0_15px_rgba(0,161,155,0.1)]">
            <div class="flex items-center gap-3">
                <span class="text-petronas text-lg font-bold">✓</span>
                <p class="text-sm text-petronas font-semibold">{{ session('success') }}</p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-muted hover:text-petronas transition font-bold text-lg">✕</button>
        </div>
    @endif

    @if (session('error') || $errors->any())
        <div class="bg-carbonSoft border border-red-500 rounded-xl p-4 flex justify-between items-start shadow-[0_0_15px_rgba(239,68,68,0.1)]">
            <div class="flex gap-3">
                <span class="text-red-500 text-lg font-bold mt-0.5">⚠</span>
                <div class="text-sm text-red-500">
                    @if (session('error'))
                        <p class="font-semibold">{{ session('error') }}</p>
                    @endif
                    
                    @if ($errors->any())
                        <ul class="list-disc list-inside opacity-90 mt-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
            <button onclick="this.parentElement.remove()" class="text-muted hover:text-red-500 transition font-bold text-lg">✕</button>
        </div>
    @endif

    @if (session('warning') || session('info'))
        <div class="bg-carbonSoft border border-yellow-500 rounded-xl p-4 flex justify-between items-center shadow-[0_0_15px_rgba(234,179,8,0.1)]">
            <div class="flex items-center gap-3">
                <span class="text-yellow-500 text-lg font-bold">ℹ</span>
                <p class="text-sm text-yellow-500 font-semibold">{{ session('warning') ?? session('info') }}</p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-muted hover:text-yellow-500 transition font-bold text-lg">✕</button>
        </div>
    @endif

</div>