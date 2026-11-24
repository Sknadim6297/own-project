<?php

namespace App\Helpers;

/**
 * Presentation-only name formatting for display surfaces.
 * Does not mutate stored database values.
 */
class DisplayName
{
    /**
     * Normalize accidental all-lowercase name parts for display.
     * Preserves mixed-case, ALL-CAPS, initials, hyphens, and apostrophes.
     */
    public static function present(?string $name): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', (string) $name) ?? '');

        if ($normalized === '') {
            return '';
        }

        $tokens = preg_split('/(\s+)/u', $normalized, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($tokens === false) {
            return $normalized;
        }

        $formatted = '';
        foreach ($tokens as $token) {
            if ($token === '' || preg_match('/^\s+$/u', $token)) {
                $formatted .= $token;
                continue;
            }

            $formatted .= self::formatWord($token);
        }

        return trim($formatted);
    }

    private static function formatWord(string $word): string
    {
        if ($word === '') {
            return $word;
        }

        if (str_contains($word, '-')) {
            return implode('-', array_map(
                static fn (string $part): string => self::formatWord($part),
                explode('-', $word)
            ));
        }

        if (str_contains($word, "'") || str_contains($word, '’')) {
            $parts = preg_split("/['’]/u", $word);
            if ($parts === false) {
                return $word;
            }

            $delimiters = [];
            preg_match_all("/['’]/u", $word, $delimiters);
            $delimiterList = $delimiters[0] ?? [];

            $built = '';
            foreach ($parts as $index => $part) {
                $built .= self::formatSimplePart($part);
                if (isset($delimiterList[$index])) {
                    $built .= $delimiterList[$index];
                }
            }

            return $built;
        }

        return self::formatSimplePart($word);
    }

    private static function formatSimplePart(string $part): string
    {
        if ($part === '') {
            return $part;
        }

        // Initials like "s." or "s.k." → "S." / "S.K."
        if (preg_match('/^(?:\p{L}\.)+\p{L}?$/u', $part) && self::isAllLowercase(str_replace('.', '', $part))) {
            return mb_strtoupper($part, 'UTF-8');
        }

        // Leave intentional mixed-case or ALL-CAPS unchanged.
        if (! self::isAllLowercase($part)) {
            return $part;
        }

        return self::mbUcfirst($part);
    }

    private static function isAllLowercase(string $value): bool
    {
        $letters = preg_replace('/[^\p{L}]/u', '', $value) ?? '';

        if ($letters === '') {
            return false;
        }

        return mb_strtolower($letters, 'UTF-8') === $letters
            && mb_strtoupper($letters, 'UTF-8') !== $letters;
    }

    private static function mbUcfirst(string $value): string
    {
        $first = mb_substr($value, 0, 1, 'UTF-8');
        $rest = mb_substr($value, 1, null, 'UTF-8');

        return mb_strtoupper($first, 'UTF-8').$rest;
    }
}
