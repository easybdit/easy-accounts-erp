<?php

namespace Tests\Feature\Sales;

use App\Actions\Sales\PostInvoice;
use App\Actions\Sales\ReceivePayment;
use App\Actions\Sales\SaveInvoiceDraft;
use App\Actions\Sales\SendOverdueInvoiceReminder;
use App\Mail\InvoiceOverdueReminderMail;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class OverdueInvoiceReminderTest extends TestCase
{
    use RefreshDatabase;

    private function postedInvoice(string $dueDate, array $customerOverrides = []): Invoice
    {
        $customer = Customer::factory()->create($customerOverrides);
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $invoice = app(SaveInvoiceDraft::class)->handle([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => now()->subDays(30)->toDateString(),
            'due_date' => $dueDate,
            'created_by' => null,
            'items' => [
                ['account_id' => $income->id, 'description' => 'Service', 'quantity' => 1, 'unit_price' => 500, 'discount' => 0],
            ],
        ]);

        return app(PostInvoice::class)->handle($invoice);
    }

    public function test_action_rejects_an_invoice_that_is_not_overdue(): void
    {
        Mail::fake();
        $invoice = $this->postedInvoice(now()->addDays(10)->toDateString());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not overdue');

        app(SendOverdueInvoiceReminder::class)->handle($invoice);
    }

    public function test_action_sends_a_reminder_and_stamps_last_reminder_sent_at(): void
    {
        Mail::fake();
        $invoice = $this->postedInvoice(now()->subDays(5)->toDateString(), ['email' => 'customer@example.com']);

        app(SendOverdueInvoiceReminder::class)->handle($invoice);

        Mail::assertSent(InvoiceOverdueReminderMail::class, fn (InvoiceOverdueReminderMail $mail) => $mail->hasTo('customer@example.com'));
        $this->assertNotNull($invoice->fresh()->last_reminder_sent_at);
    }

    public function test_action_rejects_a_customer_with_no_email(): void
    {
        Mail::fake();
        $invoice = $this->postedInvoice(now()->subDays(5)->toDateString(), ['email' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no email address');

        app(SendOverdueInvoiceReminder::class)->handle($invoice);
    }

    public function test_the_mail_body_renders_and_reports_days_overdue(): void
    {
        $invoice = $this->postedInvoice(now()->subDays(5)->toDateString());
        $invoice->load('items.account', 'customer');

        $html = (new InvoiceOverdueReminderMail($invoice))->render();

        $this->assertStringContainsString($invoice->invoice_number, $html);
        $this->assertStringContainsString('5 day', $html);
    }

    public function test_the_scheduled_command_reminds_an_overdue_invoice(): void
    {
        Mail::fake();
        $invoice = $this->postedInvoice(now()->subDays(3)->toDateString(), ['email' => 'customer@example.com']);

        $this->artisan('invoices:send-overdue-reminders')->assertSuccessful();

        Mail::assertSent(InvoiceOverdueReminderMail::class);
        $this->assertNotNull($invoice->fresh()->last_reminder_sent_at);
    }

    public function test_the_scheduled_command_skips_an_invoice_that_is_not_yet_due(): void
    {
        Mail::fake();
        $this->postedInvoice(now()->addDays(3)->toDateString());

        $this->artisan('invoices:send-overdue-reminders')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_the_scheduled_command_skips_an_overdue_invoice_that_has_been_fully_paid(): void
    {
        Mail::fake();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);
        $cash = Account::factory()->create(['type' => 'asset']);

        $invoice = app(SaveInvoiceDraft::class)->handle([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => now()->subDays(30)->toDateString(),
            'due_date' => now()->subDays(3)->toDateString(),
            'created_by' => null,
            'items' => [
                ['account_id' => $income->id, 'description' => 'Service', 'quantity' => 1, 'unit_price' => 500, 'discount' => 0],
            ],
        ]);
        app(PostInvoice::class)->handle($invoice);

        app(ReceivePayment::class)->handle([
            'customer_id' => $customer->id,
            'deposit_account_id' => $cash->id,
            'payment_date' => now()->toDateString(),
            'reference' => null,
            'method' => null,
            'amount' => 500,
            'notes' => null,
            'created_by' => null,
            'allocations' => [['invoice_id' => $invoice->id, 'amount' => 500]],
        ]);

        $this->artisan('invoices:send-overdue-reminders')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_the_scheduled_command_does_not_re_remind_within_the_cooldown_window(): void
    {
        Mail::fake();
        $invoice = $this->postedInvoice(now()->subDays(10)->toDateString(), ['email' => 'customer@example.com']);
        $invoice->update(['last_reminder_sent_at' => now()->subDays(2)]);

        $this->artisan('invoices:send-overdue-reminders')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_the_scheduled_command_reminds_again_after_the_cooldown_window(): void
    {
        Mail::fake();
        $invoice = $this->postedInvoice(now()->subDays(20)->toDateString(), ['email' => 'customer@example.com']);
        $invoice->update(['last_reminder_sent_at' => now()->subDays(8)]);

        $this->artisan('invoices:send-overdue-reminders')->assertSuccessful();

        Mail::assertSent(InvoiceOverdueReminderMail::class);
    }

    public function test_guest_cannot_send_a_manual_reminder(): void
    {
        $invoice = $this->postedInvoice(now()->subDays(5)->toDateString());

        $this->post(route('sales.invoices.send-reminder', $invoice))->assertRedirect(route('login'));
    }

    public function test_a_user_can_manually_send_a_reminder_for_an_overdue_invoice(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $invoice = $this->postedInvoice(now()->subDays(5)->toDateString(), ['email' => 'customer@example.com']);

        $this->actingAs($user)->post(route('sales.invoices.send-reminder', $invoice))->assertRedirect();

        Mail::assertSent(InvoiceOverdueReminderMail::class);
    }

    public function test_a_user_cannot_manually_send_a_reminder_for_a_non_overdue_invoice(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $invoice = $this->postedInvoice(now()->addDays(10)->toDateString());

        $this->actingAs($user)->post(route('sales.invoices.send-reminder', $invoice))->assertSessionHas('error');

        Mail::assertNothingSent();
    }
}
