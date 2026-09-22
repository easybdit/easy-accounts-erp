<?php

namespace Tests\Feature\Purchases;

use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Purchases\Bill;
use App\Models\Purchases\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        $vendor = Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);
        $expense = Account::factory()->create(['type' => 'expense']);

        return array_merge([
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'order_date' => '2026-01-10',
            'items' => [
                ['account_id' => $expense->id, 'description' => 'Office supplies', 'quantity' => 3, 'unit_price' => 50, 'discount' => 0],
            ],
        ], $overrides);
    }

    public function test_guest_cannot_view_purchase_orders(): void
    {
        $this->get(route('purchases.purchase-orders.index'))->assertRedirect(route('login'));
    }

    public function test_create_route_resolves_to_the_create_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('purchases.purchase-orders.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Purchases/PurchaseOrders/Create'));
    }

    public function test_a_purchase_order_can_be_created_with_computed_totals(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('purchases.purchase-orders.store'), $this->payload())->assertRedirect();

        $po = PurchaseOrder::first();
        $this->assertSame('150.0000', (string) $po->total);
        $this->assertSame('draft', $po->status);
        $this->assertStringStartsWith('PO-', $po->po_number);
    }

    public function test_a_purchase_order_never_creates_a_journal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('purchases.purchase-orders.store'), $this->payload());

        $this->assertDatabaseCount('journals', 0);
    }

    public function test_converting_a_purchase_order_creates_a_matching_draft_bill(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.purchase-orders.store'), $this->payload());
        $po = PurchaseOrder::first();

        $response = $this->actingAs($user)->post(route('purchases.purchase-orders.convert', $po));

        $this->assertDatabaseCount('bills', 1);
        $bill = Bill::first();
        $response->assertRedirect(route('purchases.bills.edit', $bill));
        $this->assertSame('150.0000', (string) $bill->total);
        $this->assertSame('draft', $bill->status);

        $po->refresh();
        $this->assertSame('converted', $po->status);
        $this->assertSame($bill->id, $po->converted_bill_id);
    }

    public function test_a_converted_purchase_order_cannot_be_edited_or_converted_again(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.purchase-orders.store'), $this->payload());
        $po = PurchaseOrder::first();
        $this->actingAs($user)->post(route('purchases.purchase-orders.convert', $po));

        $this->actingAs($user)->get(route('purchases.purchase-orders.edit', $po->fresh()))->assertForbidden();
        $this->actingAs($user)->post(route('purchases.purchase-orders.convert', $po->fresh()))->assertSessionHas('error');

        $this->assertDatabaseCount('bills', 1);
    }
}
