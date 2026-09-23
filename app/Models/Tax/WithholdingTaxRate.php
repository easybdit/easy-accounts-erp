<?php

namespace App\Models\Tax;

use App\Models\Accounting\Account;
use App\Models\Purchases\VendorPayment;
use Database\Factories\Tax\WithholdingTaxRateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A Bangladesh TDS (Tax Deducted at Source) / VDS (VAT Deducted at Source)
 * rate — e.g. "TDS 10% — Professional Fee", "VDS 7.5% — Service". Unlike
 * TaxRate (App\Models\Tax\TaxRate), which is charged BY a vendor ON TOP of
 * a bill and increases what the business owes, a withholding rate is
 * deducted FROM a vendor payment: the business keeps that portion instead
 * of paying it out, and owes it to the tax authority instead of the vendor
 * (see App\Actions\Purchases\MakePayment). The specific rate for a given
 * payment is a compliance judgment call the accountant makes when applying
 * it — this app does not hardcode or guess at Bangladesh's current TDS/VDS
 * schedule, which changes by Finance Act/SRO year to year.
 */
class WithholdingTaxRate extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected $fillable = [
        'name',
        'rate',
        'liability_account_id',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): WithholdingTaxRateFactory
    {
        return WithholdingTaxRateFactory::new();
    }

    public function liabilityAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'liability_account_id');
    }

    public function vendorPayments(): HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }

    /**
     * Mirrors TaxRate::calculate — the withheld amount is a percentage of
     * the gross payment amount being settled.
     */
    public function calculate(string $grossAmount): string
    {
        return bcdiv(bcmul($grossAmount, (string) $this->rate, 6), '100', 4);
    }
}
