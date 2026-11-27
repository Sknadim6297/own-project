<?php

namespace App\Support\Homepage;

/**
 * Priority 4 — reversible homepage topic layout modes.
 *
 * Default remains the Task 55 single-row rail until the client confirms
 * which interpretation of "divide topics in two lines" they want.
 */
class TopicLayout
{
    public const SINGLE_ROW = 'single-row';

    public const LABEL_WRAP = 'label-wrap';

    public const TWO_ROW = 'two-row';

    /**
     * @var list<string>
     */
    public const ALLOWED = [
        self::SINGLE_ROW,
        self::LABEL_WRAP,
        self::TWO_ROW,
    ];

    public static function resolve(?string $requested = null): string
    {
        $candidate = is_string($requested) ? trim($requested) : '';

        if ($candidate !== '' && in_array($candidate, self::ALLOWED, true)) {
            return $candidate;
        }

        $configured = (string) config('yuktione.topic_layout', self::SINGLE_ROW);

        return in_array($configured, self::ALLOWED, true)
            ? $configured
            : self::SINGLE_ROW;
    }

    /**
     * Modifier classes for the Solutions rail (empty when default single-row).
     */
    public static function solutionsClasses(string $layout): string
    {
        return match ($layout) {
            self::TWO_ROW => 'yk-home-cats--two-row',
            self::LABEL_WRAP => 'yk-home-cats--label-wrap',
            default => '',
        };
    }

    /**
     * Modifier classes for the Tools strip (empty when default single-row).
     */
    public static function toolsClasses(string $layout): string
    {
        return match ($layout) {
            self::TWO_ROW => 'yk-tools-strip--two-row',
            self::LABEL_WRAP => 'yk-tools-strip--label-wrap',
            default => '',
        };
    }
}
