<?php

namespace Tests\Feature\Sales;

use App\Actions\Accounting\PostJournal;
use App\Actions\Sales\PostInvoice;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class InvoiceTest extends TestCase
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
            'invoice_date' => '2026-01-10',
            'due_date' => '2026-02-10',
            'items' => [
                ['account_id' => $income->id, 'description' => 'Service A', 'quantity' => 2, 'unit_price' => 100, 'discount' => 0],
            ],
        ], $overrides);
    }

    public function test_guest_cannot_view_invoices(): void
    {
        $this->get(route('sales.invoices.index'))->assertRedirect(route('login'));
    }

    public function test_draft_invoice_can_be_created_with_computed_totals(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('sales.invoices.store'), $this->payload());

        $response->assertRedirect();
        $invoice = Invoice::first();
        $this->assertSame('draft', $invoice->status);
        $this->assertSame('200.0000', $invoice->total);
        $this->assertNotNull($invoice->invoice_number);
    }

    public function test_receivable_account_must_be_an_asset_account(): void
    {
        $user = User::factory()->create();
        $incomeAsReceivable = Account::factory()->create(['type' => 'income']);

        $response = $this->actingAs($user)->post(
            route('sales.invoices.store'),
            $this->payload(['receivable_account_id' => $incomeAsReceivable->id])
        );

        $response->assertSessionHasErrors('receivable_account_id');
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_item_account_must_be_an_income_account(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $expenseAsIncome = Account::factory()->create(['type' => 'expense']);

        $response = $this->actingAs($user)->post(route('sales.invoices.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-10',
            'items' => [
                ['account_id' => $expenseAsIncome->id, 'description' => 'Bad line', 'quantity' => 1, 'unit_price' => 50],
            ],
        ]);

        $response->assertSessionHasErrors('items.0.account_id');
    }

    public function test_discount_cannot_exceed_line_amount(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $response = $this->actingAs($user)->post(route('sales.invoices.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-10',
            'items' => [
                ['account_id' => $income->id, 'description' => 'X', 'quantity' => 1, 'unit_price' => 50, 'discount' => 100],
            ],
        ]);

        $response->assertSessionHasErrors('items.0.discount');
    }

    public function test_draft_invoice_can_be_edited(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('sales.invoices.store'), $this->payload());
        $invoice = Invoice::first();

        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $this->actingAs($user)->put(route('sales.invoices.update', $invoice), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-15',
            'items' => [
                ['account_id' => $income->id, 'description' => 'Updated', 'quantity' => 1, 'unit_price' => 500, 'discount' => 0],
            ],
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('500.0000', $invoice->total);
        $this->assertCount(1, $invoice->items);
    }

    public function test_posting_creates_a_balanced_journal_tagged_to_the_customer(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('sales.invoices.store'), $this->payload());
        $invoice = Invoice::first();

        $response = $this->actingAs($user)->post(route('sales.invoices.post', $invoice));
        $response->assertRedirect();

        $invoice->refresh();
        $this->assertSame('posted', $invoice->status);
        $this->assertNotNull($invoice->posted_at);

        $journal = $invoice->journal;
        $this->assertNotNull($journal);
        $this->assertTrue($journal->isBalanced());
        $this->assertSame('200.0000', $journal->totalDebit());

        $customer = $invoice->customer;
        $this->assertSame(
            bcadd((string) $customer->opening_balance, '200.0000', 4),
            $customer->currentBalance()
        );
    }

    public function test_posted_invoice_cannot_be_edited_or_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('sales.invoices.store'), $this->payload());
        $invoice = Invoice::first();
        (new PostInvoice(new PostJournal))->handle($invoice);

        $this->actingAs($user)->get(route('sales.invoices.edit', $invoice))->assertForbidden();
        $this->actingAs($user)->delete(route('sales.invoices.destroy', $invoice))->assertForbidden();

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'posted']);
    }

    public function test_a_draft_invoice_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('sales.invoices.store'), $this->payload());
        $invoice = Invoice::first();

        $this->actingAs($user)->delete(route('sales.invoices.destroy', $invoice))->assertRedirect();

        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    public function test_an_invoice_without_items_cannot_be_posted(): void
    {
        $invoice = Invoice::factory()->create();

        $this->expectException(RuntimeException::class);

        (new PostInvoice(new PostJournal))->handle($invoice);
    }
}
