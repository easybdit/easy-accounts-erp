<?php

namespace Tests\Feature\Reports;

use App\Actions\Sales\PostInvoice;
use App\Actions\Sales\ReceivePayment;
use App\Actions\Sales\SaveInvoiceDraft;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerStatementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_blocked(): void
    {
        $this->get(route('reports.customer-statement'))->assertRedirect(route('login'));
    }

    public function test_with_no_customer_selected_it_renders_without_a_statement(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.customer-statement'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('statement', null));
    }

    public function test_it_shows_opening_balance_invoice_and_payment_with_a_running_balance(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['opening_balance' => 200]);
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);
        $cash = Account::factory()->create(['type' => 'asset']);

        $invoice = app(SaveInvoiceDraft::class)->handle([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-03-10',
            'due_date' => null,
            'created_by' => $user->id,
            'items' => [
                ['account_id' => $income->id, 'description' => 'Service', 'quantity' => 1, 'unit_price' => 500, 'discount' => 0],
            ],
        ]);
        app(PostInvoice::class)->handle($invoice);

        app(ReceivePayment::class)->handle([
            'customer_id' => $customer->id,
            'deposit_account_id' => $cash->id,
            'payment_date' => '2026-03-15',
            'reference' => null,
            'method' => null,
            'amount' => 300,
            'notes' => null,
            'created_by' => $user->id,
            'allocations' => [
                ['invoice_id' => Invoice::first()->id, 'amount' => 300],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('reports.customer-statement', [
            'customer_id' => $customer->id,
            'from' => '2026-03-01',
            'to' => '2026-03-31',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('statement.starting_balance', '200.0000')
            ->where('statement.entries.0.running_balance', '700.0000')
            ->where('statement.entries.1.running_balance', '400.0000')
            ->where('statement.ending_balance', '400.0000')
            ->has('statement.entries', 2));
    }

    public function test_transactions_before_the_from_date_are_folded_into_the_opening_balance(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['opening_balance' => 0]);
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $invoice = app(SaveInvoiceDraft::class)->handle([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-05',
            'due_date' => null,
            'created_by' => $user->id,
            'items' => [
                ['account_id' => $income->id, 'description' => 'Old service', 'quantity' => 1, 'unit_price' => 150, 'discount' => 0],
            ],
        ]);
        app(PostInvoice::class)->handle($invoice);

        $response = $this->actingAs($user)->get(route('reports.customer-statement', [
            'customer_id' => $customer->id,
            'from' => '2026-02-01',
            'to' => '2026-02-28',
        ]));

        $response->assertInertia(fn ($page) => $page
            ->where('statement.starting_balance', '150.0000')
            ->where('statement.ending_balance', '150.0000')
            ->has('statement.entries', 0));
    }

    public function test_pdf_can_be_downloaded(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.customer-statement.pdf', ['customer_id' => $customer->id]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
