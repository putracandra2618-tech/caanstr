<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\AutoTopupLog;
use App\Services\TokovoucherService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:sync-tokovoucher {--order= : Nomor pesanan tertentu (opsional)}')]
#[Description('Sinkronkan status top-up Tokovoucher yang masih pending (fallback jika callback provider tidak sampai)')]
class SyncTokovoucherPending extends Command
{
    public function handle(TokovoucherService $tokovoucher): int
    {
        $logs = AutoTopupLog::query()
            ->where('provider', 'tokovoucher')
            ->where('status', 'pending')
            ->when($this->option('order'), function ($query, $order) {
                $query->where('request_data->ref_id', $order);
            })
            ->whereHas('order', fn ($query) => $query->where('status', OrderStatus::Processing))
            ->get();

        $checked = 0;
        $completed = 0;
        $failed = 0;

        foreach ($logs as $log) {
            $order = $log->order;
            $result = $tokovoucher->checkStatus($order->order_number);

            if (($result['pending'] ?? false) || ($result['status'] ?? '') === 'Unknown') {
                $this->warn("{$order->order_number}: masih pending/tidak diketahui di provider.");

                continue;
            }

            $checked++;

            $log->update([
                'response_data' => $result,
                'status' => ($result['success'] ?? false) ? 'success' : 'failed',
                'processed_at' => now(),
                'error_message' => ($result['success'] ?? false) ? null : ($result['message'] ?? 'Top up gagal atau status tidak diketahui'),
            ]);

            if ($result['success'] ?? false) {
                $order->update([
                    'status' => OrderStatus::Completed,
                    'completed_at' => now(),
                ]);

                $completed++;
            } else {
                $order->update([
                    'status' => OrderStatus::Failed,
                    'failed_reason' => $result['message'] ?? 'Top up gagal atau status tidak diketahui',
                ]);

                $failed++;
            }
        }

        $this->info("Sinkronisasi top-up selesai. Diperiksa: {$checked}, completed: {$completed}, failed: {$failed}.");

        return Command::SUCCESS;
    }
}
