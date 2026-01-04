<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Production System</title>
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
<body class="bg-blackBase text-silver h-screen flex items-center justify-center">

    <div class="w-full max-w-md p-8 bg-carbonSoft border border-carbon rounded-2xl shadow-[0_0_30px_rgba(0,161,155,0.1)]">
        
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-petronas">Login System</h1>
            <p class="text-sm text-muted mt-2">Production Planning System</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-500/10 border border-red-500 rounded-lg text-red-400 text-sm">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-4 p-3 bg-petronas/10 border border-petronas rounded-lg text-petronas text-sm">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label for="email" class="block text-xs uppercase tracking-wider text-muted mb-2">Email Address</label>
                <input type="email" name="email" id="email" required autofocus value="{{ old('email') }}"
                    class="w-full px-4 py-3 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none text-silver placeholder-muted/30 transition"
                    placeholder="admin@example.com">
            </div>

            <div>
                <label for="password" class="block text-xs uppercase tracking-wider text-muted mb-2">Password</label>
                <input type="password" name="password" id="password" required
                    class="w-full px-4 py-3 rounded-lg bg-carbon border border-carbon focus:border-petronas focus:outline-none text-silver placeholder-muted/30 transition"
                    placeholder="••••••••">
            </div>

            <button type="submit" 
                class="w-full py-3 bg-petronas text-blackBase font-bold rounded-lg hover:bg-petronas/90 transition shadow-lg shadow-petronas/20">
                Sign In
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-xs text-muted">Protected by Production Security</p>
        </div>
    </div>

</body>
</html>