<?php

namespace App\Mail;

use App\Models\Sales\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceOverdueReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public ?string $paymentLinkUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment Reminder: Invoice {$this->invoice->invoice_number} is overdue",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoice-overdue-reminder',
            with: [
                'invoice' => $this->invoice,
                'amountDue' => $this->invoice->amountDue(),
                'daysOverdue' => (int) $this->invoice->due_date->diffInDays(now()),
                'paymentLinkUrl' => $this->paymentLinkUrl,
            ],
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('pdfs.invoice', [
            'invoice' => $this->invoice,
            'amountPaid' => $this->invoice->amountPaid(),
            'amountDue' => $this->invoice->amountDue(),
            'appName' => config('app.name'),
        ]);

        return [
            Attachment::fromData(fn () => $pdf->output(), "{$this->invoice->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
