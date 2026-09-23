<?php

namespace Tests\Feature\Reports;

use App\Actions\Accounting\PostJournal;
use App\Actions\Accounting\SaveBudget;
use App\Models\Accounting\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetVsActualTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_blocked(): void
    {
        $this->get(route('reports.budget-vs-actual'))->assertRedirect(route('login'));
    }

    public function test_with_no_budget_selected_it_renders_without_a_comparison(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.budget-vs-actual'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('comparison', null));
    }

    public function test_a_full_year_range_compares_the_full_budgeted_amount_against_actual(): void
    {
        $user = User::factory()->create();
        $cash = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);
        $expense = Account::factory()->create(['type' => 'expense']);

        $budget = app(SaveBudget::class)->handle([
            'name' => 'FY2026', 'fiscal_year' => 2026, 'notes' => null, 'created_by' => $user->id,
            'lines' => [
                ['account_id' => $income->id, 'amount' => 120000],
                ['account_id' => $expense->id, 'amount' => 60000],
            ],
        ]);

        // Actual income of 100,000 and actual expense of 70,000 in 2026.
        app(PostJournal::class)->handle([
            'date' => '2026-06-15', 'reference' => null, 'description' => null, 'created_by' => null,
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 100000, 'credit' => 0],
                ['account_id' => $income->id, 'debit' => 0, 'credit' => 100000],
            ],
        ]);
        app(PostJournal::class)->handle([
            'date' => '2026-06-16', 'reference' => null, 'description' => null, 'created_by' => null,
            'lines' => [
                ['account_id' => $expense->id, 'debit' => 70000, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 70000],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('reports.budget-vs-actual', [
            'budget_id' => $budget->id,
            'from' => '2026-01-01',
            'to' => '2026-12-31',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('comparison.totalBudgeted', '180000.0000')
            ->where('comparison.totalActual', '170000.0000'));
    }

    public function test_a_half_year_range_prorates_the_budgeted_amount_to_roughly_half(): void
    {
        $user = User::factory()->create();
        $income = Account::factory()->create(['type' => 'income']);

        $budget = app(SaveBudget::class)->handle([
            'name' => 'FY2026', 'fiscal_year' => 2026, 'notes' => null, 'created_by' => $user->id,
            'lines' => [['account_id' => $income->id, 'amount' => 120000]],
        ]);

        // 2026-01-01 through 2026-06-30 is 181 of 365 days — not an exact
        // half, so assert the value lands in the expected neighborhood
        // rather than asserting a suspiciously round number.
        $response = $this->actingAs($user)->get(route('reports.budget-vs-actual', [
            'budget_id' => $budget->id,
            'from' => '2026-01-01',
            'to' => '2026-06-30',
        ]));

        $response->assertInertia(function ($page) {
            $page->where('comparison.rows.0.actual', '0.0000');
            $budgeted = (float) $page->toArray()['props']['comparison']['rows'][0]['budgeted'];
            $this->assertGreaterThan(58000, $budgeted);
            $this->assertLessThan(60000, $budgeted);

            return $page;
        });
    }
}
