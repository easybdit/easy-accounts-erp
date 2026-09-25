<?php

namespace App\Models\HR;

use App\Models\Accounting\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PayrollComponent extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected $fillable = [
        'name',
        'type',
        'account_id',
        'source_component_key',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const TYPES = ['earning', 'deduction'];

    /**
     * Fixed salary-slip fields every organization must map before payroll
     * can post — everything else is a dynamic key inside the slip's
     * `allowances` JSON map.
     */
    public const FIXED_KEYS = ['basic_salary', 'deduction_amount', 'overtime_amount', 'special_pay_amount', 'net_salary'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
