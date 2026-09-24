<?php

namespace App\Models\Sales;

use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\Contacts\Customer;
use App\Models\User;
use Database\Factories\Sales\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Invoice extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'recurring_invoice_id',
        'receivable_account_id',
        'cost_center_id',
        'invoice_date',
        'due_date',
        'tax_inclusive',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'notes',
        'posted_at',
        'last_emailed_at',
        'last_reminder_sent_at',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'tax_inclusive' => 'boolean',
        'subtotal' => 'decimal:4',
        'discount_total' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'total' => 'decimal:4',
        'posted_at' => 'datetime',
        'last_emailed_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function receivableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'receivable_account_id');
    }

    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function paymentLinks(): HasMany
    {
        return $this->hasMany(InvoicePaymentLink::class);
    }

    /**
     * Amount paid/due are always computed from real PaymentAllocation rows
     * (never a stored, independently-editable column), so an invoice can
     * never be "marked paid" without a corresponding payment record
     * (Section 79 Payment Integrity).
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

    public function isOverdue(): bool
    {
        return $this->status === 'posted'
            && $this->due_date !== null
            && $this->due_date->isPast()
            && bccomp($this->amountDue(), '0', 4) > 0;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The journal this invoice was posted into, via the shared PostJournal
     * source_type/source_id tag (Section 15/17 traceability) — no separate
     * journal_id column needed.
     */
    public function journal(): MorphOne
    {
        return $this->morphOne(Journal::class, 'source');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    protected static function newFactory(): InvoiceFactory
    {
        return InvoiceFactory::new();
    }
}
