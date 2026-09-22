<?php

namespace App\Models\Inventory;

use App\Models\Accounting\Journal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'date',
        'quantity',
        'reason',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Only 'adjustment' movements post a journal — 'opening' movements are
     * treated like Account/Customer/Vendor opening balances (an assumed
     * starting position, not a journaled event).
     */
    public function journal(): MorphOne
    {
        return $this->morphOne(Journal::class, 'source');
    }
}
