<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Jobs\ProcessTopUpJob;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SimulateSettlementTest extends TestCase
{
    use RefreshDatabase;

    private function order(): Order
    {
        $category = Category::create([
            'name' => 'Mobile Legends',
            'slug' => 'mobile-legends',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Diamonds 100',
            'slug' => 'diamonds-100',
            'price' => 11000,
            'cost_price' => 10000,
            'product_code' => 'ML-100',
            'is_active' => true,
        ]);

        return Order::create([
            'user_id' => User::factory()->create()->id,
            'order_number' => Order::generateOrderNumber(),
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Pending,
        ]);
    }

    public function test_settlement_marks_order_paid_and_dispatches_topup_job(): void
    {
        config()->set('services.midtrans.server_key', 'test-server-key');
        Queue::fake();

        $order = $this->order();

        $this->artisan('midtrans:simulate-settlement', ['order' => $order->order_number])
            ->assertExitCode(0);

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'status' => 'settlement',
            'payment_type' => 'qris',
        ]);

        Queue::assertPushed(ProcessTopUpJob::class);

        $this->artisan('midtrans:simulate-settlement', ['order' => $order->order_number])
            ->assertExitCode(1);
    }

    public function test_expire_marks_order_failed_without_dispatching_job(): void
    {
        config()->set('services.midtrans.server_key', 'test-server-key');
        Queue::fake();

        $order = $this->order();

        $this->artisan('midtrans:simulate-settlement', [
            'order' => $order->order_number,
            '--status' => 'expire',
        ])->assertExitCode(0);

        $order->refresh();
        $this->assertSame(OrderStatus::Failed, $order->status);

        Queue::assertNotPushed(ProcessTopUpJob::class);
    }

    public function test_refuses_in_production(): void
    {
        config()->set('services.midtrans.is_production', true);

        $order = $this->order();

        $this->artisan('midtrans:simulate-settlement', ['order' => $order->order_number])
            ->assertExitCode(1);

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_unknown_order_fails(): void
    {
        $this->artisan('midtrans:simulate-settlement', ['order' => 'ORD-NOT-FOUND'])
            ->assertExitCode(1);
    }
}
