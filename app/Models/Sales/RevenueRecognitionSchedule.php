<?php

namespace App\Models\Sales;

use App\Models\Accounting\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Tracks recognizing one deferred invoice line's revenue evenly over
 * months_total months, starting the month the invoice was posted.
 * RecognizeRevenue posts each month's entry: debit deferred_revenue_
 * account_id (the liability PostInvoice credited instead of income at
 * posting time), credit income_account_id.
 */
class RevenueRecognitionSchedule extends Model
{
    use LogsActivity;

    protected $fillable = [
        'invoice_item_id',
        'deferred_revenue_account_id',
        'income_account_id',
        'total_amount',
        'months_total',
        'months_recognized',
        'next_period_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:4',
        'months_total' => 'integer',
        'months_recognized' => 'integer',
        'next_period_date' => 'date',
    ];

    public const STATUSES = ['active', 'completed'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function deferredRevenueAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'deferred_revenue_account_id');
    }

    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(RevenueRecognitionEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * bcadd (not a raw cast) normalizes the DB driver's raw SUM() result to
     * a consistent DECIMAL(19,4)-formatted string — SQLite (used in tests)
     * does not preserve column scale the way MySQL/MariaDB do.
     */
    public function recognizedTotal(): string
    {
        return bcadd((string) ($this->entries()->sum('amount') ?: 0), '0', 4);
    }

    public function remaining(): string
    {
        return bcsub((string) $this->total_amount, $this->recognizedTotal(), 4);
    }
}
