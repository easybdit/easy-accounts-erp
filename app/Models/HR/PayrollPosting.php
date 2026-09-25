<?php

namespace App\Models\HR;

use App\Models\Accounting\Journal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PayrollPosting extends Model
{
    protected $fillable = [
        'salary_slip_type',
        'salary_slip_id',
        'journal_id',
        'cost_center_id',
        'gross_amount',
        'total_deductions',
        'net_amount',
        'employee_name',
        'employee_code',
        'period_year',
        'period_month',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:4',
        'total_deductions' => 'decimal:4',
        'net_amount' => 'decimal:4',
        'posted_at' => 'datetime',
    ];

    public function salarySlip(): MorphTo
    {
        return $this->morphTo();
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
