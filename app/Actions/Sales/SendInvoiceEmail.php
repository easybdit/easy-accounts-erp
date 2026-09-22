<?php

namespace App\Actions\Sales;

use App\Mail\InvoiceMail;
use App\Models\Sales\Invoice;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class SendInvoiceEmail
{
    public function handle(Invoice $invoice, ?string $recipientEmail): void
    {
        if ($invoice->status !== 'posted') {
            throw new RuntimeException('Only a posted invoice can be emailed.');
        }

        $recipientEmail ??= $invoice->customer->email;

        if (empty($recipientEmail)) {
            throw new RuntimeException('This customer has no email address on file — enter one to send to.');
        }

        $activeLink = $invoice->paymentLinks()->where('is_active', true)->latest()->first();

        Mail::to($recipientEmail)->send(new InvoiceMail(
            $invoice,
            $activeLink ? route('pay.show', $activeLink->token) : null,
        ));

        $invoice->update(['last_emailed_at' => now()]);
    }
}
