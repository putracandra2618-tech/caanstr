@extends('layouts.app')
@section('title', $product->name)

@section('content')
    <div class="container-site py-8">
        <a href="{{ route('category', $product->category->slug) }}"
           class="inline-flex items-center gap-1 text-sm font-semibold text-forest-700 hover:text-forest-800">
            <x-icon name="chevron-left" class="h-4 w-4" /> {{ $product->category->name }}
        </a>

        <div class="mt-6 grid grid-cols-1 gap-8 lg:grid-cols-[1fr_1.1fr] lg:gap-12">
            {{-- Gambar --}}
            <div class="fade-in is-visible">
                <div class="overflow-hidden rounded-[1.75rem] border border-line bg-white shadow-soft">
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                             class="aspect-[4/3] w-full object-cover">
                    @else
                        <div class="flex aspect-[4/3] w-full items-center justify-center bg-gradient-to-br from-stone-100 to-stone-200">
                            <x-icon name="gamepad" class="h-24 w-24 text-stone-300" />
                        </div>
                    @endif
                </div>
            </div>

            {{-- Info & Form --}}
            <div class="fade-in is-visible lg:sticky lg:top-20 lg:self-start">
                <span class="badge bg-forest-50 text-forest-700">{{ $product->category->name }}</span>
                <h1 class="mt-3 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">{{ $product->name }}</h1>
                <p class="mt-4 font-display text-4xl font-bold tracking-tight text-forest-700">
                    Rp {{ number_format($product->price, 0, ',', '.') }}
                </p>

                @if($product->description)
                    <div class="mt-5 rounded-2xl border border-line bg-white p-4 text-sm leading-relaxed text-stone-600">
                        {!! nl2br(e($product->description)) !!}
                    </div>
                @endif

                <form action="{{ route('order.store') }}" method="POST" class="card mt-6 space-y-5 p-6">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    <div>
                        <label for="game_id" class="label">ID Game</label>
                        <input type="text" name="game_id" id="game_id" required placeholder="Masukkan ID game kamu"
                               class="input" value="{{ old('game_id') }}">
                        @error('game_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="game_zone" class="label">Zone/Server <span class="font-normal text-stone-400">(Opsional)</span></label>
                        <input type="text" name="game_zone" id="game_zone" placeholder="Contoh: 2233"
                               class="input" value="{{ old('game_zone') }}">
                    </div>

                    <div>
                        <label class="label">Jumlah</label>
                        <div class="mt-2 inline-flex items-center rounded-lg border border-stone-300 bg-white shadow-sm" data-qty-stepper>
                            <button type="button" data-qty-btn="-1"
                                    class="flex h-11 w-11 items-center justify-center rounded-l-lg text-stone-500 transition hover:bg-stone-100">
                                <x-icon name="minus" class="h-4 w-4" />
                            </button>
                            <input type="number" name="quantity" id="quantity" value="1" min="1" max="10" data-qty-input
                                   class="h-11 w-16 border-0 text-center text-base font-bold text-ink focus:outline-none focus:ring-0" readonly>
                            <button type="button" data-qty-btn="1"
                                    class="flex h-11 w-11 items-center justify-center rounded-r-lg text-stone-500 transition hover:bg-stone-100">
                                <x-icon name="plus" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="promo_code" class="label">Kode Promo <span class="font-normal text-stone-400">(Opsional)</span></label>
                        <input type="text" name="promo_code" id="promo_code" placeholder="Masukkan kode promo"
                               class="input" value="{{ old('promo_code') }}">
                    </div>

                    @guest
                        <a href="{{ route('login') }}"
                           class="btn-primary w-full !py-4 text-base">
                            Masuk untuk Membeli
                            <x-icon name="arrow-right" class="h-5 w-5" />
                        </a>
                    @else
                        <button type="submit" class="btn-primary w-full !py-4 text-base">
                            <x-icon name="bolt" class="h-5 w-5" /> Beli Sekarang
                        </button>
                    @endguest

                    <div class="flex items-center justify-center gap-4 border-t border-line pt-4 text-xs text-stone-500">
                        <span class="flex items-center gap-1.5"><x-icon name="bolt" class="h-4 w-4 text-forest-600" /> Instan</span>
                        <span class="flex items-center gap-1.5"><x-icon name="shield" class="h-4 w-4 text-forest-600" /> Aman</span>
                        <span class="flex items-center gap-1.5"><x-icon name="clock" class="h-4 w-4 text-forest-600" /> 24/7</span>
                    </div>
                </form>
            </div>
        </div>

        {{-- Produk Terkait --}}
        @if($relatedProducts->count())
            <div class="mt-14 fade-in">
                <p class="section-eyebrow">Lainnya</p>
                <h2 class="section-title mt-1">Produk Terkait</h2>
                <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($relatedProducts as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
