<?php

namespace App\Models\Purchases;

use App\Models\Accounting\Account;
use App\Models\Tax\TaxRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillItem extends Model
{
    protected $fillable = [
        'account_id',
        'tax_rate_id',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'line_total',
        'tax_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount' => 'decimal:4',
        'line_total' => 'decimal:4',
        'tax_amount' => 'decimal:4',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }
}
