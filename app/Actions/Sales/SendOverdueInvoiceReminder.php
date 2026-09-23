<?php

namespace App\Actions\Sales;

use App\Mail\InvoiceOverdueReminderMail;
use App\Models\Sales\Invoice;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class SendOverdueInvoiceReminder
{
    public function handle(Invoice $invoice, ?string $recipientEmail = null): void
    {
        if (! $invoice->isOverdue()) {
            throw new RuntimeException('This invoice is not overdue — nothing to remind.');
        }

        $recipientEmail ??= $invoice->customer->email;

        if (empty($recipientEmail)) {
            throw new RuntimeException('This customer has no email address on file — enter one to send to.');
        }

        $activeLink = $invoice->paymentLinks()->where('is_active', true)->latest()->first();

        Mail::to($recipientEmail)->send(new InvoiceOverdueReminderMail(
            $invoice,
            $activeLink ? route('pay.show', $activeLink->token) : null,
        ));

        $invoice->update(['last_reminder_sent_at' => now()]);
    }
}
