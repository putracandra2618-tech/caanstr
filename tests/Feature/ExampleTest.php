<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $category = Category::create([
            'name' => 'Mobile Legends',
            'slug' => 'mobile-legends',
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Diamonds 100',
            'slug' => 'diamonds-100',
            'price' => 11000,
            'cost_price' => 10000,
            'product_code' => 'ML-100',
            'is_active' => true,
        ]);

        Banner::create(['title' => 'Promo', 'image' => 'banner.jpg', 'is_active' => true]);

        $this->get('/')->assertOk();
    }
}
