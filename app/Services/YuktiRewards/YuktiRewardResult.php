<?php

namespace App\Services\YuktiRewards;

final class YuktiRewardResult
{
    public const STATUS_GRANTED = 'granted';

    public const STATUS_DISABLED = 'disabled';

    public const STATUS_DUPLICATE = 'duplicate';

    public const STATUS_WEEKLY_CAP = 'weekly_cap_reached';

    public const STATUS_INVALID_TYPE = 'invalid_type';

    public const STATUS_INVALID_CONFIG = 'invalid_config';

    public const STATUS_OVERFLOW = 'overflow';

    public const STATUS_USER_NOT_FOUND = 'user_not_found';

    public function __construct(
        public readonly string $status,
        public readonly bool $granted,
        public readonly ?int $points = null,
        public readonly ?int $availablePoints = null,
        public readonly ?int $pendingPoints = null,
        public readonly ?string $weekId = null,
        public readonly ?string $idempotencyKey = null,
        public readonly ?string $rewardType = null,
        public readonly ?int $ledgerId = null,
        public readonly ?string $message = null,
    ) {
    }

    public static function disabled(?string $rewardType = null, ?string $idempotencyKey = null): self
    {
        return new self(
            status: self::STATUS_DISABLED,
            granted: false,
            rewardType: $rewardType,
            idempotencyKey: $idempotencyKey,
            message: 'YuktiPoints reward engine is disabled.',
        );
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'granted' => $this->granted,
            'points' => $this->points,
            'available_points' => $this->availablePoints,
            'pending_points' => $this->pendingPoints,
            'week_id' => $this->weekId,
            'idempotency_key' => $this->idempotencyKey,
            'reward_type' => $this->rewardType,
            'ledger_id' => $this->ledgerId,
            'message' => $this->message,
        ];
    }
}
