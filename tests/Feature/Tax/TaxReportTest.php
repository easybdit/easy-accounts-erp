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

class TaxReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_tax_report(): void
    {
        $this->get(route('tax.report'))->assertRedirect(route('login'));
    }

    public function test_report_shows_collected_paid_and_net_per_rate(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $vendor = Vendor::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $payable = Account::factory()->create(['type' => 'liability']);
        $income = Account::factory()->create(['type' => 'income']);
        $expense = Account::factory()->create(['type' => 'expense']);
        $taxLiability = Account::factory()->create(['type' => 'liability']);
        $taxRate = TaxRate::factory()->create(['name' => 'VAT 15%', 'rate' => 15, 'tax_account_id' => $taxLiability->id]);

        $this->actingAs($user)->post(route('sales.invoices.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-10',
            'items' => [['account_id' => $income->id, 'tax_rate_id' => $taxRate->id, 'description' => 'Sale', 'quantity' => 1, 'unit_price' => 100]],
        ]);
        $this->actingAs($user)->post(route('sales.invoices.post', Invoice::first()));

        $this->actingAs($user)->post(route('purchases.bills.store'), [
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'bill_date' => '2026-01-10',
            'items' => [['account_id' => $expense->id, 'tax_rate_id' => $taxRate->id, 'description' => 'Purchase', 'quantity' => 1, 'unit_price' => 40]],
        ]);
        $this->actingAs($user)->post(route('purchases.bills.post', Bill::first()));

        $response = $this->actingAs($user)->get(route('tax.report'));
        $props = $response->viewData('page')['props'];

        $row = collect($props['rows'])->firstWhere('id', $taxRate->id);
        $this->assertSame('15.0000', $row['collected']);
        $this->assertSame('6.0000', $row['paid']);
        $this->assertSame('9.0000', $row['net']);
        $this->assertSame('9.0000', $props['totalNet']);
    }

    public function test_unposted_documents_are_excluded(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);
        $taxRate = TaxRate::factory()->create();

        // Draft invoice — never posted.
        $this->actingAs($user)->post(route('sales.invoices.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-10',
            'items' => [['account_id' => $income->id, 'tax_rate_id' => $taxRate->id, 'description' => 'Sale', 'quantity' => 1, 'unit_price' => 100]],
        ]);

        $response = $this->actingAs($user)->get(route('tax.report'));
        $props = $response->viewData('page')['props'];

        $row = collect($props['rows'])->firstWhere('id', $taxRate->id);
        $this->assertSame('0.0000', $row['collected']);
    }
}
