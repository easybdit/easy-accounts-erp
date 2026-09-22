<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\Account;
use Database\Seeders\Accounting\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartOfAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_chart_of_accounts_seeds_all_core_types(): void
    {
        $this->seed(ChartOfAccountsSeeder::class);

        foreach (Account::TYPES as $type) {
            $this->assertTrue(
                Account::where('type', $type)->exists(),
                "Expected at least one seeded account of type [{$type}]."
            );
        }
    }

    public function test_seeded_chart_of_accounts_opening_balances_are_balanced(): void
    {
        $this->seed(ChartOfAccountsSeeder::class);

        // The fundamental accounting equation, applied to opening balances:
        // debit-normal accounts (assets/expenses) must net to the same
        // total as credit-normal accounts (liabilities/equity/income).
        // A naive SUM(opening_balance) == 0 only happened to hold when every
        // opening balance was zero; it does not generalize to a real funded
        // starting position (e.g. Cash offset by Owner's Equity).
        $debitNormalTotal = (float) Account::whereIn('type', ['asset', 'expense'])->sum('opening_balance');
        $creditNormalTotal = (float) Account::whereIn('type', ['liability', 'equity', 'income'])->sum('opening_balance');

        $this->assertEquals(
            $debitNormalTotal,
            $creditNormalTotal,
            'Default seeded chart of accounts must start balanced (assets+expenses opening balances must equal liabilities+equity+income opening balances).'
        );
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(ChartOfAccountsSeeder::class);
        $countAfterFirstRun = Account::count();

        $this->seed(ChartOfAccountsSeeder::class);

        $this->assertSame($countAfterFirstRun, Account::count());
    }
}
