<?php

namespace App\Models\Tax;

use App\Models\Accounting\Account;
use App\Models\Purchases\BillItem;
use App\Models\Sales\InvoiceItem;
use Database\Factories\Tax\TaxRateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rate',
        'tax_account_id',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): TaxRateFactory
    {
        return TaxRateFactory::new();
    }

    public function taxAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'tax_account_id');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function billItems(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    /**
     * Exclusive tax only (Section 33): the amount is calculated on top of
     * the line's net amount, never backed out of a tax-inclusive price.
     * "Tax Inclusive" pricing is a documented scope limit, not a guess.
     */
    public function calculate(string $netAmount): string
    {
        return bcdiv(bcmul($netAmount, (string) $this->rate, 6), '100', 4);
    }
}
