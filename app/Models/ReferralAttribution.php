<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralAttribution extends Model
{
    public const TYPES = ['general', 'tool', 'expert'];

    public const STATUSES = ['invited', 'joined', 'activated', 'rewarded', 'rejected'];

    protected $fillable = [
        'referrer_user_id',
        'referee_user_id',
        'referral_code',
        'referral_type',
        'target_type',
        'target_id',
        'status',
        'idempotency_key',
        'signed_up_at',
        'activated_at',
        'rewarded_at',
        'activation_meta',
    ];

    protected $casts = [
        'target_id' => 'integer',
        'signed_up_at' => 'datetime',
        'activated_at' => 'datetime',
        'rewarded_at' => 'datetime',
        'activation_meta' => 'array',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_user_id');
    }
}
