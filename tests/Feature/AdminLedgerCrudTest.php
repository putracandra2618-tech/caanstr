<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Resources\AutoTopupLogs\Pages\CreateAutoTopupLog;
use App\Filament\Resources\AutoTopupLogs\Pages\EditAutoTopupLog;
use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Settings\Pages\CreateSetting;
use App\Filament\Resources\Settings\Pages\EditSetting;
use App\Filament\Resources\Transactions\Pages\CreateTransaction;
use App\Filament\Resources\Transactions\Pages\EditTransaction;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\WalletTransactions\Pages\CreateWalletTransaction;
use App\Filament\Resources\WalletTransactions\Pages\EditWalletTransaction;
use App\Models\AutoTopupLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletTransaction;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLedgerCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

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

    private function order(User $user, Product $product, string $number): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'order_number' => $number,
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

    public function test_admin_can_create_user(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Kasir Baru',
                'email' => 'kasir@test.com',
                'phone' => '081234',
                'password' => 'password123',
                'is_admin' => true,
            ])
            ->call('create')
            ->assertNotified();

        $created = User::where('email', 'kasir@test.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->isAdmin());
        $this->assertNotSame('password123', $created->password);
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $target->getKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertDatabaseMissing('users', ['id' => $target->getKey()]);
    }

    public function test_admin_can_create_order(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $product = $this->product();

        Livewire::actingAs($admin)
            ->test(CreateOrder::class)
            ->fillForm([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'game_id' => '12345',
                'game_zone' => '2233',
                'quantity' => 2,
                'status' => 'pending',
            ])
            ->call('create')
            ->assertNotified();

        $order = Order::latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame(2, $order->quantity);
    }

    public function test_admin_can_delete_order(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $order = $this->order($user, $this->product(), 'ORD-20260901-0100');

        Livewire::actingAs($admin)
            ->test(EditOrder::class, ['record' => $order->getKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertDatabaseMissing('orders', ['id' => $order->getKey()]);
    }

    public function test_admin_can_create_and_update_transaction(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $order = $this->order($user, $this->product(), 'ORD-20260901-0101');

        Livewire::actingAs($admin)
            ->test(CreateTransaction::class)
            ->fillForm([
                'order_id' => $order->id,
                'midtrans_order_id' => 'TXN-1',
                'payment_type' => 'bank_transfer',
                'status' => 'settlement',
                'gross_amount' => 11000,
                'va_number' => '1234567890',
                'fraud_status' => 'accept',
            ])
            ->call('create')
            ->assertNotified();

        $transaction = Transaction::where('midtrans_order_id', 'TXN-1')->first();
        $this->assertNotNull($transaction);
        $this->assertSame($order->id, $transaction->order_id);
        $this->assertSame('settlement', $transaction->status);

        Livewire::actingAs($admin)
            ->test(EditTransaction::class, ['record' => $transaction->getKey()])
            ->fillForm(['status' => 'pending'])
            ->call('save')
            ->assertNotified();

        $this->assertSame('pending', $transaction->fresh()->status);
    }

    public function test_admin_can_create_update_and_delete_wallet_transaction(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['balance' => 50000]);

        Livewire::actingAs($admin)
            ->test(CreateWalletTransaction::class)
            ->fillForm([
                'user_id' => $user->id,
                'type' => 'adjustment',
                'amount' => 5000,
                'balance_before' => 50000,
                'balance_after' => 55000,
                'description' => 'Koreksi saldo',
                'reference' => 'ADJ-1',
            ])
            ->call('create')
            ->assertNotified();

        $wallet = WalletTransaction::where('reference', 'ADJ-1')->first();
        $this->assertNotNull($wallet);
        $this->assertSame(55000.0, (float) $wallet->balance_after);

        Livewire::actingAs($admin)
            ->test(EditWalletTransaction::class, ['record' => $wallet->getKey()])
            ->fillForm(['description' => 'Koreksi saldo (updated)'])
            ->call('save')
            ->assertNotified();

        $this->assertSame('Koreksi saldo (updated)', $wallet->fresh()->description);

        Livewire::actingAs($admin)
            ->test(EditWalletTransaction::class, ['record' => $wallet->getKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertDatabaseMissing('wallet_transactions', ['id' => $wallet->getKey()]);
    }

    public function test_admin_can_create_and_delete_auto_topup_log(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $order = $this->order($user, $this->product(), 'ORD-20260901-0102');

        Livewire::actingAs($admin)
            ->test(CreateAutoTopupLog::class)
            ->fillForm([
                'order_id' => $order->id,
                'provider' => 'digiflazz',
                'request_data' => ['sku' => 'ML-100'],
                'response_data' => ['rc' => '00'],
                'status' => 'success',
                'attempts' => 1,
            ])
            ->call('create')
            ->assertNotified();

        $log = AutoTopupLog::where('order_id', $order->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('success', $log->status);

        Livewire::actingAs($admin)
            ->test(EditAutoTopupLog::class, ['record' => $log->getKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertDatabaseMissing('auto_topup_logs', ['id' => $log->getKey()]);
    }

    public function test_admin_can_create_update_and_delete_setting(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(CreateSetting::class)
            ->fillForm([
                'key' => 'site_name',
                'value' => 'GameTopUp',
                'type' => 'text',
            ])
            ->call('create')
            ->assertNotified();

        $setting = Setting::where('key', 'site_name')->first();
        $this->assertNotNull($setting);
        $this->assertSame('GameTopUp', $setting->value);

        Livewire::actingAs($admin)
            ->test(EditSetting::class, ['record' => $setting->getKey()])
            ->fillForm(['value' => 'GameTopUp Plus'])
            ->call('save')
            ->assertNotified();

        $this->assertSame('GameTopUp Plus', $setting->fresh()->value);

        Livewire::actingAs($admin)
            ->test(EditSetting::class, ['record' => $setting->getKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertDatabaseMissing('settings', ['id' => $setting->getKey()]);
    }
}
