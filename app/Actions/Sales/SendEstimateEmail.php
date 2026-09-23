<?php

namespace App\Actions\Sales;

use App\Mail\EstimateMail;
use App\Models\Sales\Estimate;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class SendEstimateEmail
{
    public function handle(Estimate $estimate, ?string $recipientEmail): void
    {
        if ($estimate->isConverted()) {
            throw new RuntimeException('This estimate has already been converted to an invoice — email that instead.');
        }

        $recipientEmail ??= $estimate->customer->email;

        if (empty($recipientEmail)) {
            throw new RuntimeException('This customer has no email address on file — enter one to send to.');
        }

        Mail::to($recipientEmail)->send(new EstimateMail($estimate));

        $estimate->update([
            'last_emailed_at' => now(),
            'status' => $estimate->status === 'draft' ? 'sent' : $estimate->status,
        ]);
    }
}
