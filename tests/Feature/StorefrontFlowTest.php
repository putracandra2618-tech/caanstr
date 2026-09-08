<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontFlowTest extends TestCase
{
    use RefreshDatabase;

    private function category(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'name' => 'Mobile Legends',
            'slug' => 'mobile-legends',
            'is_active' => true,
            'is_game' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    private function product(Category $category, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Diamonds 100',
            'slug' => 'diamonds-100',
            'price' => 11000,
            'cost_price' => 10000,
            'product_code' => 'ML-100',
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    public function test_home_shows_active_categories_products_and_banners(): void
    {
        $category = $this->category();
        $this->product($category);
        $banner = Banner::create([
            'title' => 'Promo Spesial',
            'image' => 'banners/banner.jpg',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Mobile Legends')
            ->assertSee('Diamonds 100')
            ->assertSee('Promo Spesial');
    }

    public function test_home_hides_inactive_content(): void
    {
        $inactiveCategory = $this->category(['name' => 'Free Fire', 'slug' => 'free-fire', 'is_active' => false]);
        $this->product($this->category(), ['name' => 'Hidden Product', 'slug' => 'hidden', 'is_active' => false]);
        Banner::create([
            'title' => 'Banner Kadaluarsa',
            'image' => 'banners/old.jpg',
            'is_active' => false,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Hidden Product')
            ->assertDontSee('Banner Kadaluarsa');

        $this->get('/category/free-fire')->assertNotFound();
    }

    public function test_category_page_shows_only_active_products_in_that_category(): void
    {
        $category = $this->category();
        $this->product($category, ['name' => 'Diamonds 100', 'slug' => 'diamonds-100']);
        $this->product($category, ['name' => 'Diamonds 500', 'slug' => 'diamonds-500']);
        $this->product($category, ['name' => 'Starlight Member', 'slug' => 'starlight', 'is_active' => false]);

        $other = $this->category(['name' => 'Free Fire', 'slug' => 'free-fire']);
        $this->product($other, ['name' => 'Diamond FF', 'slug' => 'ff-70']);

        $this->get('/category/mobile-legends')
            ->assertOk()
            ->assertSee('Diamonds 100')
            ->assertSee('Diamonds 500')
            ->assertDontSee('Starlight Member')
            ->assertDontSee('Diamond FF');
    }

    public function test_home_filters_pick_game_grid_to_game_categories(): void
    {
        $this->category();
        $this->product($this->category(['name' => 'Pulsa Telkomsel', 'slug' => 'pulsa-telkomsel', 'is_game' => false]), ['name' => 'Pulsa 10K', 'slug' => 'pulsa-10k']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Mobile Legends')
            ->assertDontSee('Pulsa Telkomsel')
            ->assertDontSee('Pulsa 10K');
    }

    public function test_home_pick_game_grid_paginates_game_categories(): void
    {
        foreach (range(1, 26) as $index) {
            $this->category([
                'name' => "Game $index",
                'slug' => "game-$index",
            ]);
        }

        $this->get('/')
            ->assertOk()
            ->assertSee('Game 1')
            ->assertDontSee('Game 25');

        $this->get('/?page=2')
            ->assertOk()
            ->assertSee('Game 25')
            ->assertSee('Game 26')
            ->assertDontSee('Game 1');
    }

    public function test_category_page_paginates_products(): void
    {
        $category = $this->category();
        foreach (range(1, 25) as $index) {
            $this->product($category, [
                'name' => "Product $index",
                'slug' => "product-$index",
            ]);
        }

        $this->get('/category/mobile-legends')
            ->assertOk()
            ->assertSee('Product 1')
            ->assertDontSee('Product 25');

        $this->get('/category/mobile-legends?page=2')
            ->assertOk()
            ->assertSee('Product 25');
    }

    public function test_product_detail_page_shows_price_and_related_products(): void
    {
        $category = $this->category();
        $product = $this->product($category);
        $related = $this->product($category, ['name' => 'Diamonds 500', 'slug' => 'diamonds-500', 'price' => 55000]);

        $this->get("/product/{$product->slug}")
            ->assertOk()
            ->assertSee('Diamonds 100')
            ->assertSee('Diamonds 500')
            ->assertSee('Rp 11.000');
    }

    public function test_inactive_product_detail_is_not_found(): void
    {
        $product = $this->product($this->category(), ['is_active' => false]);

        $this->get("/product/{$product->slug}")->assertNotFound();
    }

    public function test_guest_is_redirected_to_login_for_order_and_history_pages(): void
    {
        $this->get('/history')->assertRedirect(route('login'));
        $this->get('/order/ORD-20260901-0001')->assertRedirect(route('login'));
        $this->get('/order/ORD-20260901-0001/pay')->assertRedirect(route('login'));
        $this->get('/profile')->assertRedirect(route('login'));
    }

    public function test_user_history_shows_only_own_orders(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $product = $this->product($this->category());

        $mine = Order::create($this->orderData($user, $product, 'ORD-20260901-0001'));
        $theirs = Order::create($this->orderData($other, $product, 'ORD-20260901-0002'));

        $this->actingAs($user)->get('/history')
            ->assertOk()
            ->assertSee($mine->order_number)
            ->assertDontSee($theirs->order_number);
    }

    public function test_user_cannot_view_another_users_order_detail(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = $this->product($this->category());
        $order = Order::create($this->orderData($owner, $product, 'ORD-20260901-0003'));

        $this->actingAs($other)->get("/order/{$order->order_number}")->assertNotFound();
        $this->actingAs($owner)->get("/order/{$order->order_number}")->assertOk();
    }

    public function test_order_store_rejects_quantity_out_of_bounds(): void
    {
        $user = User::factory()->create();
        $product = $this->product($this->category());

        $this->actingAs($user)->post('/order', [
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 11,
        ])->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_store_ignores_unusable_promo(): void
    {
        $user = User::factory()->create();
        $product = $this->product($this->category(), ['price' => 11000]);
        $promo = Promo::create([
            'code' => 'BATAL',
            'type' => 'percentage',
            'value' => 10,
            'min_purchase' => 100000,
            'is_active' => true,
        ]);

        $this->actingAs($user)->post('/order', [
            'product_id' => $product->id,
            'game_id' => '12345',
            'quantity' => 1,
            'promo_code' => 'BATAL',
        ])->assertRedirect();

        $order = Order::first();
        $this->assertSame(0.0, (float) $order->discount);
        $this->assertSame(0, $promo->fresh()->used_count);
    }

    private function orderData(User $user, Product $product, string $number): array
    {
        return [
            'user_id' => $user->id,
            'order_number' => $number,
            'product_id' => $product->id,
            'game_id' => '12345',
            'game_zone' => null,
            'quantity' => 1,
            'subtotal' => $product->price,
            'admin_fee' => 0,
            'discount' => 0,
            'total' => $product->price,
            'status' => OrderStatus::Pending,
        ];
    }
}
