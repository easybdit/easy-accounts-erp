<?php

namespace Database\Factories\Sales;

use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-'.$this->faker->unique()->numberBetween(1000, 9999),
            'customer_id' => Customer::factory(),
            'receivable_account_id' => Account::factory()->state(['type' => 'asset']),
            'invoice_date' => now()->toDateString(),
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
