<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YuktiRewardConfig extends Model
{
    protected $table = 'yukti_reward_configs';

    protected $fillable = [
        'key',
        'points',
        'weekly_cap_count',
        'weekly_cap_points',
        'is_active',
        'approval_status',
        'meta',
        'updated_by_admin_id',
    ];

    protected $casts = [
        'points' => 'integer',
        'weekly_cap_count' => 'integer',
        'weekly_cap_points' => 'integer',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by_admin_id');
    }
}
