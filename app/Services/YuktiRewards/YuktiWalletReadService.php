<?php

namespace App\Services\YuktiRewards;

use App\Models\ReferralAttribution;
use App\Models\User;
use App\Models\YuktiRedemption;
use App\Models\YuktiRewardConfig;
use App\Models\YuktiRewardLedgerEntry;
use App\Models\YuktiSocialShareClaim;
use App\Models\YuktiWallet;
use App\Models\YuktiWeeklyCounter;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Read-only customer wallet aggregations. Never mutates balances.
 * Balances always come from yukti_wallets / yukti_reward_ledger — never from request input.
 */
class YuktiWalletReadService
{
    public const HISTORY_DEFAULT_PER_PAGE = 20;

    public const HISTORY_MAX_PER_PAGE = 50;

    /**
     * @return array{
     *   user_id:int,
     *   engine_enabled:bool,
     *   available_points:int,
     *   pending_points:int,
     *   total_earned_points:int,
     *   total_redeemed_points:int,
     *   has_wallet_row:bool,
     *   redemption:array<string,mixed>
     * }
     */
    public function summary(int $userId): array
    {
        $this->assertCustomerExists($userId);

        $wallet = YuktiWallet::query()->where('user_id', $userId)->first();

        $totals = YuktiRewardLedgerEntry::query()
            ->where('user_id', $userId)
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN direction = 'credit' AND status = 'completed' THEN points ELSE 0 END), 0) as earned"
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN direction = 'debit' AND status IN ('completed', 'reversed') THEN points ELSE 0 END), 0) as debited"
            )
            ->first();

        // Prefer ledger debits; also include completed redemption rows if ledger debit not yet wired.
        $redeemedFromTable = (int) YuktiRedemption::query()
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->sum('points');

        $ledgerDebits = (int) ($totals->debited ?? 0);
        $totalRedeemed = max($ledgerDebits, $redeemedFromTable);

        return [
            'user_id' => $userId,
            'engine_enabled' => (bool) config('yuktione_rewards.engine_enabled'),
            'available_points' => max(0, (int) ($wallet->available_points ?? 0)),
            'pending_points' => max(0, (int) ($wallet->pending_points ?? 0)),
            'total_earned_points' => (int) ($totals->earned ?? 0),
            'total_redeemed_points' => $totalRedeemed,
            'has_wallet_row' => $wallet !== null,
            'redemption' => $this->redemptionReadiness(),
        ];
    }

    /**
     * Paginated immutable ledger history for one customer only.
     */
    public function history(int $userId, int $page = 1, int $perPage = self::HISTORY_DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $this->assertCustomerExists($userId);

        $perPage = max(1, min($perPage, self::HISTORY_MAX_PER_PAGE));
        $page = max(1, $page);

        return YuktiRewardLedgerEntry::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->paginate(perPage: $perPage, page: $page)
            ->through(function (YuktiRewardLedgerEntry $row) {
                return [
                    'id' => (int) $row->id,
                    'direction' => (string) $row->direction,
                    'points' => (int) $row->points,
                    'available_after' => $row->available_after !== null ? (int) $row->available_after : null,
                    'pending_after' => $row->pending_after !== null ? (int) $row->pending_after : null,
                    'event_type' => (string) $row->event_type,
                    'source_type' => $row->source_type,
                    'source_id' => $row->source_id !== null ? (int) $row->source_id : null,
                    'week_id' => $row->week_id,
                    'status' => (string) $row->status,
                    'rewarded_at' => optional($row->rewarded_at)?->toIso8601String(),
                    'created_at' => optional($row->created_at)?->toIso8601String(),
                ];
            });
    }

    /**
     * Referral activity for the authenticated customer as referrer.
     * Activated (not rewarded) rows expose estimate labels only — never spendable.
     *
     * @return array{
     *   as_referrer:list<array<string,mixed>>,
     *   as_referee:?array<string,mixed>,
     *   activated_estimate_points:int,
     *   activated_estimate_label:string
     * }
     */
    public function referralActivity(int $userId): array
    {
        $this->assertCustomerExists($userId);

        $configs = $this->rewardConfigMap();

        $asReferrer = ReferralAttribution::query()
            ->where('referrer_user_id', $userId)
            ->orderByDesc('id')
            ->get()
            ->map(fn (ReferralAttribution $row) => $this->mapAttribution($row, $configs))
            ->values()
            ->all();

        $asReferee = ReferralAttribution::query()
            ->where('referee_user_id', $userId)
            ->first();

        $activatedEstimate = 0;
        foreach ($asReferrer as $item) {
            if ($item['status'] === 'activated' && $item['estimate_points'] !== null) {
                $activatedEstimate += (int) $item['estimate_points'];
            }
        }

        return [
            'as_referrer' => $asReferrer,
            'as_referee' => $asReferee ? $this->mapAttribution($asReferee, $configs) : null,
            'activated_estimate_points' => $activatedEstimate,
            'activated_estimate_label' => 'Estimate only — not available to spend until ledger-backed reward.',
        ];
    }

    /**
     * Monday-based weekly progress for the configured timezone.
     *
     * @return array{
     *   week_id:string,
     *   timezone:string,
     *   metrics:array<string,array<string,mixed>>,
     *   social_share:array<string,mixed>
     * }
     */
    public function weeklyProgress(int $userId, ?DateTimeInterface $at = null): array
    {
        $this->assertCustomerExists($userId);

        $weekId = YuktiWeekId::for($at);
        $configs = $this->rewardConfigMap();

        $counters = YuktiWeeklyCounter::query()
            ->where('user_id', $userId)
            ->where('week_id', $weekId)
            ->get()
            ->keyBy('metric');

        $metricKeys = [
            'login' => 'weekly_login',
            'tool_complete' => 'tool_completion',
            'tool_referral' => 'tool_referral',
            'expert_referral' => 'expert_referral',
            'social_share' => 'social_share',
        ];

        $metrics = [];
        foreach ($metricKeys as $metric => $configKey) {
            $cfg = $configs->get($configKey);
            $counter = $counters->get($metric);
            $capCount = $cfg?->weekly_cap_count;
            $capPoints = $cfg?->weekly_cap_points;
            $count = (int) ($counter->count ?? 0);
            $points = (int) ($counter->points_awarded ?? 0);

            $metrics[$metric] = [
                'metric' => $metric,
                'config_key' => $configKey,
                'count' => $count,
                'points_awarded' => $points,
                'cap_count' => $capCount,
                'cap_points' => $capPoints,
                'points_per_event' => $cfg?->points,
                'remaining_count' => $capCount === null ? null : max(0, (int) $capCount - $count),
                'is_active' => (bool) ($cfg?->is_active ?? false),
                'earned' => $points > 0,
            ];
        }

        return [
            'week_id' => $weekId,
            'timezone' => (string) config('yuktione_rewards.week_timezone', 'Asia/Kolkata'),
            'accounting_week_policy' => (string) config('yuktione_rewards.referral_accounting_week', 'activation_at'),
            'metrics' => $metrics,
            'social_share' => $this->socialShareStatus($userId, $weekId),
        ];
    }

    /**
     * Social share eligibility — never treats unverified claims as earned.
     *
     * @return array<string,mixed>
     */
    public function socialShareStatus(int $userId, ?string $weekId = null): array
    {
        $this->assertCustomerExists($userId);
        $weekId = $weekId ?? YuktiWeekId::for();

        $claim = YuktiSocialShareClaim::query()
            ->where('user_id', $userId)
            ->where('week_id', $weekId)
            ->first();

        $verified = $claim !== null && (string) $claim->verification_status === 'verified';
        $ledgerEarned = YuktiRewardLedgerEntry::query()
            ->where('user_id', $userId)
            ->where('week_id', $weekId)
            ->where('event_type', YuktiRewardEngine::TYPE_SOCIAL_SHARE)
            ->where('direction', 'credit')
            ->where('status', 'completed')
            ->exists();

        return [
            'week_id' => $weekId,
            'has_claim' => $claim !== null,
            'verification_status' => $claim?->verification_status,
            'channel' => $claim?->channel,
            'eligible_to_earn' => $claim === null,
            'earned' => $ledgerEarned,
            'note' => $verified && ! $ledgerEarned
                ? 'Claim verified but not yet ledger-backed.'
                : ($ledgerEarned
                    ? 'Ledger-backed social share reward recorded.'
                    : 'Social share rewards are not shown as earned without a verified, ledger-backed grant.'),
        ];
    }

    /**
     * Documented redemption readiness — no debit execution.
     *
     * @return array<string,mixed>
     */
    public function redemptionReadiness(): array
    {
        $cfg = YuktiRewardConfig::query()->where('key', 'redemption_rupee_per_point')->first();

        return [
            'executable' => false,
            'conversion_rate_approved' => $cfg !== null
                && $cfg->is_active
                && (string) $cfg->approval_status === 'approved'
                && $cfg->points !== null,
            'rupee_per_point' => null,
            'minimum_points' => config('yuktione_rewards.redemption.minimum_points'),
            'status' => 'pending_business_rule',
            'message' => 'Redemption is prepared in schema only. No debit endpoint is active. Rupee conversion is not approved.',
        ];
    }

    /**
     * @param  Collection<string, YuktiRewardConfig>  $configs
     * @return array<string,mixed>
     */
    private function mapAttribution(ReferralAttribution $row, Collection $configs): array
    {
        $estimate = null;
        $estimateLabel = null;

        if ($row->referral_type === 'tool') {
            $estimate = $configs->get('tool_referral')?->points;
        } elseif ($row->referral_type === 'expert') {
            $estimate = $configs->get('expert_referral')?->points;
        }

        if ($row->status === 'activated' && $estimate !== null) {
            $estimateLabel = 'Estimate only — not available to spend until ledger-backed.';
        } elseif ($row->status === 'rewarded') {
            $estimateLabel = 'Rewarded (see ledger for actual credit).';
        } else {
            $estimate = null;
        }

        return [
            'id' => (int) $row->id,
            'referral_type' => (string) $row->referral_type,
            'status' => (string) $row->status,
            'target_type' => $row->target_type,
            'target_id' => $row->target_id !== null ? (int) $row->target_id : null,
            'signed_up_at' => optional($row->signed_up_at)?->toIso8601String(),
            'activated_at' => optional($row->activated_at)?->toIso8601String(),
            'rewarded_at' => optional($row->rewarded_at)?->toIso8601String(),
            'activation_week_id' => is_array($row->activation_meta)
                ? ($row->activation_meta['activated_week_id'] ?? null)
                : null,
            'estimate_points' => $estimate,
            'estimate_label' => $estimateLabel,
            // Activated estimates are never spendable; only ledger-backed wallet.available_points are.
            'counts_toward_available_balance' => false,
            'ledger_rewarded' => $row->status === 'rewarded',
        ];
    }

    /**
     * @return Collection<string, YuktiRewardConfig>
     */
    private function rewardConfigMap(): Collection
    {
        return YuktiRewardConfig::query()->get()->keyBy('key');
    }

    private function assertCustomerExists(int $userId): void
    {
        if ($userId <= 0 || ! User::query()->whereKey($userId)->exists()) {
            throw new InvalidArgumentException('Customer not found for wallet read.');
        }
    }
}
