<?php

namespace Database\Factories;

use App\Models\FinanceCategory;
use App\Models\FinancialTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialTransaction>
 */
class FinancialTransactionFactory extends Factory
{
    protected $model = FinancialTransaction::class;

    public function definition(): array
    {
        return [
            'finance_category_id' => FinanceCategory::factory(),
            'transaction_type' => FinanceCategory::TYPE_INCOME,
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'transaction_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'description' => fake()->sentence(6),
            'is_automatic' => false,
        ];
    }

    public function expense(): static
    {
        return $this->state(fn () => [
            'finance_category_id' => FinanceCategory::factory()->expense(),
            'transaction_type' => FinanceCategory::TYPE_EXPENSE,
        ]);
    }
}
