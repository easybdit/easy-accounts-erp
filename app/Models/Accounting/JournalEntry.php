<?php

namespace App\Models\Accounting;

use App\Models\Banking\BankReconciliation;
use App\Models\Contacts\Customer;
use App\Models\Contacts\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntry extends Model
{
    protected $fillable = [
        'account_id',
        'customer_id',
        'vendor_id',
        'cost_center_id',
        'date',
        'debit',
        'credit',
        'description',
        'reconciled_at',
        'bank_reconciliation_id',
    ];

    protected $casts = [
        'date' => 'date',
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
        'reconciled_at' => 'datetime',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function bankReconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function isReconciled(): bool
    {
        return $this->reconciled_at !== null;
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
