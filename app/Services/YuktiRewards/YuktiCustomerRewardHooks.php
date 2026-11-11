<?php

namespace App\Services\YuktiRewards;

/**
 * Thin adapters from domain events → reward engine / referral activation
 * (engine may be disabled). Failures never block the primary customer action.
 */
class YuktiCustomerRewardHooks
{
    public function __construct(
        private readonly YuktiRewardSafeDispatcher $dispatcher,
        private readonly ReferralActivationService $referralActivation,
    ) {
    }

    public function onCustomerLogin(int $userId): ?YuktiRewardResult
    {
        $weekId = YuktiWeekId::for();

        return $this->dispatcher->tryGrant(
            userId: $userId,
            rewardType: YuktiRewardEngine::TYPE_WEEKLY_LOGIN,
            idempotencyKey: YuktiRewardIdempotency::weeklyLogin($userId, $weekId),
            sourceType: 'auth_login',
            sourceId: null,
            meta: ['week_id' => $weekId, 'guard' => 'user'],
        );
    }

    public function onEligibleToolCompleted(
        int $userId,
        string $toolSlug,
        string $completionKey,
        ?int $sourceId = null,
    ): ?YuktiRewardResult {
        $eligible = config('yuktione_rewards.eligible_tool_completion_slugs', []);
        if (! is_array($eligible) || ! in_array($toolSlug, $eligible, true)) {
            return null;
        }

        $weekId = YuktiWeekId::for();

        $completionReward = $this->dispatcher->tryGrant(
            userId: $userId,
            rewardType: YuktiRewardEngine::TYPE_TOOL_COMPLETION,
            idempotencyKey: YuktiRewardIdempotency::toolCompletion($userId, $weekId),
            sourceType: 'tool_completion',
            sourceId: $sourceId,
            meta: [
                'week_id' => $weekId,
                'tool_slug' => $toolSlug,
                'completion_key' => $completionKey,
            ],
        );

        try {
            $this->referralActivation->activateToolReferral(
                refereeUserId: $userId,
                toolSlug: $toolSlug,
                completionKey: $completionKey,
                completionRecordId: $sourceId,
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Tool referral activation failed without affecting completion.', [
                'user_id' => $userId,
                'tool_slug' => $toolSlug,
                'exception' => $e->getMessage(),
            ]);
        }

        return $completionReward;
    }

    /**
     * Paid + fully completed expert booking → expert referral activation.
     *
     * @return array{status:string, attribution:?\App\Models\ReferralAttribution, reward:?YuktiRewardResult, message:string}|null
     */
    public function onExpertBookingFullyCompleted(int $bookingId): ?array
    {
        try {
            return $this->referralActivation->activateExpertReferral($bookingId);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Expert referral activation failed without affecting booking.', [
                'booking_id' => $bookingId,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
