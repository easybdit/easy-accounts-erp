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

class Payment extends Model
{
    protected $fillable = [
        'payment_number',
        'customer_id',
        'deposit_account_id',
        'payment_date',
        'reference',
        'method',
        'amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:4',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function depositAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'deposit_account_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journal(): MorphOne
    {
        return $this->morphOne(Journal::class, 'source');
    }
}
