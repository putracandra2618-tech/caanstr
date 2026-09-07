<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\AutoTopupLog;
use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function success(string $orderNumber, MidtransService $midtrans)
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', auth()->id())
            ->with('product.category', 'transaction')
            ->firstOrFail();

        if ($order->isPending()) {
            try {
                $status = $midtrans->getStatus($order->order_number);

                if ($status !== null) {
                    $midtrans->applyStatus($order, $status);
                }
            } catch (\RuntimeException $exception) {
                Log::warning('Gagal verifikasi status pembayaran di halaman sukses', [
                    'order' => $order->order_number,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return view('pages.payment-success', compact('order'));
    }

    public function failed(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', auth()->id())
            ->with('product.category')
            ->firstOrFail();

        return view('pages.payment-failed', compact('order'));
    }

    public function callback(Request $request, MidtransService $midtrans)
    {
        $payload = $request->all();

        Log::info('Midtrans callback received', $payload);

        $orderId = $payload['order_id'] ?? '';

        if (! $this->midtransSignatureValid($payload)) {
            Log::warning('Midtrans callback signature mismatch', ['order_id' => $orderId]);

            return response()->json(['status' => 'invalid_signature'], 403);
        }

        $order = Order::where('order_number', $orderId)->first();

        if (! $order) {
            Log::warning('Order not found for callback', ['order_id' => $orderId]);

            return response()->json(['status' => 'order_not_found'], 404);
        }

        $midtrans->applyStatus($order, $payload);

        return response()->json(['status' => 'ok']);
    }

    public function digiflazzCallback(Request $request)
    {
        $payload = $request->all();

        Log::info('DigiFlazz callback received', $payload);

        $refId = $payload['ref_id'] ?? '';
        $status = $payload['status'] ?? '';
        $sn = $payload['sn'] ?? '';

        if (! $this->digiflazzSignatureValid($request)) {
            Log::warning('DigiFlazz callback signature mismatch', ['ref_id' => $refId]);

            return response()->json(['status' => 'invalid_signature'], 403);
        }

        $order = Order::where('order_number', $refId)->first();

        if (! $order) {
            return response()->json(['status' => 'order_not_found'], 404);
        }

        $orderStatus = match ($status) {
            'Sukses' => OrderStatus::Completed,
            'Gagal' => OrderStatus::Failed,
            default => $order->status,
        };

        $locked = in_array($order->status, [OrderStatus::Completed, OrderStatus::Refunded], true);

        if (! $locked) {
            $order->update([
                'status' => $orderStatus,
                'completed_at' => $orderStatus === OrderStatus::Completed ? now() : null,
                'failed_reason' => $status === 'Gagal' ? ($payload['message'] ?? 'Top up gagal') : null,
            ]);
        }

        AutoTopupLog::where('order_id', $order->id)
            ->where('provider', 'digiflazz')
            ->latest('id')
            ->first()?->update([
                'response_data' => $payload,
                'status' => $status === 'Sukses' ? 'success' : ($status === 'Gagal' ? 'failed' : 'pending'),
                'processed_at' => in_array($status, ['Sukses', 'Gagal'], true) ? now() : null,
                'error_message' => $status === 'Gagal' ? ($payload['message'] ?? 'Top up gagal') : null,
            ]);

        return response()->json(['status' => 'ok']);
    }

    protected function midtransSignatureValid(array $payload): bool
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if (blank($serverKey)) {
            return false;
        }

        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');

        if (! $orderId || ! $statusCode || ! $grossAmount || ! $signatureKey) {
            return false;
        }

        return hash_equals(
            $signatureKey,
            hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey)
        );
    }

    protected function digiflazzSignatureValid(Request $request): bool
    {
        $secret = (string) config('digiflazz.webhook_secret');

        if (blank($secret)) {
            return false;
        }

        $header = (string) $request->header('X-Hub-Signature', '');

        if (! str_starts_with($header, 'sha1=')) {
            return false;
        }

        $provided = substr($header, 5);
        $expected = hash_hmac('sha1', $request->getContent(), $secret);

        return hash_equals($expected, $provided);
    }
}
