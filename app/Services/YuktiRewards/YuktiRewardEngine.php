<?php

namespace App\Services\YuktiRewards;

use App\Models\User;
use App\Models\YuktiRewardConfig;
use App\Models\YuktiRewardLedgerEntry;
use App\Models\YuktiWallet;
use App\Models\YuktiWeeklyCounter;
use DateTimeInterface;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Internal YuktiPoints grant engine.
 *
 * No public HTTP endpoints. Callers in later phases must remain server-side.
 * Default: config('yuktione_rewards.engine_enabled') === false → no grants.
 */
class YuktiRewardEngine
{
    public const TYPE_WEEKLY_LOGIN = 'weekly_login';

    public const TYPE_TOOL_COMPLETION = 'tool_completion';

    public const TYPE_TOOL_REFERRAL = 'tool_referral';

    public const TYPE_EXPERT_REFERRAL = 'expert_referral';

    public const TYPE_SOCIAL_SHARE = 'social_share';

    public const BUCKET_AVAILABLE = 'available';

    public const BUCKET_PENDING = 'pending';

    /** @var array<string, array{config_key:string, metric:string, default_bucket:string}> */
    private const REWARD_TYPES = [
        self::TYPE_WEEKLY_LOGIN => [
            'config_key' => 'weekly_login',
            'metric' => 'login',
            'default_bucket' => self::BUCKET_AVAILABLE,
        ],
        self::TYPE_TOOL_COMPLETION => [
            'config_key' => 'tool_completion',
            'metric' => 'tool_complete',
            'default_bucket' => self::BUCKET_AVAILABLE,
        ],
        self::TYPE_TOOL_REFERRAL => [
            'config_key' => 'tool_referral',
            'metric' => 'tool_referral',
            'default_bucket' => self::BUCKET_AVAILABLE,
        ],
        self::TYPE_EXPERT_REFERRAL => [
            'config_key' => 'expert_referral',
            'metric' => 'expert_referral',
            'default_bucket' => self::BUCKET_AVAILABLE,
        ],
        self::TYPE_SOCIAL_SHARE => [
            'config_key' => 'social_share',
            'metric' => 'social_share',
            'default_bucket' => self::BUCKET_AVAILABLE,
        ],
    ];

    /**
     * Credit points to available or pending wallet bucket using server config amounts.
     *
     * @param  array<string, mixed>|null  $meta
     */
    public function grant(
        int $userId,
        string $rewardType,
        string $idempotencyKey,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?array $meta = null,
        ?DateTimeInterface $at = null,
        ?string $bucket = null,
    ): YuktiRewardResult {
        if (! config('yuktione_rewards.engine_enabled')) {
            return YuktiRewardResult::disabled($rewardType, $idempotencyKey);
        }

        if (! isset(self::REWARD_TYPES[$rewardType])) {
            return new YuktiRewardResult(
                status: YuktiRewardResult::STATUS_INVALID_TYPE,
                granted: false,
                rewardType: $rewardType,
                idempotencyKey: $idempotencyKey,
                message: "Unsupported reward type [{$rewardType}].",
            );
        }

        $idempotencyKey = trim($idempotencyKey);
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 160) {
            return new YuktiRewardResult(
                status: YuktiRewardResult::STATUS_INVALID_TYPE,
                granted: false,
                rewardType: $rewardType,
                idempotencyKey: $idempotencyKey,
                message: 'Idempotency key is required and must be <= 160 characters.',
            );
        }

        if (! User::query()->whereKey($userId)->exists()) {
            return new YuktiRewardResult(
                status: YuktiRewardResult::STATUS_USER_NOT_FOUND,
                granted: false,
                rewardType: $rewardType,
                idempotencyKey: $idempotencyKey,
                message: "User [{$userId}] not found.",
            );
        }

        $typeMeta = self::REWARD_TYPES[$rewardType];
        $bucket = $bucket ?? $typeMeta['default_bucket'];
        if (! in_array($bucket, [self::BUCKET_AVAILABLE, self::BUCKET_PENDING], true)) {
            return new YuktiRewardResult(
                status: YuktiRewardResult::STATUS_INVALID_TYPE,
                granted: false,
                rewardType: $rewardType,
                idempotencyKey: $idempotencyKey,
                message: "Unsupported wallet bucket [{$bucket}].",
            );
        }

        $weekId = YuktiWeekId::for($at);

