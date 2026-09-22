<?php

namespace Database\Factories\Expenses;

use App\Models\Expenses\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'default_account_id' => null,
            'is_active' => true,
        ];
    }
}
