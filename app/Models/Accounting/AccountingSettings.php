<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A singleton settings row (always id=1). Today it holds only the Period
 * Lock: no journal may post on or before locked_through_date (enforced in
 * PostJournal, the single choke point every posting action already goes
 * through) — the standard "close the books" control that stops an old
 * period from being edited after it's been reported on and reconciled.
 */
class AccountingSettings extends Model
{
    use LogsActivity;

    protected $fillable = [
        'locked_through_date',
        'locked_by',
    ];

    protected $casts = [
        'locked_through_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public static function current(): self
    {
        return self::firstOrCreate(['id' => 1]);
    }
}
