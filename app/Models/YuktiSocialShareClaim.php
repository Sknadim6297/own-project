<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YuktiSocialShareClaim extends Model
{
    protected $table = 'yukti_social_share_claims';

    protected $fillable = [
        'user_id',
        'week_id',
        'channel',
        'verification_status',
        'idempotency_key',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
