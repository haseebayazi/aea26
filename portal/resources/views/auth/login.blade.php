<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CUI Alumni Excellence Awards 2026</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
</head>
<body class="h-full flex items-center justify-center bg-gradient-to-br from-slate-900 to-blue-950 p-4">

<div class="w-full max-w-md">
    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">

        {{-- Header --}}
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-8 py-8 text-center">
            <div class="w-14 h-14 bg-yellow-500 rounded-xl mx-auto flex items-center justify-center mb-3">
                <span class="font-black text-slate-900 text-xl">CUI</span>
            </div>
            <h1 class="text-white font-bold text-xl">Alumni Excellence Awards</h1>
            <p class="text-blue-200 text-sm mt-1">Review Portal — 2026</p>
        </div>

        {{-- Form --}}
        <div class="px-8 py-8">
            @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
                {{ session('error') }}
            </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                @csrf

                <div class="space-y-5">
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                                      @error('email') border-red-300 bg-red-50 @else border-slate-300 @enderror"
                               placeholder="you@example.com" required autocomplete="email">
                        @error('email')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                        <input type="password" id="password" name="password"
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="••••••••" required autocomplete="current-password">
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" class="w-4 h-4 text-blue-600 rounded border-slate-300">
                        <label for="remember" class="ml-2 text-sm text-slate-600">Remember me</label>
                    </div>

                    <button type="submit"
                            :disabled="loading"
                            class="w-full bg-blue-900 hover:bg-blue-800 disabled:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="loading ? 'Signing in…' : 'Sign In'">Sign In</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <p class="text-center text-slate-400 text-xs mt-4">
        COMSATS University Islamabad — Registrar Secretariat
    </p>
</div>

</body>
</html>
