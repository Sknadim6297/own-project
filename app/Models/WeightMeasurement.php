<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeightMeasurement extends Model
{
    protected $fillable = [
        'weight_kg',
        'recorded_at',
        'note',
        'display_unit',
        'idempotency_key',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:3',
        'recorded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
