<?php

namespace Tests\Feature\Purchases;

use App\Actions\Accounting\PostJournal;
use App\Actions\Purchases\PostBill;
use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Purchases\Bill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class BillTest extends TestCase
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
            'bill_date' => '2026-01-10',
            'due_date' => '2026-02-10',
            'items' => [
                ['account_id' => $expense->id, 'description' => 'Supplies', 'quantity' => 2, 'unit_price' => 100, 'discount' => 0],
            ],
        ], $overrides);
    }

    public function test_guest_cannot_view_bills(): void
    {
        $this->get(route('purchases.bills.index'))->assertRedirect(route('login'));
    }

    public function test_draft_bill_can_be_created_with_computed_totals(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('purchases.bills.store'), $this->payload());

        $response->assertRedirect();
        $bill = Bill::first();
        $this->assertSame('draft', $bill->status);
        $this->assertSame('200.0000', $bill->total);
    }

    public function test_payable_account_must_be_a_liability_account(): void
    {
        $user = User::factory()->create();
        $assetAsPayable = Account::factory()->create(['type' => 'asset']);

        $response = $this->actingAs($user)->post(
            route('purchases.bills.store'),
            $this->payload(['payable_account_id' => $assetAsPayable->id])
        );

        $response->assertSessionHasErrors('payable_account_id');
        $this->assertDatabaseCount('bills', 0);
    }

    public function test_item_account_must_be_an_expense_account(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);
        $incomeAsExpense = Account::factory()->create(['type' => 'income']);

        $response = $this->actingAs($user)->post(route('purchases.bills.store'), [
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'bill_date' => '2026-01-10',
            'items' => [
                ['account_id' => $incomeAsExpense->id, 'description' => 'Bad line', 'quantity' => 1, 'unit_price' => 50],
            ],
        ]);

        $response->assertSessionHasErrors('items.0.account_id');
    }

    public function test_draft_bill_can_be_edited(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.bills.store'), $this->payload());
        $bill = Bill::first();

        $vendor = Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);
        $expense = Account::factory()->create(['type' => 'expense']);

        $this->actingAs($user)->put(route('purchases.bills.update', $bill), [
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'bill_date' => '2026-01-15',
            'items' => [
                ['account_id' => $expense->id, 'description' => 'Updated', 'quantity' => 1, 'unit_price' => 500, 'discount' => 0],
            ],
        ])->assertRedirect();

        $bill->refresh();
        $this->assertSame('500.0000', $bill->total);
    }

    public function test_posting_creates_a_balanced_journal_tagged_to_the_vendor(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.bills.store'), $this->payload());
        $bill = Bill::first();

        $response = $this->actingAs($user)->post(route('purchases.bills.post', $bill));
        $response->assertRedirect();

        $bill->refresh();
        $this->assertSame('posted', $bill->status);

        $journal = $bill->journal;
        $this->assertNotNull($journal);
        $this->assertTrue($journal->isBalanced());
        $this->assertSame('200.0000', $journal->totalCredit());

        $vendor = $bill->vendor;
        $this->assertSame(
            bcadd((string) $vendor->opening_balance, '200.0000', 4),
            $vendor->currentBalance()
        );
    }

    public function test_posted_bill_cannot_be_edited_or_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.bills.store'), $this->payload());
        $bill = Bill::first();
        (new PostBill(new PostJournal))->handle($bill);

        $this->actingAs($user)->get(route('purchases.bills.edit', $bill))->assertForbidden();
        $this->actingAs($user)->delete(route('purchases.bills.destroy', $bill))->assertForbidden();
    }

    public function test_a_draft_bill_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.bills.store'), $this->payload());
        $bill = Bill::first();

        $this->actingAs($user)->delete(route('purchases.bills.destroy', $bill))->assertRedirect();

        $this->assertDatabaseMissing('bills', ['id' => $bill->id]);
    }

    public function test_a_bill_without_items_cannot_be_posted(): void
    {
        $bill = Bill::factory()->create();

        $this->expectException(RuntimeException::class);

        (new PostBill(new PostJournal))->handle($bill);
    }

    public function test_a_pdf_can_be_downloaded(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.bills.store'), $this->payload());
        $bill = Bill::first();

        $response = $this->actingAs($user)->get(route('purchases.bills.pdf', $bill));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
