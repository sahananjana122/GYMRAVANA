<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $cart = $request->session()->get('cart', []);
        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');
        $items = collect($cart)->map(fn (int $quantity, int|string $id) => ['product' => $products->get((int) $id), 'quantity' => $quantity])->filter(fn (array $item) => $item['product']);

        return view('cart.index', ['items' => $items, 'total' => $items->sum(fn (array $item) => (float) $item['product']->price * $item['quantity'])]);
    }

    public function add(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active && $product->stock > 0, 404);
        $validated = $request->validate(['quantity' => ['nullable', 'integer', 'min:1', 'max:20']]);
        $quantity = (int) ($validated['quantity'] ?? 1);
        $cart = $request->session()->get('cart', []);
        $cart[$product->id] = min(($cart[$product->id] ?? 0) + $quantity, $product->stock, 20);
        $request->session()->put('cart', $cart);

        return back()->with('status', "{$product->name} was added to your cart.");
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $quantity = (int) $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:20']])['quantity'];
        $cart = $request->session()->get('cart', []);
        if (isset($cart[$product->id])) {
            $cart[$product->id] = min($quantity, $product->stock);
            $request->session()->put('cart', $cart);
        }

        return back()->with('status', 'Cart updated.');
    }

    public function remove(Request $request, Product $product): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$product->id]);
        $request->session()->put('cart', $cart);

        return back()->with('status', 'Item removed from your cart.');
    }
}
