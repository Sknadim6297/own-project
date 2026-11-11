<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable points ledger row. Reversals must insert a new debit/credit row —
 * never mutate historical points amounts.
 */
class YuktiRewardLedgerEntry extends Model
{
    public $timestamps = false;

    protected $table = 'yukti_reward_ledger';

    protected $fillable = [
        'user_id',
        'direction',
        'points',
        'available_after',
        'pending_after',
        'event_type',
        'source_type',
        'source_id',
        'week_id',
        'status',
        'idempotency_key',
        'meta',
        'rewarded_at',
        'created_at',
    ];

    protected $casts = [
        'points' => 'integer',
        'available_after' => 'integer',
        'pending_after' => 'integer',
        'source_id' => 'integer',
        'meta' => 'array',
        'rewarded_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
