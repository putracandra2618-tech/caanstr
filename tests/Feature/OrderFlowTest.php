<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Jobs\ProcessTopUpJob;
use App\Models\AutoTopupLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $category = Category::create([
            'name' => 'Mobile Legends',
            'slug' => 'mobile-legends',
            'is_active' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Diamonds 100',
            'slug' => 'diamonds-100',
            'price' => 11000,
            'cost_price' => 10000,
            'product_code' => 'ML-100',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_create_order(): void
    {
        $this->post('/order', [
            'product_id' => 1,
            'game_id' => '12345',
            'quantity' => 1,
        ])->assertRedirect(route('login'));
    }

    public function test_user_can_create_order_with_promo_discount(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $promo = Promo::create([
            'code' => 'HEMAT10',
            'type' => 'percentage',
            'value' => 10,
            'min_purchase' => 0,
            'max_discount' => 5000,
            'usage_limit' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($user)->post('/order', [
            'product_id' => $product->id,
            'game_id' => '12345',
            'game_zone' => '2233',
            'quantity' => 2,
            'promo_code' => 'HEMAT10',
        ])->assertRedirect();

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame(22000.0, (float) $order->subtotal);
        $this->assertSame(2200.0, (float) $order->discount);
        $this->assertSame(19800.0, (float) $order->total);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(1, $promo->fresh()->used_count);
    }

    public function test_payment_page_generates_snap_token(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response(['token' => 'snap-token-xyz']),
        ]);

        $this->actingAs($user)->post('/order', [
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
        ]);

        $order = Order::first();

        $this->actingAs($user)->get('/order/'.$order->order_number.'/pay')
            ->assertOk()
            ->assertSee('snap-token-xyz');
    }

    public function test_user_cannot_access_other_users_payment_page(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = $this->product();

        $order = Order::create([
            'user_id' => $owner->id,
            'order_number' => 'ORD-20260901-0001',
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Pending,
        ]);

        $this->actingAs($other)->get("/order/{$order->order_number}/pay")->assertNotFound();
    }

    public function test_midtrans_callback_marks_order_paid_and_runs_topup(): void
    {
        config()->set('services.midtrans.server_key', 'test-server-key');

        $user = User::factory()->create();
        $product = $this->product();

        Http::fake([
            'https://api.tokovoucher.net/v1/transaksi' => Http::response([
                'status' => 'sukses',
                'message' => 'ok',
                'sn' => 'SN-123',
            ]),
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-20260901-0002',
            'product_id' => $product->id,
            'game_id' => '12345',
            'game_zone' => null,
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Pending,
        ]);

        $payload = [
            'order_id' => $order->order_number,
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'fraud_status' => 'accept',
            'transaction_id' => 'TXN-1',
            'status_code' => '200',
            'gross_amount' => '11000.00',
            'signature_key' => hash('sha512', $order->order_number.'200'.'11000.00'.'test-server-key'),
        ];

        $this->post('/midtrans/callback', $payload)->assertOk();

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        $this->assertSame('bank_transfer', $order->fresh()->payment_method);
        $this->assertDatabaseHas('transactions', ['order_id' => $order->id, 'status' => 'settlement']);
        $this->assertNotEmpty(AutoTopupLog::where('order_id', $order->id)->first());
    }

    public function test_duplicate_midtrans_settlement_callback_does_not_run_double_topup(): void
    {
        config()->set('services.midtrans.server_key', 'test-server-key');

        $user = User::factory()->create();
        $product = $this->product();

        $calls = 0;
        Http::fake(function ($request) use (&$calls) {
            $calls++;
            if ($request->url() === 'https://app.sandbox.midtrans.com/snap/v1/transactions') {
                return Http::response(['token' => 'snap-token-xyz']);
            }

            return Http::response([
                'status' => 'sukses',
                'message' => 'ok',
                'sn' => 'SN-123',
            ]);
        });

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-20260901-0030',
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Pending,
        ]);

        $payload = [
            'order_id' => $order->order_number,
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
            'fraud_status' => 'accept',
            'transaction_id' => 'TXN-DUP-1',
            'status_code' => '200',
            'gross_amount' => '11000.00',
            'signature_key' => hash('sha512', $order->order_number.'200'.'11000.00'.'test-server-key'),
        ];

        $this->post('/midtrans/callback', $payload)->assertOk();
        $this->post('/midtrans/callback', $payload)->assertOk();

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        $this->assertSame(1, AutoTopupLog::where('order_id', $order->id)->count(), 'Top up harus diproses tepat satu kali walaupun callback duplikat.');
    }

    public function test_midtrans_callback_rejects_forged_signature(): void
    {
        config()->set('services.midtrans.server_key', 'test-server-key');

        $user = User::factory()->create();
        $product = $this->product();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-20260901-0020',
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Pending,
        ]);

        $this->post('/midtrans/callback', [
            'order_id' => $order->order_number,
            'transaction_status' => 'settlement',
            'status_code' => '200',
            'gross_amount' => '11000.00',
            'signature_key' => 'forged-signature',
        ])->assertForbidden();

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertDatabaseMissing('transactions', ['order_id' => $order->id]);
    }

    public function test_midtrans_callback_rejects_amount_mismatch(): void
    {
        config()->set('services.midtrans.server_key', 'test-server-key');

        $user = User::factory()->create();
        $product = $this->product();
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-20260901-0023',
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Pending,
        ]);

        $payload = [
            'order_id' => $order->order_number,
            'transaction_status' => 'settlement',
            'status_code' => '200',
            'gross_amount' => '10000.00',
            'signature_key' => hash('sha512', $order->order_number.'200'.'10000.00'.'test-server-key'),
        ];

        $this->post('/midtrans/callback', $payload)->assertStatus(422);

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertDatabaseMissing('transactions', ['order_id' => $order->id]);
    }

    public function test_midtrans_callback_does_not_regress_completed_order(): void
    {
        config()->set('services.midtrans.server_key', 'test-server-key');

        $user = User::factory()->create();
        $product = $this->product();
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-20260901-0024',
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Completed,
            'completed_at' => now(),
        ]);

        $payload = [
            'order_id' => $order->order_number,
            'transaction_status' => 'expire',
            'status_code' => '407',
            'gross_amount' => '11000.00',
            'signature_key' => hash('sha512', $order->order_number.'407'.'11000.00'.'test-server-key'),
        ];

        $this->post('/midtrans/callback', $payload)->assertOk();

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
    }

    public function test_tokovoucher_callback_completes_pending_order(): void
    {
        config()->set('tokovoucher.member_code', 'MEMBER-TEST');
        config()->set('tokovoucher.secret_key', 'test-secret-key');

        $user = User::factory()->create();
        $product = $this->product();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-20260901-0003',
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
            'request_data' => [],
            'status' => 'pending',
        ]);

        $body = json_encode([
            'ref_id' => $order->order_number,
            'status' => 'sukses',
            'sn' => 'SN-999',
        ]);

        $signature = md5('MEMBER-TEST:test-secret-key:'.$order->order_number);

        $this->call('POST', '/tokovoucher/callback', [], [], [], [
            'HTTP_X_TOKOVOUCHER_AUTHORIZATION' => $signature,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $body)->assertOk();

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        $this->assertDatabaseHas('auto_topup_logs', ['order_id' => $order->id, 'status' => 'success']);
    }

    public function test_tokovoucher_callback_does_not_regress_completed_order(): void
    {
        config()->set('tokovoucher.member_code', 'MEMBER-TEST');
        config()->set('tokovoucher.secret_key', 'test-secret-key');

        $user = User::factory()->create();
        $product = $this->product();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-20260901-0022',
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Completed,
            'completed_at' => now(),
        ]);

        $body = json_encode([
            'ref_id' => $order->order_number,
            'status' => 'gagal',
            'message' => 'Late failure',
        ]);

        $signature = md5('MEMBER-TEST:test-secret-key:'.$order->order_number);

        $this->call('POST', '/tokovoucher/callback', [], [], [], [
            'HTTP_X_TOKOVOUCHER_AUTHORIZATION' => $signature,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $body)->assertOk();

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        $this->assertNull($order->fresh()->failed_reason);
    }

    public function test_expire_pending_command_marks_stale_orders_as_failed(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $stale = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-20260901-0031',
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Pending,
        ]);
        Order::where('id', $stale->id)->update(['created_at' => now()->subMinutes(30)]);

        $fresh = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-20260901-0032',
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Pending,
            'created_at' => now()->subMinutes(1),
        ]);

        $this->artisan('orders:expire-pending')->assertSuccessful();

        $this->assertSame(OrderStatus::Failed, $stale->fresh()->status);
        $this->assertSame(OrderStatus::Pending, $fresh->fresh()->status);
    }

    public function test_tokovoucher_callback_rejects_forged_signature(): void
    {
        config()->set('tokovoucher.member_code', 'MEMBER-TEST');
        config()->set('tokovoucher.secret_key', 'test-secret-key');

        $user = User::factory()->create();
        $product = $this->product();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-20260901-0021',
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Processing,
        ]);

        $this->post('/tokovoucher/callback', [
            'ref_id' => $order->order_number,
            'status' => 'sukses',
            'sn' => 'SN-999',
        ], [
            'X-TokoVoucher-Authorization' => 'forged-signature',
        ])->assertForbidden();

        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);
    }

    public function test_sync_midtrans_command_marks_settled_order_as_paid_and_runs_topup(): void
    {
        config()->set('services.midtrans.server_key', 'test-server-key');

        $user = User::factory()->create();
        $product = $this->product();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-20260907-0100',
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Pending,
            'snap_token' => 'snap-token-sync',
        ]);

        $apiUrl = config('services.midtrans.api_url');

        Http::fake([
            "{$apiUrl}/{$order->order_number}/status" => Http::response([
                'status_code' => '200',
                'transaction_status' => 'settlement',
                'payment_type' => 'qris',
                'fraud_status' => 'accept',
                'transaction_id' => 'TXN-SYNC-1',
                'gross_amount' => '11000.00',
            ]),
            'https://api.tokovoucher.net/v1/transaksi' => Http::response([
                'status' => 'sukses',
                'message' => 'ok',
                'sn' => 'SN-SYNC',
            ]),
        ]);

        $this->artisan('orders:sync-midtrans')->assertSuccessful();

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        $this->assertSame('qris', $order->fresh()->payment_method);
        $this->assertDatabaseHas('transactions', ['order_id' => $order->id, 'status' => 'settlement']);
        $this->assertNotEmpty(AutoTopupLog::where('order_id', $order->id)->first());
    }

    public function test_sync_midtrans_command_marks_expired_order_as_failed(): void
    {
        config()->set('services.midtrans.server_key', 'test-server-key');

        $user = User::factory()->create();
        $product = $this->product();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-20260907-0101',
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Pending,
            'snap_token' => 'snap-token-sync-expired',
        ]);

        $apiUrl = config('services.midtrans.api_url');

        Http::fake([
            "{$apiUrl}/{$order->order_number}/status" => Http::response([
                'status_code' => '407',
                'transaction_status' => 'expire',
                'payment_type' => 'qris',
            ]),
        ]);

        $this->artisan('orders:sync-midtrans')->assertSuccessful();

        $this->assertSame(OrderStatus::Failed, $order->fresh()->status);
    }

    public function test_sync_midtrans_command_skips_order_when_transaction_not_found(): void
    {
        config()->set('services.midtrans.server_key', 'test-server-key');

        $user = User::factory()->create();
        $product = $this->product();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-20260907-0102',
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Pending,
            'snap_token' => 'snap-token-sync-missing',
        ]);

        $apiUrl = config('services.midtrans.api_url');

        Http::fake([
            "{$apiUrl}/{$order->order_number}/status" => Http::response([
                'status_code' => '404',
                'status_message' => 'Transaction doesn\'t exist.',
            ], 404),
        ]);

        $this->artisan('orders:sync-midtrans')->assertSuccessful();

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_success_page_marks_pending_order_paid_from_midtrans_status(): void
    {
        config()->set('services.midtrans.server_key', 'test-server-key');

        Queue::fake();

        $user = User::factory()->create();
        $product = $this->product();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-20260907-0200',
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Pending,
        ]);

        $apiUrl = config('services.midtrans.api_url');

        Http::fake([
            "{$apiUrl}/{$order->order_number}/status" => Http::response([
                'status_code' => '200',
                'transaction_status' => 'settlement',
                'payment_type' => 'qris',
                'fraud_status' => 'accept',
                'transaction_id' => 'TXN-SUCCESS-1',
                'gross_amount' => '11000.00',
            ]),
        ]);

        $this->actingAs($user)->get(route('payment.success', $order->order_number))->assertOk();

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame('qris', $order->fresh()->payment_method);
        $this->assertNotNull($order->fresh()->paid_at);
        $this->assertDatabaseHas('transactions', ['order_id' => $order->id, 'status' => 'settlement']);
        Queue::assertPushed(ProcessTopUpJob::class);
    }

    public function test_success_page_refreshing_paid_order_does_not_recheck_midtrans(): void
    {
        config()->set('services.midtrans.server_key', 'test-server-key');

        Queue::fake();

        $user = User::factory()->create();
        $product = $this->product();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-20260907-0201',
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);

        $this->actingAs($user)->get(route('payment.success', $order->order_number))->assertOk();

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        Http::assertNothingSent();
        Queue::assertNotPushed(ProcessTopUpJob::class);
    }

    public function test_success_page_keeps_order_pending_when_midtrans_not_settled(): void
    {
        config()->set('services.midtrans.server_key', 'test-server-key');

        Queue::fake();

        $user = User::factory()->create();
        $product = $this->product();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-20260907-0202',
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'subtotal' => 11000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 11000,
            'status' => OrderStatus::Pending,
        ]);

        $apiUrl = config('services.midtrans.api_url');

        Http::fake([
            "{$apiUrl}/{$order->order_number}/status" => Http::response([
                'status_code' => '201',
                'transaction_status' => 'pending',
                'payment_type' => 'qris',
            ]),
        ]);

        $this->actingAs($user)->get(route('payment.success', $order->order_number))->assertOk();

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        Queue::assertNotPushed(ProcessTopUpJob::class);
    }

    public function test_full_flow_from_web_to_midtrans_to_tokovoucher_webhook(): void
    {
        config()->set('services.midtrans.server_key', 'test-server-key');
        config()->set('tokovoucher.member_code', 'MEMBER-TEST');
        config()->set('tokovoucher.secret_key', 'test-secret-key');

        Http::preventStrayRequests();

        $user = User::factory()->create();
        $product = $this->product();

        $tokovoucherBodies = [];
        Http::fake(function ($request) use (&$tokovoucherBodies) {
            if (str_contains($request->url(), 'app.sandbox.midtrans.com/snap/v1/transactions')) {
                return Http::response(['token' => 'snap-token-flow']);
            }

            if (str_contains($request->url(), 'api.tokovoucher.net/v1/transaksi')) {
                $tokovoucherBodies[] = $request->data();

                return Http::response(['status' => 'pending', 'message' => 'processing']);
            }

            return Http::response('Unexpected request: '.$request->url(), 500);
        });

        $this->actingAs($user)->post('/order', [
            'product_id' => $product->id,
            'game_id' => '12345',
            'game_zone' => '2233',
            'quantity' => 1,
        ])->assertRedirect();

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);

        $this->actingAs($user)->get("/order/{$order->order_number}/pay")
            ->assertOk()
            ->assertSee('snap-token-flow');

        $this->post('/midtrans/callback', [
            'order_id' => $order->order_number,
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'fraud_status' => 'accept',
            'transaction_id' => 'TXN-FLOW-1',
            'status_code' => '200',
            'gross_amount' => '11000.00',
            'signature_key' => hash('sha512', $order->order_number.'200'.'11000.00'.'test-server-key'),
        ])->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Processing, $order->status);
        $this->assertSame('bank_transfer', $order->payment_method);
        $this->assertNotNull($order->paid_at);
        $this->assertDatabaseHas('transactions', ['order_id' => $order->id, 'status' => 'settlement', 'payment_type' => 'bank_transfer']);

        $this->assertCount(1, $tokovoucherBodies);
        $this->assertSame($product->product_code, $tokovoucherBodies[0]['produk']);
        $this->assertSame('12345', $tokovoucherBodies[0]['tujuan']);
        $this->assertSame('2233', $tokovoucherBodies[0]['server_id']);
        $this->assertSame($order->order_number, $tokovoucherBodies[0]['ref_id']);
        $this->assertSame('MEMBER-TEST', $tokovoucherBodies[0]['member_code']);
        $this->assertSame(
            md5('MEMBER-TEST:test-secret-key:'.$order->order_number),
            $tokovoucherBodies[0]['signature']
        );

        $log = AutoTopupLog::where('order_id', $order->id)->first();
        $this->assertSame('tokovoucher', $log->provider);
        $this->assertSame('pending', $log->status);
        $this->assertSame([
            'sku' => $product->product_code,
            'customer_no' => '12345|2233',
            'zone' => '2233',
            'ref_id' => $order->order_number,
        ], $log->request_data);

        $body = json_encode([
            'ref_id' => $order->order_number,
            'status' => 'sukses',
            'sn' => 'SN-FLOW-1',
        ]);

        $signature = md5('MEMBER-TEST:test-secret-key:'.$order->order_number);

        $this->call('POST', '/tokovoucher/callback', [], [], [], [
            'HTTP_X_TOKOVOUCHER_AUTHORIZATION' => $signature,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $body)->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertNotNull($order->completed_at);
        $this->assertSame('success', $log->fresh()->status);
        $this->assertSame('SN-FLOW-1', data_get($log->fresh()->response_data, 'sn'));
    }
}
