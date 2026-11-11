<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TarotReading extends Model
{
    public const SPREAD_ONE = 'one';

    public const SPREAD_THREE = 'three';

    protected $fillable = [
        'user_id',
        'completion_key',
        'category',
        'question',
        'spread_type',
        'reversed_enabled',
        'overall_reflection',
    ];

    protected $casts = [
        'reversed_enabled' => 'boolean',
    ];

    protected $attributes = [
        'reversed_enabled' => false,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(TarotReadingCard::class)->orderBy('position_order');
    }
}
