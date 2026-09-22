<?php

namespace Tests\Feature\Tax;

use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Invoice;
use App\Models\Tax\TaxRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_total_includes_tax_and_posts_a_balanced_journal(): void
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
            'items' => [
                ['account_id' => $income->id, 'tax_rate_id' => $taxRate->id, 'description' => 'Widget', 'quantity' => 1, 'unit_price' => 100, 'discount' => 0],
            ],
        ]);

        $invoice = Invoice::first();
        $this->assertSame('15.0000', $invoice->tax_total);
        $this->assertSame('115.0000', $invoice->total);

        $this->actingAs($user)->post(route('sales.invoices.post', $invoice));

        $journal = $invoice->fresh()->journal;
        $this->assertTrue($journal->isBalanced());
        $this->assertSame('115.0000', $journal->totalDebit());

        $taxLine = $journal->entries->firstWhere('account_id', $taxLiability->id);
        $this->assertSame('15.0000', $taxLine->credit);

        $incomeLine = $journal->entries->firstWhere('account_id', $income->id);
        $this->assertSame('100.0000', $incomeLine->credit);
    }

    public function test_invoice_without_tax_rate_behaves_exactly_as_before(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $this->actingAs($user)->post(route('sales.invoices.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-10',
            'items' => [
                ['account_id' => $income->id, 'description' => 'Widget', 'quantity' => 1, 'unit_price' => 100, 'discount' => 0],
            ],
        ]);

        $invoice = Invoice::first();
        $this->assertSame('0.0000', $invoice->tax_total);
        $this->assertSame('100.0000', $invoice->total);
    }

    public function test_inactive_tax_rate_is_rejected(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);
        $taxRate = TaxRate::factory()->create(['is_active' => false]);

        $response = $this->actingAs($user)->post(route('sales.invoices.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-10',
            'items' => [
                ['account_id' => $income->id, 'tax_rate_id' => $taxRate->id, 'description' => 'Widget', 'quantity' => 1, 'unit_price' => 100],
            ],
        ]);

        $response->assertSessionHasErrors('items.0.tax_rate_id');
    }
}
