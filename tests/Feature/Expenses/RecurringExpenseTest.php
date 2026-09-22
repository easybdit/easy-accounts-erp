<?php

namespace Tests\Feature\Expenses;

use App\Models\Accounting\Account;
use App\Models\Expenses\Expense;
use App\Models\Expenses\ExpenseCategory;
use App\Models\Expenses\RecurringExpense;
use App\Models\Tax\TaxRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        $category = ExpenseCategory::factory()->create();
        $expenseAccount = Account::factory()->create(['type' => 'expense']);
        $paymentAccount = Account::factory()->create(['type' => 'asset']);

        return array_merge([
            'name' => 'Monthly Office Rent',
            'expense_category_id' => $category->id,
            'account_id' => $expenseAccount->id,
            'payment_account_id' => $paymentAccount->id,
            'payee' => 'Landlord Co',
            'amount' => 500,
        ], $overrides);
    }

    public function test_guest_cannot_view_recurring_expenses(): void
    {
        $this->get(route('expenses.recurring.index'))->assertRedirect(route('login'));
    }

    public function test_a_template_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('expenses.recurring.store'), $this->payload())->assertRedirect();

        $template = RecurringExpense::first();
        $this->assertSame('Monthly Office Rent', $template->name);
        $this->assertTrue($template->is_active);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_generating_from_a_template_immediately_records_and_posts_an_expense(): void
    {
        $user = User::factory()->create();
        $taxLiability = Account::factory()->create(['type' => 'liability']);
        $taxRate = TaxRate::factory()->create(['rate' => 15, 'tax_account_id' => $taxLiability->id]);

        $this->actingAs($user)->post(route('expenses.recurring.store'), $this->payload(['tax_rate_id' => $taxRate->id]));
        $template = RecurringExpense::first();

        $response = $this->actingAs($user)->post(route('expenses.recurring.generate', $template));

        $this->assertDatabaseCount('expenses', 1);
        $expense = Expense::first();
        $response->assertRedirect(route('expenses.entries.show', $expense));
        $this->assertSame('500.0000', (string) $expense->amount);
        $this->assertSame('75.0000', (string) $expense->tax_amount);
        $this->assertNotNull($expense->journal);
        $this->assertTrue($expense->journal->isBalanced());
        $this->assertSame(now()->toDateString(), $expense->expense_date->toDateString());
    }

    public function test_a_template_can_be_updated_and_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('expenses.recurring.store'), $this->payload());
        $template = RecurringExpense::first();

        $this->actingAs($user)->put(route('expenses.recurring.update', $template), [
            ...$this->payload(['name' => 'Updated Rent', 'amount' => 600]),
        ])->assertRedirect();

        $this->assertSame('Updated Rent', $template->fresh()->name);
        $this->assertSame('600.0000', (string) $template->fresh()->amount);

        $this->actingAs($user)->delete(route('expenses.recurring.destroy', $template))->assertRedirect();
        $this->assertDatabaseCount('recurring_expenses', 0);
    }

    public function test_a_template_with_no_next_generation_date_stays_manual_only(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('expenses.recurring.store'), $this->payload());

        $this->artisan('expenses:generate-recurring')->assertSuccessful();

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_the_scheduled_command_records_an_expense_and_advances_the_next_generation_date_by_one_month(): void
    {
        $user = User::factory()->create();
        $dueDate = now()->toDateString();
        $this->actingAs($user)->post(route('expenses.recurring.store'), $this->payload([
            'next_generation_date' => $dueDate,
        ]));
        $template = RecurringExpense::first();

        $this->artisan('expenses:generate-recurring')->assertSuccessful();

        $this->assertDatabaseCount('expenses', 1);
        $expense = Expense::first();
        $this->assertNotNull($expense->journal);
        $this->assertTrue($expense->journal->isBalanced());

        $this->assertSame(now()->addMonthNoOverflow()->toDateString(), $template->fresh()->next_generation_date->toDateString());

        // Running it again the same day does not record a second expense —
        // the due date has already moved a month into the future.
        $this->artisan('expenses:generate-recurring')->assertSuccessful();
        $this->assertDatabaseCount('expenses', 1);
    }

    public function test_an_inactive_template_is_not_auto_generated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('expenses.recurring.store'), $this->payload([
            'next_generation_date' => now()->toDateString(),
            'is_active' => false,
        ]));

        $this->artisan('expenses:generate-recurring')->assertSuccessful();

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_a_future_next_generation_date_is_not_generated_early(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('expenses.recurring.store'), $this->payload([
            'next_generation_date' => now()->addMonth()->toDateString(),
        ]));

        $this->artisan('expenses:generate-recurring')->assertSuccessful();

        $this->assertDatabaseCount('expenses', 0);
    }
}
