<?php

namespace Database\Factories\Accounting;

use App\Models\Accounting\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'code' => (string) $this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->unique()->words(2, true),
            'type' => $this->faker->randomElement(Account::TYPES),
            'parent_id' => null,
            'opening_balance' => 0,
            'is_active' => true,
        ];
    }
}
