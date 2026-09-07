@extends('layouts.auth')
@section('title', 'Masuk')

@section('content')
    <div class="container-site flex flex-1 items-center justify-center py-10">
        <div class="grid w-full max-w-4xl overflow-hidden rounded-[1.75rem] border border-line bg-white shadow-lift lg:grid-cols-2">
            {{-- Branding panel --}}
            <div class="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-forest-800 via-forest-900 to-forest-950 p-10 lg:flex">
                <div class="relative">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-white">
                            <x-icon name="gamepad" class="h-5 w-5" />
                        </span>
                        <span class="font-display text-lg font-bold tracking-tight text-white">{{ config('app.name') }}</span>
                    </a>
                    <h2 class="mt-8 font-display text-3xl font-bold leading-tight text-white">
                        Top up game favoritmu, <span class="text-forest-300">instan</span> &amp; aman.
                    </h2>
                    <p class="mt-3 text-sm text-forest-100/70">Masuk untuk mulai top up dan kelola semua pesananmu di satu tempat.</p>
                </div>
                <div class="relative space-y-3">
                    <div class="flex items-center gap-2 text-sm text-forest-100/90">
                        <x-icon name="bolt" class="h-4 w-4 text-forest-300" /> Proses saldo otomatis
                    </div>
                    <div class="flex items-center gap-2 text-sm text-forest-100/90">
                        <x-icon name="shield" class="h-4 w-4 text-forest-300" /> Pembayaran terenkripsi
                    </div>
                    <div class="flex items-center gap-2 text-sm text-forest-100/90">
                        <x-icon name="clock" class="h-4 w-4 text-forest-300" /> Dukungan 24/7
                    </div>
                </div>
            </div>

            {{-- Form --}}
            <div class="p-6 sm:p-10">
                <div class="mb-6 lg:hidden">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-forest-700 text-white">
                            <x-icon name="gamepad" class="h-5 w-5" />
                        </span>
                        <span class="font-display text-lg font-bold tracking-tight text-ink">{{ config('app.name') }}</span>
                    </a>
                </div>

                <h1 class="font-display text-2xl font-bold tracking-tight text-ink">Masuk</h1>
                <p class="mt-1.5 text-sm text-stone-500">Belum punya akun?
                    <a href="{{ route('register') }}" class="font-semibold text-forest-700 hover:text-forest-800">Daftar</a>
                </p>

                <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="label">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                               class="input" placeholder="nama@email.com">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="label">Password</label>
                        <div class="relative mt-1">
                            <input type="password" id="password" name="password" required class="input pr-10 !mt-0" placeholder="Password kamu" data-password-toggle>
                            <button type="button" data-toggle-password="password" class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 transition hover:text-stone-600" aria-label="Tampilkan password">
                                <x-icon name="eye" class="h-4 w-4" />
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-stone-600">
                        <input type="checkbox" name="remember" id="remember"
                               class="h-4 w-4 rounded border-stone-300 text-forest-600 focus:ring-forest-500">
                        Ingat saya
                    </label>

                    <button type="submit" class="btn-primary w-full !py-3.5">
                        Masuk <x-icon name="arrow-right" class="h-4 w-4" />
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection