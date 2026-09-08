<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\AutoTopupLog;
use App\Models\Order;
use App\Services\TokovoucherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTopUpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public Order $order
    ) {}

    public function handle(TokovoucherService $tokovoucher): void
    {
        if ($this->order->status !== OrderStatus::Paid) {
            return;
        }

        $this->order->update(['status' => OrderStatus::Processing]);

        $customerNo = $this->order->game_id;
        $customerNo .= filled($this->order->game_zone) ? '|'.$this->order->game_zone : '';

        $log = AutoTopupLog::create([
            'order_id' => $this->order->id,
            'provider' => 'tokovoucher',
            'request_data' => [
                'sku' => $this->order->product->product_code,
                'customer_no' => $customerNo,
                'zone' => $this->order->game_zone,
                'ref_id' => $this->order->order_number,
            ],
            'status' => 'pending',
            'attempts' => 0,
        ]);

        $result = $tokovoucher->topUp(
            $this->order->product->product_code,
            $this->order->game_id,
            $this->order->game_zone,
            $this->order->order_number
        );

        $log->update([
            'response_data' => $result,
            'status' => ($result['pending'] ?? false) ? 'pending' : ($result['success'] ? 'success' : 'failed'),
            'attempts' => $log->attempts + 1,
            'processed_at' => ($result['pending'] ?? false) ? null : now(),
            'error_message' => ($result['success'] || ($result['pending'] ?? false)) ? null : $result['message'],
        ]);

        if ($result['pending'] ?? false) {
            Log::info('TopUp pending provider callback', [
                'order' => $this->order->order_number,
            ]);

            return;
        }

        if ($result['success']) {
            $this->order->update([
                'status' => OrderStatus::Completed,
                'completed_at' => now(),
            ]);

            Log::info('TopUp completed', [
                'order' => $this->order->order_number,
                'sn' => $result['data']['sn'] ?? null,
            ]);
        } else {
            $this->order->update([
                'status' => OrderStatus::Failed,
                'failed_reason' => $result['message'],
            ]);

            Log::warning('TopUp failed', [
                'order' => $this->order->order_number,
                'message' => $result['message'],
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->order->update([
            'status' => OrderStatus::Failed,
            'failed_reason' => 'System error: '.$exception->getMessage(),
        ]);

        Log::error('TopUp job failed', [
            'order' => $this->order->order_number,
            'error' => $exception->getMessage(),
        ]);
    }
}
