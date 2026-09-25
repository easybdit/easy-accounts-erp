<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class HrSettings extends Model
{
    use LogsActivity;

    protected $fillable = [
        'special_working_day_grade_rates',
        'late_deduction_ratio',
        'late_warning_threshold',
        'multi_step_leave_approval_enabled',
    ];

    protected $casts = [
        'special_working_day_grade_rates' => 'array',
        'multi_step_leave_approval_enabled' => 'boolean',
    ];

    public const DEFAULT_GRADE_RATES = ['A' => 1000, 'B' => 700, 'C' => 500];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    /**
     * @see App\Models\Accounting\AccountingSettings::current() — identical
     * shape: firstOrCreate() alone leaves defaulted columns unset in memory
     * on the very first call ever, so a fresh row is re-fetched once.
     */
    public static function current(): self
    {
        $settings = self::firstOrCreate(['id' => 1], [
            'special_working_day_grade_rates' => self::DEFAULT_GRADE_RATES,
        ]);

        return $settings->wasRecentlyCreated ? $settings->fresh() : $settings;
    }

    public function gradeRate(?string $grade): ?float
    {
        if ($grade === null) {
            return null;
        }

        $rate = ($this->special_working_day_grade_rates ?? self::DEFAULT_GRADE_RATES)[$grade] ?? null;

        return $rate !== null ? (float) $rate : null;
    }
}
