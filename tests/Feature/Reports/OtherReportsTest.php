<?php

namespace Tests\Feature\Reports;

use App\Actions\Accounting\PostJournal;
use App\Actions\Expenses\RecordExpense;
use App\Actions\Purchases\PostBill;
use App\Actions\Sales\PostInvoice;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Contacts\Vendor;
use App\Models\Expenses\ExpenseCategory;
use App\Models\Inventory\Product;
use App\Models\Purchases\Bill;
use App\Models\Sales\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtherReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_blocked_from_every_report(): void
    {
        foreach ([
            'reports.cash-flow', 'reports.sales', 'reports.purchases', 'reports.expenses',
            'reports.customer-balances', 'reports.vendor-balances', 'reports.payments', 'reports.inventory',
        ] as $routeName) {
            $this->get(route($routeName))->assertRedirect(route('login'));
        }
    }

    public function test_cash_flow_shows_movement_for_bank_accounts_only(): void
    {
        $user = User::factory()->create();
        $bank = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true, 'opening_balance' => 1000]);
        $receivable = Account::factory()->create(['type' => 'asset', 'is_bank_account' => false]);
        $revenue = Account::factory()->create(['type' => 'income']);

        (new PostJournal)->handle([
            'date' => '2026-03-01',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => [
                ['account_id' => $bank->id, 'debit' => 200, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 200],
            ],
        ]);
        (new PostJournal)->handle([
            'date' => '2026-03-02',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => [
                ['account_id' => $receivable->id, 'debit' => 999, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 999],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('reports.cash-flow', ['from' => '2026-03-01', 'to' => '2026-03-31']));
        $props = $response->viewData('page')['props'];

        $accounts = collect($props['accounts']);
        $this->assertTrue($accounts->contains('id', $bank->id));
        $this->assertFalse($accounts->contains('id', $receivable->id));

        $row = $accounts->firstWhere('id', $bank->id);
        $this->assertSame('1000.0000', $row['opening']);
        $this->assertSame('200.0000', $row['in']);
        $this->assertSame('1200.0000', $row['closing']);
    }

    public function test_sales_report_groups_by_customer(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-03-10',
            'status' => 'draft',
            'total' => 0,
        ]);
        $invoice->items()->create(['account_id' => $income->id, 'description' => 'X', 'quantity' => 1, 'unit_price' => 300, 'discount' => 0, 'line_total' => 300]);
        (new PostInvoice(new PostJournal))->handle($invoice->fresh());

        $response = $this->actingAs($user)->get(route('reports.sales', ['from' => '2026-03-01', 'to' => '2026-03-31']));
        $props = $response->viewData('page')['props'];

        $this->assertSame('300.0000', $props['total']);
        $this->assertSame(1, $props['count']);
        $this->assertSame($customer->name, $props['rows'][0]['customer']);
    }

    public function test_purchase_report_groups_by_vendor(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);
        $expense = Account::factory()->create(['type' => 'expense']);

        $bill = Bill::factory()->create([
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'bill_date' => '2026-03-10',
            'status' => 'draft',
            'total' => 0,
        ]);
        $bill->items()->create(['account_id' => $expense->id, 'description' => 'X', 'quantity' => 1, 'unit_price' => 150, 'discount' => 0, 'line_total' => 150]);
        (new PostBill(new PostJournal))->handle($bill->fresh());

        $response = $this->actingAs($user)->get(route('reports.purchases', ['from' => '2026-03-01', 'to' => '2026-03-31']));
        $props = $response->viewData('page')['props'];

        $this->assertSame('150.0000', $props['total']);
    }

    public function test_expense_report_groups_by_category(): void
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
            'payee' => 'X',
            'expense_date' => '2026-03-10',
            'amount' => 75,
            'reference' => null,
            'notes' => null,
            'created_by' => null,
        ]);

        $response = $this->actingAs($user)->get(route('reports.expenses', ['from' => '2026-03-01', 'to' => '2026-03-31']));
        $props = $response->viewData('page')['props'];

        $this->assertSame('75.0000', $props['total']);
        $this->assertSame($category->name, $props['rows'][0]['category']);
    }

    public function test_customer_balances_reuses_current_balance_logic(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['opening_balance' => 250]);

        $response = $this->actingAs($user)->get(route('reports.customer-balances'));
        $props = $response->viewData('page')['props'];

        $row = collect($props['rows'])->firstWhere('id', $customer->id);
        $this->assertSame('250.0000', $row['balance']);
    }

    public function test_vendor_balances_reuses_current_balance_logic(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create(['opening_balance' => 400]);

        $response = $this->actingAs($user)->get(route('reports.vendor-balances'));
        $props = $response->viewData('page')['props'];

        $row = collect($props['rows'])->firstWhere('id', $vendor->id);
        $this->assertSame('400.0000', $row['balance']);
    }

    public function test_inventory_report_shows_stock_value(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->inventoryTracked()->create(['purchase_price' => 10]);
        $product->stockMovements()->create(['date' => '2026-01-01', 'quantity' => 20, 'reason' => 'opening']);

        $response = $this->actingAs($user)->get(route('reports.inventory'));
        $props = $response->viewData('page')['props'];

        $row = collect($props['rows'])->firstWhere('id', $product->id);
        $this->assertSame('20.0000', $row['current_stock']);
        $this->assertSame('200.0000', $row['stock_value']);
    }
}
