@extends('layouts.auth')
@section('title', 'Daftar')

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
                        Gabung dan mulai <span class="text-forest-300">top up</span>.
                    </h2>
                    <p class="mt-3 text-sm text-forest-100/70">Buat akun untuk top up game favoritmu dengan cepat dan aman.</p>
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

                <h1 class="font-display text-2xl font-bold tracking-tight text-ink">Buat Akun</h1>
                <p class="mt-1.5 text-sm text-stone-500">Sudah punya akun?
                    <a href="{{ route('login') }}" class="font-semibold text-forest-700 hover:text-forest-800">Masuk</a>
                </p>

                <form method="POST" action="{{ route('register') }}" class="mt-7 space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="label">Nama Lengkap</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                               class="input" placeholder="Nama kamu">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="label">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required
                               class="input" placeholder="nama@email.com">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="label">No. HP <span class="font-normal text-stone-400">(Opsional)</span></label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                               class="input" placeholder="08xxxxxxxxxx">
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="password" class="label">Password</label>
                            <div class="relative mt-1">
                                <input type="password" id="password" name="password" required
                                       class="input pr-10 !mt-0" placeholder="Min 8 karakter" data-password-toggle>
                                <button type="button" data-toggle-password="password" class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 transition hover:text-stone-600" aria-label="Tampilkan password">
                                    <x-icon name="eye" class="h-4 w-4" />
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="label">Konfirmasi</label>
                            <div class="relative mt-1">
                                <input type="password" id="password_confirmation" name="password_confirmation" required
                                       class="input pr-10 !mt-0" placeholder="Ulangi password" data-password-toggle>
                                <button type="button" data-toggle-password="password_confirmation" class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 transition hover:text-stone-600" aria-label="Tampilkan password">
                                    <x-icon name="eye" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary w-full !py-3.5">
                        Daftar <x-icon name="arrow-right" class="h-4 w-4" />
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection