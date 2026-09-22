<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\Contacts\Customer;
use App\Models\Contacts\Vendor;
use Database\Seeders\Accounting\ChartOfAccountsSeeder;
use Database\Seeders\Accounting\DemoTransactionsSeeder;
use Database\Seeders\Contacts\CustomerSeeder;
use Database\Seeders\Contacts\VendorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the full seeder pipeline (Chart of Accounts -> Customers/Vendors
 * -> demo transactions) produces consistent, balanced, understandable data.
 */
class DemoTransactionsSeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedAll(): void
    {
        $this->seed(ChartOfAccountsSeeder::class);
        $this->seed(CustomerSeeder::class);
        $this->seed(VendorSeeder::class);
        $this->seed(DemoTransactionsSeeder::class);
    }

    public function test_demo_transactions_are_posted_and_balanced(): void
    {
        $this->seedAll();

        $this->assertSame(2, Journal::count());

        foreach (Journal::with('entries')->get() as $journal) {
            $this->assertTrue($journal->isBalanced(), "Journal #{$journal->id} is not balanced.");
        }
    }

    public function test_demo_customer_balance_includes_opening_balance_and_demo_sale(): void
    {
        $this->seedAll();

        $acme = Customer::where('name', 'Acme Traders')->firstOrFail();

        // 5000 opening balance + 2000 demo sale on account.
        $this->assertSame('7000.0000', $acme->currentBalance());
    }

    public function test_demo_vendor_balance_includes_opening_balance_and_demo_expense(): void
    {
        $this->seedAll();

        $vendor = Vendor::where('name', 'Global Supplies Co')->firstOrFail();

        // 3000 opening balance + 1500 demo expense on account.
        $this->assertSame('4500.0000', $vendor->currentBalance());
    }

    public function test_running_it_twice_does_not_duplicate_demo_journals(): void
    {
        $this->seedAll();
        $this->seed(DemoTransactionsSeeder::class);

        $this->assertSame(2, Journal::count());
    }

    public function test_seeded_trial_balance_stays_balanced(): void
    {
        $this->seedAll();

        $totalDebit = (float) Account::query()
            ->withSum('journalEntries as d', 'debit')
            ->get()
            ->sum(fn ($a) => (float) ($a->d ?? 0));

        $totalCredit = (float) Account::query()
            ->withSum('journalEntries as c', 'credit')
            ->get()
            ->sum(fn ($a) => (float) ($a->c ?? 0));

        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.0001);
    }
}
