<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Promos\Pages\CreatePromo;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Widgets\AdminStats;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthAdminFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login(): void
    {
        $this->post('/register', [
            'name' => 'Budi',
            'email' => 'budi@test.com',
            'phone' => '081234',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'budi@test.com']);

        auth()->logout();

        $this->post('/login', [
            'email' => 'budi@test.com',
            'password' => 'password123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        $user = User::factory()->create(['email' => 'a@test.com']);

        $this->post('/register', [
            'name' => 'Budi',
            'email' => 'a@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');
    }

    public function test_banned_user_is_logged_out_on_login(): void
    {
        $user = User::factory()->create([
            'email' => 'banned@test.com',
            'password' => 'password123',
            'is_banned' => true,
        ]);

        $this->post('/login', [
            'email' => 'banned@test.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_cannot_access_admin(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_non_admin_cannot_access_admin(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_admin_dashboard_shows_revenue_and_completed_orders(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::create(['name' => 'C', 'slug' => 'c', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'P',
            'slug' => 'p',
            'price' => 10000,
            'cost_price' => 9000,
            'product_code' => 'X',
            'is_active' => true,
        ]);

        $this->order($admin, $product, 'ORD-20260907-0301', 27000, OrderStatus::Completed, paid: true);
        $this->order($admin, $product, 'ORD-20260907-0302', 20000, OrderStatus::Paid, paid: true);
        $this->order($admin, $product, 'ORD-20260907-0303', 10000, OrderStatus::Pending);

        Livewire::actingAs($admin)
            ->test(AdminStats::class)
            ->assertSee('Rp 47.000')
            ->assertSee('Pesanan Selesai')
            ->assertSee('1 selesai · 1 dibayar');
    }

    private function order(User $user, Product $product, string $orderNumber, int $total, OrderStatus $status, bool $paid = false): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'order_number' => $orderNumber,
            'product_id' => $product->id,
            'game_id' => '123',
            'quantity' => 1,
            'subtotal' => $total,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => $total,
            'status' => $status,
            'paid_at' => $paid ? now() : null,
        ]);
    }

    public function test_admin_can_block_a_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create(['is_admin' => false]);

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $target->getKey()])
            ->fillForm([
                'is_banned' => true,
                'banned_reason' => 'spam',
            ])
            ->call('save')
            ->assertNotified();

        $target->refresh();

        $this->assertTrue($target->is_banned);
        $this->assertNotNull($target->banned_at);
        $this->assertSame('spam', $target->banned_reason);
    }

    public function test_admin_cannot_block_another_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $otherAdmin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $otherAdmin->getKey()])
            ->fillForm(['is_banned' => true])
            ->call('save')
            ->assertHasFormErrors(['is_banned'])
            ->assertNotNotified();

        $this->assertFalse($otherAdmin->fresh()->is_banned);
    }

    public function test_admin_can_change_order_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::create(['name' => 'C', 'slug' => 'c', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'P',
            'slug' => 'p',
            'price' => 10000,
            'cost_price' => 9000,
            'product_code' => 'X',
            'is_active' => true,
        ]);
        $order = Order::create([
            'user_id' => $admin->id,
            'order_number' => 'ORD-20260901-0099',
            'product_id' => $product->id,
            'game_id' => '123',
            'quantity' => 1,
            'subtotal' => 10000,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => 10000,
            'status' => OrderStatus::Pending,
        ]);

        Livewire::actingAs($admin)
            ->test(EditOrder::class, ['record' => $order->getKey()])
            ->fillForm(['status' => 'refunded'])
            ->call('save')
            ->assertNotified();

        $this->assertSame(OrderStatus::Refunded, $order->fresh()->status);
    }

    public function test_admin_can_create_promo(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test(CreatePromo::class)
            ->fillForm([
                'code' => 'gratis10',
                'type' => 'percentage',
                'value' => 10,
                'min_purchase' => 5000,
                'usage_limit' => 5,
                'is_active' => true,
            ])
            ->call('create')
            ->assertNotified();

        $this->assertDatabaseHas('promos', ['code' => 'GRATIS10']);
    }
}
