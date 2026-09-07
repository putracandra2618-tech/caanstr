<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Jobs\ProcessTopUpJob;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransService
{
    public function createSnapToken(Order $order): string
    {
        $serverKey = config('services.midtrans.server_key');

        if (blank($serverKey)) {
            throw new RuntimeException('Konfigurasi Midtrans server key belum tersedia.');
        }

        $grossAmount = (int) round((float) $order->total);

        if ($grossAmount < 1) {
            throw new RuntimeException('Total pembayaran harus lebih besar dari Rp0.');
        }

        try {
            $response = Http::acceptJson()
                ->withBasicAuth($serverKey, '')
                ->post(config('services.midtrans.snap_api_url'), [
                    'transaction_details' => [
                        'order_id' => $order->order_number,
                        'gross_amount' => $grossAmount,
                    ],
                    'item_details' => [[
                        'id' => (string) $order->product_id,
                        'price' => $grossAmount,
                        'quantity' => 1,
                        'name' => str($order->product->name)->limit(50, ''),
                    ]],
                    'customer_details' => [
                        'first_name' => $order->user->name,
                        'email' => $order->user->email,
                        'phone' => $order->user->phone,
                    ],
                ])
                ->throw();
        } catch (RequestException $exception) {
            report($exception);

            throw new RuntimeException('Gagal membuat pembayaran Midtrans. Silakan coba lagi.');
        }

        $token = $response->json('token');

        if (blank($token)) {
            throw new RuntimeException('Midtrans tidak mengembalikan token pembayaran.');
        }

        return $token;
    }

    public function getStatus(string $orderNumber): ?array
    {
        $serverKey = config('services.midtrans.server_key');

        if (blank($serverKey)) {
            throw new RuntimeException('Konfigurasi Midtrans server key belum tersedia.');
        }

        try {
            $response = Http::acceptJson()
                ->withBasicAuth($serverKey, '')
                ->get(config('services.midtrans.api_url')."/{$orderNumber}/status")
                ->throw();

            $statusCode = $response->json('status_code');

            if ((string) $statusCode === '404') {
                return null;
            }

            return $response->json();
        } catch (RequestException $exception) {
            report($exception);

            throw new RuntimeException('Gagal mengambil status pembayaran Midtrans.');
        }
    }

    public function applyStatus(Order $order, array $payload): OrderStatus
    {
        $transactionStatus = (string) ($payload['transaction_status'] ?? $payload['status_code'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');
        $orderId = (string) ($payload['order_id'] ?? $order->order_number);

        $status = match (true) {
            $transactionStatus === 'capture' && $fraudStatus === 'accept' => OrderStatus::Paid,
            $transactionStatus === 'settlement' => OrderStatus::Paid,
            $transactionStatus === 'pending' => OrderStatus::Pending,
            in_array($transactionStatus, ['deny', 'expire', 'cancel'], true) => OrderStatus::Failed,
            default => $order->status,
        };

        $progressed = in_array($order->status, [OrderStatus::Processing, OrderStatus::Completed], true);
        $alreadyPaid = $order->isPaid();

        if (! $progressed) {
            $order->update([
                'status' => $status,
                'payment_method' => $payload['payment_type'] ?? null,
                'paid_at' => $status === OrderStatus::Paid ? ($order->paid_at ?? now()) : null,
            ]);
        }

        Transaction::updateOrCreate(
            ['order_id' => $order->id],
            [
                'midtrans_order_id' => $payload['transaction_id'] ?? $orderId,
                'payment_type' => $payload['payment_type'] ?? null,
                'status' => $transactionStatus,
                'gross_amount' => $payload['gross_amount'] ?? $order->total,
                'va_number' => $payload['va_number'] ?? $payload['va_numbers'][0]['va_number'] ?? null,
                'fraud_status' => $fraudStatus ?: null,
                'raw_response' => $payload,
                'paid_at' => $status === OrderStatus::Paid ? ($order->paid_at ?? now()) : null,
            ]
        );

        if ($status === OrderStatus::Paid && ! ($alreadyPaid || $progressed)) {
            ProcessTopUpJob::dispatch($order->fresh());
        }

        return $status;
    }
}
