<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\FinanceLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.orders.index', ['orders' => Order::with(['user', 'items'])->latest()->get()]);
    }

    public function update(Request $request, Order $order, FinanceLedgerService $ledger): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(Order::STATUSES)]]);

        DB::transaction(function () use ($order, $data, $ledger, $request): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());
            $lockedOrder->update($data);
            $ledger->syncOrderIncome($lockedOrder, $request->user());
        });

        return back()->with('status', 'Order status updated.');
    }
}
