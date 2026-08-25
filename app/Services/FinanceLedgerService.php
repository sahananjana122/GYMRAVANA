<?php

namespace App\Services;

use App\Models\FinanceCategory;
use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FinanceLedgerService
{
    public function syncOrderIncome(Order $order, ?User $actor = null): ?FinancialTransaction
    {
        return DB::transaction(function () use ($order, $actor): ?FinancialTransaction {
            $transaction = FinancialTransaction::query()
                ->where('source_type', $order->getMorphClass())
                ->where('source_id', $order->getKey())
                ->lockForUpdate()
                ->first();

            if ($order->status !== 'completed') {
                if ($transaction && ! $transaction->voided_at) {
                    $transaction->update(['voided_at' => now()]);
                }

                return $transaction;
            }

            $category = FinanceCategory::query()
                ->where('system_code', FinanceCategory::CODE_PRODUCTS)
                ->firstOrFail();

            $wasVoided = $transaction?->voided_at !== null;
            $transaction ??= new FinancialTransaction([
                'source_type' => $order->getMorphClass(),
                'source_id' => $order->getKey(),
            ]);

            $transaction->fill([
                'finance_category_id' => $category->id,
                'created_by' => $transaction->created_by ?? $actor?->id,
                'transaction_type' => FinanceCategory::TYPE_INCOME,
                'amount' => $order->total,
                'transaction_date' => ! $transaction->exists || $wasVoided
                    ? ($order->updated_at ?? now())->toDateString()
                    : $transaction->transaction_date,
                'description' => "Product order {$order->order_number}",
                'programme_name' => null,
                'supplier_payee' => $order->customer_name,
                'reference_number' => $order->order_number,
                'notes' => 'Automatically synchronized from a completed store order.',
                'is_automatic' => true,
                'voided_at' => null,
            ]);
            $transaction->save();

            return $transaction;
        });
    }
}
