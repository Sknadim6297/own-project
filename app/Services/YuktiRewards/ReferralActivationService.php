<?php

namespace App\Services\YuktiRewards;

use App\Models\Booking;
use App\Models\ReferralAttribution;
use App\Models\Tool;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Phase 3F — trustworthy tool/expert referral activation.
 *
 * Lifecycle: joined → activated → rewarded.
 * Rewarded only after the engine confirms a successful grant (or duplicate ledger hit).
 * When the engine is disabled, evidence is preserved at `activated` without points.
 *
 * Weekly accounting week = attribution.activated_at (not later settlement wall-clock).
 */
class ReferralActivationService
{
    public function __construct(
        private readonly YuktiRewardSafeDispatcher $dispatcher,
    ) {
    }

    /**
     * Activate a tool referral after a server-verified eligible tool completion by the referee.
     *
     * @return array{status:string, attribution:?ReferralAttribution, reward:?YuktiRewardResult, message:string}
     */
    public function activateToolReferral(
        int $refereeUserId,
        string $toolSlug,
        string $completionKey,
        ?int $completionRecordId = null,
        ?DateTimeInterface $completedAt = null,
    ): array {
        $eligible = config('yuktione_rewards.eligible_tool_completion_slugs', []);
        if (! is_array($eligible) || ! in_array($toolSlug, $eligible, true)) {
            return $this->outcome('ineligible_tool', null, null, 'Tool slug is not approved for referral activation.');
        }

        $tool = Tool::query()->where('slug', $toolSlug)->first();
        if ($tool === null) {
            return $this->outcome('tool_not_found', null, null, 'Tool not found.');
        }

        // Use app-clock moments so comparisons with Eloquent signed_up_at stay consistent.
        $completedAt = $completedAt
            ? CarbonImmutable::instance($completedAt)
            : CarbonImmutable::instance(now());

        try {
            $attribution = $this->markToolActivated(
                $refereeUserId,
                $tool,
                $toolSlug,
                $completionKey,
                $completionRecordId,
                $completedAt,
            );
        } catch (ReferralActivationRejectedException $e) {
            return $this->outcome($e->reason, $e->attribution, null, $e->getMessage());
        }

        return $this->settle($attribution, YuktiRewardEngine::TYPE_TOOL_REFERRAL);
    }

    /**
     * Activate an expert referral after a paid booking with all sessions completed.
     *
     * @return array{status:string, attribution:?ReferralAttribution, reward:?YuktiRewardResult, message:string}
     */
    public function activateExpertReferral(int $bookingId): array
    {
        $booking = Booking::query()->with('timedates')->find($bookingId);
        if ($booking === null) {
            return $this->outcome('booking_not_found', null, null, 'Booking not found.');
        }

        if ((string) $booking->payment_status !== 'paid') {
            return $this->outcome('payment_not_paid', null, null, 'Booking payment is not verified paid.');
        }

        $timedates = $booking->timedates;
        if ($timedates->isEmpty()) {
            return $this->outcome('no_sessions', null, null, 'Booking has no session rows.');
        }

        if ($timedates->contains(fn ($td) => (string) $td->status !== 'completed')) {
            return $this->outcome('sessions_incomplete', null, null, 'Not all booking sessions are completed.');
        }

        $refereeId = (int) $booking->user_id;
        $professionalId = (int) $booking->professional_id;
        if ($refereeId <= 0 || $professionalId <= 0) {
            return $this->outcome('booking_incomplete', null, null, 'Booking missing customer or expert.');
        }

        try {
            $attribution = $this->markExpertActivated($booking, $refereeId, $professionalId);
        } catch (ReferralActivationRejectedException $e) {
            return $this->outcome($e->reason, $e->attribution, null, $e->getMessage());
        }

        return $this->settle($attribution, YuktiRewardEngine::TYPE_EXPERT_REFERRAL);
    }

    /**
     * Resume settlement for an already-activated attribution (e.g. after enabling the engine).
     *
     * @return array{status:string, attribution:?ReferralAttribution, reward:?YuktiRewardResult, message:string}
     */
    public function settleActivated(ReferralAttribution $attribution): array
    {
        if ($attribution->status === 'rewarded') {
            return $this->outcome('already_rewarded', $attribution, null, 'Attribution already rewarded.');
        }

        if ($attribution->status !== 'activated') {
            return $this->outcome('not_activated', $attribution, null, 'Attribution is not activated.');
        }

        $type = match ((string) $attribution->referral_type) {
            'tool' => YuktiRewardEngine::TYPE_TOOL_REFERRAL,
            'expert' => YuktiRewardEngine::TYPE_EXPERT_REFERRAL,
            default => null,
        };

        if ($type === null) {
            return $this->outcome('unsupported_type', $attribution, null, 'Referral type cannot be settled.');
        }

        return $this->settle($attribution, $type);
    }

