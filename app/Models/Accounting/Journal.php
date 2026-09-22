<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Journal extends Model
{
    protected $fillable = [
        'date',
        'reference',
        'description',
        'posted_at',
        'source_type',
        'source_id',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
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
