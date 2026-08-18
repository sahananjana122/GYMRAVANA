<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('cart') || count($request->session()->get('cart', [])) === 0) {
            return redirect()->route('cart.index')->with('status', 'Add a product before checking out.');
        }

        return view('checkout.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'guest_email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'delivery_address' => ['required', 'string', 'max:1000'],
        ]);
        $cart = $request->session()->get('cart', []);

        if ($cart === []) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        $order = DB::transaction(function () use ($cart, $validated, $request) {
            $products = Product::whereIn('id', array_keys($cart))->lockForUpdate()->get()->keyBy('id');
            $total = 0;

            foreach ($cart as $productId => $quantity) {
                $product = $products->get((int) $productId);
                if (! $product || ! $product->is_active || $product->stock < $quantity) {
                    throw ValidationException::withMessages(['cart' => 'One or more products are no longer available in the requested quantity.']);
                }
                $total += (float) $product->price * $quantity;
            }

            $order = Order::create($validated + ['order_number' => (string) Str::uuid(), 'user_id' => $request->user()?->id, 'status' => 'pending', 'total' => $total]);
            foreach ($cart as $productId => $quantity) {
                $product = $products->get((int) $productId);
                $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => $quantity, 'unit_price' => $product->price]);
                $product->decrement('stock', $quantity);
            }

            return $order;
        });

        $request->session()->forget('cart');

        return redirect()->route('checkout.success', $order);
    }

    public function success(Order $order): View
    {
        return view('checkout.success', compact('order'));
    }
}
