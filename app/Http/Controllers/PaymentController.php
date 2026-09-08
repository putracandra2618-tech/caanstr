<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\AutoTopupLog;
use App\Models\Order;
use App\Services\MidtransService;
use App\Services\TokovoucherService;
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
                    $order->refresh();
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

        if (! $this->midtransAmountMatches($order, $payload)) {
            Log::warning('Midtrans callback amount mismatch', [
                'order_id' => $orderId,
                'expected' => $order->total,
                'received' => $payload['gross_amount'] ?? null,
            ]);

            return response()->json(['status' => 'amount_mismatch'], 422);
        }

        $midtrans->applyStatus($order, $payload);

        return response()->json(['status' => 'ok']);
    }

    public function tokovoucherCallback(Request $request, TokovoucherService $tokovoucher)
    {
        $payload = $request->all();

        Log::info('Tokovoucher callback received', $payload);

        $refId = (string) ($payload['ref_id'] ?? '');
        $status = strtolower((string) ($payload['status'] ?? ''));
        $sn = (string) ($payload['sn'] ?? '');

        $providedSignature = (string) $request->header('X-TokoVoucher-Authorization', '');

        if (! $tokovoucher->verifyWebhookSignature($refId, $providedSignature)) {
            Log::warning('Tokovoucher callback signature mismatch', ['ref_id' => $refId]);

            return response()->json(['status' => 'invalid_signature'], 403);
        }

        $order = Order::where('order_number', $refId)->first();

        if (! $order) {
            return response()->json(['status' => 'order_not_found'], 404);
        }

        $topupLogs = AutoTopupLog::where('order_id', $order->id)
            ->where('provider', 'tokovoucher')
            ->get();
        $topupLog = $topupLogs->first(fn (AutoTopupLog $log): bool => data_get($log->request_data, 'ref_id') === $refId);

        if (! $topupLog && $topupLogs->count() === 1 && $topupLogs->first()->status === 'pending') {
            $topupLog = $topupLogs->first();
        }

        if (! $topupLog && ! in_array($order->status, [OrderStatus::Completed, OrderStatus::Refunded], true)) {
            Log::warning('Tokovoucher callback has no matching top-up log', ['ref_id' => $refId]);

            return response()->json(['status' => 'topup_not_found'], 404);
        }

        $orderStatus = match ($status) {
            'sukses' => OrderStatus::Completed,
            'gagal' => OrderStatus::Failed,
            default => null,
        };

        $locked = in_array($order->status, [OrderStatus::Completed, OrderStatus::Refunded], true)
            || ($order->status === OrderStatus::Paid && $orderStatus === OrderStatus::Failed);

        if (! $locked && $orderStatus !== null) {
            $order->update([
                'status' => $orderStatus,
                'completed_at' => $orderStatus === OrderStatus::Completed ? now() : null,
                'failed_reason' => $status === 'gagal' ? ($payload['message'] ?? 'Top up gagal') : null,
            ]);
        }

        $topupLog?->update([
            'response_data' => $payload,
            'status' => $status === 'sukses' ? 'success' : ($status === 'gagal' ? 'failed' : 'pending'),
            'processed_at' => in_array($status, ['sukses', 'gagal'], true) ? now() : null,
            'error_message' => $status === 'gagal' ? ($payload['message'] ?? 'Top up gagal') : null,
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

    protected function midtransAmountMatches(Order $order, array $payload): bool
    {
        $grossAmount = $payload['gross_amount'] ?? null;

        if (! is_numeric($grossAmount)) {
            return false;
        }

        return number_format((float) $order->total, 2, '.', '') === number_format((float) $grossAmount, 2, '.', '');
    }
}
