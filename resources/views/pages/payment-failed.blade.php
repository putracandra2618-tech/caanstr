@extends('layouts.app')
@section('title', 'Pembayaran Gagal')

@section('content')
    <div class="container-site flex max-w-xl flex-col items-center py-14">
        <span class="flex h-20 w-20 items-center justify-center rounded-full bg-red-50 ring-1 ring-red-200">
            <x-icon name="x-circle" class="h-10 w-10 text-red-500" />
        </span>
        <h1 class="mt-6 font-display text-2xl font-bold tracking-tight text-ink">Pembayaran Gagal</h1>
        <p class="mt-2 text-center text-sm text-stone-500">Pembayaran tidak berhasil diproses. Kamu bisa coba lagi dari detail pesanan.</p>

        <div class="card mt-8 w-full px-6 py-5">
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-stone-400">No. Pesanan</span>
                    <span class="font-semibold text-ink">{{ $order->order_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-stone-400">Status</span>
                    <span class="badge bg-red-50 text-red-600 ring-1 ring-red-200">
                        <x-icon name="x-circle" class="h-3.5 w-3.5" /> {{ $order->status->label() }}
                    </span>
                </div>
            </div>
        </div>

        <div class="mt-6 w-full space-y-2">
            <a href="{{ route('order.detail', $order->order_number) }}" class="btn-primary w-full">
                Coba Bayar Lagi
            </a>
            <a href="{{ route('home') }}" class="btn-ghost w-full text-stone-500">
                Kembali ke Beranda
            </a>
        </div>
    </div>
@endsection