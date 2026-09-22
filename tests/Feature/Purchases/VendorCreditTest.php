<?php

namespace Tests\Feature\Purchases;

use App\Actions\Accounting\PostJournal;
use App\Actions\Purchases\PostBill;
use App\Actions\Purchases\PostVendorCredit;
use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Purchases\Bill;
use App\Models\Purchases\VendorCredit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VendorCreditTest extends TestCase
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
            'vendor_credit_date' => '2026-01-15',
            'items' => [
                ['account_id' => $expense->id, 'description' => 'Returned goods', 'quantity' => 1, 'unit_price' => 150, 'discount' => 0],
            ],
        ], $overrides);
    }

    public function test_guest_cannot_view_vendor_credits(): void
    {
        $this->get(route('purchases.vendor-credits.index'))->assertRedirect(route('login'));
    }

    public function test_create_route_resolves_to_the_create_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('purchases.vendor-credits.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Purchases/VendorCredits/Create'));
    }

    public function test_a_draft_vendor_credit_can_be_created_with_computed_totals(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('purchases.vendor-credits.store'), $this->payload())->assertRedirect();

        $vendorCredit = VendorCredit::first();
        $this->assertSame('150.0000', (string) $vendorCredit->total);
        $this->assertSame('draft', $vendorCredit->status);
        $this->assertStringStartsWith('VC-', $vendorCredit->vendor_credit_number);
    }

    public function test_posting_a_vendor_credit_reduces_the_vendors_balance_and_reverses_expense(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);
        $expense = Account::factory()->create(['type' => 'expense']);

        // First, a bill so the vendor is owed money.
        $bill = Bill::factory()->create([
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'status' => 'draft',
            'total' => 0,
        ]);
        $bill->items()->create([
            'account_id' => $expense->id,
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => 500,
            'discount' => 0,
            'line_total' => 500,
        ]);
        (new PostBill(new PostJournal))->handle($bill->fresh());

        $this->actingAs($user)->post(route('purchases.vendor-credits.store'), $this->payload([
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'bill_id' => $bill->id,
            'items' => [
                ['account_id' => $expense->id, 'description' => 'Partial return', 'quantity' => 1, 'unit_price' => 150, 'discount' => 0],
            ],
        ]));
        $vendorCredit = VendorCredit::first();

        $this->actingAs($user)->post(route('purchases.vendor-credits.post', $vendorCredit))->assertRedirect();

        $vendorCredit->refresh();
        $this->assertSame('posted', $vendorCredit->status);
        $this->assertNotNull($vendorCredit->journal);
        $this->assertTrue($vendorCredit->journal->isBalanced());

        // 500 (bill) - 150 (vendor credit) = 350 still owed.
        $this->assertSame('350.0000', $vendor->fresh()->currentBalance());
    }

    public function test_a_vendor_credit_with_no_items_cannot_be_posted(): void
    {
        $vendor = Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);
        $vendorCredit = VendorCredit::create([
            'vendor_credit_number' => 'VC-TEST-1',
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'vendor_credit_date' => '2026-01-15',
            'status' => 'draft',
        ]);

        $this->expectException(\RuntimeException::class);

        (new PostVendorCredit(new PostJournal))->handle($vendorCredit);
    }

    public function test_a_posted_vendor_credit_cannot_be_edited_or_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.vendor-credits.store'), $this->payload());
        $vendorCredit = VendorCredit::first();
        $this->actingAs($user)->post(route('purchases.vendor-credits.post', $vendorCredit));

        $this->actingAs($user)->get(route('purchases.vendor-credits.edit', $vendorCredit->fresh()))->assertForbidden();
        $this->actingAs($user)->delete(route('purchases.vendor-credits.destroy', $vendorCredit->fresh()))->assertForbidden();
    }
}
