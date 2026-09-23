<?php

namespace App\Models\Purchases;

use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\Contacts\Vendor;
use App\Models\Tax\WithholdingTaxRate;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class VendorPayment extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected $fillable = [
        'payment_number',
        'vendor_id',
        'payment_account_id',
        'payment_date',
        'reference',
        'method',
        'amount',
        'withholding_tax_rate_id',
        'withholding_tax_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:4',
        'withholding_tax_amount' => 'decimal:4',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function withholdingTaxRate(): BelongsTo
    {
        return $this->belongsTo(WithholdingTaxRate::class);
    }

    /**
     * The cash/bank amount actually disbursed — the settled amount less
     * whatever was withheld for TDS/VDS instead of paid to the vendor.
     */
    public function netCashPaid(): string
    {
        return bcsub((string) $this->amount, (string) $this->withholding_tax_amount, 4);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(VendorPaymentAllocation::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journal(): MorphOne
    {
        return $this->morphOne(Journal::class, 'source');
    }
}
