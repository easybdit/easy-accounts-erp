<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Journal extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected $fillable = [
        'date',
        'reference',
        'description',
        'posted_at',
        'voided_at',
        'reversal_of_journal_id',
        'source_type',
        'source_id',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The original journal this one reverses, if this journal IS a reversal.
     */
    public function reversalOfJournal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_journal_id');
    }

    /**
     * The reversal journal that voided this one, if it has been voided.
     */
    public function reversalJournal(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_journal_id');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function isReversal(): bool
    {
        return $this->reversal_of_journal_id !== null;
    }

    /**
     * Only journals posted directly through the Journal module (no
     * source_type) can be voided here — a journal generated behind an
     * Invoice/Bill/Payment/Expense/Transfer must be voided through that
     * document's own lifecycle instead, since voiding it here would corrupt
     * that document's status (Section 90 Phase-13 notes: deliberately
     * scoped, not an oversight).
     */
    public function canBeVoided(): bool
    {
        return $this->source_type === null && ! $this->isVoided() && ! $this->isReversal();
    }

    /**
     * Sum entry amounts using bcmath to avoid floating-point error,
     * since DECIMAL(19,4) values must be compared exactly (Section 21).
     */
    public function totalDebit(): string
    {
        return $this->entries->reduce(
            fn (string $carry, JournalEntry $entry) => bcadd($carry, (string) $entry->debit, 4),
            '0.0000'
        );
    }

    public function totalCredit(): string
    {
        return $this->entries->reduce(
            fn (string $carry, JournalEntry $entry) => bcadd($carry, (string) $entry->credit, 4),
            '0.0000'
        );
    }

    public function isBalanced(): bool
    {
        return bccomp($this->totalDebit(), $this->totalCredit(), 4) === 0;
    }
}
