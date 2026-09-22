<?php

namespace App\Models\Expenses;

use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Tax\TaxRate;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A saved expense template a user manually generates a new Expense from
 * (Section 60: no scheduler-driven automation, just a "Generate Now"
 * action — mirrors App\Models\Sales\RecurringInvoice). Unlike a recurring
 * invoice, a generated expense posts immediately: Expense has never had a
 * draft state, so this isn't a special case, it's the same behavior as
 * recording one by hand.
 */
class RecurringExpense extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'expense_category_id',
        'account_id',
        'payment_account_id',
        'vendor_id',
        'payee',
        'amount',
        'tax_rate_id',
        'reference',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
