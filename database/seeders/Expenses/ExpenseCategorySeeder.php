<?php

namespace Database\Seeders\Expenses;

use App\Models\Accounting\Account;
use App\Models\Expenses\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $operatingExpenses = Account::where('code', '5002')->first();
        $salariesExpense = Account::where('code', '5003')->first();

        $categories = [
            ['name' => 'Office Supplies', 'default_account_id' => $operatingExpenses?->id],
            ['name' => 'Utilities', 'default_account_id' => $operatingExpenses?->id],
            ['name' => 'Travel', 'default_account_id' => $operatingExpenses?->id],
            ['name' => 'Payroll', 'default_account_id' => $salariesExpense?->id],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::updateOrCreate(['name' => $category['name']], $category);
        }
    }
}
