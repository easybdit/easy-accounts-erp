<?php

namespace Tests\Feature\Sales;

use App\Actions\Sales\PostInvoice;
use App\Mail\InvoiceMail;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceEmailTest extends TestCase
{
    use RefreshDatabase;

    private function postedInvoice(array $customerOverrides = []): Invoice
    {
        $customer = Customer::factory()->create($customerOverrides);
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-0001',
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-10',
            'due_date' => '2026-02-10',
            'status' => 'draft',
            'subtotal' => 200,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 200,
        ]);
        $invoice->items()->create([
            'account_id' => $income->id,
            'description' => 'Service A',
            'quantity' => 2,
            'unit_price' => 100,
            'discount' => 0,
            'line_total' => 200,
        ]);

        app(PostInvoice::class)->handle($invoice->fresh());

        return $invoice->fresh();
    }

    public function test_a_posted_invoice_can_be_emailed_to_its_customer(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $invoice = $this->postedInvoice(['email' => 'customer@example.com']);

        $this->actingAs($user)->post(route('sales.invoices.email', $invoice))->assertRedirect();

        Mail::assertSent(InvoiceMail::class, function (InvoiceMail $mail) use ($invoice) {
            return $mail->invoice->is($invoice)
                && $mail->hasTo('customer@example.com');
        });

        $this->assertNotNull($invoice->fresh()->last_emailed_at);
    }

    public function test_a_custom_recipient_email_overrides_the_customer_email(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $invoice = $this->postedInvoice(['email' => 'customer@example.com']);

        $this->actingAs($user)->post(route('sales.invoices.email', $invoice), [
            'recipient_email' => 'billing@example.com',
        ])->assertRedirect();

        Mail::assertSent(InvoiceMail::class, fn (InvoiceMail $mail) => $mail->hasTo('billing@example.com'));
    }

    public function test_a_draft_invoice_cannot_be_emailed(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-0002',
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-10',
            'status' => 'draft',
            'subtotal' => 100, 'discount_total' => 0, 'tax_total' => 0, 'total' => 100,
        ]);

        $this->actingAs($user)->post(route('sales.invoices.email', $invoice))->assertSessionHas('error');

        Mail::assertNothingSent();
    }

    public function test_a_customer_with_no_email_and_no_override_cannot_be_emailed(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $invoice = $this->postedInvoice(['email' => null]);

        $this->actingAs($user)->post(route('sales.invoices.email', $invoice))->assertSessionHas('error');

        Mail::assertNothingSent();
        $this->assertNull($invoice->fresh()->last_emailed_at);
    }

    public function test_the_mail_body_renders_without_error_and_includes_the_payment_link(): void
    {
        $invoice = $this->postedInvoice();
        $invoice->load('items.account');

        $html = (new InvoiceMail($invoice, 'https://pay.example.com/abc123'))->render();

        $this->assertStringContainsString($invoice->invoice_number, $html);
        $this->assertStringContainsString('https://pay.example.com/abc123', $html);
    }
}
