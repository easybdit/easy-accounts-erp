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

class BillTaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_bill_total_includes_tax_and_posts_a_balanced_journal_with_tax_debited(): void
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
            'items' => [
                ['account_id' => $expense->id, 'tax_rate_id' => $taxRate->id, 'description' => 'Supplies', 'quantity' => 1, 'unit_price' => 100, 'discount' => 0],
            ],
        ]);

        $bill = Bill::first();
        $this->assertSame('15.0000', $bill->tax_total);
        $this->assertSame('115.0000', $bill->total);

        $this->actingAs($user)->post(route('purchases.bills.post', $bill));

        $journal = $bill->fresh()->journal;
        $this->assertTrue($journal->isBalanced());
        $this->assertSame('115.0000', $journal->totalCredit());

        // Input tax is DEBITED (offsets output tax on the same account),
        // not credited — the reverse of the Invoice side.
        $taxLine = $journal->entries->firstWhere('account_id', $taxLiability->id);
        $this->assertSame('15.0000', $taxLine->debit);
    }

    public function test_net_tax_liability_nets_output_against_input_on_the_same_account(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $vendor = Vendor::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $payable = Account::factory()->create(['type' => 'liability']);
        $income = Account::factory()->create(['type' => 'income']);
        $expense = Account::factory()->create(['type' => 'expense']);
        $taxLiability = Account::factory()->create(['type' => 'liability']);
        $taxRate = TaxRate::factory()->create(['rate' => 15, 'tax_account_id' => $taxLiability->id]);

        // Sale: collects 15 in tax.
        $this->actingAs($user)->post(route('sales.invoices.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-10',
            'items' => [
                ['account_id' => $income->id, 'tax_rate_id' => $taxRate->id, 'description' => 'Sale', 'quantity' => 1, 'unit_price' => 100],
            ],
        ]);
        $this->actingAs($user)->post(route('sales.invoices.post', Invoice::first()));

        // Purchase: pays 6 in tax.
        $this->actingAs($user)->post(route('purchases.bills.store'), [
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'bill_date' => '2026-01-10',
            'items' => [
                ['account_id' => $expense->id, 'tax_rate_id' => $taxRate->id, 'description' => 'Purchase', 'quantity' => 1, 'unit_price' => 40],
            ],
        ]);
        $this->actingAs($user)->post(route('purchases.bills.post', Bill::first()));

        // Net tax payable = 15 collected - 6 paid = 9, reflected directly on
        // the shared tax liability account's own balance.
        $this->assertSame('9.0000', $taxLiability->fresh()->balanceAsOf());
    }
}
