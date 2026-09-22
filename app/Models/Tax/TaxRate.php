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
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class TaxRate extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

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
     * Exclusive tax: the amount is calculated on top of the line's net
     * amount.
     */
    public function calculate(string $netAmount): string
    {
        return bcdiv(bcmul($netAmount, (string) $this->rate, 6), '100', 4);
    }

    /**
     * Inverse of calculate(): backs the net amount out of a tax-inclusive
     * gross amount (Section 33 Tax Inclusive pricing) — net = gross / (1 +
     * rate/100). Used only at draft-save time to interpret a line's entered
     * unit price; everywhere downstream (posting, totals) keeps treating
     * line_total as a plain net, pre-tax amount regardless of which mode
     * produced it.
     */
    public function extractNet(string $grossAmount): string
    {
        $divisor = bcadd('100', (string) $this->rate, 6);

        return bcdiv(bcmul($grossAmount, '100', 6), $divisor, 4);
    }
}
