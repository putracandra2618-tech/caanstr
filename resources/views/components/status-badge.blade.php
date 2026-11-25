@props(['status'])

@php
    $colorMap = [
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'paid' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'processing' => 'bg-forest-50 text-forest-700 ring-forest-200',
        'completed' => 'bg-green-50 text-green-700 ring-green-200',
        'failed' => 'bg-red-50 text-red-600 ring-red-200',
        'refunded' => 'bg-stone-100 text-stone-500 ring-stone-200',
    ];
    $iconMap = [
        'pending' => 'clock',
        'paid' => 'wallet',
        'processing' => 'sync',
        'completed' => 'check-circle',
        'failed' => 'x-circle',
        'refunded' => 'refresh',
    ];
@endphp

<span class="badge ring-1 {{ $colorMap[$status->value] ?? 'bg-stone-100 text-stone-500 ring-stone-200' }}">
    <x-icon name="{{ $iconMap[$status->value] ?? 'info' }}" class="h-3.5 w-3.5" />
    {{ $status->label() }}
</span>