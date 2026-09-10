<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Trask') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <main class="min-h-screen bg-[#f4f7f6] lg:grid lg:grid-cols-[minmax(360px,0.9fr)_1.1fr]">
            <section class="relative hidden overflow-hidden bg-[#123b3a] px-12 py-12 text-white lg:flex lg:flex-col lg:justify-between xl:px-20">
                <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full border-[28px] border-[#d7f36b]/20"></div>
                <div class="absolute -bottom-32 -left-24 h-80 w-80 rounded-full border-[36px] border-[#69c6b5]/20"></div>
                <a href="{{ url('/') }}" class="relative flex items-center gap-3 text-white">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#d7f36b] font-display text-xl font-bold text-[#123b3a]">T</span>
                    <span class="font-display text-2xl font-bold tracking-tight">Trask</span>
                </a>
                <div class="relative max-w-md">
                    <p class="mb-5 text-sm font-semibold uppercase tracking-[0.24em] text-[#d7f36b]">Keuangan yang lebih tertata</p>
                    <h1 class="font-display text-5xl font-bold leading-[1.05] xl:text-6xl">Bikin bisnis kecil terasa lebih ringan.</h1>
                    <p class="mt-6 max-w-sm text-base leading-7 text-teal-100">Catat transaksi, kelola stok, dan pantau arus kas dalam satu ruang kerja yang tenang.</p>
                </div>
                <p class="relative text-sm text-teal-200">Workspace bisnis untuk bergerak lebih cepat.</p>
            </section>
            <section class="flex min-h-screen items-start justify-center overflow-y-auto px-4 py-6 sm:px-8 sm:py-10 lg:items-center">
                <div class="w-full max-w-md">
                    <div class="mb-6 flex items-center gap-3 sm:mb-8 lg:hidden">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#123b3a] font-display text-lg font-bold text-[#d7f36b]">T</span>
                        <span class="font-display text-2xl font-bold tracking-tight text-[#123b3a]">Trask</span>
                    </div>
                    {{ $slot }}
                </div>
            </section>
        </div>
    </body>
</html>
