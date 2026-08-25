<?php

namespace Database\Factories;

use App\Models\FinanceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FinanceCategory>
 */
class FinanceCategoryFactory extends Factory
{
    protected $model = FinanceCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999),
            'transaction_type' => FinanceCategory::TYPE_INCOME,
            'system_code' => null,
            'is_active' => true,
        ];
    }

    public function expense(): static
    {
        return $this->state(fn () => ['transaction_type' => FinanceCategory::TYPE_EXPENSE]);
    }
}
