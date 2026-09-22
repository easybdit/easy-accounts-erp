<?php

namespace Tests\Feature\Expenses;

use App\Actions\Expenses\RecordExpense;
use App\Models\Accounting\Account;
use App\Models\Expenses\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_categories(): void
    {
        $this->get(route('expenses.categories.index'))->assertRedirect(route('login'));
    }

    public function test_category_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('expenses.categories.store'), [
            'name' => 'Office Supplies',
        ])->assertRedirect(route('expenses.categories.index'));

        $this->assertDatabaseHas('expense_categories', ['name' => 'Office Supplies']);
    }

    public function test_category_name_must_be_unique(): void
    {
        ExpenseCategory::factory()->create(['name' => 'Utilities']);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('expenses.categories.store'), [
            'name' => 'Utilities',
        ])->assertSessionHasErrors('name');
    }

    public function test_category_with_expenses_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create();
        $expenseAccount = Account::factory()->create(['type' => 'expense']);
        $paymentAccount = Account::factory()->create(['type' => 'asset']);

        app(RecordExpense::class)->handle([
            'expense_category_id' => $category->id,
            'account_id' => $expenseAccount->id,
            'payment_account_id' => $paymentAccount->id,
            'vendor_id' => null,
            'payee' => 'Some Payee',
            'expense_date' => '2026-01-10',
            'amount' => 50,
            'reference' => null,
            'notes' => null,
            'created_by' => null,
        ]);

        $this->actingAs($user)->delete(route('expenses.categories.destroy', $category))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('expense_categories', ['id' => $category->id]);
    }

    public function test_category_without_expenses_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create();

        $this->actingAs($user)->delete(route('expenses.categories.destroy', $category))
            ->assertRedirect(route('expenses.categories.index'));

        $this->assertDatabaseMissing('expense_categories', ['id' => $category->id]);
    }
}
