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

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'receivable_account_id',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'discount_total',
        'total',
        'notes',
        'posted_at',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:4',
        'discount_total' => 'decimal:4',
        'total' => 'decimal:4',
        'posted_at' => 'datetime',
    ];

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
        return $this->hasMany(InvoiceItem::class);
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
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
