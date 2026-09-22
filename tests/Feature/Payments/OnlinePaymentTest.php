<?php

namespace Tests\Feature\Payments;

use App\Actions\Accounting\PostJournal;
use App\Actions\Sales\PostInvoice;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoicePaymentLink;
use App\Models\Sales\OnlinePaymentTransaction;
use App\Models\Sales\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OnlinePaymentTest extends TestCase
{
    use RefreshDatabase;

    private function postedInvoice(string $total = '1000'): Invoice
    {
        $customer = Customer::factory()->create();
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
            'unit_price' => $total,
            'discount' => 0,
            'line_total' => $total,
        ]);

        return (new PostInvoice(new PostJournal))->handle($invoice->fresh());
    }

    public function test_staff_can_generate_a_payment_link_for_a_posted_invoice(): void
    {
        $user = User::factory()->create();
        $invoice = $this->postedInvoice();
        $deposit = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('sales.invoices.payment-link', $invoice), [
            'deposit_account_id' => $deposit->id,
        ])->assertRedirect();

        $link = InvoicePaymentLink::first();
        $this->assertNotNull($link);
        $this->assertTrue($link->is_active);
        $this->assertSame($deposit->id, $link->deposit_account_id);
    }

    public function test_a_new_link_deactivates_the_previous_one(): void
    {
        $user = User::factory()->create();
        $invoice = $this->postedInvoice();
        $deposit = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('sales.invoices.payment-link', $invoice), ['deposit_account_id' => $deposit->id]);
        $firstLink = InvoicePaymentLink::first();

        $this->actingAs($user)->post(route('sales.invoices.payment-link', $invoice), ['deposit_account_id' => $deposit->id]);

        $this->assertFalse($firstLink->fresh()->is_active);
        $this->assertSame(2, InvoicePaymentLink::count());
    }

    public function test_a_draft_invoice_cannot_get_a_payment_link(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $draft = Invoice::factory()->create(['customer_id' => $customer->id, 'receivable_account_id' => $receivable->id, 'status' => 'draft']);
        $deposit = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('sales.invoices.payment-link', $draft), ['deposit_account_id' => $deposit->id])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('invoice_payment_links', 0);
    }

    public function test_the_public_show_page_renders_the_invoice_summary(): void
    {
        $invoice = $this->postedInvoice('750');
        $deposit = Account::factory()->create(['type' => 'asset']);
        $link = InvoicePaymentLink::create([
            'invoice_id' => $invoice->id,
            'token' => 'test-token-123',
            'deposit_account_id' => $deposit->id,
            'is_active' => true,
        ]);

        $this->get(route('pay.show', $link->token))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Pay/Show')
                ->where('invoiceNumber', $invoice->invoice_number)
                ->where('amountDue', '750.0000')
                ->where('alreadyPaid', false)
            );
    }

    public function test_an_invalid_token_404s(): void
    {
        $this->get(route('pay.show', 'not-a-real-token'))->assertNotFound();
    }

    public function test_initiating_payment_redirects_to_the_gateway_url(): void
    {
        Http::fake([
            '*/gwprocess/v4/api.php' => Http::response([
                'status' => 'SUCCESS',
                'GatewayPageURL' => 'https://sandbox.sslcommerz.com/EasyCheckOut/testcheckout',
            ]),
        ]);

        $invoice = $this->postedInvoice('750');
        $deposit = Account::factory()->create(['type' => 'asset']);
        $link = InvoicePaymentLink::create([
            'invoice_id' => $invoice->id,
            'token' => 'test-token-456',
            'deposit_account_id' => $deposit->id,
            'is_active' => true,
        ]);

        $response = $this->post(route('pay.initiate', $link->token));

        $response->assertRedirect('https://sandbox.sslcommerz.com/EasyCheckOut/testcheckout');
        $this->assertDatabaseCount('online_payment_transactions', 1);
        $transaction = OnlinePaymentTransaction::first();
        $this->assertSame('750.0000', (string) $transaction->amount);
        $this->assertSame('initiated', $transaction->status);
    }

    public function test_a_failed_gateway_session_shows_an_error_and_stays_on_the_pay_page(): void
    {
        Http::fake([
            '*/gwprocess/v4/api.php' => Http::response(['status' => 'FAILED', 'failedreason' => 'Invalid store credentials']),
        ]);

        $invoice = $this->postedInvoice('750');
        $deposit = Account::factory()->create(['type' => 'asset']);
        $link = InvoicePaymentLink::create([
            'invoice_id' => $invoice->id, 'token' => 'test-token-789', 'deposit_account_id' => $deposit->id, 'is_active' => true,
        ]);

        $this->post(route('pay.initiate', $link->token))
            ->assertRedirect(route('pay.show', $link->token))
            ->assertSessionHas('error');

        $this->assertSame('failed', OnlinePaymentTransaction::first()->status);
    }

    public function test_a_validated_success_callback_records_and_allocates_the_payment(): void
    {
        Http::fake([
            '*/validator/api/validationserverAPI.php*' => Http::response([
                'status' => 'VALID',
                'amount' => '750.00',
                'currency_type' => 'BDT',
                'tran_id' => 'INV-TEST-1',
            ]),
        ]);

        $invoice = $this->postedInvoice('750');
        $deposit = Account::factory()->create(['type' => 'asset']);
        $link = InvoicePaymentLink::create([
            'invoice_id' => $invoice->id, 'token' => 'tok-1', 'deposit_account_id' => $deposit->id, 'is_active' => true,
        ]);
        $transaction = OnlinePaymentTransaction::create([
            'invoice_payment_link_id' => $link->id, 'tran_id' => 'INV-TEST-1', 'amount' => 750, 'currency' => 'BDT', 'status' => 'initiated',
        ]);

        $response = $this->post(route('pay.callback.success'), ['tran_id' => 'INV-TEST-1', 'val_id' => 'val-1']);

        $response->assertInertia(fn ($page) => $page->component('Public/Pay/Result')->where('outcome', 'success'));
        $this->assertSame('validated', $transaction->fresh()->status);
        $this->assertDatabaseCount('payments', 1);
        $payment = Payment::first();
        $this->assertSame('750.0000', (string) $payment->amount);
        $this->assertSame('0.0000', $invoice->fresh()->amountDue());
    }

    public function test_a_mismatched_amount_is_rejected_and_no_payment_is_recorded(): void
    {
        Http::fake([
            '*/validator/api/validationserverAPI.php*' => Http::response([
                'status' => 'VALID',
                'amount' => '1.00',
                'currency_type' => 'BDT',
            ]),
        ]);

        $invoice = $this->postedInvoice('750');
        $deposit = Account::factory()->create(['type' => 'asset']);
        $link = InvoicePaymentLink::create([
            'invoice_id' => $invoice->id, 'token' => 'tok-2', 'deposit_account_id' => $deposit->id, 'is_active' => true,
        ]);
        OnlinePaymentTransaction::create([
            'invoice_payment_link_id' => $link->id, 'tran_id' => 'INV-TEST-2', 'amount' => 750, 'currency' => 'BDT', 'status' => 'initiated',
        ]);

        $response = $this->post(route('pay.callback.success'), ['tran_id' => 'INV-TEST-2', 'val_id' => 'val-2']);

        $response->assertInertia(fn ($page) => $page->where('outcome', 'failed'));
        $this->assertSame('failed', OnlinePaymentTransaction::first()->status);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_the_ipn_and_success_callbacks_do_not_double_process_the_same_transaction(): void
    {
        Http::fake([
            '*/validator/api/validationserverAPI.php*' => Http::response([
                'status' => 'VALID', 'amount' => '750.00', 'currency_type' => 'BDT',
            ]),
        ]);

        $invoice = $this->postedInvoice('750');
        $deposit = Account::factory()->create(['type' => 'asset']);
        $link = InvoicePaymentLink::create([
            'invoice_id' => $invoice->id, 'token' => 'tok-3', 'deposit_account_id' => $deposit->id, 'is_active' => true,
        ]);
        OnlinePaymentTransaction::create([
            'invoice_payment_link_id' => $link->id, 'tran_id' => 'INV-TEST-3', 'amount' => 750, 'currency' => 'BDT', 'status' => 'initiated',
        ]);

        $this->post(route('pay.callback.success'), ['tran_id' => 'INV-TEST-3', 'val_id' => 'val-3']);
        $this->post(route('pay.callback.ipn'), ['tran_id' => 'INV-TEST-3', 'val_id' => 'val-3'])->assertOk();

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_the_cancel_callback_renders_the_cancelled_result_page(): void
    {
        $this->post(route('pay.callback.cancel'))
            ->assertInertia(fn ($page) => $page->component('Public/Pay/Result')->where('outcome', 'cancelled'));
    }

    public function test_the_fail_callback_renders_the_failed_result_page(): void
    {
        $this->post(route('pay.callback.fail'))
            ->assertInertia(fn ($page) => $page->component('Public/Pay/Result')->where('outcome', 'failed'));
    }
}
