<?php

namespace App\Models\Sales;

use App\Models\Accounting\Account;
use App\Models\Inventory\Product;
use App\Models\Tax\TaxRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InvoiceItem extends Model
{
    protected $fillable = [
        'product_id',
        'account_id',
        'tax_rate_id',
        'tax_rate_2_id',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'line_total',
        'tax_amount',
        'tax_amount_2',
        'is_deferred',
        'deferred_months',
        'deferred_revenue_account_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount' => 'decimal:4',
        'line_total' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'tax_amount_2' => 'decimal:4',
        'is_deferred' => 'boolean',
        'deferred_months' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * An optional second, independent tax rate on the same line (e.g.
     * Bangladesh SD + VAT) — computed on the same net line_total as the
     * primary rate, not compounded on top of it (Section 33).
     */
    public function taxRate2(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_2_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function deferredRevenueAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'deferred_revenue_account_id');
    }

    public function revenueRecognitionSchedule(): HasOne
    {
        return $this->hasOne(RevenueRecognitionSchedule::class);
    }
}
