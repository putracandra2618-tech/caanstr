@extends('layouts.app')
@section('title', 'Status Pembayaran')

@section('content')
    <div class="container-site flex max-w-xl flex-col items-center py-14">
        <span class="flex h-20 w-20 items-center justify-center rounded-full bg-stone-100 ring-1 ring-stone-200">
            <x-icon name="check-circle" class="h-10 w-10 text-stone-500" />
        </span>
        <h1 class="mt-6 font-display text-2xl font-bold tracking-tight text-ink">Pembayaran Diterima</h1>
        <p class="mt-2 text-center text-sm text-stone-500">
            Midtrans mengonfirmasi pembayaran kamu. Status pesanan masih kami perbarui otomatis — cek kembali dalam beberapa saat.
        </p>

        <div class="card mt-8 w-full px-6 py-5">
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-stone-400">No. Pesanan</span>
                    <span class="font-semibold text-ink">{{ $order->order_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-stone-400">Status</span>
                    <span class="badge {{ match ($order->status->value) {
                        'paid' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
                        'processing' => 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200',
                        'completed' => 'bg-green-50 text-green-700 ring-1 ring-green-200',
                        'failed' => 'bg-red-50 text-red-700 ring-1 ring-red-200',
                        default => 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200',
                    } }}">
                        <x-icon name="check" class="h-3.5 w-3.5" /> {{ $order->status->label() }}
                    </span>
                </div>
            </div>
        </div>

        <p class="mt-4 text-center text-xs text-stone-400">
            Status diperbarui otomatis oleh sistem. Jika masih "Menunggu Pembayaran", coba muat ulang detail pesanan.
        </p>

        <div class="mt-6 w-full space-y-2">
            <a href="{{ route('order.detail', $order->order_number) }}" class="btn-primary w-full">
                <x-icon name="history" class="h-4 w-4" /> Lihat Detail Pesanan
            </a>
            <a href="{{ route('home') }}" class="btn-ghost w-full text-stone-500">
                Kembali ke Beranda
            </a>
        </div>
    </div>
@endsection