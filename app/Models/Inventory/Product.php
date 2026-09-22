<?php

namespace App\Models\Inventory;

use App\Models\Accounting\Account;
use Database\Factories\Inventory\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Product extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected $fillable = [
        'sku',
        'name',
        'product_category_id',
        'type',
        'unit',
        'purchase_price',
        'selling_price',
        'income_account_id',
        'cogs_account_id',
        'inventory_account_id',
        'low_stock_threshold',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:4',
        'selling_price' => 'decimal:4',
        'low_stock_threshold' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public const TYPES = ['inventory', 'service'];

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cogs_account_id');
    }

    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'inventory_account_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isInventoryTracked(): bool
    {
        return $this->type === 'inventory';
    }

    /**
     * Always computed from real StockMovement rows (Section 32: every stock
     * movement must be traceable) — never a separately-editable column.
     */
    public function currentStock(): string
    {
        // bcadd (not a raw cast) normalizes the DB driver's raw SUM() result
        // to a consistent DECIMAL(19,4)-formatted string — SQLite (used in
        // tests) does not preserve column scale the way MySQL/MariaDB do.
        return bcadd((string) ($this->stockMovements()->sum('quantity') ?: 0), '0', 4);
    }

    /**
     * Simple valuation: quantity on hand x current purchase price. Not
     * FIFO/LIFO/weighted-average cost layering — that is a materially
     * larger feature, deliberately not attempted here (see Section 32
     * Implementation Status notes).
     */
    public function stockValue(): string
    {
        return bcmul($this->currentStock(), (string) $this->purchase_price, 4);
    }

    public function isLowStock(): bool
    {
        if (! $this->isInventoryTracked() || $this->low_stock_threshold === null) {
            return false;
        }

        return bccomp($this->currentStock(), (string) $this->low_stock_threshold, 4) <= 0;
    }
}
