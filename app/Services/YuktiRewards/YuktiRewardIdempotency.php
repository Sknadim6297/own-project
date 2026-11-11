<?php

namespace App\Services\YuktiRewards;

/**
 * Server-side idempotency key builders. Callers must not accept raw keys from clients.
 */
final class YuktiRewardIdempotency
{
    public static function weeklyLogin(int $userId, string $weekId): string
    {
        return "login:{$userId}:{$weekId}";
    }

    public static function toolCompletion(int $userId, string $weekId): string
    {
        return "tool_complete:{$userId}:{$weekId}";
    }

    public static function toolReferral(int $attributionId): string
    {
        return "tool_ref:{$attributionId}";
    }

    public static function expertReferral(int $bookingId): string
    {
        return "expert_ref:{$bookingId}";
    }

    public static function socialShare(int $userId, string $weekId): string
    {
        return "social:{$userId}:{$weekId}";
    }

    public static function reversalOf(string $originalIdempotencyKey): string
    {
        return 'rev:'.$originalIdempotencyKey;
    }
}
