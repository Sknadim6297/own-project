<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NutritionUserGoal extends Model
{
    protected $fillable = [
        'track_only',
        'calories_kcal',
        'protein_g',
        'carbs_g',
        'fat_g',
    ];

    protected $casts = [
        'track_only' => 'boolean',
        'calories_kcal' => 'integer',
        'protein_g' => 'decimal:2',
        'carbs_g' => 'decimal:2',
        'fat_g' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
