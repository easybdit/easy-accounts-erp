<?php

namespace App\Models\Purchases;

use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\Contacts\Vendor;
use App\Models\User;
use Database\Factories\Purchases\BillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Bill extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected $fillable = [
        'bill_number',
        'vendor_id',
        'recurring_bill_id',
        'purchase_order_id',
        'payable_account_id',
        'bill_date',
        'due_date',
        'tax_inclusive',
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
        'bill_date' => 'date',
        'due_date' => 'date',
        'tax_inclusive' => 'boolean',
        'subtotal' => 'decimal:4',
        'discount_total' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'total' => 'decimal:4',
        'posted_at' => 'datetime',
    ];

    protected static function newFactory(): BillFactory
    {
        return BillFactory::new();
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function recurringBill(): BelongsTo
    {
        return $this->belongsTo(RecurringBill::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function payableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payable_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(BillAttachment::class);
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(VendorPaymentAllocation::class);
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

    /**
     * Always computed from real VendorPaymentAllocation rows (Section 79
     * Payment Integrity) — never a separate, independently-editable column.
     */
    public function amountPaid(): string
    {
        // bcadd (not a raw cast) normalizes the DB driver's raw SUM()
        // result to a consistent DECIMAL(19,4)-formatted string — SQLite
        // (used in tests) does not preserve column scale the way
        // MySQL/MariaDB do.
        return bcadd((string) ($this->paymentAllocations()->sum('amount') ?: 0), '0', 4);
    }

    public function amountDue(): string
    {
        return bcsub((string) $this->total, $this->amountPaid(), 4);
    }

    public function isFullyPaid(): bool
    {
        return $this->status === 'posted' && bccomp($this->amountDue(), '0', 4) <= 0;
    }
}
