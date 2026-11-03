<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NutritionMealEntry extends Model
{
    protected $fillable = [
        'nutrition_food_id',
        'meal_type',
        'quantity',
        'unit',
        'portion_grams',
        'consumed_at',
        'food_name_snapshot',
        'brand_snapshot',
        'calories_kcal_snapshot',
        'protein_g_snapshot',
        'carbs_g_snapshot',
        'fat_g_snapshot',
        'fibre_g_snapshot',
        'incomplete_nutrients',
        'idempotency_key',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'portion_grams' => 'decimal:4',
        'consumed_at' => 'datetime',
        'calories_kcal_snapshot' => 'decimal:4',
        'protein_g_snapshot' => 'decimal:4',
        'carbs_g_snapshot' => 'decimal:4',
        'fat_g_snapshot' => 'decimal:4',
        'fibre_g_snapshot' => 'decimal:4',
        'incomplete_nutrients' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(NutritionFood::class, 'nutrition_food_id');
    }
}
