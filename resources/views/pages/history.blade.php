@extends('layouts.app')
@section('title', 'Riwayat Transaksi')

@section('content')
    <div class="container-site max-w-5xl py-8 sm:py-10 lg:py-12">
        <div class="flex flex-col gap-3 border-b border-line pb-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-eyebrow">Pesananmu</p>
                <h1 class="page-title mt-1">Riwayat Transaksi</h1>
                <p class="page-subtitle">Semua pesanan top up kamu dalam satu tempat.</p>
            </div>
            <a href="{{ route('home') }}#kategori" class="btn-secondary w-full sm:w-auto">
                <x-icon name="gamepad" class="h-4 w-4" /> Top Up Lagi
            </a>
        </div>

        @if($orders->count())
            <div class="mt-6" data-paginate>
                <div class="grid gap-3 lg:grid-cols-2">
                    @foreach($orders as $order)
                    @php
                        $statusColors = [
                            'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
                            'paid' => 'bg-blue-50 text-blue-700 ring-blue-200',
                            'processing' => 'bg-forest-50 text-forest-700 ring-forest-200',
                            'completed' => 'bg-green-50 text-green-700 ring-green-200',
                            'failed' => 'bg-red-50 text-red-600 ring-red-200',
                            'refunded' => 'bg-stone-100 text-stone-500 ring-stone-200',
                        ];
                        $statusIcons = [
                            'pending' => 'clock', 'paid' => 'wallet', 'processing' => 'sync',
                            'completed' => 'check-circle', 'failed' => 'x-circle', 'refunded' => 'refresh',
                        ];
                    @endphp
                    <a href="{{ route('order.detail', $order->order_number) }}"
                       class="relative flex items-center justify-between gap-4 overflow-hidden rounded-2xl border border-line bg-white p-4 shadow-soft transition hover:-translate-y-0.5 hover:border-forest-300 hover:shadow-lift">
                        <div class="flex min-w-0 items-center gap-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-forest-50 text-forest-600">
                                <x-icon name="gamepad" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-ink">{{ $order->product->name }}</p>
                                <p class="mt-0.5 text-xs text-stone-400">{{ $order->order_number }}</p>
                                <p class="mt-0.5 text-xs text-stone-400">{{ $order->created_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-bold text-ink">Rp {{ number_format($order->total, 0, ',', '.') }}</p>
                            <span class="badge mt-1.5 ring-1 {{ $statusColors[$order->status->value] ?? 'bg-stone-100 text-stone-500 ring-stone-200' }}">
                                <x-icon name="{{ $statusIcons[$order->status->value] ?? 'info' }}" class="h-3.5 w-3.5" />
                                {{ $order->status->label() }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

                @if(method_exists($orders, 'links'))
                    <div class="mt-8" data-paginate-nav>{{ $orders->links() }}</div>
                @endif
            </div>
        @else
            <div class="card mt-6 flex flex-col items-center px-6 py-16 text-center">
                <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-stone-100 text-stone-400">
                    <x-icon name="history" class="h-8 w-8" />
                </span>
                <p class="mt-4 font-semibold text-ink">Belum Ada Transaksi</p>
                <p class="mt-1 text-sm text-stone-500">Yuk mulai top up game favoritmu sekarang.</p>
                <a href="{{ route('home') }}" class="btn-primary mt-6">
                    Mulai Belanja <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>
        @endif
    </div>
@endsection
