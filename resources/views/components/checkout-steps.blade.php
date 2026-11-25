@props(['current' => 1])

@php
    $steps = ['Detail Pesanan', 'Pembayaran', 'Selesai'];
@endphp

<ol class="flex w-full items-center">
    @foreach ($steps as $number => $label)
        @if ($number > 1)
            <span class="mx-2 h-px flex-1 sm:mx-3 {{ $number <= $current ? 'bg-forest-600' : 'bg-line' }}"></span>
        @endif
        <li class="flex flex-col items-center gap-1.5">
            <span class="flex h-8 w-8 items-center justify-center rounded-full font-display text-sm font-bold transition {{ $number < $current ? 'bg-forest-600 text-white' : ($number === $current ? 'bg-forest-700 text-white shadow-soft' : 'bg-stone-100 text-stone-400') }}">
                @if ($number < $current)
                    <x-icon name="check" class="h-4 w-4" />
                @else
                    {{ $number }}
                @endif
            </span>
            <span class="whitespace-nowrap text-[0.7rem] font-semibold {{ $number <= $current ? 'text-forest-700' : 'text-stone-400' }} sm:text-xs">
                {{ $label }}
            </span>
        </li>
    @endforeach
</ol>