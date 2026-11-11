<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarotReadingCard extends Model
{
    protected $fillable = [
        'tarot_reading_id',
        'tarot_card_id',
        'position',
        'position_order',
        'is_reversed',
        'insight_text',
        'drawn_at',
    ];

    protected $casts = [
        'is_reversed' => 'boolean',
        'position_order' => 'integer',
        'drawn_at' => 'datetime',
    ];

    protected $attributes = [
        'is_reversed' => false,
    ];

    public function reading(): BelongsTo
    {
        return $this->belongsTo(TarotReading::class, 'tarot_reading_id');
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(TarotCard::class, 'tarot_card_id');
    }
}
