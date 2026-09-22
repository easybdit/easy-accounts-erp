<?php

namespace Tests\Feature\Reports;

use App\Actions\Accounting\PostJournal;
use App\Models\Accounting\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_balance_sheet(): void
    {
        $this->get(route('reports.balance-sheet'))->assertRedirect(route('login'));
    }

    public function test_balance_sheet_balances_with_current_earnings_folded_into_equity(): void
    {
        $user = User::factory()->create();
        $cash = Account::factory()->create(['type' => 'asset', 'opening_balance' => 1000]);
        $equity = Account::factory()->create(['type' => 'equity', 'opening_balance' => 1000]);
        $revenue = Account::factory()->create(['type' => 'income']);

        // A sale: increases cash (asset) and income, which must show up as
        // "current earnings" inside Equity for the sheet to balance, since
        // there is no period-close mechanism zeroing income into equity.
        (new PostJournal)->handle([
            'date' => '2026-03-01',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('reports.balance-sheet', ['as_of' => '2026-03-31']));
        $props = $response->viewData('page')['props'];

        $this->assertSame('1500.0000', $props['totalAssets']);
        $this->assertSame('500.0000', $props['currentEarnings']);
        $this->assertSame('1500.0000', $props['totalEquity']);
        $this->assertTrue($props['isBalanced']);
    }
}
