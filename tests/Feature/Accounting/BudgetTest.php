<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Budget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        $income = Account::factory()->create(['type' => 'income']);
        $expense = Account::factory()->create(['type' => 'expense']);

        return array_merge([
            'name' => 'FY2026 Operating Budget',
            'fiscal_year' => 2026,
            'lines' => [
                ['account_id' => $income->id, 'amount' => 120000],
                ['account_id' => $expense->id, 'amount' => 60000],
            ],
        ], $overrides);
    }

    public function test_guest_cannot_view_budgets(): void
    {
        $this->get(route('accounting.budgets.index'))->assertRedirect(route('login'));
    }

    public function test_a_budget_can_be_created_with_lines(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('accounting.budgets.store'), $this->payload())->assertRedirect();

        $budget = Budget::first();
        $this->assertSame('FY2026 Operating Budget', $budget->name);
        $this->assertSame(2026, $budget->fiscal_year);
        $this->assertCount(2, $budget->lines);
    }

    public function test_a_zero_amount_line_is_not_persisted(): void
    {
        $user = User::factory()->create();
        $income = Account::factory()->create(['type' => 'income']);

        $this->actingAs($user)->post(route('accounting.budgets.store'), $this->payload([
            'lines' => [['account_id' => $income->id, 'amount' => 0]],
        ]))->assertRedirect();

        $this->assertCount(0, Budget::first()->lines);
    }

    public function test_a_budget_line_must_use_an_income_or_expense_account(): void
    {
        $user = User::factory()->create();
        $asset = Account::factory()->create(['type' => 'asset']);

        $response = $this->actingAs($user)->post(route('accounting.budgets.store'), $this->payload([
            'lines' => [['account_id' => $asset->id, 'amount' => 100]],
        ]));

        $response->assertSessionHasErrors('lines.0.account_id');
        $this->assertDatabaseCount('budgets', 0);
    }

    public function test_updating_a_budget_replaces_its_lines(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('accounting.budgets.store'), $this->payload());
        $budget = Budget::first();
        $newIncome = Account::factory()->create(['type' => 'income']);

        $this->actingAs($user)->put(route('accounting.budgets.update', $budget), [
            'name' => 'Revised Budget',
            'fiscal_year' => 2026,
            'lines' => [['account_id' => $newIncome->id, 'amount' => 999]],
        ])->assertRedirect();

        $budget->refresh();
        $this->assertSame('Revised Budget', $budget->name);
        $this->assertCount(1, $budget->lines);
        $this->assertSame('999.0000', (string) $budget->lines->first()->amount);
    }

    public function test_a_budget_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('accounting.budgets.store'), $this->payload());
        $budget = Budget::first();

        $this->actingAs($user)->delete(route('accounting.budgets.destroy', $budget))->assertRedirect();

        $this->assertDatabaseCount('budgets', 0);
        $this->assertDatabaseCount('budget_lines', 0);
    }

    public function test_a_viewer_cannot_create_a_budget(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Viewer']);

        $response = $this->actingAs($user)->post(route('accounting.budgets.store'), $this->payload());

        $response->assertForbidden();
        $this->assertDatabaseCount('budgets', 0);
    }
}
