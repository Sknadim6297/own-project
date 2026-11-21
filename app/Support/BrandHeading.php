<?php

namespace App\Support;

/**
 * Home-style two-tone headings: charcoal base + plum accent on the last words.
 * Matches index patterns like "Explore by Need" / "Try Our Tools".
 */
final class BrandHeading
{
    /**
     * Wrap the last $accentWords of $text in .yk-heading-accent (escaped HTML).
     * Keeps at least one leading word in the base (dark) color when possible.
     */
    public static function twoTone(string $text, int $accentWords = 2): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($text === '') {
            return '';
        }

        $words = preg_split('/\s+/u', $text) ?: [];
        $count = count($words);

        if ($count === 1) {
            return '<span class="yk-heading-accent">'.e($words[0]).'</span>';
        }

        $accentWords = max(1, min($accentWords, $count - 1));
        $lead = array_slice($words, 0, $count - $accentWords);
        $tail = array_slice($words, $count - $accentWords);

        return e(implode(' ', $lead))
            .' <span class="yk-heading-accent">'.e(implode(' ', $tail)).'</span>';
    }
}
