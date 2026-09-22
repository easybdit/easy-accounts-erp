<?php

namespace Tests\Feature\Expenses;

use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Expenses\Expense;
use App\Models\Expenses\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        $category = ExpenseCategory::factory()->create();
        $expenseAccount = Account::factory()->create(['type' => 'expense']);
        $paymentAccount = Account::factory()->create(['type' => 'asset']);

        return array_merge([
            'expense_category_id' => $category->id,
            'account_id' => $expenseAccount->id,
            'payment_account_id' => $paymentAccount->id,
            'payee' => 'City Power Co',
            'expense_date' => '2026-01-10',
            'amount' => 150,
        ], $overrides);
    }

    public function test_guest_cannot_view_expenses(): void
    {
        $this->get(route('expenses.entries.index'))->assertRedirect(route('login'));
    }

    public function test_expense_is_recorded_and_posted_immediately(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('expenses.entries.store'), $this->payload());

        $response->assertRedirect();
        $expense = Expense::first();
        $this->assertNotNull($expense);
        $this->assertNotNull($expense->journal);
        $this->assertTrue($expense->journal->isBalanced());
        $this->assertSame('150.0000', $expense->journal->totalDebit());
    }

    public function test_expense_account_must_be_an_expense_type_account(): void
    {
        $user = User::factory()->create();
        $incomeAsExpense = Account::factory()->create(['type' => 'income']);

        $response = $this->actingAs($user)->post(
            route('expenses.entries.store'),
            $this->payload(['account_id' => $incomeAsExpense->id])
        );

        $response->assertSessionHasErrors('account_id');
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_payment_account_must_be_an_asset_account(): void
    {
        $user = User::factory()->create();
        $expenseAsPayment = Account::factory()->create(['type' => 'expense']);

        $response = $this->actingAs($user)->post(
            route('expenses.entries.store'),
            $this->payload(['payment_account_id' => $expenseAsPayment->id])
        );

        $response->assertSessionHasErrors('payment_account_id');
    }

    public function test_expense_can_optionally_be_linked_to_a_vendor_without_affecting_vendor_balance(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create(['opening_balance' => 1000]);

        $this->actingAs($user)->post(route('expenses.entries.store'), $this->payload([
            'vendor_id' => $vendor->id,
            'payee' => $vendor->name,
        ]))->assertRedirect();

        $expense = Expense::first();
        $this->assertSame($vendor->id, $expense->vendor_id);

        // The vendor's Accounts Payable balance must be untouched by a cash
        // expense — it isn't a payable movement (see Expense model docblock).
        $this->assertSame('1000.0000', $vendor->fresh()->currentBalance());
    }

    public function test_no_edit_or_delete_routes_exist_for_expenses(): void
    {
        $this->assertFalse(Route::has('expenses.entries.edit'));
        $this->assertFalse(Route::has('expenses.entries.update'));
        $this->assertFalse(Route::has('expenses.entries.destroy'));
    }

    public function test_show_page_renders(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('expenses.entries.store'), $this->payload());
        $expense = Expense::first();

        $this->actingAs($user)->get(route('expenses.entries.show', $expense))->assertOk();
    }

    /**
     * Regression test: the "{expense}" route wildcard must match the
     * controller's $expense parameter name so implicit route model binding
     * actually resolves the requested record. It previously used "{entry}",
     * which silently bound nothing — Inertia rendered a blank Expense on
     * every visit rather than erroring, so no prior test caught it.
     */
    public function test_show_page_renders_the_requested_expenses_own_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('expenses.entries.store'), $this->payload(['payee' => 'Distinctive Payee Name']));
        $expense = Expense::first();

        $this->actingAs($user)->get(route('expenses.entries.show', $expense))
            ->assertInertia(fn ($page) => $page->where('expense.payee', 'Distinctive Payee Name'));
    }
}
