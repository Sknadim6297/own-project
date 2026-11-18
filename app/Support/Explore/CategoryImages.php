<?php

namespace App\Support\Explore;

/**
 * Photos for the "Explore by Need" categories (ExploreCatalog::CATEGORIES keys).
 *
 * Must stay in step with resources/views/frontend/home/partials/categories.blade.php,
 * which renders the same files on the website home page.
 */
final class CategoryImages
{
    public const DIRECTORY = 'frontend/assets/img/home-cats/';

    private const FILES = [
        'career-business' => 'cat-lotus.jpg',
        'mental-health' => 'cat-stones.jpg',
        'nutrition-fitness' => 'cat-fruit.jpg',
        'spirituality' => 'cat-meditate.jpg',
        'relationships' => 'cat-heart.jpg',
        'money-finance' => 'cat-growth.jpg',
        'lifestyle' => 'cat-lifestyle.jpg',
        'astrology-tarot' => 'cat-moon.jpg',
        'health-wellness' => 'cat-health.jpg',
        'parenting' => 'cat-parent.jpg',
        'creativity' => 'cat-create.jpg',
    ];

    public static function url(string $categoryKey): ?string
    {
        $file = self::FILES[$categoryKey] ?? null;
        if ($file === null || ! is_file(public_path(self::DIRECTORY.$file))) {
            return null;
        }

        return asset(self::DIRECTORY.$file);
    }
}
