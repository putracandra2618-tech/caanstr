@extends('layouts.app')
@section('title', 'Profil')

@section('content')
    <div class="container-site max-w-5xl py-8 sm:py-10 lg:py-12">
        <div class="flex items-center gap-4 border-b border-line pb-6">
            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-forest-100 text-forest-700">
                <x-icon name="user" class="h-7 w-7" />
            </span>
            <div>
                <h1 class="page-title">Profil Saya</h1>
                <p class="mt-0.5 text-sm text-stone-500">Kelola data akun dan keamanan kamu.</p>
            </div>
        </div>

        <form action="{{ route('profile.update') }}" method="POST" class="mt-8 grid gap-6 lg:grid-cols-2">
            @csrf
            @method('PUT')

            <div class="card p-6">
                <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
                    <x-icon name="user" class="h-4 w-4 text-forest-600" /> Data Diri
                </h2>
                <div class="mt-5 space-y-4">
                    <div>
                        <label for="name" class="label">Nama</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="input">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="label">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="input">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="label">No. HP</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="input">
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
                    <x-icon name="lock" class="h-4 w-4 text-forest-600" /> Ubah Password
                    <span class="badge bg-stone-100 text-stone-500">Opsional</span>
                </h2>
                <div class="mt-5 space-y-4">
                    <div>
                        <label for="current_password" class="label">Password Lama</label>
                        <div class="relative mt-1">
                            <input type="password" id="current_password" name="current_password" class="input pr-10 !mt-0" autocomplete="current-password" data-password-toggle>
                            <button type="button" data-toggle-password="current_password" class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 transition hover:text-stone-600" aria-label="Tampilkan password">
                                <x-icon name="eye" class="h-4 w-4" />
                            </button>
                        </div>
                        @error('current_password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="password" class="label">Password Baru</label>
                            <div class="relative mt-1">
                                <input type="password" id="password" name="password" class="input pr-10 !mt-0" autocomplete="new-password" data-password-toggle>
                                <button type="button" data-toggle-password="password" class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 transition hover:text-stone-600" aria-label="Tampilkan password">
                                    <x-icon name="eye" class="h-4 w-4" />
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="label">Konfirmasi Password Baru</label>
                            <div class="relative mt-1">
                                <input type="password" id="password_confirmation" name="password_confirmation" class="input pr-10 !mt-0" autocomplete="new-password" data-password-toggle>
                                <button type="button" data-toggle-password="password_confirmation" class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 transition hover:text-stone-600" aria-label="Tampilkan password">
                                    <x-icon name="eye" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-primary w-full !py-4 text-base lg:col-span-2">
                <x-icon name="check" class="h-5 w-5" /> Simpan Perubahan
            </button>
        </form>
    </div>
@endsection