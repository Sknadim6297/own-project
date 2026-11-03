<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NutritionFood extends Model
{
    protected $table = 'nutrition_foods';

    protected $fillable = [
        'nutrition_food_category_id',
        'name',
        'normalized_name',
        'brand',
        'variant',
        'preparation_state',
        'reference_basis',
        'calories_kcal',
        'protein_g',
        'carbs_g',
        'fat_g',
        'fibre_g',
        'sugar_g',
        'sodium_mg',
        'saturated_fat_g',
        'source_name',
        'source_url',
        'verification_status',
        'nutrient_completeness',
        'source_verified_at',
        'is_published',
        'import_batch',
        'import_row_ref',
        'import_identity',
        'nutrition_import_batch_id',
    ];

    protected $casts = [
        'calories_kcal' => 'decimal:4',
        'protein_g' => 'decimal:4',
        'carbs_g' => 'decimal:4',
        'fat_g' => 'decimal:4',
        'fibre_g' => 'decimal:4',
        'sugar_g' => 'decimal:4',
        'sodium_mg' => 'decimal:4',
        'saturated_fat_g' => 'decimal:4',
        'is_published' => 'boolean',
        'source_verified_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(NutritionFoodCategory::class, 'nutrition_food_category_id');
    }

    public function servings(): HasMany
    {
        return $this->hasMany(NutritionFoodServing::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(NutritionImportBatch::class, 'nutrition_import_batch_id');
    }

    public function reviewEvents(): HasMany
    {
        return $this->hasMany(NutritionFoodReviewEvent::class);
    }

    public function duplicateReviews(): HasMany
    {
        return $this->hasMany(NutritionDuplicateReview::class);
    }

    public function scopeCatalogueVisible(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where('verification_status', '!=', 'rejected');
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }
}
