<?php

namespace Database\Seeders\Expenses;

use App\Actions\Expenses\RecordExpense;
use App\Models\Accounting\Account;
use App\Models\Expenses\Expense;
use App\Models\Expenses\ExpenseCategory;
use Illuminate\Database\Seeder;

/**
 * A demo expense with no vendor link (a one-off payee, e.g. a utility bill),
 * to show that path distinctly from the vendor-linked Bill/VendorPayment
 * demo data already seeded.
 */
class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        if (Expense::where('expense_number', 'like', 'EXP-%')->exists()) {
            return;
        }

        $category = ExpenseCategory::where('name', 'Utilities')->first();
        $operatingExpenses = Account::where('code', '5002')->first();
        $cash = Account::where('code', '1001')->first();

        if (! $category || ! $operatingExpenses || ! $cash) {
            return;
        }

        app(RecordExpense::class)->handle([
            'expense_category_id' => $category->id,
            'account_id' => $operatingExpenses->id,
            'payment_account_id' => $cash->id,
            'vendor_id' => null,
            'payee' => 'City Power Co',
            'expense_date' => now()->subDays(2)->toDateString(),
            'amount' => 120,
            'reference' => null,
            'notes' => 'Demo expense seeded for verification purposes.',
            'created_by' => null,
        ]);
    }
}
