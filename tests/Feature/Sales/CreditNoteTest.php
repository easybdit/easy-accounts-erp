<?php

namespace Tests\Feature\Sales;

use App\Actions\Accounting\PostJournal;
use App\Actions\Sales\PostInvoice;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\CreditNote;
use App\Models\Sales\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CreditNoteTest extends TestCase
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
            'credit_note_date' => '2026-01-15',
            'items' => [
                ['account_id' => $income->id, 'description' => 'Refund for defective item', 'quantity' => 1, 'unit_price' => 150, 'discount' => 0],
            ],
        ], $overrides);
    }

    public function test_guest_cannot_view_credit_notes(): void
    {
        $this->get(route('sales.credit-notes.index'))->assertRedirect(route('login'));
    }

    public function test_create_route_resolves_to_the_create_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('sales.credit-notes.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Sales/CreditNotes/Create'));
    }

    public function test_a_draft_credit_note_can_be_created_with_computed_totals(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('sales.credit-notes.store'), $this->payload())->assertRedirect();

        $creditNote = CreditNote::first();
        $this->assertSame('150.0000', (string) $creditNote->total);
        $this->assertSame('draft', $creditNote->status);
        $this->assertStringStartsWith('CN-', $creditNote->credit_note_number);
    }

    public function test_posting_a_credit_note_reduces_the_customers_balance_and_reverses_income(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        // First, an invoice so the customer has an outstanding balance.
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'status' => 'draft',
            'total' => 0,
        ]);
        $invoice->items()->create([
            'account_id' => $income->id,
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => 500,
            'discount' => 0,
            'line_total' => 500,
        ]);
        (new PostInvoice(new PostJournal))->handle($invoice->fresh());

        $this->actingAs($user)->post(route('sales.credit-notes.store'), $this->payload([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_id' => $invoice->id,
            'items' => [
                ['account_id' => $income->id, 'description' => 'Partial refund', 'quantity' => 1, 'unit_price' => 150, 'discount' => 0],
            ],
        ]));
        $creditNote = CreditNote::first();

        $this->actingAs($user)->post(route('sales.credit-notes.post', $creditNote))->assertRedirect();

        $creditNote->refresh();
        $this->assertSame('posted', $creditNote->status);
        $this->assertNotNull($creditNote->journal);
        $this->assertTrue($creditNote->journal->isBalanced());

        // 500 (invoice) - 150 (credit note) = 350 still owed.
        $this->assertSame('350.0000', $customer->fresh()->currentBalance());
    }

    public function test_a_credit_note_with_no_items_cannot_be_posted(): void
    {
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $creditNote = CreditNote::create([
            'credit_note_number' => 'CN-TEST-1',
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'credit_note_date' => '2026-01-15',
            'status' => 'draft',
        ]);

        $this->expectException(\RuntimeException::class);

        (new \App\Actions\Sales\PostCreditNote(new PostJournal))->handle($creditNote);
    }

    public function test_a_posted_credit_note_cannot_be_edited_or_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('sales.credit-notes.store'), $this->payload());
        $creditNote = CreditNote::first();
        $this->actingAs($user)->post(route('sales.credit-notes.post', $creditNote));

        $this->actingAs($user)->get(route('sales.credit-notes.edit', $creditNote->fresh()))->assertForbidden();
        $this->actingAs($user)->delete(route('sales.credit-notes.destroy', $creditNote->fresh()))->assertForbidden();
    }
}