    /**
     * @throws ReferralActivationRejectedException
     */
    private function markToolActivated(
        int $refereeUserId,
        Tool $tool,
        string $toolSlug,
        string $completionKey,
        ?int $completionRecordId,
        CarbonImmutable $completedAt,
    ): ReferralAttribution {
        return DB::transaction(function () use (
            $refereeUserId,
            $tool,
            $toolSlug,
            $completionKey,
            $completionRecordId,
            $completedAt,
        ) {
            $attribution = ReferralAttribution::query()
                ->where('referee_user_id', $refereeUserId)
                ->lockForUpdate()
                ->first();

            if ($attribution === null) {
                throw new ReferralActivationRejectedException('no_attribution', 'No referral attribution for this customer.');
            }

            if (in_array($attribution->status, ['activated', 'rewarded'], true)) {
                return $attribution;
            }

            if ($attribution->status !== 'joined') {
                throw new ReferralActivationRejectedException(
                    'invalid_status',
                    "Attribution status [{$attribution->status}] cannot activate.",
                    $attribution,
                );
            }

            if ((string) $attribution->referral_type !== 'tool'
                || (string) $attribution->target_type !== 'tool'
                || (int) $attribution->target_id !== (int) $tool->id
            ) {
                throw new ReferralActivationRejectedException(
                    'wrong_tool',
                    'Completion does not match the referred tool.',
                    $attribution,
                );
            }

            $this->assertCommonActivationRules($attribution, $completedAt);

            $attribution->status = 'activated';
            $attribution->activated_at = $completedAt;
            $attribution->activation_meta = [
                'kind' => 'tool_completion',
                'tool_slug' => $toolSlug,
                'tool_id' => (int) $tool->id,
                'completion_key' => $completionKey,
                'completion_record_id' => $completionRecordId,
                'activated_week_id' => YuktiWeekId::for($completedAt),
            ];
            $attribution->save();

            return $attribution->refresh();
        }, 3);
    }

    /**
     * @throws ReferralActivationRejectedException
     */
    private function markExpertActivated(Booking $booking, int $refereeId, int $professionalId): ReferralAttribution
    {
        return DB::transaction(function () use ($booking, $refereeId, $professionalId) {
            $attribution = ReferralAttribution::query()
                ->where('referee_user_id', $refereeId)
                ->lockForUpdate()
                ->first();

            if ($attribution === null) {
                throw new ReferralActivationRejectedException('no_attribution', 'No referral attribution for this customer.');
            }

            if (in_array($attribution->status, ['activated', 'rewarded'], true)) {
                $meta = is_array($attribution->activation_meta) ? $attribution->activation_meta : [];
                if (($meta['kind'] ?? null) === 'expert_booking'
                    && (int) ($meta['booking_id'] ?? 0) === (int) $booking->id
                ) {
                    return $attribution;
                }

                // Already activated/rewarded for another event — one attribution per referee.
                return $attribution;
            }

            if ($attribution->status !== 'joined') {
                throw new ReferralActivationRejectedException(
                    'invalid_status',
                    "Attribution status [{$attribution->status}] cannot activate.",
                    $attribution,
                );
            }

            if ((string) $attribution->referral_type !== 'expert'
                || (string) $attribution->target_type !== 'professional'
                || (int) $attribution->target_id !== $professionalId
            ) {
                throw new ReferralActivationRejectedException(
                    'wrong_expert',
                    'Booking expert does not match the referred expert.',
                    $attribution,
                );
            }

            $activatedAt = CarbonImmutable::instance(now());
            $this->assertCommonActivationRules($attribution, $activatedAt);

            $attribution->status = 'activated';
            $attribution->activated_at = $activatedAt;
            $attribution->activation_meta = [
                'kind' => 'expert_booking',
                'booking_id' => (int) $booking->id,
                'professional_id' => $professionalId,
                'payment_status' => (string) $booking->payment_status,
                'session_count' => $booking->timedates->count(),
                'activated_week_id' => YuktiWeekId::for($activatedAt),
            ];
            $attribution->save();

            return $attribution->refresh();
        }, 3);
    }

    /**
     * @throws ReferralActivationRejectedException
     */
    private function assertCommonActivationRules(ReferralAttribution $attribution, CarbonImmutable $at): void
    {
        if ((int) $attribution->referrer_user_id === (int) $attribution->referee_user_id) {
            throw new ReferralActivationRejectedException('self_referral', 'Self-referral is not allowed.', $attribution);
        }

        $referee = User::query()->whereKey($attribution->referee_user_id)->first();
        if ($referee === null || ! $referee->registration_completed) {
            throw new ReferralActivationRejectedException(
                'registration_incomplete',
                'Referee registration is not completed.',
                $attribution,
            );
        }

        if ($attribution->signed_up_at !== null
            && $at->getTimestamp() < $attribution->signed_up_at->getTimestamp()
        ) {
            throw new ReferralActivationRejectedException(
                'before_signup',
                'Activation evidence precedes signup.',
                $attribution,
            );
        }
    }

