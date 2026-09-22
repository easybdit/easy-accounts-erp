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

        $this->assertEquals(
            0,
            (float) Account::sum('opening_balance'),
            'Default seeded chart of accounts must start balanced.'
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
