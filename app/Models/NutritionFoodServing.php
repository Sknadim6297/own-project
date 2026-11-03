<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NutritionFoodServing extends Model
{
    protected $fillable = [
        'nutrition_food_id',
        'label',
        'quantity',
        'unit',
        'grams_equivalent',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'grams_equivalent' => 'decimal:4',
    ];

    public function food(): BelongsTo
    {
        return $this->belongsTo(NutritionFood::class, 'nutrition_food_id');
    }
}
