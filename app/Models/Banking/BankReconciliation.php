<?php

namespace App\Models\Banking;

use App\Models\Accounting\Account;
use App\Models\Accounting\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A completed reconciliation "session" for one bank/cash account against a
 * bank statement: which already-posted journal entries were matched, and
 * what the statement's ending balance was. Once created it is never
 * updated or deleted (Section 20 pattern) — its entries' reconciled_at
 * stays set permanently, which is what "locks" a prior reconciled period:
 * ReconcileAccount only ever offers entries with reconciled_at still null.
 */
class BankReconciliation extends Model
{
    use LogsActivity;

    protected $fillable = [
        'account_id',
        'statement_date',
        'statement_balance',
        'reconciled_at',
        'created_by',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'statement_balance' => 'decimal:4',
        'reconciled_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
