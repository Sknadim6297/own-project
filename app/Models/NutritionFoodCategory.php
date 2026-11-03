<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NutritionFoodCategory extends Model
{
    protected $fillable = ['name', 'slug', 'display_order', 'is_active'];

    protected $casts = [
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function foods(): HasMany
    {
        return $this->hasMany(NutritionFood::class);
    }
}
