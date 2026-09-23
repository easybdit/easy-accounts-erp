<?php

namespace App\Models\Purchases;

use App\Models\Accounting\Account;
use App\Models\Concerns\HasRecurrenceFrequency;
use App\Models\Contacts\Vendor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A saved bill template a user generates a draft Bill from — either
 * manually ("Generate Now") or, if next_generation_date is set,
 * automatically via the scheduled bills:generate-recurring command —
 * mirrors App\Models\Sales\RecurringInvoice exactly. Always produces a
 * DRAFT bill, never auto-posted: automation removes the "remember to
 * click a button" step, not the review-before-posting safety net.
 */
class RecurringBill extends Model
{
    use HasRecurrenceFrequency, LogsActivity;

    protected $fillable = [
        'name',
        'vendor_id',
        'payable_account_id',
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

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function payableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payable_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecurringBillItem::class);
    }

    public function generatedBills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
