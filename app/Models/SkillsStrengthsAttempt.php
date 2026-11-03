<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillsStrengthsAttempt extends Model
{
    protected $fillable = [
        'completion_key',
        'assessment_version',
        'scoring_version',
        'answers',
        'category_totals',
        'category_percentages',
        'strengths',
        'develop',
        'summary',
        'meaning',
        'completed_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'category_totals' => 'array',
        'category_percentages' => 'array',
        'strengths' => 'array',
        'develop' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
