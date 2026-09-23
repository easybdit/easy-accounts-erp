<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * An annual budget: one target amount per income/expense account for a
 * calendar fiscal_year. The Budget vs Actual report (ReportController::
 * budgetVsActual) prorates each line's annual amount by the fraction of
 * the year covered by the selected date range, so a mid-year run still
 * compares like with like rather than a full year's budget against a
 * partial year's actual.
 */
class Budget extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'fiscal_year',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
