<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TarotCard extends Model
{
    public const ARCANA_MAJOR = 'major';

    public const ARCANA_MINOR = 'minor';

    public const SUITS = ['wands', 'cups', 'swords', 'pentacles'];

    protected $fillable = [
        'card_id',
        'card_name',
        'arcana',
        'suit',
        'number',
        'upright_meaning',
        'reversed_meaning',
        'upright_keywords',
        'reversed_keywords',
        'image_path',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function readingCards(): HasMany
    {
        return $this->hasMany(TarotReadingCard::class);
    }

    public function isMajor(): bool
    {
        return $this->arcana === self::ARCANA_MAJOR;
    }

    public function isMinor(): bool
    {
        return $this->arcana === self::ARCANA_MINOR;
    }
}
