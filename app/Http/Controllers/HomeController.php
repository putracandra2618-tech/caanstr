<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function __invoke()
    {
        $categories = Category::where('is_active', true)
            ->where('is_game', true)
            ->withCount('products')
            ->orderBy('sort_order')
            ->paginate(24);

        $popularProducts = Product::where('is_active', true)
            ->whereHas('category', fn ($query) => $query->where('is_game', true))
            ->with('category')
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        $banners = Banner::active()
            ->orderBy('sort_order')
            ->limit(5)
            ->get();

        return view('pages.home', compact('categories', 'popularProducts', 'banners'));
    }
}
