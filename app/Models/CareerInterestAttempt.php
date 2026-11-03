<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerInterestAttempt extends Model
{
    protected $fillable = [
        'completion_key',
        'assessment_version',
        'scoring_version',
        'catalogue_version',
        'age_group',
        'education',
        'situation',
        'geography',
        'answers',
        'category_totals',
        'category_percentages',
        'top_categories',
        'interpretation',
        'recommendations',
        'completed_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'category_totals' => 'array',
        'category_percentages' => 'array',
        'top_categories' => 'array',
        'recommendations' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
