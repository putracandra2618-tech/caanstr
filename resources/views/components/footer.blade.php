<footer class="mt-20 border-t border-line bg-white">
    <div class="container-site grid grid-cols-1 gap-12 py-14 sm:grid-cols-2 lg:grid-cols-4 lg:py-16">
        <div class="lg:col-span-2">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-forest-700 text-white">
                    <x-icon name="gamepad" class="h-5 w-5" />
                </span>
                <span class="font-display text-lg font-bold tracking-tight text-ink">{{ config('app.name') }}</span>
            </a>
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-stone-500">
                Top up game favoritmu dengan mudah, cepat, dan aman. Proses instan, harga bersahabat, dan dukungan pelanggan 24/7.
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
                <span class="badge bg-forest-50 text-forest-700"><x-icon name="bolt" class="h-3.5 w-3.5" /> Proses Instan</span>
                <span class="badge bg-forest-50 text-forest-700"><x-icon name="shield" class="h-3.5 w-3.5" /> Pembayaran Aman</span>
                <span class="badge bg-forest-50 text-forest-700"><x-icon name="clock" class="h-3.5 w-3.5" /> 24/7</span>
            </div>
        </div>

        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-stone-400">Menu</h4>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ route('home') }}" class="text-stone-600 transition hover:text-forest-700">Beranda</a></li>
                @auth
                    <li><a href="{{ route('history') }}" class="text-stone-600 transition hover:text-forest-700">Riwayat Transaksi</a></li>
                    <li><a href="{{ route('profile') }}" class="text-stone-600 transition hover:text-forest-700">Profil</a></li>
                @else
                    <li><a href="{{ route('login') }}" class="text-stone-600 transition hover:text-forest-700">Masuk</a></li>
                    <li><a href="{{ route('register') }}" class="text-stone-600 transition hover:text-forest-700">Daftar</a></li>
                @endauth
            </ul>
        </div>

        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-stone-400">Kontak</h4>
            <ul class="mt-4 space-y-2.5 text-sm text-stone-600">
                <li class="flex items-center gap-2"><x-icon name="mail" class="h-4 w-4 text-stone-400" /> support@gametopup.com</li>
                <li class="flex items-center gap-2"><x-icon name="phone" class="h-4 w-4 text-stone-400" /> +62 812 3456 7890</li>
            </ul>
        </div>
    </div>
    <div class="border-t border-line bg-stone-50/60">
        <div class="container-site flex flex-col items-center justify-between gap-2 py-5 text-sm text-stone-400 sm:flex-row">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
            <span class="flex items-center gap-1.5"><x-icon name="shield" class="h-4 w-4" /> Pembayaran aman via Midtrans</span>
        </div>
    </div>
</footer>
