<?php

namespace Database\Seeders\Tax;

use App\Models\Accounting\Account;
use App\Models\Tax\TaxRate;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        $taxPayable = Account::where('code', '2002')->first();

        if (! $taxPayable) {
            return;
        }

        TaxRate::updateOrCreate(
            ['name' => 'VAT 15%'],
            ['rate' => 15, 'tax_account_id' => $taxPayable->id, 'is_active' => true]
        );
    }
}
