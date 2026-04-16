<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AEA 2026') — CUI Alumni Excellence Awards</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary:  { DEFAULT: '#1a365d', 50: '#eff6ff', 100: '#dbeafe', 500: '#1a365d', 600: '#152c4d', 700: '#0f2038' },
                        accent:   { DEFAULT: '#d4a843', 50: '#fffbeb', 100: '#fef3c7', 500: '#d4a843', 600: '#b8922e' },
                        sidebar:  '#1e293b',
                    }
                }
            }
        }
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @stack('head')

    <style>
        [x-cloak] { display: none !important; }
        .sidebar-link { @apply flex items-center gap-3 px-4 py-2.5 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition-colors text-sm font-medium; }
        .sidebar-link.active { @apply bg-blue-800 text-white; }
    </style>
</head>
<body class="h-full bg-slate-50" x-data="app()" x-cloak>

{{-- Toast container --}}
<div class="fixed top-4 right-4 z-50 space-y-2" id="toastContainer">
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="flex items-center gap-3 bg-green-600 text-white px-5 py-3 rounded-xl shadow-lg min-w-72">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="text-sm">{{ session('success') }}</span>
        <button @click="show=false" class="ml-auto">✕</button>
    </div>
    @endif
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="flex items-center gap-3 bg-red-600 text-white px-5 py-3 rounded-xl shadow-lg min-w-72">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        <span class="text-sm">{{ session('error') }}</span>
        <button @click="show=false" class="ml-auto">✕</button>
    </div>
    @endif
    @if(session('warning'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="flex items-center gap-3 bg-yellow-500 text-white px-5 py-3 rounded-xl shadow-lg min-w-72">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        <span class="text-sm">{{ session('warning') }}</span>
        <button @click="show=false" class="ml-auto">✕</button>
    </div>
    @endif
</div>

<div class="flex h-full">
    {{-- Sidebar --}}
    <aside class="hidden lg:flex lg:flex-col w-64 bg-slate-800 shrink-0"
           :class="sidebarOpen ? 'flex' : ''">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-700">
            <div class="w-9 h-9 bg-yellow-500 rounded-lg flex items-center justify-center font-bold text-slate-900 text-sm">CUI</div>
            <div>
                <p class="text-white font-semibold text-sm leading-tight">Alumni Excellence</p>
                <p class="text-slate-400 text-xs">Awards 2026</p>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">

            <a href="{{ route('dashboard') }}"
               class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>

            <a href="{{ route('students.index') }}"
               class="sidebar-link {{ request()->routeIs('students.*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Students
                @php $totalStudents = auth()->user()->isAdmin() ? \App\Models\Student::count() : \App\Models\Student::whereIn('category_id', auth()->user()->assignedCategories->pluck('id'))->count(); @endphp
                @if($totalStudents > 0)
                <span class="ml-auto bg-blue-700 text-white text-xs px-2 py-0.5 rounded-full">{{ $totalStudents }}</span>
                @endif
            </a>

            <a href="{{ route('reviews.mine') }}"
               class="sidebar-link {{ request()->routeIs('reviews.mine') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                My Reviews
                @php $pendingCount = \App\Models\Review::forReviewer(auth()->id())->pending()->count(); @endphp
                @if($pendingCount > 0)
                <span class="ml-auto bg-yellow-500 text-slate-900 text-xs px-2 py-0.5 rounded-full">{{ $pendingCount }}</span>
                @endif
            </a>

            @if(auth()->user()->isAdmin())
            <div class="pt-3 pb-1">
                <p class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Admin</p>
            </div>

            <a href="{{ route('analytics.index') }}"
               class="sidebar-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Analytics
            </a>

            <a href="{{ route('admin.import') }}"
               class="sidebar-link {{ request()->routeIs('admin.import*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import Data
            </a>

            <a href="{{ route('admin.users.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Users
            </a>

            <a href="{{ route('admin.export') }}"
               class="sidebar-link {{ request()->routeIs('admin.export*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export
            </a>
            @endif
        </nav>

        {{-- User info --}}
        <div class="border-t border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-semibold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-white text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                    <p class="text-slate-400 text-xs capitalize">{{ auth()->user()->role }}</p>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-slate-400 hover:text-white transition-colors" title="Logout">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main content --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Top bar --}}
        <header class="bg-white border-b border-slate-200 px-4 lg:px-6 py-3 flex items-center gap-4 shrink-0">
            {{-- Mobile menu toggle --}}
            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-slate-500 hover:text-slate-700">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <div class="flex-1">
                <h1 class="text-sm font-semibold text-slate-800 leading-tight">
                    COMSATS University Islamabad — Alumni Excellence Awards 2026
                </h1>
            </div>

            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                    @if(auth()->user()->isAdmin()) bg-red-100 text-red-700
                    @elseif(auth()->user()->isReviewer()) bg-blue-100 text-blue-700
                    @else bg-slate-100 text-slate-700 @endif">
                    {{ ucfirst(auth()->user()->role) }}
                </span>
                <span class="hidden sm:block text-sm text-slate-600">{{ auth()->user()->name }}</span>
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto p-4 lg:p-6">
            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="shrink-0 border-t border-slate-200 bg-white px-6 py-3 text-center text-xs text-slate-500">
            COMSATS University Islamabad — Registrar Secretariat &nbsp;|&nbsp; CUI-Reg/Notif-06/26/14
        </footer>
    </div>
</div>

{{-- Mobile sidebar overlay --}}
<div x-show="sidebarOpen" @click="sidebarOpen = false"
     class="fixed inset-0 bg-black/50 z-40 lg:hidden" x-cloak></div>

<script>
function app() {
    return {
        sidebarOpen: false,
        toast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const colors = { success: 'bg-green-600', error: 'bg-red-600', warning: 'bg-yellow-500 text-slate-900' };
            const el = document.createElement('div');
            el.className = `flex items-center gap-3 ${colors[type] || colors.success} text-white px-5 py-3 rounded-xl shadow-lg min-w-72 transition-opacity`;
            el.innerHTML = `<span class="text-sm flex-1">${message}</span><button onclick="this.parentElement.remove()" class="shrink-0">✕</button>`;
            container.appendChild(el);
            setTimeout(() => el.remove(), 4000);
        }
    }
}
</script>

@stack('scripts')

</body>
</html>
