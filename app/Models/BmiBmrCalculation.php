<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BmiBmrCalculation extends Model
{
    protected $fillable = [
        'completion_key',
        'formula_version',
        'age',
        'height_cm',
        'weight_kg',
        'sex',
        'activity',
        'activity_factor',
        'excluded_population',
        'bmi_exact',
        'bmi_display',
        'bmi_category_key',
        'bmi_category_label',
        'bmr_kcal',
        'tdee_kcal',
        'payload',
        'calculated_at',
    ];

    protected $casts = [
        'height_cm' => 'float',
        'weight_kg' => 'float',
        'activity_factor' => 'float',
        'excluded_population' => 'boolean',
        'bmi_exact' => 'float',
        'bmi_display' => 'float',
        'bmr_kcal' => 'integer',
        'tdee_kcal' => 'integer',
        'payload' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
