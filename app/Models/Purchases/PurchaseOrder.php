<?php

namespace App\Models\Purchases;

use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * The Purchases-side mirror of Estimate: a non-financial, pre-bill
 * document that never touches the Journal. Only becomes a real
 * transaction once ConvertPurchaseOrderToBill turns it into a normal
 * draft Bill via SaveBillDraft (Section 90 Phase 5 open item).
 *
 * A PO can be converted more than once, each time for a chosen quantity
 * per line (e.g. 6 of 10 ordered routers arrived this week) — see
 * PurchaseOrderItem::billed_quantity and ConvertPurchaseOrderToBill.
 * status only ever flips to 'converted' once every line is fully billed;
 * while a partial conversion has happened but some quantity remains, it
 * stays 'draft' but is no longer editable (isEditable() also checks
 * whether any bill already exists) — editing line quantities out from
 * under a bill that already references them would desync the two.
 */
class PurchaseOrder extends Model
{
    use LogsActivity;

    protected $fillable = [
        'po_number',
        'vendor_id',
        'payable_account_id',
        'order_date',
        'expected_date',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'notes',
        'converted_bill_id',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'subtotal' => 'decimal:4',
        'discount_total' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'total' => 'decimal:4',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function payableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payable_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function convertedBill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'converted_bill_id');
    }

    /**
     * Every Bill ever generated from this PO — plural, since a partial
     * conversion means more than one may exist over time. convertedBill()
     * above stays a pointer at just the most recent one, kept for
     * backward-compatible display purposes.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    public function isFullyBilled(): bool
    {
        $this->loadMissing('items');

        return $this->items->every(fn (PurchaseOrderItem $item) => bccomp($item->remainingQuantity(), '0', 4) <= 0);
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft' && ! $this->bills()->exists();
    }
}
