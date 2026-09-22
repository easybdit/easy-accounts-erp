<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A depreciable asset (e.g. a server) tracked separately from its GL
 * account balance. PostDepreciation posts straight-line monthly
 * depreciation — debit the expense account, credit the accumulated
 * depreciation (contra-asset) account — never the asset account itself,
 * so the balance sheet keeps showing original cost less accumulated
 * depreciation rather than silently shrinking the asset's historical cost.
 */
class FixedAsset extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'asset_account_id',
        'accumulated_depreciation_account_id',
        'depreciation_expense_account_id',
        'purchase_date',
        'purchase_cost',
        'salvage_value',
        'useful_life_months',
        'months_depreciated',
        'status',
        'disposed_at',
        'disposal_notes',
        'disposal_proceeds',
        'disposal_proceeds_account_id',
        'gain_loss_account_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:4',
        'salvage_value' => 'decimal:4',
        'useful_life_months' => 'integer',
        'months_depreciated' => 'integer',
        'disposed_at' => 'datetime',
        'disposal_proceeds' => 'decimal:4',
    ];

    public const STATUSES = ['active', 'fully_depreciated', 'disposed'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(FixedAssetDepreciation::class);
    }

    public function disposalProceedsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'disposal_proceeds_account_id');
    }

    public function gainLossAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gain_loss_account_id');
    }

    /**
     * The disposal journal — debit accumulated depreciation, debit any
     * proceeds, debit/credit the gain or loss, credit the asset's original
     * cost. Kept separate from each month's depreciation journal (those
     * belong to FixedAssetDepreciation, not this model) via its own
     * source_type.
     */
    public function disposalJournal(): MorphOne
    {
        return $this->morphOne(Journal::class, 'source');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function depreciableBase(): string
    {
        return bcsub((string) $this->purchase_cost, (string) $this->salvage_value, 4);
    }

    /**
     * bcadd (not a raw cast) normalizes the DB driver's raw SUM() result to
     * a consistent DECIMAL(19,4)-formatted string — SQLite (used in tests)
     * does not preserve column scale the way MySQL/MariaDB do.
     */
    public function accumulatedDepreciation(): string
    {
        return bcadd((string) ($this->depreciations()->sum('amount') ?: 0), '0', 4);
    }

    public function bookValue(): string
    {
        return bcsub((string) $this->purchase_cost, $this->accumulatedDepreciation(), 4);
    }
}
