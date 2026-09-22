<?php

namespace Tests\Feature\Reports;

use App\Actions\Accounting\PostJournal;
use App\Actions\Banking\RecordTransfer;
use App\Actions\Expenses\RecordExpense;
use App\Models\Accounting\Account;
use App\Models\Expenses\ExpenseCategory;
use App\Models\Tax\TaxRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashFlowStatementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_blocked(): void
    {
        $this->get(route('reports.cash-flow-statement'))->assertRedirect(route('login'));
    }

    public function test_movements_are_classified_by_the_contra_accounts_category(): void
    {
        $user = User::factory()->create();
        $bank = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true, 'opening_balance' => 0]);
        $revenue = Account::factory()->create(['type' => 'income', 'cash_flow_category' => 'operating']);
        $equipment = Account::factory()->create(['type' => 'asset', 'cash_flow_category' => 'investing']);
        $loan = Account::factory()->create(['type' => 'liability', 'cash_flow_category' => 'financing']);

        // Operating: cash sale.
        (new PostJournal)->handle([
            'date' => '2026-03-05', 'reference' => null, 'description' => null, 'created_by' => null,
            'lines' => [
                ['account_id' => $bank->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        // Investing: sold equipment for cash.
        (new PostJournal)->handle([
            'date' => '2026-03-06', 'reference' => null, 'description' => null, 'created_by' => null,
            'lines' => [
                ['account_id' => $bank->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $equipment->id, 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        // Financing: drew down a loan.
        (new PostJournal)->handle([
            'date' => '2026-03-07', 'reference' => null, 'description' => null, 'created_by' => null,
            'lines' => [
                ['account_id' => $bank->id, 'debit' => 2000, 'credit' => 0],
                ['account_id' => $loan->id, 'debit' => 0, 'credit' => 2000],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('reports.cash-flow-statement', ['from' => '2026-03-01', 'to' => '2026-03-31']));
        $props = $response->viewData('page')['props'];

        $this->assertSame('500.0000', $props['totalOperating']);
        $this->assertSame('1000.0000', $props['totalInvesting']);
        $this->assertSame('2000.0000', $props['totalFinancing']);
        $this->assertSame('3500.0000', $props['netChange']);
        $this->assertSame('0.0000', $props['openingCash']);
        $this->assertSame('3500.0000', $props['closingCash']);

        $this->assertSame($revenue->id, $props['operating'][0]['id']);
        $this->assertSame($equipment->id, $props['investing'][0]['id']);
        $this->assertSame($loan->id, $props['financing'][0]['id']);
    }

    public function test_transfers_between_the_businesss_own_bank_accounts_are_excluded(): void
    {
        $user = User::factory()->create();
        $checking = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true, 'opening_balance' => 1000]);
        $savings = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true, 'opening_balance' => 0]);

        app(RecordTransfer::class)->handle([
            'from_account_id' => $checking->id,
            'to_account_id' => $savings->id,
            'transfer_date' => '2026-03-10',
            'amount' => 300,
            'reference' => null,
            'notes' => null,
            'created_by' => null,
        ]);

        $response = $this->actingAs($user)->get(route('reports.cash-flow-statement', ['from' => '2026-03-01', 'to' => '2026-03-31']));
        $props = $response->viewData('page')['props'];

        $this->assertSame('0.0000', $props['totalOperating']);
        $this->assertSame('0.0000', $props['totalInvesting']);
        $this->assertSame('0.0000', $props['totalFinancing']);
        $this->assertSame('0.0000', $props['netChange']);
        // Combined cash is unchanged by an internal transfer, even though it
        // moved between the two accounts.
        $this->assertSame('1000.0000', $props['openingCash']);
        $this->assertSame('1000.0000', $props['closingCash']);
    }

    public function test_a_multi_line_journal_splits_the_cash_movement_across_its_contra_lines(): void
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create();
        $bank = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true, 'opening_balance' => 0]);
        $expenseAccount = Account::factory()->create(['type' => 'expense', 'cash_flow_category' => 'operating']);
        $taxLiability = Account::factory()->create(['type' => 'liability', 'cash_flow_category' => 'operating']);
        $taxRate = TaxRate::factory()->create(['rate' => 15, 'tax_account_id' => $taxLiability->id]);

        app(RecordExpense::class)->handle([
            'expense_category_id' => $category->id,
            'account_id' => $expenseAccount->id,
            'payment_account_id' => $bank->id,
            'vendor_id' => null,
            'payee' => 'Office Supplies Co',
            'expense_date' => '2026-03-12',
            'amount' => 100,
            'tax_rate_id' => $taxRate->id,
            'reference' => null,
            'notes' => null,
            'created_by' => null,
        ]);

        $response = $this->actingAs($user)->get(route('reports.cash-flow-statement', ['from' => '2026-03-01', 'to' => '2026-03-31']));
        $props = $response->viewData('page')['props'];

        // Both the expense (100) and its tax (15) are operating, so the
        // full 115 cash outflow lands in operating regardless of the split.
        $this->assertSame('-115.0000', $props['totalOperating']);
    }
}
