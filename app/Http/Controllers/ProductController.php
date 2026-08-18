<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('products.index', [
            'categories' => ProductCategory::withCount(['products' => fn ($query) => $query->where('is_active', true)])->get(),
            'products' => Product::where('is_active', true)->with('category')->orderBy('name')->get(),
            'activeCategory' => null,
        ]);
    }

    public function category(ProductCategory $productCategory): View
    {
        return view('products.index', [
            'categories' => ProductCategory::withCount(['products' => fn ($query) => $query->where('is_active', true)])->get(),
            'products' => $productCategory->products()->where('is_active', true)->with('category')->orderBy('name')->get(),
            'activeCategory' => $productCategory,
        ]);
    }

    public function show(ProductCategory $productCategory, Product $product): View
    {
        abort_unless($product->product_category_id === $productCategory->id && $product->is_active, 404);

        return view('products.show', compact('productCategory', 'product'));
    }
}
