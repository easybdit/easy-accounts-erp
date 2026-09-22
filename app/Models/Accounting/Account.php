<?php

namespace App\Models\Accounting;

use Database\Factories\Accounting\AccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Account extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_id',
        'opening_balance',
        'is_active',
        'is_bank_account',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:4',
        'is_active' => 'boolean',
        'is_bank_account' => 'boolean',
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

    /**
     * Sum of posted entry debit/credit amounts in an inclusive date range,
     * using the database's own SUM (no N+1) with a bcmath-safe string return.
     */
    public function netMovement(?string $from = null, ?string $to = null): array
    {
        $row = $this->journalEntries()
            ->when($from, fn ($query) => $query->where('date', '>=', $from))
            ->when($to, fn ($query) => $query->where('date', '<=', $to))
            ->selectRaw('COALESCE(SUM(debit), 0) as debit, COALESCE(SUM(credit), 0) as credit')
            ->first();

        return [
            'debit' => (string) ($row->debit ?? '0.0000'),
            'credit' => (string) ($row->credit ?? '0.0000'),
        ];
    }

    /**
     * Signed balance as of a date, in this account's natural (normal-balance) direction.
     */
    public function balanceAsOf(?string $asOf = null): string
    {
        $movement = $this->netMovement(null, $asOf);
        $opening = (string) $this->opening_balance;

        return $this->normalBalance() === 'debit'
            ? bcsub(bcadd($opening, $movement['debit'], 4), $movement['credit'], 4)
            : bcsub(bcadd($opening, $movement['credit'], 4), $movement['debit'], 4);
    }
}
