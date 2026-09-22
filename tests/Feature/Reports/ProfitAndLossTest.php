<?php

namespace Tests\Feature\Reports;

use App\Actions\Accounting\PostJournal;
use App\Models\Accounting\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitAndLossTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_profit_and_loss(): void
    {
        $this->get(route('reports.profit-and-loss'))->assertRedirect(route('login'));
    }

    public function test_net_profit_is_income_minus_expense(): void
    {
        $user = User::factory()->create();
        $cash = Account::factory()->create(['type' => 'asset']);
        $revenue = Account::factory()->create(['type' => 'income']);
        $expenseAccount = Account::factory()->create(['type' => 'expense']);

        (new PostJournal)->handle([
            'date' => '2026-03-01',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 1000],
            ],
        ]);
        (new PostJournal)->handle([
            'date' => '2026-03-05',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => [
                ['account_id' => $expenseAccount->id, 'debit' => 300, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 300],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('reports.profit-and-loss', ['from' => '2026-03-01', 'to' => '2026-03-31']));
        $props = $response->viewData('page')['props'];

        $this->assertSame('1000.0000', $props['totalIncome']);
        $this->assertSame('300.0000', $props['totalExpense']);
        $this->assertSame('700.0000', $props['netProfit']);
    }

    public function test_transactions_outside_the_period_are_excluded(): void
    {
        $user = User::factory()->create();
        $cash = Account::factory()->create(['type' => 'asset']);
        $revenue = Account::factory()->create(['type' => 'income']);

        (new PostJournal)->handle([
            'date' => '2026-01-01',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('reports.profit-and-loss', ['from' => '2026-03-01', 'to' => '2026-03-31']));
        $props = $response->viewData('page')['props'];

        $this->assertSame('0.0000', $props['totalIncome']);
    }
}
