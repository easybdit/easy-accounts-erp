<?php

namespace App\Models\Sales;

use App\Models\Accounting\Account;
use App\Models\Concerns\HasRecurrenceFrequency;
use App\Models\Contacts\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A saved invoice template a user generates a draft Invoice from — either
 * manually ("Generate Now") or, if next_generation_date is set,
 * automatically via the scheduled invoices:generate-recurring command
 * (Section 90 Phase 12: explicitly requested automation, a deliberate
 * reversal of the original "no scheduler-driven generation" decision).
 * Either way the result is always a DRAFT invoice, never auto-posted —
 * automation removes the "remember to click a button" step, not the
 * review-before-posting safety net.
 */
class RecurringInvoice extends Model
{
    use HasRecurrenceFrequency, LogsActivity;

    protected $fillable = [
        'name',
        'customer_id',
        'receivable_account_id',
        'notes',
        'is_active',
        'next_generation_date',
        'frequency',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'next_generation_date' => 'date',
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

    public function items(): HasMany
    {
        return $this->hasMany(RecurringInvoiceItem::class);
    }

    public function generatedInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
