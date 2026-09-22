<?php

namespace App\Models\Accounting;

use Database\Factories\Accounting\AccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_id',
        'opening_balance',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public const TYPES = ['asset', 'liability', 'equity', 'income', 'expense'];

    protected static function newFactory(): AccountFactory
    {
        return AccountFactory::new();
    }

    protected static function booted(): void
    {
        static::saving(function (Account $account) {
            if ($account->parent_id) {
                $parentType = $account->relationLoaded('parent')
                    ? $account->parent?->type
                    : static::where('id', $account->parent_id)->value('type');

                if ($parentType !== null && $parentType !== $account->type) {
                    throw new RuntimeException(
                        'A child account must have the same account type as its parent account.'
                    );
                }
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * All descendant account IDs (recursive), computed iteratively to stay
     * portable across MySQL/MariaDB versions without relying on recursive CTEs.
     */
    public function descendantIds(): array
    {
        $ids = [];
        $queue = [$this->id];

        while ($queue !== []) {
            $childIds = static::whereIn('parent_id', $queue)->pluck('id')->all();
            $ids = [...$ids, ...$childIds];
            $queue = $childIds;
        }

        return $ids;
    }

    /**
     * Normal balance side for this account's type.
     * Assets/Expenses increase on debit; Liabilities/Equity/Income increase on credit.
     * This is fixed accounting fact, not a configurable business rule.
     */
    public function normalBalance(): string
    {
        return in_array($this->type, ['asset', 'expense'], true) ? 'debit' : 'credit';
    }
}
