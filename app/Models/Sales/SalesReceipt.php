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
 * An immediate cash sale (Section 12/28): unlike Invoice, there is no draft
 * state and no Accounts Receivable step — creating one IS posting it, same
 * lifecycle as Payment (no status column; existence of the row means it's
 * posted).
 */
class SalesReceipt extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected $fillable = [
        'receipt_number',
        'customer_id',
        'deposit_account_id',
        'receipt_date',
        'tax_inclusive',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'tax_inclusive' => 'boolean',
        'subtotal' => 'decimal:4',
        'discount_total' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'total' => 'decimal:4',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function depositAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'deposit_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReceiptItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The journal this receipt was posted into, via the shared PostJournal
     * source_type/source_id tag (Section 15/17 traceability) — no separate
     * journal_id column needed.
     */
    public function journal(): MorphOne
    {
        return $this->morphOne(Journal::class, 'source');
    }
}
