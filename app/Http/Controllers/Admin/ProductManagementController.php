<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.products.index', [
            'categories' => ProductCategory::with('products')->orderBy('name')->get(),
            'products' => Product::with('category')->orderBy('name')->get(),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:120', 'unique:product_categories,name']]);
        ProductCategory::create($validated + ['slug' => Str::slug($validated['name'])]);

        return back()->with('status', 'Product category created.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        Product::create($validated + ['slug' => Str::slug($validated['name'])]);

        return back()->with('status', 'Product created.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request));

        return back()->with('status', 'Product updated.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'product_category_id' => ['required', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:3000'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
