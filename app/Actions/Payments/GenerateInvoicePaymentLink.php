<?php

namespace App\Actions\Payments;

use App\Models\Sales\Invoice;
use App\Models\Sales\InvoicePaymentLink;
use Illuminate\Support\Str;
use RuntimeException;

class GenerateInvoicePaymentLink
{
    public function handle(Invoice $invoice, int $depositAccountId, ?int $userId): InvoicePaymentLink
    {
        if ($invoice->status !== 'posted') {
            throw new RuntimeException('Only a posted invoice can have a payment link.');
        }

        if (bccomp($invoice->amountDue(), '0', 4) <= 0) {
            throw new RuntimeException('This invoice has nothing left to pay.');
        }

        // Deactivate any earlier links so only one is ever shareable at a
        // time per invoice — avoids a customer paying against a stale link
        // whose deposit account no longer matches what staff intended.
        $invoice->paymentLinks()->update(['is_active' => false]);

        return InvoicePaymentLink::create([
            'invoice_id' => $invoice->id,
            'token' => Str::random(48),
            'deposit_account_id' => $depositAccountId,
            'is_active' => true,
            'created_by' => $userId,
        ]);
    }
}
