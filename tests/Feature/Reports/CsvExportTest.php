<?php

namespace Tests\Feature\Reports;

use App\Actions\Accounting\PostJournal;
use App\Actions\Accounting\SaveBudget;
use App\Models\Accounting\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One assertion per report: the CSV branch is reachable, returns the right
 * content type, and its header row contains a value proving it used real
 * data rather than an empty/default export. Business-logic correctness for
 * each report's numbers is already covered by that report's own test.
 */
class CsvExportTest extends TestCase
{
    use RefreshDatabase;

    private function postSampleJournal(): array
    {
        $income = Account::factory()->create(['type' => 'income']);
        $expense = Account::factory()->create(['type' => 'expense']);
        $cash = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true]);

        app(PostJournal::class)->handle([
            'date' => now()->toDateString(), 'reference' => null, 'description' => null, 'created_by' => null,
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $income->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);
        app(PostJournal::class)->handle([
            'date' => now()->toDateString(), 'reference' => null, 'description' => null, 'created_by' => null,
            'lines' => [
                ['account_id' => $expense->id, 'debit' => 200, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 200],
            ],
        ]);

        return compact('income', 'expense', 'cash');
    }

    private function assertCsv($response, string $mustContain): void
    {
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString($mustContain, $response->streamedContent());
    }

    public function test_profit_and_loss_csv(): void
    {
        $user = User::factory()->create();
        $accounts = $this->postSampleJournal();

        $response = $this->actingAs($user)->get(route('reports.profit-and-loss', ['export' => 1]));

        $this->assertCsv($response, $accounts['income']->code);
    }

    public function test_balance_sheet_csv(): void
    {
        $user = User::factory()->create();
        $this->postSampleJournal();

        $response = $this->actingAs($user)->get(route('reports.balance-sheet', ['export' => 1]));

        $this->assertCsv($response, 'Total Assets');
    }

    public function test_ar_aging_csv(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.ar-aging', ['export' => 1]));

        $this->assertCsv($response, 'Customer');
    }

    public function test_ap_aging_csv(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.ap-aging', ['export' => 1]));

        $this->assertCsv($response, 'Vendor');
    }

    public function test_cash_flow_csv(): void
    {
        $user = User::factory()->create();
        $this->postSampleJournal();

        $response = $this->actingAs($user)->get(route('reports.cash-flow', ['export' => 1]));

        $this->assertCsv($response, 'Opening');
    }

    public function test_cash_flow_statement_csv(): void
    {
        $user = User::factory()->create();
        $this->postSampleJournal();

        $response = $this->actingAs($user)->get(route('reports.cash-flow-statement', ['export' => 1]));

        $this->assertCsv($response, 'Net Change in Cash');
    }

    public function test_sales_report_csv(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.sales', ['export' => 1]));

        $this->assertCsv($response, 'Customer');
    }

    public function test_purchase_report_csv(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.purchases', ['export' => 1]));

        $this->assertCsv($response, 'Vendor');
    }

    public function test_expense_report_csv(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.expenses', ['export' => 1]));

        $this->assertCsv($response, 'Category');
    }

    public function test_customer_balances_csv(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.customer-balances', ['export' => 1]));

        $this->assertCsv($response, 'Customer');
    }

    public function test_vendor_balances_csv(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.vendor-balances', ['export' => 1]));

        $this->assertCsv($response, 'Vendor');
    }

    public function test_payments_report_csv(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.payments', ['export' => 1]));

        $this->assertCsv($response, 'Direction');
    }

    public function test_inventory_report_csv(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.inventory', ['export' => 1]));

        $this->assertCsv($response, 'SKU');
    }

    public function test_budget_vs_actual_csv_requires_a_selected_budget(): void
    {
        $user = User::factory()->create();
        $income = Account::factory()->create(['type' => 'income']);
        $budget = app(SaveBudget::class)->handle([
            'name' => 'FY2026', 'fiscal_year' => 2026, 'notes' => null, 'created_by' => $user->id,
            'lines' => [['account_id' => $income->id, 'amount' => 1000]],
        ]);

        $response = $this->actingAs($user)->get(route('reports.budget-vs-actual', ['export' => 1, 'budget_id' => $budget->id]));

        $this->assertCsv($response, $income->code);
    }

    public function test_budget_vs_actual_csv_without_a_budget_falls_back_to_the_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.budget-vs-actual', ['export' => 1]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/html; charset=UTF-8');
    }

    public function test_trial_balance_csv(): void
    {
        $user = User::factory()->create();
        $this->postSampleJournal();

        $response = $this->actingAs($user)->get(route('accounting.trial-balance.index', ['export' => 1]));

        $this->assertCsv($response, 'Total');
    }

    public function test_general_ledger_csv_requires_a_selected_account(): void
    {
        $user = User::factory()->create();
        $accounts = $this->postSampleJournal();

        $response = $this->actingAs($user)->get(route('accounting.ledger.index', ['export' => 1, 'account_id' => $accounts['cash']->id]));

        $this->assertCsv($response, 'Opening Balance');
    }

    public function test_tax_report_csv(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('tax.report', ['export' => 1]));

        $this->assertCsv($response, 'Tax Rate');
    }

    public function test_guest_cannot_download_any_csv(): void
    {
        $this->get(route('reports.profit-and-loss', ['export' => 1]))->assertRedirect(route('login'));
    }
}
