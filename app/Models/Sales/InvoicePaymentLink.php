<?php

namespace App\Models\Sales;

use App\Models\Accounting\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A shareable, unauthenticated payment link for one invoice (Section 90):
 * this app has no customer login/portal, so "online payment" means a staff
 * member generates this token-bearing link and sends it to the customer
 * directly (email/WhatsApp/SMS, outside this app) rather than the customer
 * logging in anywhere. The token itself is the only credential — treat it
 * like a bearer secret, not a public identifier.
 */
class InvoicePaymentLink extends Model
{
    protected $fillable = [
        'invoice_id',
        'token',
        'deposit_account_id',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function depositAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'deposit_account_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(OnlinePaymentTransaction::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
