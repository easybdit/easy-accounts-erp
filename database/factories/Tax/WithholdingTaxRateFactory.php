<?php

namespace Database\Factories\Tax;

use App\Models\Accounting\Account;
use App\Models\Tax\WithholdingTaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WithholdingTaxRate>
 */
class WithholdingTaxRateFactory extends Factory
{
    protected $model = WithholdingTaxRate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'rate' => 10,
            'liability_account_id' => Account::factory()->state(['type' => 'liability']),
            'is_active' => true,
        ];
    }
}
