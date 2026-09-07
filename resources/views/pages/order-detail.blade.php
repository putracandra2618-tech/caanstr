@extends('layouts.app')
@section('title', 'Detail Pesanan - ' . $order->order_number)

@section('content')
    <div class="container-site max-w-2xl py-8">
        <a href="{{ route('history') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-forest-700 hover:text-forest-800">
            <x-icon name="chevron-left" class="h-4 w-4" /> Riwayat
        </a>
        <h1 class="mt-4 font-display text-2xl font-bold tracking-tight text-ink">Detail Pesanan</h1>

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

        <div class="card mt-6 overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b border-line px-6 py-4">
                <div>
                    <p class="text-xs text-stone-400">No. Pesanan</p>
                    <p class="font-mono text-sm font-bold text-ink">{{ $order->order_number }}</p>
                </div>
                <span class="badge ring-1 {{ $statusColors[$order->status->value] ?? 'bg-stone-100 text-stone-500 ring-stone-200' }}">
                    <x-icon name="{{ $statusIcons[$order->status->value] ?? 'info' }}" class="h-3.5 w-3.5" />
                    {{ $order->status->label() }}
                </span>
            </div>

            <div class="space-y-3 px-6 py-5 text-sm">
                <div class="flex justify-between gap-4">
                    <span class="text-stone-400">Produk</span>
                    <span class="text-right font-semibold text-ink">{{ $order->product->name }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-stone-400">Kategori</span>
                    <span class="font-semibold text-ink">{{ $order->product->category->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-stone-400">ID Game</span>
                    <span class="font-mono font-semibold text-ink">{{ $order->game_id }}</span>
                </div>
                @if($order->game_zone)
                    <div class="flex justify-between gap-4">
                        <span class="text-stone-400">Zone</span>
                        <span class="font-semibold text-ink">{{ $order->game_zone }}</span>
                    </div>
                @endif
                <div class="flex justify-between gap-4">
                    <span class="text-stone-400">Jumlah</span>
                    <span class="font-semibold text-ink">x{{ $order->quantity }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-stone-400">Subtotal</span>
                    <span class="font-semibold text-ink">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($order->discount > 0)
                    <div class="flex justify-between gap-4 text-forest-600">
                        <span>Diskon</span>
                        <span class="font-semibold">- Rp {{ number_format($order->discount, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between border-t border-dashed border-line pt-4">
                    <span class="text-base font-bold text-ink">Total</span>
                    <span class="font-display text-xl font-bold tracking-tight text-forest-700">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Info Pembayaran --}}
            @if($order->transaction)
                <div class="border-t border-line px-6 py-5">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-ink">
                        <x-icon name="wallet" class="h-4 w-4 text-forest-600" /> Info Pembayaran
                    </h3>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-stone-400">Metode</span>
                            <span class="font-semibold capitalize text-ink">{{ str_replace('_', ' ', $order->transaction->payment_type ?? '-') }}</span>
                        </div>
                        @if($order->transaction->va_number)
                            <div class="flex items-center justify-between gap-4 rounded-xl border border-line bg-stone-50 px-3 py-2.5">
                                <div>
                                    <span class="text-stone-400">No. VA</span>
                                    <p class="font-mono font-bold text-ink">{{ $order->transaction->va_number }}</p>
                                </div>
                                <button type="button" onclick="copyText('{{ $order->transaction->va_number }}')"
                                        class="rounded-lg p-2 text-stone-400 transition hover:bg-stone-100 hover:text-forest-700" aria-label="Salin nomor VA">
                                    <x-icon name="copy" class="h-4 w-4" />
                                </button>
                            </div>
                        @endif
                        @if($order->paid_at)
                            <div class="flex justify-between gap-4">
                                <span class="text-stone-400">Dibayar</span>
                                <span class="font-semibold text-ink">{{ $order->paid_at->format('d M Y, H:i') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Status completion --}}
            @if($order->status->value === 'completed')
                <div class="flex items-start gap-3 border-t border-line bg-green-50 px-6 py-5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                        <x-icon name="check-circle" class="h-4 w-4" />
                    </span>
                    <div>
                        <h3 class="text-sm font-semibold text-green-700">Top Up Berhasil!</h3>
                        <p class="mt-1 text-sm text-green-700/80">Produk kamu sudah masuk ke akun game. Silakan cek di game kamu.</p>
                    </div>
                </div>
            @endif

            @if($order->failed_reason)
                <div class="flex items-start gap-3 border-t border-line bg-red-50 px-6 py-5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                        <x-icon name="x-circle" class="h-4 w-4" />
                    </span>
                    <div>
                        <h3 class="text-sm font-semibold text-red-700">Gagal</h3>
                        <p class="mt-1 text-sm text-red-700/80">{{ $order->failed_reason }}</p>
                    </div>
                </div>
            @endif

            <div class="px-6 py-5">
                <a href="{{ route('history') }}" class="btn-secondary w-full">
                    Lihat Semua Riwayat
                </a>
            </div>
        </div>
    </div>
@endsection
