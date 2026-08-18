<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.orders.index', ['orders' => Order::with(['user', 'items'])->latest()->get()]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $order->update($request->validate(['status' => ['required', Rule::in(Order::STATUSES)]]));

        return back()->with('status', 'Order status updated.');
    }
}
