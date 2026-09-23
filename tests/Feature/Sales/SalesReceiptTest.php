<?php

namespace Tests\Feature\Sales;

use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Inventory\Product;
use App\Models\Sales\SalesReceipt;
use App\Models\Tax\TaxRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the Sales Receipt feature (Section 12/28): an immediate cash sale,
 * posted atomically at creation — no draft state, no Accounts Receivable
 * step, same lifecycle as a customer Payment.
 */
class SalesReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_sales_receipts(): void
    {
        $this->get(route('sales.sales-receipts.index'))->assertRedirect(route('login'));
    }

    public function test_a_sales_receipt_is_recorded_and_posted_immediately(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $bank = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $response = $this->actingAs($user)->post(route('sales.sales-receipts.store'), [
            'customer_id' => $customer->id,
            'deposit_account_id' => $bank->id,
            'receipt_date' => '2026-01-10',
            'items' => [
                ['account_id' => $income->id, 'description' => 'Cash sale', 'quantity' => 1, 'unit_price' => 500, 'discount' => 0],
            ],
        ]);

        $response->assertRedirect();

        $receipt = SalesReceipt::first();
        $this->assertNotNull($receipt);
        $this->assertSame('500.0000', $receipt->total);
        $this->assertNotNull($receipt->fresh()->journal);
    }

    public function test_receipt_number_follows_the_sr_prefix_by_default(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $bank = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $this->actingAs($user)->post(route('sales.sales-receipts.store'), [
            'customer_id' => $customer->id,
            'deposit_account_id' => $bank->id,
            'receipt_date' => '2026-01-10',
            'items' => [
                ['account_id' => $income->id, 'description' => 'Cash sale', 'quantity' => 1, 'unit_price' => 500, 'discount' => 0],
            ],
        ]);

        $this->assertSame('SR-2026-0001', SalesReceipt::first()->receipt_number);
    }

    public function test_posting_creates_a_balanced_journal_debiting_the_deposit_account(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $bank = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $this->actingAs($user)->post(route('sales.sales-receipts.store'), [
            'customer_id' => $customer->id,
            'deposit_account_id' => $bank->id,
            'receipt_date' => '2026-01-10',
            'items' => [
                ['account_id' => $income->id, 'description' => 'Cash sale', 'quantity' => 2, 'unit_price' => 250, 'discount' => 0],
            ],
        ]);

        $journal = SalesReceipt::first()->journal;
        $this->assertTrue($journal->isBalanced());
        $this->assertSame('500.0000', $journal->totalDebit());

        $depositLine = $journal->entries->firstWhere('account_id', $bank->id);
        $this->assertSame('500.0000', $depositLine->debit);

        $incomeLine = $journal->entries->firstWhere('account_id', $income->id);
        $this->assertSame('500.0000', $incomeLine->credit);
    }

    public function test_tax_is_included_in_the_total_and_credited_to_the_tax_account(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $bank = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);
        $taxLiability = Account::factory()->create(['type' => 'liability']);
        $taxRate = TaxRate::factory()->create(['rate' => 15, 'tax_account_id' => $taxLiability->id]);

        $this->actingAs($user)->post(route('sales.sales-receipts.store'), [
            'customer_id' => $customer->id,
            'deposit_account_id' => $bank->id,
            'receipt_date' => '2026-01-10',
            'items' => [
                ['account_id' => $income->id, 'tax_rate_id' => $taxRate->id, 'description' => 'Widget', 'quantity' => 1, 'unit_price' => 100, 'discount' => 0],
            ],
        ]);

        $receipt = SalesReceipt::first();
        $this->assertSame('15.0000', $receipt->tax_total);
        $this->assertSame('115.0000', $receipt->total);

        $taxLine = $receipt->journal->entries->firstWhere('account_id', $taxLiability->id);
        $this->assertSame('15.0000', $taxLine->credit);
    }

    public function test_deposit_account_must_be_an_asset_account(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $incomeAsDeposit = Account::factory()->create(['type' => 'income']);
        $income = Account::factory()->create(['type' => 'income']);

        $response = $this->actingAs($user)->post(route('sales.sales-receipts.store'), [
            'customer_id' => $customer->id,
            'deposit_account_id' => $incomeAsDeposit->id,
            'receipt_date' => '2026-01-10',
            'items' => [
                ['account_id' => $income->id, 'description' => 'Cash sale', 'quantity' => 1, 'unit_price' => 500, 'discount' => 0],
            ],
        ]);

        $response->assertSessionHasErrors('deposit_account_id');
        $this->assertDatabaseCount('sales_receipts', 0);
    }

    public function test_selling_an_inventory_tracked_product_records_a_stock_movement_and_cogs_journal(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->inventoryTracked()->create(['purchase_price' => 50, 'selling_price' => 120]);
        $customer = Customer::factory()->create();
        $bank = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('sales.sales-receipts.store'), [
            'customer_id' => $customer->id,
            'deposit_account_id' => $bank->id,
            'receipt_date' => '2026-01-10',
            'items' => [
                ['product_id' => $product->id, 'account_id' => $product->income_account_id, 'description' => $product->name, 'quantity' => 3, 'unit_price' => 120, 'discount' => 0],
            ],
        ]);

        $this->assertSame('-3.0000', $product->fresh()->currentStock());

        $movement = $product->stockMovements()->latest('id')->first();
        $this->assertNotNull($movement);
        $this->assertSame('sale', $movement->reason);
        $this->assertNotNull($movement->journal);
        $this->assertTrue($movement->journal->isBalanced());

        $cogsDebit = $movement->journal->entries()->where('account_id', $product->cogs_account_id)->sum('debit');
        $this->assertSame('150.0000', bcadd((string) $cogsDebit, '0', 4));
    }

    public function test_a_zero_total_receipt_is_rejected(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $bank = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $response = $this->actingAs($user)->post(route('sales.sales-receipts.store'), [
            'customer_id' => $customer->id,
            'deposit_account_id' => $bank->id,
            'receipt_date' => '2026-01-10',
            'items' => [
                ['account_id' => $income->id, 'description' => 'Free sample', 'quantity' => 1, 'unit_price' => 0, 'discount' => 0],
            ],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('sales_receipts', 0);
    }
}
