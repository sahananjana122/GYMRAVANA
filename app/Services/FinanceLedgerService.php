<?php

namespace App\Services;

use App\Models\FinanceCategory;
use App\Models\FinancialTransaction;
use App\Models\MembershipPayment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FinanceLedgerService
{
    public function syncMembershipPayment(MembershipPayment $payment, ?User $actor = null): ?FinancialTransaction
    {
        return DB::transaction(function () use ($payment, $actor): ?FinancialTransaction {
            $payment->loadMissing(['member', 'subscription.tier']);
            $transaction = FinancialTransaction::query()
                ->where('source_type', $payment->getMorphClass())
                ->where('source_id', $payment->getKey())
                ->lockForUpdate()
                ->first();

            if ($payment->status !== MembershipPayment::STATUS_PAID) {
                if ($transaction && ! $transaction->voided_at) {
                    $transaction->update(['voided_at' => now()]);
                }

                return $transaction;
            }

            $category = FinanceCategory::query()
                ->where('system_code', FinanceCategory::CODE_MEMBERSHIPS)
                ->firstOrFail();
            $wasVoided = $transaction?->voided_at !== null;
            $transaction ??= new FinancialTransaction([
                'source_type' => $payment->getMorphClass(),
                'source_id' => $payment->getKey(),
            ]);

            $transaction->fill([
                'finance_category_id' => $category->id,
                'created_by' => $transaction->created_by ?? $actor?->id,
                'transaction_type' => FinanceCategory::TYPE_INCOME,
                'amount' => $payment->amount,
                'transaction_date' => ! $transaction->exists || $wasVoided
                    ? $payment->paid_at->toDateString()
                    : $transaction->transaction_date,
                'description' => 'Membership payment for '.$payment->subscription->tier->name,
                'programme_name' => $payment->subscription->tier->name,
                'supplier_payee' => $payment->member->name,
                'reference_number' => $payment->reference_number,
                'notes' => 'Automatically synchronized from a paid membership subscription.',
                'is_automatic' => true,
                'voided_at' => null,
            ]);
            $transaction->save();

            return $transaction;
        });
    }

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
