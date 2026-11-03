<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeightGoal extends Model
{
    protected $fillable = [
        'direction',
        'starting_weight_kg',
        'target_weight_kg',
        'target_date',
        'display_unit',
        'status',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'starting_weight_kg' => 'decimal:3',
        'target_weight_kg' => 'decimal:3',
        'target_date' => 'date',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
