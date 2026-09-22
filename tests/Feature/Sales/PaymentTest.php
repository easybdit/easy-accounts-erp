<?php

namespace Tests\Feature\Sales;

use App\Actions\Accounting\PostJournal;
use App\Actions\Sales\PostInvoice;
use App\Actions\Sales\ReceivePayment;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function postedInvoice(array $overrides = []): Invoice
    {
        $customer = $overrides['customer'] ?? Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

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
            'unit_price' => $overrides['total'] ?? 1000,
            'discount' => 0,
            'line_total' => $overrides['total'] ?? 1000,
        ]);

        return (new PostInvoice(new PostJournal))->handle($invoice->fresh());
    }

    public function test_guest_cannot_view_payments(): void
    {
        $this->get(route('sales.payments.index'))->assertRedirect(route('login'));
    }

    public function test_full_payment_marks_invoice_fully_paid(): void
    {
        $user = User::factory()->create();
        $invoice = $this->postedInvoice(['total' => 1000]);
        $bank = Account::factory()->create(['type' => 'asset']);

        $response = $this->actingAs($user)->post(route('sales.payments.store'), [
            'customer_id' => $invoice->customer_id,
            'deposit_account_id' => $bank->id,
            'payment_date' => '2026-01-20',
            'amount' => 1000,
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 1000],
            ],
        ]);

        $response->assertRedirect();
        $this->assertTrue($invoice->fresh()->isFullyPaid());
        $this->assertSame('0.0000', $invoice->fresh()->amountDue());
    }

    public function test_partial_payment_reduces_amount_due(): void
    {
        $user = User::factory()->create();
        $invoice = $this->postedInvoice(['total' => 1000]);
        $bank = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('sales.payments.store'), [
            'customer_id' => $invoice->customer_id,
            'deposit_account_id' => $bank->id,
            'payment_date' => '2026-01-20',
            'amount' => 400,
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 400],
            ],
        ]);

        $this->assertSame('600.0000', $invoice->fresh()->amountDue());
        $this->assertFalse($invoice->fresh()->isFullyPaid());
    }

    public function test_allocated_total_must_equal_payment_amount(): void
    {
        $user = User::factory()->create();
        $invoice = $this->postedInvoice(['total' => 1000]);
        $bank = Account::factory()->create(['type' => 'asset']);

        $response = $this->actingAs($user)->post(route('sales.payments.store'), [
            'customer_id' => $invoice->customer_id,
            'deposit_account_id' => $bank->id,
            'payment_date' => '2026-01-20',
            'amount' => 500,
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 400],
            ],
        ]);

        $response->assertSessionHasErrors('allocations');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_cannot_allocate_more_than_amount_due(): void
    {
        $user = User::factory()->create();
        $invoice = $this->postedInvoice(['total' => 1000]);
        $bank = Account::factory()->create(['type' => 'asset']);

        $response = $this->actingAs($user)->post(route('sales.payments.store'), [
            'customer_id' => $invoice->customer_id,
            'deposit_account_id' => $bank->id,
            'payment_date' => '2026-01-20',
            'amount' => 1500,
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 1500],
            ],
        ]);

        $response->assertSessionHasErrors('allocations.0.amount');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_cannot_allocate_to_a_draft_invoice(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $bank = Account::factory()->create(['type' => 'asset']);
        $draftInvoice = Invoice::factory()->create(['customer_id' => $customer->id, 'total' => 500, 'status' => 'draft']);

        $response = $this->actingAs($user)->post(route('sales.payments.store'), [
            'customer_id' => $customer->id,
            'deposit_account_id' => $bank->id,
            'payment_date' => '2026-01-20',
            'amount' => 500,
            'allocations' => [
                ['invoice_id' => $draftInvoice->id, 'amount' => 500],
            ],
        ]);

        $response->assertSessionHasErrors('allocations.0.invoice_id');
    }

    public function test_cannot_allocate_to_another_customers_invoice(): void
    {
        $user = User::factory()->create();
        $invoice = $this->postedInvoice(['total' => 1000]);
        $otherCustomer = Customer::factory()->create();
        $bank = Account::factory()->create(['type' => 'asset']);

        $response = $this->actingAs($user)->post(route('sales.payments.store'), [
            'customer_id' => $otherCustomer->id,
            'deposit_account_id' => $bank->id,
            'payment_date' => '2026-01-20',
            'amount' => 1000,
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 1000],
            ],
        ]);

        $response->assertSessionHasErrors('allocations.0.invoice_id');
    }

    public function test_deposit_account_must_be_an_asset_account(): void
    {
        $user = User::factory()->create();
        $invoice = $this->postedInvoice(['total' => 1000]);
        $incomeAsDeposit = Account::factory()->create(['type' => 'income']);

        $response = $this->actingAs($user)->post(route('sales.payments.store'), [
            'customer_id' => $invoice->customer_id,
            'deposit_account_id' => $incomeAsDeposit->id,
            'payment_date' => '2026-01-20',
            'amount' => 1000,
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 1000],
            ],
        ]);

        $response->assertSessionHasErrors('deposit_account_id');
    }

    public function test_posting_creates_a_balanced_journal_crediting_the_invoice_receivable_account(): void
    {
        $user = User::factory()->create();
        $invoice = $this->postedInvoice(['total' => 1000]);
        $bank = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('sales.payments.store'), [
            'customer_id' => $invoice->customer_id,
            'deposit_account_id' => $bank->id,
            'payment_date' => '2026-01-20',
            'amount' => 1000,
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 1000],
            ],
        ]);

        $payment = Payment::first();
        $this->assertTrue($payment->journal->isBalanced());
        $this->assertSame('1000.0000', $payment->journal->totalDebit());
    }

    public function test_two_partial_payments_can_fully_pay_an_invoice(): void
    {
        $user = User::factory()->create();
        $invoice = $this->postedInvoice(['total' => 1000]);
        $bank = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('sales.payments.store'), [
            'customer_id' => $invoice->customer_id,
            'deposit_account_id' => $bank->id,
            'payment_date' => '2026-01-20',
            'amount' => 600,
            'allocations' => [['invoice_id' => $invoice->id, 'amount' => 600]],
        ]);

        $this->actingAs($user)->post(route('sales.payments.store'), [
            'customer_id' => $invoice->customer_id,
            'deposit_account_id' => $bank->id,
            'payment_date' => '2026-01-25',
            'amount' => 400,
            'allocations' => [['invoice_id' => $invoice->id, 'amount' => 400]],
        ]);

        $this->assertTrue($invoice->fresh()->isFullyPaid());
    }

    public function test_amount_paid_is_formatted_with_four_decimals(): void
    {
        $user = User::factory()->create();
        $invoice = $this->postedInvoice(['total' => 1000]);
        $bank = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('sales.payments.store'), [
            'customer_id' => $invoice->customer_id,
            'deposit_account_id' => $bank->id,
            'payment_date' => '2026-01-20',
            'amount' => 400,
            'allocations' => [['invoice_id' => $invoice->id, 'amount' => 400]],
        ]);

        // Regression: amountPaid() must not return a raw, unnormalized SQL
        // SUM() result (e.g. "400" instead of "400.0000").
        $this->assertSame('400.0000', $invoice->fresh()->amountPaid());
    }

    public function test_action_rejects_mismatched_allocation_total_before_writing_anything(): void
    {
        $invoice = $this->postedInvoice(['total' => 1000]);
        $bank = Account::factory()->create(['type' => 'asset']);

        $this->expectException(RuntimeException::class);

        try {
            app(ReceivePayment::class)->handle([
                'customer_id' => $invoice->customer_id,
                'deposit_account_id' => $bank->id,
                'payment_date' => '2026-01-20',
                'reference' => null,
                'method' => null,
                'amount' => 1000,
                'notes' => null,
                'created_by' => null,
                'allocations' => [['invoice_id' => $invoice->id, 'amount' => 999]],
            ]);
        } finally {
            $this->assertDatabaseCount('payments', 0);
        }
    }
}
