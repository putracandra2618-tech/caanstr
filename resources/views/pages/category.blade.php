@extends('layouts.app')
@section('title', $category->name)

@section('content')
    <div class="container-site py-8 sm:py-10 lg:py-12">
        <div class="mb-8 overflow-hidden rounded-[1.75rem] border border-line bg-white shadow-soft sm:mb-10 fade-in is-visible">
            <div class="h-24 bg-gradient-to-br from-forest-100 via-forest-50 to-transparent"></div>
            <div class="px-6 pb-7 pt-6 sm:px-8">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-forest-700 hover:text-forest-800">
                    <x-icon name="chevron-left" class="h-4 w-4" /> Beranda
                </a>
                <div class="mt-4 flex items-center gap-4">
                    @if($category->image)
                        <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}"
                             class="h-14 w-14 rounded-2xl object-cover shadow-soft ring-1 ring-stone-100">
                    @else
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-forest-100 text-forest-700">
                            <x-icon name="gamepad" class="h-7 w-7" />
                        </span>
                    @endif
                    <div>
                        <h1 class="page-title">{{ $category->name }}</h1>
                        @if($category->description)
                            <p class="mt-1 text-sm text-stone-500">{{ $category->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($products->count())
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
                @foreach($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>

            @if(method_exists($products, 'links'))
                <div class="mt-8">{{ $products->links() }}</div>
            @endif
        @else
            <div class="card flex flex-col items-center px-6 py-16 text-center">
                <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-stone-100 text-stone-400">
                    <x-icon name="box" class="h-8 w-8" />
                </span>
                <p class="mt-4 font-semibold text-ink">Belum Ada Produk</p>
                <p class="mt-1 text-sm text-stone-500">Produk untuk kategori ini belum tersedia. Coba kategori lain dulu ya.</p>
                <a href="{{ route('home') }}" class="btn-primary mt-6">
                    Kembali ke Beranda
                </a>
            </div>
        @endif
    </div>
@endsection
