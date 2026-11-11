<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YuktiRedemption extends Model
{
    protected $table = 'yukti_redemptions';

    protected $fillable = [
        'user_id',
        'points',
        'status',
        'offering_type',
        'offering_id',
        'idempotency_key',
        'reviewed_by_admin_id',
        'reviewed_at',
        'notes',
    ];

    protected $casts = [
        'points' => 'integer',
        'offering_id' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by_admin_id');
    }
}
