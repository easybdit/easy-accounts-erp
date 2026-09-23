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

    public function test_a_partial_conversion_bills_only_the_requested_quantity_and_stays_open(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.purchase-orders.store'), $this->payload());
        $po = PurchaseOrder::first();
        $item = $po->items->first();

        // Ordered 3, only 2 have arrived so far.
        $response = $this->actingAs($user)->post(route('purchases.purchase-orders.convert', $po), [
            'quantities' => [$item->id => 2],
        ]);

        $this->assertDatabaseCount('bills', 1);
        $bill = Bill::first();
        $response->assertRedirect(route('purchases.bills.edit', $bill));
        $this->assertSame('100.0000', (string) $bill->total); // 2 x 50
        $this->assertSame($po->id, $bill->purchase_order_id);

        $po->refresh();
        $this->assertSame('draft', $po->status);
        $this->assertFalse($po->isFullyBilled());
        $this->assertSame('2.0000', (string) $item->fresh()->billed_quantity);
        $this->assertSame('1.0000', $item->fresh()->remainingQuantity());
    }

    public function test_a_partially_billed_purchase_order_cannot_be_edited_even_though_status_is_still_draft(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.purchase-orders.store'), $this->payload());
        $po = PurchaseOrder::first();
        $item = $po->items->first();
        $this->actingAs($user)->post(route('purchases.purchase-orders.convert', $po), [
            'quantities' => [$item->id => 1],
        ]);

        $this->assertSame('draft', $po->fresh()->status);
        $this->actingAs($user)->get(route('purchases.purchase-orders.edit', $po->fresh()))->assertForbidden();
    }

    public function test_completing_the_remaining_quantity_marks_the_purchase_order_converted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.purchase-orders.store'), $this->payload());
        $po = PurchaseOrder::first();
        $item = $po->items->first();

        $this->actingAs($user)->post(route('purchases.purchase-orders.convert', $po), ['quantities' => [$item->id => 2]]);
        $this->actingAs($user)->post(route('purchases.purchase-orders.convert', $po->fresh()), ['quantities' => [$item->id => 1]]);

        $this->assertDatabaseCount('bills', 2);
        $this->assertSame('converted', $po->fresh()->status);
        $this->assertTrue($po->fresh()->isFullyBilled());
        $this->assertSame('3.0000', (string) $item->fresh()->billed_quantity);
    }

    public function test_converting_more_than_the_remaining_quantity_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.purchase-orders.store'), $this->payload());
        $po = PurchaseOrder::first();
        $item = $po->items->first();

        $response = $this->actingAs($user)->post(route('purchases.purchase-orders.convert', $po), [
            'quantities' => [$item->id => 5],
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('bills', 0);
    }

    public function test_a_partial_conversion_prorates_the_line_discount(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.purchase-orders.store'), $this->payload([
            'items' => [
                ['account_id' => Account::factory()->create(['type' => 'expense'])->id, 'description' => 'Bulk item', 'quantity' => 10, 'unit_price' => 10, 'discount' => 10],
            ],
        ]));
        $po = PurchaseOrder::first();
        $item = $po->items->first();

        // Billing half the quantity carries half the discount: 10 * (5/10) = 5.
        $this->actingAs($user)->post(route('purchases.purchase-orders.convert', $po), ['quantities' => [$item->id => 5]]);

        $bill = Bill::first();
        $billItem = $bill->items->first();
        $this->assertSame('5.0000', (string) $billItem->quantity);
        $this->assertSame('5.0000', (string) $billItem->discount);
        // (5 x 10) - 5 discount = 45.
        $this->assertSame('45.0000', (string) $billItem->line_total);
    }
}
