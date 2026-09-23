<?php

namespace App\Models\Purchases;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillAttachment extends Model
{
    protected $fillable = [
        'original_filename',
        'stored_path',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
