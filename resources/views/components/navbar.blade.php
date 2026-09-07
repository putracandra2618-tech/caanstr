<nav class="sticky top-0 z-40 border-b border-line bg-white/75 backdrop-blur-lg">
    <div class="container-site flex h-16 items-center justify-between gap-4">
        {{-- Brand --}}
        <a href="{{ route('home') }}" class="group flex shrink-0 items-center gap-2.5">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-forest-700 text-white shadow-soft transition group-hover:bg-forest-800">
                <x-icon name="gamepad" class="h-5 w-5" />
            </span>
            <span class="font-display text-lg font-bold tracking-tight text-ink">{{ config('app.name') }}</span>
        </a>

        <div class="ml-auto flex items-center gap-3 sm:gap-4">
            {{-- Desktop navigation --}}
            <nav class="hidden items-center gap-1 md:flex">
                <a href="{{ route('home') }}"
                   class="rounded-lg px-3.5 py-2 font-sans text-[0.95rem] font-semibold transition {{ request()->routeIs('home') ? 'text-forest-700' : 'text-stone-600 hover:text-stone-900' }}">
                    Beranda
                </a>
                <a href="{{ route('history') }}"
                   class="rounded-lg px-3.5 py-2 font-sans text-[0.95rem] font-semibold transition {{ request()->routeIs('history', 'order.detail', 'order.payment', 'payment.success', 'payment.failed') ? 'text-forest-700' : 'text-stone-600 hover:text-stone-900' }}">
                    Riwayat
                </a>
            </nav>

            {{-- Right actions --}}
            <div class="flex items-center gap-2">
            @auth
                <div class="relative group hidden md:block">
                    <button class="flex cursor-pointer items-center gap-2 rounded-full border border-stone-200 bg-white py-1.5 pl-1.5 pr-3 text-sm font-semibold text-stone-700 transition hover:border-stone-300 hover:shadow-soft">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-forest-100 text-forest-700">
                            <x-icon name="user" class="h-4 w-4" />
                        </span>
                        <span class="max-w-[8rem] truncate">{{ auth()->user()->name }}</span>
                        <x-icon name="chevron-down" class="h-4 w-4 text-stone-400" />
                    </button>
                    <div class="absolute right-0 mt-2 w-48 overflow-hidden rounded-xl border border-stone-200 bg-white py-1 shadow-lift opacity-0 invisible group-hover:visible group-hover:opacity-100 transition">
                        <a href="{{ route('profile') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-stone-700 hover:bg-stone-50">
                            <x-icon name="user" class="h-4 w-4 text-stone-400" /> Profil
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                <x-icon name="logout" class="h-4 w-4" /> Logout
                            </button>
                        </form>
                    </div>
                </div>
                <a href="{{ route('profile') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-forest-100 text-forest-700 md:hidden">
                    <x-icon name="user" class="h-5 w-5" />
                </a>
            @else
                <a href="{{ route('login') }}" class="btn-ghost hidden sm:inline-flex">
                    Masuk
                </a>
                <a href="{{ route('register') }}" class="btn-primary hidden sm:inline-flex !py-2.5">
                    Daftar
                </a>
                <a href="{{ route('login') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-forest-700 text-white sm:hidden">
                    <x-icon name="user" class="h-5 w-5" />
                </a>
            @endauth
            </div>
        </div>
    </div>
</nav>

{{-- Mobile bottom nav --}}
<div class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-white/90 backdrop-blur-lg md:hidden pb-[env(safe-area-inset-bottom)]">
    <div class="grid grid-cols-3">
        <a href="{{ route('home') }}"
           class="flex flex-col items-center gap-1 py-3 font-sans text-xs font-medium {{ request()->routeIs('home') ? 'text-forest-700' : 'text-stone-400 hover:text-stone-600' }}">
            <x-icon name="home" class="h-5 w-5" />
            Beranda
        </a>
        <a href="{{ route('history') }}"
           class="flex flex-col items-center gap-1 py-3 font-sans text-xs font-medium {{ request()->routeIs('history', 'order.detail', 'order.payment', 'payment.success', 'payment.failed') ? 'text-forest-700' : 'text-stone-400 hover:text-stone-600' }}">
            <x-icon name="history" class="h-5 w-5" />
            Riwayat
        </a>
        @auth
            <a href="{{ route('profile') }}"
               class="flex flex-col items-center gap-1 py-3 font-sans text-xs font-medium {{ request()->routeIs('profile') ? 'text-forest-700' : 'text-stone-400 hover:text-stone-600' }}">
                <x-icon name="user" class="h-5 w-5" />
                Akun
            </a>
        @else
            <a href="{{ route('login') }}"
               class="flex flex-col items-center gap-1 py-3 font-sans text-xs font-medium {{ request()->routeIs('login') ? 'text-forest-700' : 'text-stone-400 hover:text-stone-600' }}">
                <x-icon name="user" class="h-5 w-5" />
                Masuk
            </a>
        @endauth
    </div>
</div>
