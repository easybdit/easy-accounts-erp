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
 * Covers an optional second, independent tax rate per line (e.g. Bangladesh
 * SD + VAT on the same invoice/bill line) — each rate is calculated on the
 * same net line_total, not compounded on top of the other.
 */
class SecondTaxRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_invoice_line_can_carry_two_independent_exclusive_taxes(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);
        $vatAccount = Account::factory()->create(['type' => 'liability']);
        $sdAccount = Account::factory()->create(['type' => 'liability']);
        $vat = TaxRate::factory()->create(['rate' => 15, 'tax_account_id' => $vatAccount->id]);
        $sd = TaxRate::factory()->create(['rate' => 5, 'tax_account_id' => $sdAccount->id]);

        $this->actingAs($user)->post(route('sales.invoices.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-10',
            'items' => [
                ['account_id' => $income->id, 'tax_rate_id' => $vat->id, 'tax_rate_2_id' => $sd->id, 'description' => 'Widget', 'quantity' => 1, 'unit_price' => 100, 'discount' => 0],
            ],
        ]);

        $invoice = Invoice::first();
        $item = $invoice->items->first();
        $this->assertSame('100.0000', (string) $item->line_total);
        $this->assertSame('15.0000', (string) $item->tax_amount);
        $this->assertSame('5.0000', (string) $item->tax_amount_2);
        $this->assertSame('20.0000', $invoice->tax_total);
        $this->assertSame('120.0000', $invoice->total);

        $this->actingAs($user)->post(route('sales.invoices.post', $invoice));

        $journal = $invoice->fresh()->journal;
        $this->assertTrue($journal->isBalanced());
        $this->assertSame('120.0000', $journal->totalDebit());

        $vatLine = $journal->entries->firstWhere('account_id', $vatAccount->id);
        $this->assertSame('15.0000', $vatLine->credit);

        $sdLine = $journal->entries->firstWhere('account_id', $sdAccount->id);
        $this->assertSame('5.0000', $sdLine->credit);
    }

    public function test_a_tax_inclusive_line_with_two_taxes_backs_the_net_out_using_the_combined_rate(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);
        $vat = TaxRate::factory()->create(['rate' => 15]);
        $sd = TaxRate::factory()->create(['rate' => 5]);

        // Entered price already contains both taxes: 100 net x 1.20 = 120.
        $this->actingAs($user)->post(route('sales.invoices.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-10',
            'tax_inclusive' => true,
            'items' => [
                ['account_id' => $income->id, 'tax_rate_id' => $vat->id, 'tax_rate_2_id' => $sd->id, 'description' => 'Widget', 'quantity' => 1, 'unit_price' => 120, 'discount' => 0],
            ],
        ]);

        $invoice = Invoice::first();
        $item = $invoice->items->first();
        $this->assertSame('100.0000', (string) $item->line_total);
        $this->assertSame('15.0000', (string) $item->tax_amount);
        $this->assertSame('5.0000', (string) $item->tax_amount_2);
        // The entered price IS the total — nothing added on top a second time.
        $this->assertSame('120.0000', $invoice->total);
    }

    public function test_a_bill_line_can_carry_two_independent_taxes_and_both_are_debited_on_posting(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);
        $expense = Account::factory()->create(['type' => 'expense']);
        $vatAccount = Account::factory()->create(['type' => 'liability']);
        $sdAccount = Account::factory()->create(['type' => 'liability']);
        $vat = TaxRate::factory()->create(['rate' => 15, 'tax_account_id' => $vatAccount->id]);
        $sd = TaxRate::factory()->create(['rate' => 5, 'tax_account_id' => $sdAccount->id]);

        $this->actingAs($user)->post(route('purchases.bills.store'), [
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'bill_date' => '2026-01-10',
            'items' => [
                ['account_id' => $expense->id, 'tax_rate_id' => $vat->id, 'tax_rate_2_id' => $sd->id, 'description' => 'Supplies', 'quantity' => 1, 'unit_price' => 100, 'discount' => 0],
            ],
        ]);

        $bill = Bill::first();
        $this->assertSame('20.0000', $bill->tax_total);
        $this->assertSame('120.0000', $bill->total);

        $this->actingAs($user)->post(route('purchases.bills.post', $bill));

        $journal = $bill->fresh()->journal;
        $this->assertTrue($journal->isBalanced());
        $this->assertSame('120.0000', $journal->totalCredit());

        $vatLine = $journal->entries->firstWhere('account_id', $vatAccount->id);
        $this->assertSame('15.0000', $vatLine->debit);

        $sdLine = $journal->entries->firstWhere('account_id', $sdAccount->id);
        $this->assertSame('5.0000', $sdLine->debit);
    }

    public function test_two_taxes_sharing_the_same_tax_account_are_combined_into_a_single_journal_line(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);
        $sharedTaxAccount = Account::factory()->create(['type' => 'liability']);
        $rate1 = TaxRate::factory()->create(['rate' => 15, 'tax_account_id' => $sharedTaxAccount->id]);
        $rate2 = TaxRate::factory()->create(['rate' => 5, 'tax_account_id' => $sharedTaxAccount->id]);

        $this->actingAs($user)->post(route('sales.invoices.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-10',
            'items' => [
                ['account_id' => $income->id, 'tax_rate_id' => $rate1->id, 'tax_rate_2_id' => $rate2->id, 'description' => 'Widget', 'quantity' => 1, 'unit_price' => 100, 'discount' => 0],
            ],
        ]);

        $invoice = Invoice::first();
        $this->actingAs($user)->post(route('sales.invoices.post', $invoice));

        $journal = $invoice->fresh()->journal;
        $taxLines = $journal->entries->where('account_id', $sharedTaxAccount->id);
        $this->assertCount(1, $taxLines);
        $this->assertSame('20.0000', $taxLines->first()->credit);
    }

    public function test_the_second_tax_rate_must_differ_from_the_first(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);
        $taxRate = TaxRate::factory()->create(['rate' => 15]);

        $response = $this->actingAs($user)->post(route('sales.invoices.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-10',
            'items' => [
                ['account_id' => $income->id, 'tax_rate_id' => $taxRate->id, 'tax_rate_2_id' => $taxRate->id, 'description' => 'Widget', 'quantity' => 1, 'unit_price' => 100],
            ],
        ]);

        $response->assertSessionHasErrors('items.0.tax_rate_2_id');
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_a_line_without_a_second_tax_behaves_exactly_as_before(): void
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
        $item = $invoice->items->first();
        $this->assertSame('0.0000', (string) $item->tax_amount_2);
        $this->assertSame('15.0000', $invoice->tax_total);
        $this->assertSame('115.0000', $invoice->total);
        $this->assertNull($item->tax_rate_2_id);
    }
}
