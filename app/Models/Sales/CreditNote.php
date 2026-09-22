<?php

namespace App\Models\Sales;

use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\Contacts\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Resolves the remaining Phase 4 open item. A Credit Note reduces a
 * customer's AR balance and reverses income — the exact opposite journal
 * of an Invoice. It may optionally reference the Invoice it relates to
 * (for traceability), but deliberately does NOT use the PaymentAllocation
 * system: it reduces the customer's balance in aggregate via the same
 * customer-tagged journal_entries every other AR movement uses, not a
 * specific invoice's amountDue(). That is a documented simplification
 * (Section 90), not an oversight — extending PaymentAllocation to also
 * cover Credit Notes is a materially larger feature.
 */
class CreditNote extends Model
{
    use LogsActivity;

    protected $fillable = [
        'credit_note_number',
        'customer_id',
        'receivable_account_id',
        'invoice_id',
        'credit_note_date',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'notes',
        'posted_at',
        'created_by',
    ];

    protected $casts = [
        'credit_note_date' => 'date',
        'subtotal' => 'decimal:4',
        'discount_total' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'total' => 'decimal:4',
        'posted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function receivableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'receivable_account_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journal(): MorphOne
    {
        return $this->morphOne(Journal::class, 'source');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
