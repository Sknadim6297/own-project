<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NutritionDuplicateReview extends Model
{
    protected $fillable = [
        'nutrition_food_id',
        'candidate_food_id',
        'match_reason',
        'score',
        'decision',
        'decided_by_admin_id',
        'notes',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function food(): BelongsTo
    {
        return $this->belongsTo(NutritionFood::class, 'nutrition_food_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(NutritionFood::class, 'candidate_food_id');
    }
}
