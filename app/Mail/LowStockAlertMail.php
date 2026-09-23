<?php

namespace App\Mail;

use App\Models\Inventory\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class LowStockAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, Product>  $products
     */
    public function __construct(
        public Collection $products,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Low Stock Alert — {$this->products->count()} item(s) at or below reorder level",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.low-stock-alert',
            with: [
                'products' => $this->products,
            ],
        );
    }
}
