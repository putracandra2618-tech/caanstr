<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\AutoTopupLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncTokovoucherPendingTest extends TestCase
{
    use RefreshDatabase;

    private function processingOrder(string $suffix = ''): Order
    {
        $category = Category::create([
            'name' => "Mobile Legends {$suffix}",
            'slug' => "mobile-legends-{$suffix}",
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Diamonds 100',
            'slug' => "diamonds-100-{$suffix}",
            'price' => 11000,
            'cost_price' => 10000,
            'product_code' => 'ML-100',
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => User::factory()->create()->id,
            'order_number' => Order::generateOrderNumber(),
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Processing,
        ]);

        AutoTopupLog::create([
            'order_id' => $order->id,
            'provider' => 'tokovoucher',
            'request_data' => ['ref_id' => $order->order_number],
            'status' => 'pending',
            'attempts' => 1,
        ]);

        return $order;
    }

    public function test_pending_topup_completed_via_provider_status(): void
    {
        Http::fake([
            '*/v1/transaksi/status' => Http::response([
                'status' => 'sukses',
                'message' => 'Transaksi sukses',
                'data' => ['sn' => 'SN-TEST-001', 'ref_id' => 'refid-1'],
            ]),
        ]);

        $order = $this->processingOrder();

        $this->artisan('orders:sync-tokovoucher')->assertExitCode(0);

        $order->refresh();
        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertNotNull($order->completed_at);

        $this->assertDatabaseHas('auto_topup_logs', [
            'order_id' => $order->id,
            'status' => 'success',
        ]);
    }

    public function test_stays_processing_when_provider_still_pending(): void
    {
        Http::fake([
            '*/v1/transaksi/status' => Http::response(['status' => 'pending', 'message' => 'Menunggu', 'data' => []]),
        ]);

        $order = $this->processingOrder();

        $this->artisan('orders:sync-tokovoucher')->assertExitCode(0);

        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);
        $this->assertDatabaseHas('auto_topup_logs', [
            'order_id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_failed_status_marks_order_failed(): void
    {
        Http::fake([
            '*/v1/transaksi/status' => Http::response([
                'status' => 'gagal',
                'message' => 'User ID tidak ditemukan',
                'data' => [],
            ]),
        ]);

        $order = $this->processingOrder();

        $this->artisan('orders:sync-tokovoucher')->assertExitCode(0);

        $order->refresh();
        $this->assertSame(OrderStatus::Failed, $order->status);
        $this->assertSame('User ID tidak ditemukan', $order->failed_reason);

        $this->assertDatabaseHas('auto_topup_logs', [
            'order_id' => $order->id,
            'status' => 'failed',
        ]);
    }

    public function test_provider_unreachable_keeps_order_processing(): void
    {
        Http::fake([
            '*/v1/transaksi/status' => Http::response('server error', 500),
        ]);

        $order = $this->processingOrder();

        $this->artisan('orders:sync-tokovoucher')->assertExitCode(0);

        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);
        $this->assertDatabaseHas('auto_topup_logs', [
            'order_id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_specific_order_filter_only_checks_that_order(): void
    {
        Http::fake(['*/v1/transaksi/status' => Http::response(['status' => 'gagal', 'message' => 'Gagal', 'data' => []])]);

        $order = $this->processingOrder();
        $other = $this->processingOrder('b');

        $this->artisan('orders:sync-tokovoucher', ['--order' => $other->order_number])->assertExitCode(0);

        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);
        $this->assertSame(OrderStatus::Failed, $other->fresh()->status);
    }
}
