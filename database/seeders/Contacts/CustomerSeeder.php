<?php

namespace Database\Seeders\Contacts;

use App\Models\Contacts\Customer;
use Illuminate\Database\Seeder;

/**
 * Generic example customers (Section 57: no client-specific data).
 */
class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'Acme Traders', 'email' => 'billing@acmetraders.example', 'phone' => '+880-1700-000001', 'opening_balance' => 5000],
            ['name' => 'Green Valley Ltd', 'email' => 'accounts@greenvalley.example', 'phone' => '+880-1700-000002', 'opening_balance' => 0],
            ['name' => 'Rahman Enterprise', 'email' => 'info@rahmanenterprise.example', 'phone' => '+880-1700-000003', 'opening_balance' => 12000],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(['name' => $customer['name']], $customer);
        }
    }
}
