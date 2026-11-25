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

class MidtransCallbackAmountTest extends TestCase
{
    use RefreshDatabase;

    private function decimalOrder(): Order
    {
        $category = Category::create([
            'name' => 'Mobile Legends',
            'slug' => 'mobile-legends',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Diamonds 120',
            'slug' => 'diamonds-120',
            'price' => 5251.40,
            'cost_price' => 4774.00,
            'product_code' => 'ML-120',
            'is_active' => true,
        ]);

        return Order::create([
            'user_id' => User::factory()->create()->id,
            'order_number' => Order::generateOrderNumber(),
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 5251.40,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 5251.40,
            'status' => OrderStatus::Pending,
        ]);
    }

    private function callbackPayload(Order $order, string $grossAmount, string $statusCode = '200', string $transactionStatus = 'settlement'): array
    {
        $serverKey = (string) config('services.midtrans.server_key');

        return [
            'order_id' => $order->order_number,
            'transaction_status' => $transactionStatus,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'currency' => 'IDR',
            'payment_type' => 'qris',
            'fraud_status' => 'accept',
            'signature_key' => hash('sha512', $order->order_number.$statusCode.$grossAmount.$serverKey),
        ];
    }

    public function test_integer_rounded_gross_amount_is_accepted_for_decimal_total(): void
    {
        config()->set('services.midtrans.server_key', 'test-server-key');
        Queue::fake();

        $order = $this->decimalOrder();

        $this->postJson('/midtrans/callback', $this->callbackPayload($order, (string) (int) round(5251.40)))
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'status' => 'settlement',
        ]);

        Queue::assertPushed(ProcessTopUpJob::class);
    }

    public function test_decimal_gross_amount_is_still_accepted(): void
    {
        config()->set('services.midtrans.server_key', 'test-server-key');
        Queue::fake();

        $order = $this->decimalOrder();

        $this->postJson('/midtrans/callback', $this->callbackPayload($order, '5251.40'))
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
    }

    public function test_tampered_amount_is_rejected(): void
    {
        config()->set('services.midtrans.server_key', 'test-server-key');
        Queue::fake();

        $order = $this->decimalOrder();

        $this->postJson('/midtrans/callback', $this->callbackPayload($order, '9000'))
            ->assertStatus(422)
            ->assertJson(['status' => 'amount_mismatch']);

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        Queue::assertNotPushed(ProcessTopUpJob::class);
    }
}
