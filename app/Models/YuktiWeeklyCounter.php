<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YuktiWeeklyCounter extends Model
{
    public const METRICS = [
        'login',
        'tool_complete',
        'tool_referral',
        'expert_referral',
        'social_share',
    ];

    protected $table = 'yukti_weekly_counters';

    protected $fillable = [
        'user_id',
        'week_id',
        'metric',
        'count',
        'points_awarded',
    ];

    protected $casts = [
        'count' => 'integer',
        'points_awarded' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
