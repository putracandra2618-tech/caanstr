<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('midtrans:simulate-settlement {order : Nomor pesanan} {--status=settlement : Status yang disimulasikan (settlement|expire|deny)}')]
#[Description('Simulasikan notifikasi pembayaran Midtrans ke pesanan pending (khusus sandbox/dev)')]
class SimulateMidtransSettlement extends Command
{
    public function handle(MidtransService $midtrans): int
    {
        if (config('services.midtrans.is_production')) {
            $this->error('Perintah ini hanya untuk sandbox. MIDTRANS_IS_PRODUCTION=true.');

            return Command::FAILURE;
        }

        $order = Order::where('order_number', $this->argument('order'))->first();

        if (! $order) {
            $this->error("Order {$this->argument('order')} tidak ditemukan.");

            return Command::FAILURE;
        }

        if ($order->status !== OrderStatus::Pending) {
            $this->warn("Order {$order->order_number} berstatus {$order->status->value}, dilewati.");

            return Command::FAILURE;
        }

        $transactionStatus = $this->option('status');
        $statusCode = match ($transactionStatus) {
            'capture' => '200',
            'expire' => '201',
            'deny' => '202',
            default => '200',
        };
        $transactionStatus = $transactionStatus === 'settlement' ? 'settlement' : $transactionStatus;

        $grossAmount = number_format((float) $order->total, 2, '.', '');
        $serverKey = (string) config('services.midtrans.server_key');

        $payload = [
            'order_id' => $order->order_number,
            'transaction_status' => $transactionStatus,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'currency' => 'IDR',
            'payment_type' => 'qris',
            'signature_key' => hash('sha512', $order->order_number.$statusCode.$grossAmount.$serverKey),
            'fraud_status' => 'accept',
        ];

        $status = $midtrans->applyStatus($order, $payload);

        $this->info("Order {$order->order_number} disimulasikan sebagai {$transactionStatus} → status pesanan: {$status->value}.");

        return Command::SUCCESS;
    }
}
