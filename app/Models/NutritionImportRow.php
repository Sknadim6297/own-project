<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NutritionImportRow extends Model
{
    protected $fillable = [
        'nutrition_import_batch_id',
        'worksheet_row',
        'import_identity',
        'status',
        'name',
        'brand',
        'variant',
        'payload',
        'warnings',
        'message',
        'nutrition_food_id',
        'duplicate_of_food_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'warnings' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(NutritionImportBatch::class, 'nutrition_import_batch_id');
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(NutritionFood::class, 'nutrition_food_id');
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(NutritionFood::class, 'duplicate_of_food_id');
    }
}
