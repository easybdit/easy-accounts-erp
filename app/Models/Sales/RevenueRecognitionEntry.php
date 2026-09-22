<?php

namespace App\Models\Sales;

use App\Models\Accounting\Journal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class RevenueRecognitionEntry extends Model
{
    protected $fillable = [
        'revenue_recognition_schedule_id',
        'period_date',
        'amount',
        'created_by',
    ];

    protected $casts = [
        'period_date' => 'date',
        'amount' => 'decimal:4',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(RevenueRecognitionSchedule::class, 'revenue_recognition_schedule_id');
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
