<?php

namespace Tests\Feature\Tax;

use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Contacts\Vendor;
use App\Models\Purchases\Bill;
use App\Models\Sales\Invoice;
use App\Models\Tax\TaxRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers Section 33's Tax Inclusive pricing mode: the entered unit price
 * already contains tax, so the net line_total is backed out of it instead
 * of tax being added on top.
 */
class TaxInclusivePricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_inclusive_invoice_backs_the_net_amount_out_of_the_entered_price(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);
        $taxLiability = Account::factory()->create(['type' => 'liability']);
        $taxRate = TaxRate::factory()->create(['rate' => 15, 'tax_account_id' => $taxLiability->id]);

        $this->actingAs($user)->post(route('sales.invoices.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-10',
            'tax_inclusive' => true,
            'items' => [
                ['account_id' => $income->id, 'tax_rate_id' => $taxRate->id, 'description' => 'Widget', 'quantity' => 1, 'unit_price' => 115, 'discount' => 0],
            ],
        ]);

        $invoice = Invoice::first();
        $this->assertTrue($invoice->tax_inclusive);
        $this->assertSame('15.0000', $invoice->tax_total);
        // Unlike exclusive mode, the entered price IS the total — tax isn't
        // added on top of it a second time.
        $this->assertSame('115.0000', $invoice->total);
        $this->assertSame('100.0000', (string) $invoice->items->first()->line_total);

        $this->actingAs($user)->post(route('sales.invoices.post', $invoice));

        $journal = $invoice->fresh()->journal;
        $this->assertTrue($journal->isBalanced());
        $this->assertSame('115.0000', $journal->totalDebit());

        $taxLine = $journal->entries->firstWhere('account_id', $taxLiability->id);
        $this->assertSame('15.0000', $taxLine->credit);

        $incomeLine = $journal->entries->firstWhere('account_id', $income->id);
        $this->assertSame('100.0000', $incomeLine->credit);
    }

    public function test_tax_exclusive_remains_the_default_when_the_flag_is_omitted(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);
        $taxRate = TaxRate::factory()->create(['rate' => 15]);

        $this->actingAs($user)->post(route('sales.invoices.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-10',
            'items' => [
                ['account_id' => $income->id, 'tax_rate_id' => $taxRate->id, 'description' => 'Widget', 'quantity' => 1, 'unit_price' => 100, 'discount' => 0],
            ],
        ]);

        $invoice = Invoice::first();
        $this->assertFalse($invoice->tax_inclusive);
        $this->assertSame('115.0000', $invoice->total);
    }

    public function test_tax_inclusive_bill_backs_the_net_amount_out_of_the_entered_price(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);
        $expense = Account::factory()->create(['type' => 'expense']);
        $taxLiability = Account::factory()->create(['type' => 'liability']);
        $taxRate = TaxRate::factory()->create(['rate' => 15, 'tax_account_id' => $taxLiability->id]);

        $this->actingAs($user)->post(route('purchases.bills.store'), [
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'bill_date' => '2026-01-10',
            'tax_inclusive' => true,
            'items' => [
                ['account_id' => $expense->id, 'tax_rate_id' => $taxRate->id, 'description' => 'Supplies', 'quantity' => 1, 'unit_price' => 115, 'discount' => 0],
            ],
        ]);

        $bill = Bill::first();
        $this->assertTrue($bill->tax_inclusive);
        $this->assertSame('15.0000', $bill->tax_total);
        $this->assertSame('115.0000', $bill->total);
        $this->assertSame('100.0000', (string) $bill->items->first()->line_total);

        $this->actingAs($user)->post(route('purchases.bills.post', $bill));

        $journal = $bill->fresh()->journal;
        $this->assertTrue($journal->isBalanced());
        $this->assertSame('115.0000', $journal->totalCredit());
    }
}
