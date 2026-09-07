<a href="{{ route('product.detail', $product->slug) }}"
   class="group flex h-full flex-col overflow-hidden rounded-2xl border border-line bg-white transition duration-200 hover:-translate-y-1 hover:border-forest-300 hover:shadow-soft">
    <div class="relative aspect-[4/3] overflow-hidden bg-stone-100">
        @if($product->image)
            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.05]">
        @else
            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-stone-100 to-stone-200">
                <x-icon name="gamepad" class="h-12 w-12 text-stone-300" />
            </div>
        @endif
        @if($product->category)
            <span class="absolute left-3 top-3 badge bg-white/95 text-forest-700 shadow-soft">
                {{ $product->category->name }}
            </span>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-4 sm:p-5">
        <h3 class="line-clamp-2 text-sm font-semibold leading-snug text-ink sm:text-base">{{ $product->name }}</h3>
        <div class="mt-auto flex items-end justify-between gap-2 pt-4">
            <span class="text-lg font-bold tracking-tight text-forest-700">
                Rp {{ number_format($product->price, 0, ',', '.') }}
            </span>
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-stone-100 text-stone-500 transition group-hover:bg-forest-700 group-hover:text-white">
                <x-icon name="arrow-right" class="h-4 w-4" />
            </span>
        </div>
    </div>
</a>
