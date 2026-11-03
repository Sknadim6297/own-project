<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaterIntakeSetting extends Model
{
    protected $fillable = [
        'daily_goal_ml',
        'previous_goal_ml',
        'goal_changed_at',
        'display_unit',
    ];

    protected $casts = [
        'daily_goal_ml' => 'integer',
        'previous_goal_ml' => 'integer',
        'goal_changed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
