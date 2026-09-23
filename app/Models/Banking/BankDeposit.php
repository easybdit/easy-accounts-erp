<?php

namespace App\Models\Banking;

use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\Sales\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A "Bank Deposit" batch: one or more customer Payments received into the
 * Undeposited Funds holding account, taken to the bank together and
 * cleared with a single journal entry (debit the real bank account, credit
 * Undeposited Funds) — mirrors what the bank statement actually shows (one
 * lump deposit), rather than crediting Undeposited Funds separately for
 * each payment. Posted immediately on creation (App\Actions\Banking\
 * MakeBankDeposit), same as Journals/Payments/Transfers/Expenses — no
 * draft state, no edit/destroy.
 */
class BankDeposit extends Model
{
    use LogsActivity;

    protected $fillable = [
        'deposit_number',
        'bank_account_id',
        'deposit_date',
        'amount',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'deposit_date' => 'date',
        'amount' => 'decimal:4',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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
