@extends('layouts.app')
@section('title', 'Pembayaran - ' . $order->order_number)

@section('content')
    <div class="container-site max-w-2xl py-8">
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink">Pembayaran</h1>
        <p class="mt-1 text-sm text-stone-500">Selesaikan pembayaran dalam 5 menit untuk melanjutkan pesanan.</p>

        <div class="card mt-6 overflow-hidden">
            <div class="border-b border-line bg-stone-50/70 px-6 py-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-forest-100 text-forest-700">
                        <x-icon name="wallet" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-ink">Rincian Pesanan</p>
                        <p class="text-xs text-stone-400">{{ $order->order_number }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-3 p-6 text-sm">
                <div class="flex justify-between">
                    <span class="text-stone-400">Produk</span>
                    <span class="font-semibold text-ink">{{ $order->product->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-stone-400">ID Game</span>
                    <span class="font-semibold text-ink">{{ $order->game_id }}</span>
                </div>
                @if($order->game_zone)
                    <div class="flex justify-between">
                        <span class="text-stone-400">Zone</span>
                        <span class="font-semibold text-ink">{{ $order->game_zone }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-stone-400">Jumlah</span>
                    <span class="font-semibold text-ink">x{{ $order->quantity }}</span>
                </div>
                @if($order->discount > 0)
                    <div class="flex justify-between text-forest-600">
                        <span>Diskon</span>
                        <span class="font-semibold">- Rp {{ number_format($order->discount, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between border-t border-dashed border-line pt-4">
                    <span class="text-base font-extrabold text-ink">Total Bayar</span>
                    <span class="font-display text-xl font-bold tracking-tight text-forest-700">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="border-t border-line p-6">
                <p class="mb-4 flex items-center gap-2 text-sm font-semibold text-ink">
                    <x-icon name="credit-card" class="h-5 w-5 text-forest-600" />
                    Pilih metode pembayaran
                </p>

                <form id="payment-form">
                    @csrf
                    <div id="snap-container"></div>
                </form>

                <div class="mt-5 space-y-2">
                    <a href="{{ route('order.detail', $order->order_number) }}" class="btn-secondary w-full">
                        <x-icon name="history" class="h-4 w-4" /> Lihat Status Pesanan
                    </a>
                    <a href="{{ route('home') }}" class="btn-ghost w-full text-stone-500">
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ config('services.midtrans.snap_js_url') }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var snapToken = @json($order->snap_token);
            if (snapToken) {
                window.snap.pay(snapToken, {
                    onSuccess: function(result) {
                        window.location.href = '{{ route("payment.success", $order->order_number) }}';
                    },
                    onPending: function(result) {
                        window.location.href = '{{ route("payment.success", $order->order_number) }}';
                    },
                    onError: function(result) {
                        window.location.href = '{{ route("payment.failed", $order->order_number) }}';
                    },
                    onClose: function() {
                    }
                });
            }
        });
    </script>
@endsection