        try {
            return DB::transaction(function () use (
                $userId,
                $rewardType,
                $idempotencyKey,
                $sourceType,
                $sourceId,
                $meta,
                $weekId,
                $typeMeta,
                $bucket,
            ) {
                // Locking order: wallet → ledger idempotency → config → weekly counter.
                $wallet = $this->lockOrCreateWallet($userId);

                $existing = YuktiRewardLedgerEntry::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    return new YuktiRewardResult(
                        status: YuktiRewardResult::STATUS_DUPLICATE,
                        granted: false,
                        points: (int) $existing->points,
                        availablePoints: (int) $wallet->available_points,
                        pendingPoints: (int) $wallet->pending_points,
                        weekId: $weekId,
                        idempotencyKey: $idempotencyKey,
                        rewardType: $rewardType,
                        ledgerId: (int) $existing->id,
                        message: 'Reward already recorded for this idempotency key.',
                    );
                }

                $config = YuktiRewardConfig::query()
                    ->where('key', $typeMeta['config_key'])
                    ->lockForUpdate()
                    ->first();

                $configError = $this->validateConfig($config, $rewardType, $idempotencyKey, $weekId);
                if ($configError !== null) {
                    return $configError;
                }

                $points = (int) $config->points;
                $capCount = $config->weekly_cap_count !== null ? (int) $config->weekly_cap_count : null;
                $capPoints = $config->weekly_cap_points !== null ? (int) $config->weekly_cap_points : null;

                $available = (int) $wallet->available_points;
                $pending = (int) $wallet->pending_points;
                $max = (int) config('yuktione_rewards.points_column_max', 4294967295);

                if ($bucket === self::BUCKET_AVAILABLE) {
                    if ($available > ($max - $points)) {
                        return new YuktiRewardResult(
                            status: YuktiRewardResult::STATUS_OVERFLOW,
                            granted: false,
                            points: $points,
                            availablePoints: $available,
                            pendingPoints: $pending,
                            weekId: $weekId,
                            idempotencyKey: $idempotencyKey,
                            rewardType: $rewardType,
                            message: 'Available points would exceed storage limit.',
                        );
                    }
                } elseif ($pending > ($max - $points)) {
                    return new YuktiRewardResult(
                        status: YuktiRewardResult::STATUS_OVERFLOW,
                        granted: false,
                        points: $points,
                        availablePoints: $available,
                        pendingPoints: $pending,
                        weekId: $weekId,
                        idempotencyKey: $idempotencyKey,
                        rewardType: $rewardType,
                        message: 'Pending points would exceed storage limit.',
                    );
                }

                $counter = $this->lockOrCreateCounter($userId, $weekId, $typeMeta['metric']);

                if ($capCount !== null && (int) $counter->count >= $capCount) {
                    return new YuktiRewardResult(
                        status: YuktiRewardResult::STATUS_WEEKLY_CAP,
                        granted: false,
                        points: $points,
                        availablePoints: (int) $wallet->available_points,
                        pendingPoints: (int) $wallet->pending_points,
                        weekId: $weekId,
                        idempotencyKey: $idempotencyKey,
                        rewardType: $rewardType,
                        message: "Weekly count cap reached for [{$rewardType}].",
                    );
                }

                if ($capPoints !== null && ((int) $counter->points_awarded + $points) > $capPoints) {
                    return new YuktiRewardResult(
                        status: YuktiRewardResult::STATUS_WEEKLY_CAP,
                        granted: false,
                        points: $points,
                        availablePoints: (int) $wallet->available_points,
                        pendingPoints: (int) $wallet->pending_points,
                        weekId: $weekId,
                        idempotencyKey: $idempotencyKey,
                        rewardType: $rewardType,
                        message: "Weekly points cap reached for [{$rewardType}].",
                    );
                }

                if ($bucket === self::BUCKET_AVAILABLE) {
                    $available += $points;
                } else {
                    $pending += $points;
                }

                try {
                    $ledgerId = YuktiRewardLedgerEntry::query()->insertGetId([
                        'user_id' => $userId,
                        'direction' => 'credit',
                        'points' => $points,
                        'available_after' => $available,
                        'pending_after' => $pending,
                        'event_type' => $rewardType,
                        'source_type' => $sourceType,
                        'source_id' => $sourceId,
                        'week_id' => $weekId,
                        'status' => 'completed',
                        'idempotency_key' => $idempotencyKey,
                        'meta' => $meta === null ? null : json_encode($meta),
                        'rewarded_at' => now(),
                        'created_at' => now(),
                    ]);
                } catch (UniqueConstraintViolationException $e) {
                    return $this->duplicateAfterRace($userId, $rewardType, $idempotencyKey, $weekId, $e);
                } catch (QueryException $e) {
                    if ($this->isUniqueViolation($e)) {
                        return $this->duplicateAfterRace($userId, $rewardType, $idempotencyKey, $weekId, $e);
                    }
                    throw $e;
                }

                $wallet->available_points = $available;
                $wallet->pending_points = $pending;
                $wallet->save();

                $counter->count = (int) $counter->count + 1;
                $counter->points_awarded = (int) $counter->points_awarded + $points;
                $counter->save();

                return new YuktiRewardResult(
                    status: YuktiRewardResult::STATUS_GRANTED,
                    granted: true,
                    points: $points,
                    availablePoints: $available,
                    pendingPoints: $pending,
                    weekId: $weekId,
                    idempotencyKey: $idempotencyKey,
                    rewardType: $rewardType,
                    ledgerId: (int) $ledgerId,
                    message: 'Reward credited.',
                );
            }, 5);
        } catch (Throwable $e) {
            // Do not report success; rethrow unexpected failures after rollback.
            throw $e;
        }
    }

    /**
     * Internal compensating debit for a prior completed credit.
     * Business reversal workflows are NOT implemented — this is a safe primitive only.
     *
     * @param  array<string, mixed>|null  $meta
     */
    public function reverseCompletedCredit(
        int $userId,
        string $originalIdempotencyKey,
        ?array $meta = null,
    ): YuktiRewardResult {
        if (! config('yuktione_rewards.engine_enabled')) {
            return YuktiRewardResult::disabled(null, YuktiRewardIdempotency::reversalOf($originalIdempotencyKey));
        }

        $reversalKey = YuktiRewardIdempotency::reversalOf($originalIdempotencyKey);

        return DB::transaction(function () use ($userId, $originalIdempotencyKey, $reversalKey, $meta) {
            $wallet = $this->lockOrCreateWallet($userId);

            $existingReversal = YuktiRewardLedgerEntry::query()
                ->where('idempotency_key', $reversalKey)
                ->lockForUpdate()
                ->first();
            if ($existingReversal !== null) {
                return new YuktiRewardResult(
                    status: YuktiRewardResult::STATUS_DUPLICATE,
                    granted: false,
                    points: (int) $existingReversal->points,
                    availablePoints: (int) $wallet->available_points,
                    pendingPoints: (int) $wallet->pending_points,
                    idempotencyKey: $reversalKey,
                    ledgerId: (int) $existingReversal->id,
                    message: 'Reversal already recorded.',
                );
            }

            $original = YuktiRewardLedgerEntry::query()
                ->where('idempotency_key', $originalIdempotencyKey)
                ->where('user_id', $userId)
                ->where('direction', 'credit')
                ->where('status', 'completed')
                ->lockForUpdate()
                ->first();

            if ($original === null) {
                return new YuktiRewardResult(
                    status: YuktiRewardResult::STATUS_INVALID_TYPE,
                    granted: false,
                    idempotencyKey: $reversalKey,
                    message: 'Original completed credit not found for this user.',
                );
            }

            $points = (int) $original->points;
            $available = (int) $wallet->available_points;
            $pending = (int) $wallet->pending_points;

            // Prefer debiting the bucket that the original credit increased.
            // Heuristic: if available covers it, debit available; else pending.
            if ($available >= $points) {
                $available -= $points;
                $bucket = self::BUCKET_AVAILABLE;
            } elseif ($pending >= $points) {
                $pending -= $points;
                $bucket = self::BUCKET_PENDING;
            } else {
                return new YuktiRewardResult(
                    status: YuktiRewardResult::STATUS_OVERFLOW,
                    granted: false,
                    points: $points,
                    availablePoints: $available,
                    pendingPoints: $pending,
                    idempotencyKey: $reversalKey,
                    message: 'Insufficient wallet balance to reverse credit (negative balances forbidden).',
                );
            }

            $ledgerId = YuktiRewardLedgerEntry::query()->insertGetId([
                'user_id' => $userId,
                'direction' => 'debit',
                'points' => $points,
                'available_after' => $available,
                'pending_after' => $pending,
                'event_type' => 'reversal',
                'source_type' => 'ledger',
                'source_id' => $original->id,
                'week_id' => $original->week_id,
                'status' => 'reversed',
                'idempotency_key' => $reversalKey,
                'meta' => json_encode(array_merge($meta ?? [], [
                    'original_idempotency_key' => $originalIdempotencyKey,
                    'bucket' => $bucket,
                    'unsupported_business_workflow' => true,
                ])),
                'rewarded_at' => now(),
                'created_at' => now(),
            ]);

            $wallet->available_points = $available;
            $wallet->pending_points = $pending;
            $wallet->save();

            return new YuktiRewardResult(
                status: YuktiRewardResult::STATUS_GRANTED,
                granted: true,
                points: $points,
                availablePoints: $available,
                pendingPoints: $pending,
                weekId: $original->week_id,
                idempotencyKey: $reversalKey,
                rewardType: 'reversal',
                ledgerId: (int) $ledgerId,
                message: 'Compensating debit recorded (internal primitive only).',
            );
        }, 5);
    }

    /**
     * @return list<string>
     */
    public function supportedRewardTypes(): array
    {
        return array_keys(self::REWARD_TYPES);
    }

    private function validateConfig(
        ?YuktiRewardConfig $config,
        string $rewardType,
        string $idempotencyKey,
        string $weekId,
    ): ?YuktiRewardResult {
        if ($config === null) {
            return new YuktiRewardResult(
                status: YuktiRewardResult::STATUS_INVALID_CONFIG,
                granted: false,
                weekId: $weekId,
                idempotencyKey: $idempotencyKey,
                rewardType: $rewardType,
                message: "Missing reward config for [{$rewardType}].",
            );
        }

        if (! $config->is_active || $config->approval_status !== 'approved') {
            return new YuktiRewardResult(
                status: YuktiRewardResult::STATUS_INVALID_CONFIG,
                granted: false,
                weekId: $weekId,
                idempotencyKey: $idempotencyKey,
                rewardType: $rewardType,
                message: "Reward config [{$config->key}] is inactive or pending approval.",
            );
        }

        if ($config->points === null || (int) $config->points <= 0) {
            return new YuktiRewardResult(
                status: YuktiRewardResult::STATUS_INVALID_CONFIG,
                granted: false,
                weekId: $weekId,
                idempotencyKey: $idempotencyKey,
                rewardType: $rewardType,
                message: "Reward config [{$config->key}] has invalid points.",
            );
        }

        return null;
    }

    private function lockOrCreateWallet(int $userId): YuktiWallet
    {
        $wallet = YuktiWallet::query()->where('user_id', $userId)->lockForUpdate()->first();
        if ($wallet !== null) {
            return $wallet;
        }

        try {
            YuktiWallet::query()->create([
                'user_id' => $userId,
                'available_points' => 0,
                'pending_points' => 0,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Concurrent create won the race.
        } catch (QueryException $e) {
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }
        }

        $wallet = YuktiWallet::query()->where('user_id', $userId)->lockForUpdate()->first();
        if ($wallet === null) {
            throw new InvalidArgumentException("Unable to initialize wallet for user [{$userId}].");
        }

        return $wallet;
    }

    private function lockOrCreateCounter(int $userId, string $weekId, string $metric): YuktiWeeklyCounter
    {
        $counter = YuktiWeeklyCounter::query()
            ->where('user_id', $userId)
            ->where('week_id', $weekId)
            ->where('metric', $metric)
            ->lockForUpdate()
            ->first();

        if ($counter !== null) {
            return $counter;
        }

        try {
            YuktiWeeklyCounter::query()->create([
                'user_id' => $userId,
                'week_id' => $weekId,
                'metric' => $metric,
                'count' => 0,
                'points_awarded' => 0,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Concurrent create won the race.
        } catch (QueryException $e) {
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }
        }

        $counter = YuktiWeeklyCounter::query()
            ->where('user_id', $userId)
            ->where('week_id', $weekId)
            ->where('metric', $metric)
            ->lockForUpdate()
            ->first();

        if ($counter === null) {
            throw new InvalidArgumentException(
                "Unable to initialize weekly counter [{$metric}] for user [{$userId}] week [{$weekId}]."
            );
        }

        return $counter;
    }

    private function duplicateAfterRace(
        int $userId,
        string $rewardType,
        string $idempotencyKey,
        string $weekId,
        Throwable $e,
    ): YuktiRewardResult {
        $existing = YuktiRewardLedgerEntry::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        $wallet = YuktiWallet::query()->where('user_id', $userId)->first();

        return new YuktiRewardResult(
            status: YuktiRewardResult::STATUS_DUPLICATE,
            granted: false,
            points: $existing ? (int) $existing->points : null,
            availablePoints: $wallet ? (int) $wallet->available_points : null,
            pendingPoints: $wallet ? (int) $wallet->pending_points : null,
            weekId: $weekId,
            idempotencyKey: $idempotencyKey,
            rewardType: $rewardType,
            ledgerId: $existing ? (int) $existing->id : null,
            message: 'Reward already recorded for this idempotency key (concurrent).',
        );
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'unique')
            || str_contains($message, 'duplicate')
            || (string) $e->getCode() === '23000';
    }
}
