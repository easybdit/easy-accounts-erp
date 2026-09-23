<?php

namespace App\Mail;

use App\Models\Sales\Estimate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EstimateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Estimate $estimate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Estimate {$this->estimate->estimate_number} from ".config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.estimate',
            with: [
                'estimate' => $this->estimate,
            ],
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('pdfs.estimate', [
            'estimate' => $this->estimate,
            'appName' => config('app.name'),
        ]);

        return [
            Attachment::fromData(fn () => $pdf->output(), "{$this->estimate->estimate_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
