<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingSettings;
use App\Models\Contacts\Customer;
use App\Models\Sales\Estimate;
use App\Models\Sales\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers Section 50's configurable document numbering: a prefix override in
 * Settings changes new document numbers; leaving it unconfigured keeps
 * today's existing default prefixes unchanged.
 */
class DocumentNumberingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_prefix_is_used_when_unconfigured(): void
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

        $this->assertSame('INV-2026-0001', Invoice::first()->invoice_number);
    }

    public function test_a_configured_prefix_override_is_honored(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $this->actingAs($user)->put(route('accounting.settings.document-numbering.update'), [
            'invoice' => 'EAINV',
        ]);

        $this->assertSame(['invoice' => 'EAINV'], AccountingSettings::current()->document_number_prefixes);

        $this->actingAs($user)->post(route('sales.invoices.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-10',
            'items' => [
                ['account_id' => $income->id, 'description' => 'Widget', 'quantity' => 1, 'unit_price' => 100, 'discount' => 0],
            ],
        ]);

        $this->assertSame('EAINV-2026-0001', Invoice::first()->invoice_number);
    }

    public function test_a_blank_override_falls_back_to_the_default_instead_of_saving_an_empty_prefix(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('accounting.settings.document-numbering.update'), [
            'invoice' => 'EAINV',
        ]);

        $this->actingAs($user)->put(route('accounting.settings.document-numbering.update'), [
            'invoice' => '',
        ]);

        $this->assertSame([], AccountingSettings::current()->document_number_prefixes);
    }

    public function test_other_document_types_are_unaffected_by_an_invoice_only_override(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $this->actingAs($user)->put(route('accounting.settings.document-numbering.update'), [
            'invoice' => 'EAINV',
        ]);

        $this->actingAs($user)->post(route('sales.estimates.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'estimate_date' => '2026-01-10',
            'items' => [
                ['account_id' => $income->id, 'description' => 'Widget', 'quantity' => 1, 'unit_price' => 100, 'discount' => 0],
            ],
        ]);

        $this->assertSame('EST-2026-0001', Estimate::first()->estimate_number);
    }
}
