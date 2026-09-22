<?php

namespace Tests\Feature;

use App\Actions\Accounting\PostJournal;
use App\Actions\Purchases\PostBill;
use App\Actions\Sales\PostInvoice;
use App\Models\Accounting\Account;
use App\Models\Banking\Transfer;
use App\Models\Contacts\Customer;
use App\Models\Contacts\Vendor;
use App\Models\Expenses\Expense;
use App\Models\Expenses\ExpenseCategory;
use App\Models\Inventory\Product;
use App\Models\Inventory\StockMovement;
use App\Models\Purchases\Bill;
use App\Models\Purchases\PurchaseOrder;
use App\Models\Purchases\VendorCredit;
use App\Models\Purchases\VendorPayment;
use App\Models\Sales\CreditNote;
use App\Models\Sales\Estimate;
use App\Models\Sales\Invoice;
use App\Models\Sales\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the same bug fixed in Journal (Accounting\
 * JournalTest::test_journal_dates_render_as_plain_dates_not_full_timestamps):
 * a raw Eloquent model passed straight to Inertia serializes its 'date'-cast
 * attributes as full ISO8601 timestamps (e.g. "2026-01-10T00:00:00.000000Z")
 * instead of a plain "Y-m-d" string. Every Show/Index page that displayed a
 * document date this way is covered here, one assertion per page, so this
 * class of bug can't silently return.
 */
class DateDisplayRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function postedInvoice(): Invoice
    {
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-10',
            'status' => 'draft',
            'total' => 0,
        ]);
        $invoice->items()->create([
            'account_id' => $income->id,
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => 1000,
            'discount' => 0,
            'line_total' => 1000,
        ]);

        return (new PostInvoice(new PostJournal))->handle($invoice->fresh());
    }

    private function postedBill(): Bill
    {
        $vendor = Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);
        $expense = Account::factory()->create(['type' => 'expense']);

        $bill = Bill::factory()->create([
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'bill_date' => '2026-01-10',
            'status' => 'draft',
            'total' => 0,
        ]);
        $bill->items()->create([
            'account_id' => $expense->id,
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => 1000,
            'discount' => 0,
            'line_total' => 1000,
        ]);

        return (new PostBill(new PostJournal))->handle($bill->fresh());
    }

    public function test_invoice_show_renders_plain_dates_including_the_nested_payment_allocation(): void
    {
        $user = User::factory()->create();
        $invoice = $this->postedInvoice();
        $bank = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('sales.payments.store'), [
            'customer_id' => $invoice->customer_id,
            'deposit_account_id' => $bank->id,
            'payment_date' => '2026-01-20',
            'amount' => 1000,
            'allocations' => [['invoice_id' => $invoice->id, 'amount' => 1000]],
        ]);

        $this->actingAs($user)->get(route('sales.invoices.show', $invoice))
            ->assertInertia(fn ($page) => $page
                ->where('invoice.invoice_date', '2026-01-10')
                ->where('invoice.payment_allocations.0.payment.payment_date', '2026-01-20')
            );
    }

    public function test_bill_show_renders_plain_dates_including_the_nested_payment_allocation(): void
    {
        $user = User::factory()->create();
        $bill = $this->postedBill();
        $cash = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('purchases.vendor-payments.store'), [
            'vendor_id' => $bill->vendor_id,
            'payment_account_id' => $cash->id,
            'payment_date' => '2026-01-22',
            'amount' => 1000,
            'allocations' => [['bill_id' => $bill->id, 'amount' => 1000]],
        ]);

        $this->actingAs($user)->get(route('purchases.bills.show', $bill))
            ->assertInertia(fn ($page) => $page
                ->where('bill.bill_date', '2026-01-10')
                ->where('bill.payment_allocations.0.vendor_payment.payment_date', '2026-01-22')
            );
    }

    public function test_estimate_show_renders_plain_dates(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $this->actingAs($user)->post(route('sales.estimates.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'estimate_date' => '2026-01-10',
            'expiry_date' => '2026-02-10',
            'items' => [['account_id' => $income->id, 'description' => 'Design work', 'quantity' => 1, 'unit_price' => 100, 'discount' => 0]],
        ]);
        $estimate = Estimate::first();

        $this->actingAs($user)->get(route('sales.estimates.show', $estimate))
            ->assertInertia(fn ($page) => $page
                ->where('estimate.estimate_date', '2026-01-10')
                ->where('estimate.expiry_date', '2026-02-10')
            );
    }

    public function test_credit_note_show_renders_plain_dates(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $this->actingAs($user)->post(route('sales.credit-notes.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'credit_note_date' => '2026-01-15',
            'items' => [['account_id' => $income->id, 'description' => 'Refund', 'quantity' => 1, 'unit_price' => 150, 'discount' => 0]],
        ]);
        $creditNote = CreditNote::first();

        $this->actingAs($user)->get(route('sales.credit-notes.show', $creditNote))
            ->assertInertia(fn ($page) => $page->where('creditNote.credit_note_date', '2026-01-15'));
    }

    public function test_purchase_order_show_renders_plain_dates(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);
        $expense = Account::factory()->create(['type' => 'expense']);

        $this->actingAs($user)->post(route('purchases.purchase-orders.store'), [
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'order_date' => '2026-01-10',
            'expected_date' => '2026-01-20',
            'items' => [['account_id' => $expense->id, 'description' => 'Supplies', 'quantity' => 1, 'unit_price' => 50, 'discount' => 0]],
        ]);
        $po = PurchaseOrder::first();

        $this->actingAs($user)->get(route('purchases.purchase-orders.show', $po))
            ->assertInertia(fn ($page) => $page
                ->where('purchaseOrder.order_date', '2026-01-10')
                ->where('purchaseOrder.expected_date', '2026-01-20')
            );
    }

    public function test_vendor_credit_show_renders_plain_dates(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);
        $expense = Account::factory()->create(['type' => 'expense']);

        $this->actingAs($user)->post(route('purchases.vendor-credits.store'), [
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'vendor_credit_date' => '2026-01-15',
            'items' => [['account_id' => $expense->id, 'description' => 'Return', 'quantity' => 1, 'unit_price' => 150, 'discount' => 0]],
        ]);
        $vendorCredit = VendorCredit::first();

        $this->actingAs($user)->get(route('purchases.vendor-credits.show', $vendorCredit))
            ->assertInertia(fn ($page) => $page->where('vendorCredit.vendor_credit_date', '2026-01-15'));
    }

    public function test_expense_show_renders_a_plain_date(): void
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create();
        $expenseAccount = Account::factory()->create(['type' => 'expense']);
        $paymentAccount = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('expenses.entries.store'), [
            'expense_category_id' => $category->id,
            'account_id' => $expenseAccount->id,
            'payment_account_id' => $paymentAccount->id,
            'payee' => 'City Power Co',
            'expense_date' => '2026-01-10',
            'amount' => 100,
        ]);
        $expense = Expense::first();

        $this->actingAs($user)->get(route('expenses.entries.show', $expense))
            ->assertInertia(fn ($page) => $page->where('expense.expense_date', '2026-01-10'));
    }

    public function test_transfer_index_and_show_render_plain_dates(): void
    {
        $user = User::factory()->create();
        $cash = Account::factory()->create(['type' => 'asset']);
        $bank = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('banking.transfers.store'), [
            'from_account_id' => $cash->id,
            'to_account_id' => $bank->id,
            'transfer_date' => '2026-01-10',
            'amount' => 500,
        ]);
        $transfer = Transfer::first();

        $this->actingAs($user)->get(route('banking.transfers.index'))
            ->assertInertia(fn ($page) => $page->where('transfers.data.0.transfer_date', '2026-01-10'));

        $this->actingAs($user)->get(route('banking.transfers.show', $transfer))
            ->assertInertia(fn ($page) => $page->where('transfer.transfer_date', '2026-01-10'));
    }

    public function test_vendor_payment_index_and_show_render_plain_dates(): void
    {
        $user = User::factory()->create();
        $bill = $this->postedBill();
        $cash = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('purchases.vendor-payments.store'), [
            'vendor_id' => $bill->vendor_id,
            'payment_account_id' => $cash->id,
            'payment_date' => '2026-01-20',
            'amount' => 1000,
            'allocations' => [['bill_id' => $bill->id, 'amount' => 1000]],
        ]);
        $payment = VendorPayment::first();

        $this->actingAs($user)->get(route('purchases.vendor-payments.index'))
            ->assertInertia(fn ($page) => $page->where('payments.data.0.payment_date', '2026-01-20'));

        $this->actingAs($user)->get(route('purchases.vendor-payments.show', $payment))
            ->assertInertia(fn ($page) => $page->where('payment.payment_date', '2026-01-20'));
    }

    public function test_sales_payment_index_and_show_render_plain_dates(): void
    {
        $user = User::factory()->create();
        $invoice = $this->postedInvoice();
        $bank = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('sales.payments.store'), [
            'customer_id' => $invoice->customer_id,
            'deposit_account_id' => $bank->id,
            'payment_date' => '2026-01-20',
            'amount' => 1000,
            'allocations' => [['invoice_id' => $invoice->id, 'amount' => 1000]],
        ]);
        $payment = Payment::first();

        $this->actingAs($user)->get(route('sales.payments.index'))
            ->assertInertia(fn ($page) => $page->where('payments.data.0.payment_date', '2026-01-20'));

        $this->actingAs($user)->get(route('sales.payments.show', $payment))
            ->assertInertia(fn ($page) => $page->where('payment.payment_date', '2026-01-20'));
    }

    public function test_stock_movement_index_and_show_render_plain_dates(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->inventoryTracked()->create();
        $product->stockMovements()->create(['date' => '2026-01-01', 'quantity' => 20, 'reason' => 'opening']);

        $this->actingAs($user)->post(route('inventory.stock-movements.store'), [
            'product_id' => $product->id,
            'counted_quantity' => 25,
            'date' => '2026-01-15',
        ]);
        $movement = StockMovement::where('reason', 'adjustment')->first();

        $this->actingAs($user)->get(route('inventory.stock-movements.index'))
            ->assertInertia(fn ($page) => $page->where('movements.data.0.date', '2026-01-15'));

        $this->actingAs($user)->get(route('inventory.stock-movements.show', $movement))
            ->assertInertia(fn ($page) => $page->where('movement.date', '2026-01-15'));
    }

    public function test_reconciliation_history_renders_a_plain_statement_date(): void
    {
        $user = User::factory()->create();
        $bank = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true, 'opening_balance' => 0]);
        $revenue = Account::factory()->create(['type' => 'income']);

        (new PostJournal)->handle([
            'date' => '2026-01-05',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => [
                ['account_id' => $bank->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);
        $entryId = $bank->journalEntries()->value('id');

        $this->actingAs($user)->post(route('banking.reconciliation.store', $bank), [
            'statement_date' => '2026-01-10',
            'statement_balance' => 500,
            'entry_ids' => [$entryId],
        ]);

        $this->actingAs($user)->get(route('banking.reconciliation.index', $bank))
            ->assertInertia(fn ($page) => $page->where('history.0.statement_date', '2026-01-10'));
    }
}
