<?php

namespace Tests\Feature;

use App\Filament\Resources\Banners\Pages\CreateBanner;
use App\Filament\Resources\Banners\Pages\EditBanner;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Promos\Pages\EditPromo;
use App\Filament\Resources\SyncedProducts\Pages\CreateSyncedProduct;
use App\Filament\Resources\SyncedProducts\Pages\EditSyncedProduct;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promo;
use App\Models\SyncedProduct;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCatalogCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function category(): Category
    {
        return Category::create([
            'name' => 'Mobile Legends',
            'slug' => 'mobile-legends',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_category(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(CreateCategory::class)
            ->fillForm([
                'name' => 'Free Fire',
                'slug' => 'free-fire',
                'description' => 'Game battle royale',
                'is_active' => true,
            ])
            ->call('create')
            ->assertNotified();

        $this->assertDatabaseHas('categories', [
            'name' => 'Free Fire',
            'slug' => 'free-fire',
        ]);
    }

    public function test_admin_can_update_and_delete_category(): void
    {
        $admin = $this->admin();
        $category = $this->category();

        Livewire::actingAs($admin)
            ->test(EditCategory::class, ['record' => $category->slug])
            ->fillForm(['name' => 'MLBB', 'is_active' => false, 'sort_order' => 5])
            ->call('save')
            ->assertNotified();

        $this->assertSame('MLBB', $category->fresh()->name);
        $this->assertFalse($category->fresh()->is_active);

        Livewire::actingAs($admin)
            ->test(EditCategory::class, ['record' => $category->slug])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertDatabaseMissing('categories', ['id' => $category->getKey()]);
    }

    public function test_admin_can_create_product(): void
    {
        $admin = $this->admin();
        $category = $this->category();

        Livewire::actingAs($admin)
            ->test(CreateProduct::class)
            ->fillForm([
                'category_id' => $category->id,
                'name' => 'Diamonds 100',
                'slug' => 'diamonds-100',
                'price' => 11000,
                'cost_price' => 10000,
                'product_code' => 'ML-100',
                'is_active' => true,
            ])
            ->call('create')
            ->assertNotified();

        $this->assertDatabaseHas('products', [
            'name' => 'Diamonds 100',
            'slug' => 'diamonds-100',
            'product_code' => 'ML-100',
        ]);
    }

    public function test_admin_can_update_and_delete_product(): void
    {
        $admin = $this->admin();
        $category = $this->category();
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Diamonds 100',
            'slug' => 'diamonds-100',
            'price' => 11000,
            'cost_price' => 10000,
            'product_code' => 'ML-100',
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(EditProduct::class, ['record' => $product->slug])
            ->fillForm(['price' => 12000, 'cost_price' => 10500])
            ->call('save')
            ->assertNotified();

        $this->assertSame(12000.0, (float) $product->fresh()->price);
        $this->assertSame(10500.0, (float) $product->fresh()->cost_price);

        Livewire::actingAs($admin)
            ->test(EditProduct::class, ['record' => $product->slug])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertDatabaseMissing('products', ['id' => $product->getKey()]);
    }

    public function test_admin_can_create_and_delete_banner(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banners/banner.jpg', 'fake-image');

        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(CreateBanner::class)
            ->fillForm([
                'title' => 'Promo Spesial',
                'image' => ['banners/banner.jpg'],
                'url' => 'https://example.com/promo',
                'is_active' => true,
            ])
            ->call('create')
            ->assertNotified();

        $banner = Banner::where('title', 'Promo Spesial')->first();
        $this->assertNotNull($banner);
        $this->assertSame('banners/banner.jpg', $banner->image);

        Livewire::actingAs($admin)
            ->test(EditBanner::class, ['record' => $banner->getKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertDatabaseMissing('banners', ['id' => $banner->getKey()]);
    }

    public function test_admin_can_create_update_and_delete_synced_product(): void
    {
        $admin = $this->admin();
        $category = $this->category();
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Diamonds 100',
            'slug' => 'diamonds-100',
            'price' => 11000,
            'cost_price' => 10000,
            'product_code' => 'ML-100',
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(CreateSyncedProduct::class)
            ->fillForm([
                'category_id' => $category->id,
                'product_id' => $product->id,
                'digiflazz_sku' => 'ML-100',
                'digiflazz_price' => 10000,
                'brand' => 'Mobile Legends',
                'type' => 'game',
                'is_active' => true,
            ])
            ->call('create')
            ->assertNotified();

        $synced = SyncedProduct::where('digiflazz_sku', 'ML-100')->first();
        $this->assertNotNull($synced);

        Livewire::actingAs($admin)
            ->test(EditSyncedProduct::class, ['record' => $synced->getKey()])
            ->fillForm(['digiflazz_price' => 9500])
            ->call('save')
            ->assertNotified();

        $this->assertSame(9500.0, (float) $synced->fresh()->digiflazz_price);

        Livewire::actingAs($admin)
            ->test(EditSyncedProduct::class, ['record' => $synced->getKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertDatabaseMissing('synced_products', ['id' => $synced->getKey()]);
    }

    public function test_admin_can_update_and_delete_promo(): void
    {
        $admin = $this->admin();
        $promo = Promo::create([
            'code' => 'HEMAT10',
            'type' => 'percentage',
            'value' => 10,
            'min_purchase' => 0,
            'max_discount' => 5000,
            'usage_limit' => 10,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(EditPromo::class, ['record' => $promo->code])
            ->fillForm(['value' => 15, 'is_active' => false])
            ->call('save')
            ->assertNotified();

        $this->assertSame(15.0, (float) $promo->fresh()->value);
        $this->assertFalse($promo->fresh()->is_active);

        Livewire::actingAs($admin)
            ->test(EditPromo::class, ['record' => $promo->code])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertDatabaseMissing('promos', ['id' => $promo->getKey()]);
    }
}
