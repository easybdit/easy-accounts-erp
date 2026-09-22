<?php

namespace Database\Factories\Purchases;

use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Purchases\Bill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bill>
 */
class BillFactory extends Factory
{
    protected $model = Bill::class;

    public function definition(): array
    {
        return [
            'bill_number' => 'BILL-'.$this->faker->unique()->numberBetween(1000, 9999),
            'vendor_id' => Vendor::factory(),
            'payable_account_id' => Account::factory()->state(['type' => 'liability']),
            'bill_date' => now()->toDateString(),
            'due_date' => null,
            'status' => 'draft',
            'subtotal' => 0,
            'discount_total' => 0,
            'total' => 0,
            'notes' => null,
            'created_by' => null,
        ];
    }
}
