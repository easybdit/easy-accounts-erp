<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlinePaymentTransaction extends Model
{
    protected $fillable = [
        'invoice_payment_link_id',
        'tran_id',
        'amount',
        'currency',
        'status',
        'val_id',
        'gateway_response',
        'payment_id',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'gateway_response' => 'array',
    ];

    public const STATUSES = ['initiated', 'validated', 'failed', 'cancelled'];

    public function invoicePaymentLink(): BelongsTo
    {
        return $this->belongsTo(InvoicePaymentLink::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isValidated(): bool
    {
        return $this->status === 'validated';
    }
}
