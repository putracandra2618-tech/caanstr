<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:expire-pending {--minutes=15 : Batas usia pesanan pending dalam menit}')]
#[Description('Tandai pesanan pending yang melewati batas waktu pembayaran sebagai failed')]
class ExpireOrdersCommand extends Command
{
    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');

        $expired = Order::query()
            ->where('status', OrderStatus::Pending)
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->get();

        $updated = 0;

        foreach ($expired as $order) {
            $order->update([
                'status' => OrderStatus::Failed,
                'failed_reason' => 'Pembayaran tidak selesai dalam batas waktu.',
            ]);

            $updated++;
        }

        $this->info("Expired {$updated} pending order(s).");

        return Command::SUCCESS;
    }
}
