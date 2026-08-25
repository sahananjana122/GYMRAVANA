<?php

namespace App\Services;

use App\Models\FinanceCategory;
use App\Models\FinancialTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FinanceReportService
{
    public function query(array $filters = []): Builder
    {
        return FinancialTransaction::query()
            ->active()
            ->with(['category', 'creator'])
            ->when($filters['from_date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('transaction_date', '>=', $date))
            ->when($filters['to_date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('transaction_date', '<=', $date))
            ->when($filters['year'] ?? null, fn (Builder $query, int|string $year) => $query->whereYear('transaction_date', (int) $year))
            ->when($filters['month'] ?? null, function (Builder $query, int|string $month) use ($filters): void {
                $query->whereMonth('transaction_date', (int) $month);

                if (empty($filters['year'])) {
                    $query->whereYear('transaction_date', now()->year);
                }
            })
            ->when($filters['transaction_type'] ?? null, fn (Builder $query, string $type) => $query->where('transaction_type', $type))
            ->when($filters['finance_category_id'] ?? null, fn (Builder $query, int|string $categoryId) => $query->where('finance_category_id', (int) $categoryId))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');
    }

    public function summary(Collection $transactions): array
    {
        $income = $transactions->where('transaction_type', FinanceCategory::TYPE_INCOME);
        $expenses = $transactions->where('transaction_type', FinanceCategory::TYPE_EXPENSE);
        $totalIncome = (float) $income->sum('amount');
        $totalExpenses = (float) $expenses->sum('amount');

        return [
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_income' => $totalIncome - $totalExpenses,
            'income_by_source' => $this->groupAmounts($income, fn (FinancialTransaction $transaction) => $transaction->category?->name ?? 'Uncategorised'),
            'income_by_programme' => $this->groupAmounts(
                $income->filter(fn (FinancialTransaction $transaction) => filled($transaction->programme_name)),
                fn (FinancialTransaction $transaction) => $transaction->programme_name,
            ),
            'product_revenue' => (float) $income->filter(fn (FinancialTransaction $transaction) => $transaction->category?->system_code === FinanceCategory::CODE_PRODUCTS)->sum('amount'),
            'membership_revenue' => (float) $income->filter(fn (FinancialTransaction $transaction) => $transaction->category?->system_code === FinanceCategory::CODE_MEMBERSHIPS)->sum('amount'),
            'monthly_trend' => $this->monthlyTrend($transactions),
        ];
    }

    public function currentMonthSummary(): array
    {
        $transactions = FinancialTransaction::query()
            ->active()
            ->whereBetween('transaction_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->get();
        $income = (float) $transactions->where('transaction_type', FinanceCategory::TYPE_INCOME)->sum('amount');
        $expenses = (float) $transactions->where('transaction_type', FinanceCategory::TYPE_EXPENSE)->sum('amount');

        return [
            'income' => $income,
            'expenses' => $expenses,
            'net' => $income - $expenses,
        ];
    }

    private function groupAmounts(Collection $transactions, callable $label): Collection
    {
        return $transactions
            ->groupBy($label)
            ->map(fn (Collection $group) => (float) $group->sum('amount'))
            ->sortDesc();
    }

    private function monthlyTrend(Collection $transactions): Collection
    {
        return $transactions
            ->groupBy(fn (FinancialTransaction $transaction) => $transaction->transaction_date->format('Y-m'))
            ->map(function (Collection $month, string $label): array {
                $income = (float) $month->where('transaction_type', FinanceCategory::TYPE_INCOME)->sum('amount');
                $expenses = (float) $month->where('transaction_type', FinanceCategory::TYPE_EXPENSE)->sum('amount');

                return [
                    'month' => $label,
                    'income' => $income,
                    'expenses' => $expenses,
                    'net' => $income - $expenses,
                ];
            })
            ->sortKeys();
    }
}
