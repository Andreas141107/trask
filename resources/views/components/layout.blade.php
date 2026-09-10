<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Trask - Tracker Keuangan & Penjualan</title>
    <style>[x-cloak] { display: none !important; }</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="h-full flex text-slate-800" x-data="{ sidebarOpen: false }">
    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" x-cloak x-transition.opacity style="display: none;" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"></div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 max-w-[80vw] bg-slate-900 text-slate-300 flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto lg:w-64 lg:shrink-0">
        <!-- Brand Logo -->
        <div class="h-16 flex items-center justify-between px-6 bg-slate-950/40 border-b border-slate-800">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-white font-bold text-xl tracking-tight">
                <div class="p-2 bg-indigo-600 rounded-lg text-white">
                    <i data-lucide="wallet-cards" class="w-5 h-5"></i>
                </div>
                <span>Trask</span>
            </a>
            <button @click="sidebarOpen = false" class="text-slate-400 hover:text-white lg:hidden">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Team Selector Dropdown -->
        <div class="p-4 border-b border-slate-800">
            <div class="bg-slate-800/80 rounded-lg p-3 text-sm flex items-center justify-between cursor-pointer hover:bg-slate-800 transition">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0">
                        {{ strtoupper(substr(auth()->user()->currentTeam?->name ?? 'T', 0, 2)) }}
                    </div>
                    <div class="overflow-hidden">
                        <p class="font-semibold text-white truncate text-xs">{{ auth()->user()->currentTeam?->name ?? 'Tanpa Tim' }}</p>
                        <p class="text-[10px] text-slate-400">{{ ucfirst(auth()->user()->currentTeam?->users()->where('user_id', auth()->id())->first()?->pivot->role ?? 'member') }} Role</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1">
            <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Menu Utama</p>
            
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                Dashboard
            </a>

            <a href="{{ route('transactions.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('transactions.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                <i data-lucide="arrow-left-right" class="w-4 h-4"></i>
                Transaksi
            </a>

            <a href="{{ route('products.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('products.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                <i data-lucide="package" class="w-4 h-4"></i>
                Produk & Stok
            </a>

            <a href="{{ route('capitals.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('capitals.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                <i data-lucide="coins" class="w-4 h-4"></i>
                Modal & Beban
            </a>

            <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-6 mb-2">Integrasi & Tim</p>

            <a href="{{ route('teams.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('teams.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                <i data-lucide="users" class="w-4 h-4"></i>
                Kelola Tim
            </a>

            <a href="{{ route('whatsapp.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('whatsapp.*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white text-slate-300' }} transition">
                <i data-lucide="message-square" class="w-4 h-4"></i>
                WhatsApp Bot 2-Way
                <span class="ml-auto text-[10px] bg-emerald-500/20 text-emerald-400 px-1.5 py-0.5 rounded font-medium">Aktif</span>
            </a>
        </nav>

        <!-- User Profile Footer -->
        <div class="p-4 border-t border-slate-800 flex items-center justify-between gap-2">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-full bg-slate-700 flex items-center justify-center font-bold text-white text-xs shrink-0">
                    {{ strtoupper(substr(auth()->user()->name ?? 'AD', 0, 2)) }}
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name ?? 'User' }}</p>
                    <p class="text-[10px] text-slate-400 truncate">{{ auth()->user()->email ?? '' }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf
                <button type="submit" class="p-2 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg transition" title="Logout">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 min-h-0">
        <!-- Top Navigation Header -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between gap-3 px-4 lg:px-8 sticky top-0 z-30 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" @click="sidebarOpen = true" aria-label="Buka menu navigasi" class="-ml-2 inline-flex min-h-11 min-w-11 flex-col items-center justify-center gap-1.5 rounded-xl px-3 text-slate-600 hover:bg-slate-100 hover:text-slate-800 lg:hidden shrink-0">
                    <span class="block h-0.5 w-6 rounded-full bg-current"></span>
                    <span class="block h-0.5 w-6 rounded-full bg-current"></span>
                    <span class="block h-0.5 w-6 rounded-full bg-current"></span>
                </button>
                <h1 class="text-base lg:text-lg font-bold text-slate-800 truncate">@yield('header', 'Dashboard')</h1>
            </div>

            <div class="flex items-center gap-2 lg:gap-3 shrink-0">
                <!-- WhatsApp Quick Status Indicator -->
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-full text-xs font-medium whitespace-nowrap">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Bot WA Terhubung
                </div>

                <!-- Profile Dropdown (desktop) -->
                <div class="hidden sm:block relative" x-data="{ open: false }" @click.away="open = false" @keydown.escape.window="open = false">
                    <button @click="open = !open" type="button" :aria-expanded="open.toString()" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-slate-100 transition">
                        <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                    </button>
                    <div x-show="open" x-cloak x-transition style="display: none;" class="absolute right-0 mt-2 w-48 bg-white rounded-xl border border-slate-200 shadow-lg py-2 z-50">
                        <div class="px-4 py-2 border-b border-slate-100">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Profil Saya</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-rose-600 hover:bg-rose-50">Keluar / Logout</button>
                        </form>
                    </div>
                </div>

                <!-- Logout mobile -->
                <form method="POST" action="{{ route('logout') }}" class="sm:hidden">
                    @csrf
                    <button type="submit" class="p-2 text-slate-400 hover:text-rose-500 rounded-lg" title="Logout">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </button>
                </form>
            </div>
        </header>

        <!-- Page Dynamic Body -->
        <main class="flex-1 p-4 lg:p-8 overflow-y-auto">
            @yield('content')
            {{ $slot ?? '' }}
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
        // Refresh ikon setiap Alpine update DOM
        document.addEventListener('alpine:initialized', () => {
            if (window.lucide) window.lucide.createIcons();
        });
    </script>
    @stack('scripts')
</body>
</html>
