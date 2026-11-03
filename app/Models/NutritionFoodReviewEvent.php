<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NutritionFoodReviewEvent extends Model
{
    protected $fillable = [
        'nutrition_food_id',
        'admin_id',
        'action',
        'from_status',
        'to_status',
        'from_published',
        'to_published',
        'source_basis',
        'notes',
    ];

    protected $casts = [
        'from_published' => 'boolean',
        'to_published' => 'boolean',
    ];

    public function food(): BelongsTo
    {
        return $this->belongsTo(NutritionFood::class, 'nutrition_food_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