    /**
     * Grant outside the attribution lock to avoid wallet/attribution lock-order deadlocks.
     *
     * @return array{status:string, attribution:?ReferralAttribution, reward:?YuktiRewardResult, message:string}
     */
    private function settle(ReferralAttribution $attribution, string $rewardType): array
    {
        $attribution = $attribution->fresh();
        if ($attribution === null) {
            return $this->outcome('missing', null, null, 'Attribution disappeared.');
        }

        if ($attribution->status === 'rewarded') {
            return $this->outcome('already_rewarded', $attribution, null, 'Attribution already rewarded.');
        }

        if ($attribution->status !== 'activated') {
            return $this->outcome('not_activated', $attribution, null, 'Attribution is not activated.');
        }

        $activatedAt = $attribution->activated_at
            ? CarbonImmutable::instance($attribution->activated_at)
            : CarbonImmutable::instance(now());

        $weekId = YuktiWeekId::for($activatedAt);
        $meta = is_array($attribution->activation_meta) ? $attribution->activation_meta : [];

        if ($rewardType === YuktiRewardEngine::TYPE_TOOL_REFERRAL) {
            $idempotencyKey = YuktiRewardIdempotency::toolReferral((int) $attribution->id);
            $sourceType = 'referral_attribution';
            $sourceId = (int) $attribution->id;
        } else {
            $bookingId = (int) ($meta['booking_id'] ?? 0);
            if ($bookingId <= 0) {
                return $this->outcome('missing_booking_evidence', $attribution, null, 'Expert activation missing booking_id.');
            }
            $idempotencyKey = YuktiRewardIdempotency::expertReferral($bookingId);
            $sourceType = 'booking';
            $sourceId = $bookingId;
        }

        $grantMeta = array_merge($meta, [
            'attribution_id' => (int) $attribution->id,
            'week_id' => $weekId,
            'accounting_week_policy' => 'activation_at',
        ]);

        $result = $this->dispatcher->tryGrant(
            userId: (int) $attribution->referrer_user_id,
            rewardType: $rewardType,
            idempotencyKey: $idempotencyKey,
            sourceType: $sourceType,
            sourceId: $sourceId,
            meta: $grantMeta,
            at: $activatedAt,
        );

        if ($result === null) {
            Log::error('Referral settlement grant failed unexpectedly; attribution left activated.', [
                'attribution_id' => $attribution->id,
                'reward_type' => $rewardType,
            ]);

            return $this->outcome('grant_error', $attribution, null, 'Grant failed; attribution remains activated.');
        }

        if ($result->status === YuktiRewardResult::STATUS_DISABLED) {
            return $this->outcome('activated_engine_disabled', $attribution, $result, 'Activated; engine disabled — not rewarded.');
        }

        if ($result->granted || $result->status === YuktiRewardResult::STATUS_DUPLICATE) {
            $this->markRewarded($attribution);

            return $this->outcome(
                $result->granted ? 'rewarded' : 'already_rewarded',
                $attribution->fresh(),
                $result,
                $result->granted ? 'Referral rewarded.' : 'Ledger already had this reward; attribution marked rewarded.',
            );
        }

        if ($result->status === YuktiRewardResult::STATUS_WEEKLY_CAP) {
            Log::info('Referral activated but weekly cap blocked reward settlement.', [
                'attribution_id' => $attribution->id,
                'reward_type' => $rewardType,
                'week_id' => $weekId,
            ]);

            return $this->outcome('activated_weekly_cap', $attribution, $result, 'Activated; weekly cap blocked reward.');
        }

        Log::warning('Referral activated but grant did not succeed.', [
            'attribution_id' => $attribution->id,
            'reward_status' => $result->status,
            'message' => $result->message,
        ]);

        return $this->outcome('activated_unsettled', $attribution, $result, 'Activated; grant not successful.');
    }

    private function markRewarded(ReferralAttribution $attribution): void
    {
        DB::transaction(function () use ($attribution) {
            $row = ReferralAttribution::query()->whereKey($attribution->id)->lockForUpdate()->first();
            if ($row === null || $row->status === 'rewarded') {
                return;
            }
            if ($row->status !== 'activated') {
                return;
            }
            $row->status = 'rewarded';
            $row->rewarded_at = now();
            $row->save();
        }, 3);
    }

    /**
     * @return array{status:string, attribution:?ReferralAttribution, reward:?YuktiRewardResult, message:string}
     */
    private function outcome(
        string $status,
        ?ReferralAttribution $attribution,
        ?YuktiRewardResult $reward,
        string $message,
    ): array {
        return [
            'status' => $status,
            'attribution' => $attribution,
            'reward' => $reward,
            'message' => $message,
        ];
    }
}
