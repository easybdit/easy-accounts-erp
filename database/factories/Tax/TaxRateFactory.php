<?php

namespace Database\Factories\Tax;

use App\Models\Accounting\Account;
use App\Models\Tax\TaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxRate>
 */
class TaxRateFactory extends Factory
{
    protected $model = TaxRate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'rate' => 15,
            'tax_account_id' => Account::factory()->state(['type' => 'liability']),
            'is_active' => true,
        ];
    }
}
