<?php

namespace App\Services\YuktiRewards;

use DateTimeInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Never lets reward failures break login, tool completion, or booking updates.
 */
class YuktiRewardSafeDispatcher
{
    public function __construct(
        private readonly YuktiRewardEngine $engine,
    ) {
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function tryGrant(
        int $userId,
        string $rewardType,
        string $idempotencyKey,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?array $meta = null,
        ?DateTimeInterface $at = null,
    ): ?YuktiRewardResult {
        try {
            return $this->engine->grant(
                userId: $userId,
                rewardType: $rewardType,
                idempotencyKey: $idempotencyKey,
                sourceType: $sourceType,
                sourceId: $sourceId,
                meta: $meta,
                at: $at,
            );
        } catch (Throwable $e) {
            Log::error('YuktiPoints grant failed without affecting primary action.', [
                'user_id' => $userId,
                'reward_type' => $rewardType,
                'idempotency_key' => $idempotencyKey,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
