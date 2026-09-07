@extends('layouts.app')
@section('title', 'Beranda')

@section('content')
    {{-- Hero --}}
    @if($banners->count())
        @php $banner = $banners->first(); @endphp
        <section class="container-site mt-6 sm:mt-8 fade-in is-visible">
            <div class="relative overflow-hidden rounded-[1.75rem] border border-line bg-white shadow-soft">
                @if($banner->image)
                    <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title }}"
                         class="absolute inset-0 h-full w-full object-cover object-[center_60%]">
                    <div class="absolute inset-0 bg-gradient-to-t from-white/85 via-white/30 to-transparent"></div>
                @else
                    <div class="absolute inset-0 bg-gradient-to-br from-forest-100 via-forest-50 to-white"></div>
                @endif

                @if($banner->title)
                    <div class="relative z-10 flex min-h-[16rem] flex-col justify-end sm:min-h-[19rem]">
                        <div class="max-w-2xl p-7 sm:p-10">
                            <span class="badge bg-forest-700 text-white">
                                <x-icon name="bolt" class="h-3.5 w-3.5" /> Top Up Instan
                            </span>
                            <h1 class="mt-4 font-display text-3xl font-bold leading-tight tracking-tight text-ink sm:text-4xl">
                                {{ $banner->title }}
                            </h1>
                            @if($banner->description ?? null)
                                <p class="mt-3 max-w-lg text-sm text-stone-700 sm:text-base">{{ $banner->description }}</p>
                            @endif
                            <div class="mt-6 flex flex-wrap gap-3">
                                <a href="#kategori" class="btn-primary">
                                    Mulai Top Up <x-icon name="arrow-right" class="h-4 w-4" />
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                @if($banner->url)
                    <a href="{{ $banner->url }}" class="absolute inset-0 z-20" aria-label="{{ $banner->title }}"></a>
                @endif
            </div>

            {{-- Trust bar --}}
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="flex items-center gap-3 rounded-xl border border-line bg-white p-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-forest-100 text-forest-700">
                        <x-icon name="bolt" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-sm font-bold text-ink">Proses Instan</p>
                        <p class="text-xs text-stone-500">Saldo masuk otomatis</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border border-line bg-white p-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-forest-100 text-forest-700">
                        <x-icon name="shield" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-sm font-bold text-ink">100% Aman</p>
                        <p class="text-xs text-stone-500">Pembayaran terenkripsi</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border border-line bg-white p-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-forest-100 text-forest-700">
                        <x-icon name="clock" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-sm font-bold text-ink">Dukungan 24/7</p>
                        <p class="text-xs text-stone-500">Selalu siap membantu</p>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Produk Populer --}}
    <section class="container-site mt-14 sm:mt-16 fade-in">
        <div class="flex items-end justify-between">
            <div>
                <p class="section-eyebrow">Koleksi Pilihan</p>
                <h2 class="section-title mt-1">Produk Populer</h2>
                <p class="section-subtitle">Paling banyak dibeli pelanggan hari ini.</p>
            </div>
            <a href="#kategori" class="hidden items-center gap-1 text-sm font-semibold text-forest-700 hover:text-forest-800 sm:inline-flex">
                Lihat Semua <x-icon name="chevron-right" class="h-4 w-4" />
            </a>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
            @foreach($popularProducts as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    </section>

    {{-- Kategori --}}
    <section id="kategori" class="container-site mt-14 sm:mt-16 fade-in">
        <div class="flex items-end justify-between">
            <div>
                <p class="section-eyebrow">Katalog Game</p>
                <h2 class="section-title mt-1">Pilih Game</h2>
                <p class="section-subtitle">Pilih game favoritmu, langsung isi saldonya.</p>
            </div>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-7 2xl:grid-cols-8">
            @foreach($categories as $category)
                <a href="{{ route('category', $category->slug) }}"
                   class="group flex flex-col items-center rounded-2xl border border-line bg-white p-4 pb-5 text-center transition hover:-translate-y-1 hover:border-forest-300 hover:shadow-soft">
                    @if($category->image)
                        <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}"
                             class="h-14 w-14 rounded-xl object-cover ring-1 ring-stone-100">
                    @else
                        <span class="flex h-14 w-14 items-center justify-center rounded-xl bg-forest-100 text-forest-700 transition group-hover:bg-forest-700 group-hover:text-white">
                            <x-icon name="gamepad" class="h-7 w-7" />
                        </span>
                    @endif
                    <h3 class="mt-3 line-clamp-1 text-sm font-semibold text-ink">{{ $category->name }}</h3>
                    <p class="mt-0.5 text-xs text-stone-400">{{ $category->products_count }} produk</p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Cara Top Up --}}
    <section class="container-site mt-16 fade-in">
        <div class="rounded-[1.75rem] border border-line bg-white p-6 sm:p-10 lg:p-12">
            <div class="text-center">
                <p class="section-eyebrow">Cara Kerja</p>
                <h2 class="section-title mt-1">Cara Top Up</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-stone-500">Hanya 3 langkah, saldo langsung masuk ke akunmu.</p>
            </div>
            <div class="mt-10 grid grid-cols-1 gap-8 md:grid-cols-3 md:gap-0 md:divide-x md:divide-line">
                <div class="flex flex-col items-start gap-4 md:items-center md:px-8 md:text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-forest-700 font-display text-lg font-bold text-white">1</span>
                    <div class="md:max-w-[14rem]">
                        <h3 class="text-base font-bold text-ink">Pilih Produk</h3>
                        <p class="mt-1 text-sm text-stone-500">Cari game dan nominal yang kamu mau.</p>
                    </div>
                </div>
                <div class="flex flex-col items-start gap-4 md:items-center md:px-8 md:text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-forest-700 font-display text-lg font-bold text-white">2</span>
                    <div class="md:max-w-[14rem]">
                        <h3 class="text-base font-bold text-ink">Masukkan ID &amp; Bayar</h3>
                        <p class="mt-1 text-sm text-stone-500">Input ID game, lalu bayar dengan metode favoritmu.</p>
                    </div>
                </div>
                <div class="flex flex-col items-start gap-4 md:items-center md:px-8 md:text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-forest-700 font-display text-lg font-bold text-white">3</span>
                    <div class="md:max-w-[14rem]">
                        <h3 class="text-base font-bold text-ink">Saldo Masuk Otomatis</h3>
                        <p class="mt-1 text-sm text-stone-500">Top up terproses otomatis dan saldo langsung masuk.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
