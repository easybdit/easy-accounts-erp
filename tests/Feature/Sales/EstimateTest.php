<?php

namespace Tests\Feature\Sales;

use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Estimate;
use App\Models\Sales\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EstimateTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        return array_merge([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'estimate_date' => '2026-01-10',
            'items' => [
                ['account_id' => $income->id, 'description' => 'Design work', 'quantity' => 2, 'unit_price' => 100, 'discount' => 0],
            ],
        ], $overrides);
    }

    public function test_guest_cannot_view_estimates(): void
    {
        $this->get(route('sales.estimates.index'))->assertRedirect(route('login'));
    }

    public function test_create_route_resolves_to_the_create_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('sales.estimates.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Sales/Estimates/Create'));
    }

    public function test_an_estimate_can_be_created_with_computed_totals(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('sales.estimates.store'), $this->payload())->assertRedirect();

        $estimate = Estimate::first();
        $this->assertSame('200.0000', (string) $estimate->total);
        $this->assertSame('draft', $estimate->status);
        $this->assertStringStartsWith('EST-', $estimate->estimate_number);
    }

    public function test_an_estimate_never_creates_a_journal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('sales.estimates.store'), $this->payload());

        $this->assertDatabaseCount('journals', 0);
    }

    public function test_converting_an_estimate_creates_a_matching_draft_invoice(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('sales.estimates.store'), $this->payload());
        $estimate = Estimate::first();

        $response = $this->actingAs($user)->post(route('sales.estimates.convert', $estimate));

        $this->assertDatabaseCount('invoices', 1);
        $invoice = Invoice::first();
        $response->assertRedirect(route('sales.invoices.edit', $invoice));
        $this->assertSame('200.0000', (string) $invoice->total);
        $this->assertSame('draft', $invoice->status);

        $estimate->refresh();
        $this->assertSame('converted', $estimate->status);
        $this->assertSame($invoice->id, $estimate->converted_invoice_id);
    }

    public function test_a_converted_estimate_cannot_be_converted_again(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('sales.estimates.store'), $this->payload());
        $estimate = Estimate::first();
        $this->actingAs($user)->post(route('sales.estimates.convert', $estimate));

        $this->actingAs($user)->post(route('sales.estimates.convert', $estimate->fresh()))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_a_converted_estimate_cannot_be_edited_or_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('sales.estimates.store'), $this->payload());
        $estimate = Estimate::first();
        $this->actingAs($user)->post(route('sales.estimates.convert', $estimate));

        $this->actingAs($user)->get(route('sales.estimates.edit', $estimate->fresh()))->assertForbidden();
        $this->actingAs($user)->delete(route('sales.estimates.destroy', $estimate->fresh()))->assertForbidden();
    }
}
