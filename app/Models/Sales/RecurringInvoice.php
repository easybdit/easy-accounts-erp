<?php

namespace App\Models\Sales;

use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A saved invoice template a user manually generates a draft Invoice from
 * (Section 60: "do not implement automation prematurely" — no scheduler-
 * driven auto-generation, just a "Generate Now" action; see Section 90
 * Phase 12 notes for why).
 */
class RecurringInvoice extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'customer_id',
        'receivable_account_id',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
