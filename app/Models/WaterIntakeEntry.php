<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaterIntakeEntry extends Model
{
    protected $fillable = [
        'amount_ml',
        'consumed_at',
        'idempotency_key',
    ];

    protected $casts = [
        'amount_ml' => 'integer',
        'consumed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
