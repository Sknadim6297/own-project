<?php

namespace App\Support\Tarot;

/**
 * Presentation helpers for Tarot Reflection UI only.
 * Does not alter deck meanings, draw security, or catalog content.
 */
final class TarotUi
{
    /**
     * @return array{icon: string, emoji: string, blurb: string}
     */
    public static function categoryPresentation(string $key): array
    {
        return match ($key) {
            'love-relationships' => [
                'icon' => 'heart',
                'emoji' => '',
                'blurb' => 'Connection, communication, and emotional clarity.',
            ],
            'career-work' => [
                'icon' => 'briefcase',
                'emoji' => '',
                'blurb' => 'Direction, confidence, and practical work choices.',
            ],
            'money-finances' => [
                'icon' => 'wallet',
                'emoji' => '',
                'blurb' => 'Security, habits, and thoughtful money decisions.',
            ],
            'personal-growth' => [
                'icon' => 'sprout',
                'emoji' => '',
                'blurb' => 'Self-awareness, habits, and healthier boundaries.',
            ],
            'life-direction' => [
                'icon' => 'compass',
                'emoji' => '',
                'blurb' => 'Values, priorities, and steady next steps.',
            ],
            'general-guidance' => [
                'icon' => 'sparkles',
                'emoji' => '',
                'blurb' => 'A broader lens on what may be useful to notice.',
            ],
            default => [
                'icon' => 'sparkles',
                'emoji' => '',
                'blurb' => 'Reflective guidance for your question.',
            ],
        };
    }

    public static function categoryIcon(string $key): string
    {
        return self::categoryPresentation($key)['icon'];
    }

    /** @deprecated Use categoryIcon(); retained for API shape compatibility. */
    public static function categoryEmoji(string $key): string
    {
        return self::categoryPresentation($key)['emoji'];
    }

    public static function suitIcon(?string $suit, string $arcana = 'minor'): string
    {
        if ($arcana === 'major') {
            return 'sparkles';
        }

        return match ($suit) {
            'wands' => 'asterisk',
            'cups' => 'heart',
            'swords' => 'swords',
            'pentacles' => 'circle-dot',
            default => 'sparkles',
        };
    }

    /** @deprecated Use suitIcon(); retained for API glyph consumers. */
    public static function suitGlyph(?string $suit, string $arcana = 'minor'): string
    {
        return '';
    }

    public static function suitLabel(?string $suit, string $arcana = 'minor'): string
    {
        if ($arcana === 'major') {
            return 'Major Arcana';
        }

        return match ($suit) {
            'wands' => 'Wands',
            'cups' => 'Cups',
            'swords' => 'Swords',
            'pentacles' => 'Pentacles',
            default => 'Minor Arcana',
        };
    }

    public static function imageUrl(?string $imagePath): ?string
    {
        if ($imagePath === null || trim($imagePath) === '') {
            return null;
        }

        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return $imagePath;
        }

        $publicRelative = ltrim($imagePath, '/');
        if (is_file(public_path($publicRelative))) {
            return asset($publicRelative);
        }

        if (is_file(public_path('storage/'.$publicRelative))) {
            return asset('storage/'.$publicRelative);
        }

        return asset($publicRelative);
    }
}
