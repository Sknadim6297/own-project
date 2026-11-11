<?php

namespace App\Services\YuktiRewards;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Monday-based ISO week identifiers in the configured rewards timezone.
 */
final class YuktiWeekId
{
    public static function timezone(): DateTimeZone
    {
        $name = (string) config('yuktione_rewards.week_timezone', 'Asia/Kolkata');

        try {
            return new DateTimeZone($name);
        } catch (\Exception $e) {
            throw new InvalidArgumentException(
                "Invalid yuktione_rewards.week_timezone [{$name}].",
                previous: $e
            );
        }
    }

    public static function now(?DateTimeInterface $at = null): CarbonImmutable
    {
        $tz = self::timezone();

        if ($at === null) {
            return CarbonImmutable::now($tz);
        }

        return CarbonImmutable::instance($at)->setTimezone($tz);
    }

    /**
     * Format: 2026-W46 (ISO-8601 year-week, Monday start).
     */
    public static function for(?DateTimeInterface $at = null): string
    {
        $format = (string) config('yuktione_rewards.week_id_format', 'o-\WW');

        return self::now($at)->format($format);
    }

    public static function startOfWeek(?DateTimeInterface $at = null): CarbonImmutable
    {
        return self::now($at)->startOfWeek(CarbonInterface::MONDAY);
    }
}
