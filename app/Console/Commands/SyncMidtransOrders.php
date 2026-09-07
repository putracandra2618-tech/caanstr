<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:sync-midtrans {--order= : Nomor pesanan tertentu (opsional)}')]
#[Description('Sinkronkan status pembayaran Midtrans ke pesanan pending (fallback jika webhook tidak sampai)')]
class SyncMidtransOrders extends Command
{
    public function handle(MidtransService $midtrans): int
    {
        $orders = $this->option('order')
            ? Order::where('order_number', $this->option('order'))->get()
            : Order::query()
                ->where('status', OrderStatus::Pending)
                ->whereNotNull('snap_token')
                ->get();

        $checked = 0;
        $paid = 0;
        $failed = 0;

        foreach ($orders as $order) {
            try {
                $status = $midtrans->getStatus($order->order_number);
            } catch (\RuntimeException $exception) {
                $this->error("Gagal cek {$order->order_number}: {$exception->getMessage()}");

                continue;
            }

            $checked++;

            if ($status === null) {
                $this->warn("{$order->order_number}: transaksi tidak ditemukan di Midtrans");

                continue;
            }

            $orderStatus = $midtrans->applyStatus($order, $status);

            if ($orderStatus === OrderStatus::Paid && $order->wasChanged('status')) {
                $paid++;
            } elseif ($orderStatus === OrderStatus::Failed && $order->wasChanged('status')) {
                $failed++;
                $this->warn("{$order->order_number}: ditandai {$orderStatus->value} oleh Midtrans");
            }
        }

        $this->info("Sinkronisasi selesai. Diperiksa: {$checked}, menjadi paid: {$paid}, menjadi failed: {$failed}.");

        return Command::SUCCESS;
    }
}
