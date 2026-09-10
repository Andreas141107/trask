<x-guest-layout>
    <div class="mb-6 sm:mb-8">
        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-teal-700 sm:mb-3 sm:text-sm sm:tracking-[0.18em]">Selamat datang kembali</p>
        <h2 class="font-display text-2xl font-bold leading-tight tracking-tight text-slate-900 sm:text-3xl">Masuk ke workspace kamu.</h2>
        <p class="mt-3 text-sm leading-6 text-slate-500">Lanjutkan pencatatan bisnis tanpa kehilangan ritmenya.</p>
    </div>

    <x-auth-session-status class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" :status="session('status')" />

    @if (session('error'))
        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <a href="{{ route('google.redirect') }}" class="flex w-full items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
        <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M21.35 12.23c0-.79-.07-1.55-.22-2.27H12v4.3h5.24a4.48 4.48 0 0 1-1.94 2.94v2.45h3.14c1.84-1.7 2.91-4.21 2.91-7.42Z"/><path fill="#34A853" d="M12 21.5c2.63 0 4.84-.87 6.45-2.35l-3.14-2.45c-.87.58-1.98.92-3.31.92-2.54 0-4.7-1.72-5.47-4.03H3.29v2.53A9.74 9.74 0 0 0 12 21.5Z"/><path fill="#FBBC05" d="M6.53 13.59A5.85 5.85 0 0 1 6.22 12c0-.55.1-1.09.31-1.59V7.88H3.29A9.74 9.74 0 0 0 2.25 12c0 1.57.38 3.06 1.04 4.12l3.24-2.53Z"/><path fill="#EA4335" d="M12 6.38c1.43 0 2.72.49 3.73 1.45l2.8-2.8C16.84 3.47 14.63 2.5 12 2.5a9.74 9.74 0 0 0-8.71 5.38l3.24 2.53c.77-2.31 2.93-4.03 5.47-4.03Z"/></svg>
        Lanjutkan dengan Google
    </a>
    <div class="my-6 flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400"><span class="h-px flex-1 bg-slate-200"></span><span>atau email</span><span class="h-px flex-1 bg-slate-200"></span></div>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="space-y-4 sm:space-y-5">
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                <input id="email" class="block w-full rounded-xl border-slate-200 bg-white px-4 py-3 text-sm shadow-sm outline-none transition placeholder:text-slate-400 focus:border-teal-600 focus:ring-4 focus:ring-teal-100" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nama@bisnis.com">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                <input id="password" class="block w-full rounded-xl border-slate-200 bg-white px-4 py-3 text-sm shadow-sm outline-none transition focus:border-teal-600 focus:ring-4 focus:ring-teal-100" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password">
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
        </div>

        <div class="mt-5 flex items-center justify-between gap-3 sm:mt-6 sm:gap-4">
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-500">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-teal-700 focus:ring-teal-600" name="remember">
                Ingat saya
            </label>
            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-teal-700 hover:text-teal-900" href="{{ route('password.request') }}">Lupa password?</a>
            @endif
        </div>

        <button type="submit" class="mt-6 w-full rounded-xl bg-[#123b3a] px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-teal-900/10 transition hover:bg-[#1d5753] focus:outline-none focus:ring-4 focus:ring-teal-200">Masuk ke Trask</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 sm:mt-8">Belum punya akun? <a href="{{ route('register') }}" class="font-bold text-teal-700 hover:text-teal-900">Buat akun baru</a></p>
</x-guest-layout>
