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
 * A non-financial, pre-invoice document (Section 90 Phase 4 open item):
 * an Estimate never touches the Journal. It only becomes a real
 * transaction once ConvertEstimateToInvoice turns it into a normal draft
 * Invoice, which then follows the Invoice's own draft -> post lifecycle
 * exactly like one created by hand.
 */
class Estimate extends Model
{
    use LogsActivity;

    protected $fillable = [
        'estimate_number',
        'customer_id',
        'receivable_account_id',
        'estimate_date',
        'expiry_date',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'notes',
        'last_emailed_at',
        'converted_invoice_id',
        'created_by',
    ];

    protected $casts = [
        'estimate_date' => 'date',
        'expiry_date' => 'date',
        'subtotal' => 'decimal:4',
        'discount_total' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'total' => 'decimal:4',
        'last_emailed_at' => 'datetime',
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
        return $this->hasMany(EstimateItem::class);
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    public function isEditable(): bool
    {
        return ! $this->isConverted();
    }
}
