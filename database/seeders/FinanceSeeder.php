<?php

namespace Database\Seeders;

use App\Models\FinanceCategory;
use App\Models\Order;
use App\Services\FinanceLedgerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [FinanceCategory::TYPE_INCOME, FinanceCategory::CODE_MEMBERSHIPS, 'Memberships / Packages'],
            [FinanceCategory::TYPE_INCOME, FinanceCategory::CODE_GROUP_PROGRAMMES, 'Zumba / Group Programmes'],
            [FinanceCategory::TYPE_INCOME, FinanceCategory::CODE_YOGA, 'Yoga Programmes'],
            [FinanceCategory::TYPE_INCOME, FinanceCategory::CODE_PERSONAL_TRAINING, 'Personal Training'],
            [FinanceCategory::TYPE_INCOME, FinanceCategory::CODE_THERAPY, 'Therapy Sessions'],
            [FinanceCategory::TYPE_INCOME, FinanceCategory::CODE_PRODUCTS, 'Product Sales'],
            [FinanceCategory::TYPE_INCOME, 'other-income', 'Other Income'],
            [FinanceCategory::TYPE_EXPENSE, 'utilities', 'Utilities'],
            [FinanceCategory::TYPE_EXPENSE, 'salaries', 'Salaries'],
            [FinanceCategory::TYPE_EXPENSE, 'equipment', 'Equipment'],
            [FinanceCategory::TYPE_EXPENSE, 'maintenance', 'Maintenance'],
            [FinanceCategory::TYPE_EXPENSE, 'rent', 'Rent'],
            [FinanceCategory::TYPE_EXPENSE, 'inventory', 'Inventory'],
            [FinanceCategory::TYPE_EXPENSE, 'marketing', 'Marketing'],
            [FinanceCategory::TYPE_EXPENSE, 'miscellaneous', 'Miscellaneous'],
        ] as [$transactionType, $systemCode, $name]) {
            FinanceCategory::updateOrCreate(
                ['system_code' => $systemCode],
                [
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'transaction_type' => $transactionType,
                    'is_active' => true,
                ],
            );
        }

        $ledger = app(FinanceLedgerService::class);
        Order::query()->where('status', 'completed')->each(
            fn (Order $order) => $ledger->syncOrderIncome($order),
        );
    }
}
