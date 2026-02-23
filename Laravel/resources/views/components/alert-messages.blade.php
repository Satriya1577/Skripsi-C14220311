<div class="space-y-4 mb-6">
    
    {{-- SUCCESS ALERT --}}
    @if (session('success'))
        <div class="bg-carbonSoft border border-petronas rounded-xl p-4 flex items-start gap-4 shadow-[0_0_15px_rgba(0,161,155,0.1)]">
            {{-- Icon --}}
            <span class="text-petronas text-lg font-bold mt-0.5 shrink-0">✓</span>
            
            {{-- Text Content --}}
            <div class="text-sm text-petronas font-semibold flex-1 pt-1">
                {{ session('success') }}
            </div>

            {{-- Close Button --}}
            <button onclick="this.parentElement.remove()" class="text-muted hover:text-petronas transition font-bold text-lg shrink-0 -mt-1">✕</button>
        </div>
    @endif

    {{-- ERROR ALERT --}}
    @if (session('error') || $errors->any())
        <div class="bg-carbonSoft border border-red-500 rounded-xl p-4 flex items-start gap-4 shadow-[0_0_15px_rgba(239,68,68,0.1)]">
            {{-- Icon --}}
            <span class="text-red-500 text-lg font-bold mt-0.5 shrink-0">⚠</span>
            
            {{-- Text Content --}}
            <div class="text-sm text-red-500 flex-1 pt-1">
                @if (session('error'))
                    <p class="font-semibold">{{ session('error') }}</p>
                @endif
                
                @if ($errors->any())
                    <ul class="list-disc list-outside ml-4 opacity-90 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Close Button --}}
            <button onclick="this.parentElement.remove()" class="text-muted hover:text-red-500 transition font-bold text-lg shrink-0 -mt-1">✕</button>
        </div>
    @endif

    {{-- WARNING / INFO ALERT --}}
    @if (session('warning') || session('info'))
        <div class="bg-carbonSoft border border-yellow-500 rounded-xl p-4 flex items-start gap-4 shadow-[0_0_15px_rgba(234,179,8,0.1)]">
            {{-- Icon --}}
            <span class="text-yellow-500 text-lg font-bold mt-0.5 shrink-0">ℹ</span>
            
            {{-- Text Content --}}
            <div class="text-sm text-yellow-500 font-semibold flex-1 pt-1">
                {{ session('warning') ?? session('info') }}
            </div>

            {{-- Close Button --}}
            <button onclick="this.parentElement.remove()" class="text-muted hover:text-yellow-500 transition font-bold text-lg shrink-0 -mt-1">✕</button>
        </div>
    @endif

</div>