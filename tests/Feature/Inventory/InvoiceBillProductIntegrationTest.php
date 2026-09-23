<?php

namespace Tests\Feature\Inventory;

use App\Actions\Accounting\PostJournal;
use App\Actions\Inventory\RecordInventorySaleMovement;
use App\Actions\Purchases\PostBill;
use App\Actions\Sales\PostInvoice;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Contacts\Vendor;
use App\Models\Inventory\Product;
use App\Models\Purchases\Bill;
use App\Models\Sales\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the Inventory <-> Invoice/Bill integration (Section 32): selecting
 * a product on a line, and posting the document recording a StockMovement
 * plus (for a sale) a companion COGS journal.
 */
class InvoiceBillProductIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_posting_an_invoice_with_an_inventory_product_reduces_stock_and_posts_cogs(): void
    {
        $product = Product::factory()->inventoryTracked()->create(['purchase_price' => 50, 'selling_price' => 120]);
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);

        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'status' => 'draft',
            'total' => 0,
        ]);
        $invoice->items()->create([
            'product_id' => $product->id,
            'account_id' => $product->income_account_id,
            'description' => $product->name,
            'quantity' => 3,
            'unit_price' => 120,
            'discount' => 0,
            'line_total' => 360,
        ]);

        (new PostInvoice(new PostJournal, new RecordInventorySaleMovement(new PostJournal)))->handle($invoice->fresh());

        $this->assertSame('-3.0000', $product->fresh()->currentStock());
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'quantity' => '-3.0000',
            'reason' => 'sale',
        ]);

        $movement = $product->stockMovements()->latest('id')->first();
        $this->assertNotNull($movement->journal);
        $this->assertTrue($movement->journal->isBalanced());
        $cogsDebit = $movement->journal->entries()->where('account_id', $product->cogs_account_id)->sum('debit');
        $this->assertSame('150.0000', bcadd((string) $cogsDebit, '0', 4));
    }

    public function test_posting_an_invoice_with_a_service_product_does_not_touch_stock(): void
    {
        $product = Product::factory()->create(['type' => 'service']);
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);

        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'status' => 'draft',
            'total' => 0,
        ]);
        $invoice->items()->create([
            'product_id' => $product->id,
            'account_id' => $product->income_account_id,
            'description' => $product->name,
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'line_total' => 100,
        ]);

        (new PostInvoice(new PostJournal, new RecordInventorySaleMovement(new PostJournal)))->handle($invoice->fresh());

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_posting_a_bill_against_the_inventory_account_increases_stock_with_no_companion_journal(): void
    {
        $product = Product::factory()->inventoryTracked()->create();
        $vendor = Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);

        $bill = Bill::factory()->create([
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'status' => 'draft',
            'total' => 0,
        ]);
        $bill->items()->create([
            'product_id' => $product->id,
            'account_id' => $product->inventory_account_id,
            'description' => $product->name,
            'quantity' => 10,
            'unit_price' => 50,
            'discount' => 0,
            'line_total' => 500,
        ]);

        (new PostBill(new PostJournal))->handle($bill->fresh());

        $this->assertSame('10.0000', $product->fresh()->currentStock());
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'quantity' => '10.0000',
            'reason' => 'purchase',
        ]);

        $movement = $product->stockMovements()->latest('id')->first();
        $this->assertNull($movement->journal);
    }
}
