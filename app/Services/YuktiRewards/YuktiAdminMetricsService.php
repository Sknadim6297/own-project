<?php

namespace App\Services\YuktiRewards;

use App\Models\ReferralAttribution;
use App\Models\YuktiFraudFlag;
use App\Models\YuktiRewardConfig;
use App\Models\YuktiRewardLedgerEntry;
use App\Models\YuktiWallet;
use Illuminate\Support\Facades\DB;

/**
 * Phase 3H — admin read aggregations. Never mutates wallets or grants points.
 */
class YuktiAdminMetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $walletAgg = YuktiWallet::query()
            ->selectRaw('COUNT(*) as wallet_count')
            ->selectRaw('COALESCE(SUM(available_points), 0) as available_points')
            ->selectRaw('COALESCE(SUM(pending_points), 0) as pending_points')
            ->first();

        $ledgerAgg = YuktiRewardLedgerEntry::query()
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' AND status = 'completed' THEN points ELSE 0 END), 0) as credited")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'debit' THEN points ELSE 0 END), 0) as debited")
            ->first();

        $referralCounts = ReferralAttribution::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $recent = YuktiRewardLedgerEntry::query()
            ->with(['user:id,name,email'])
            ->orderByDesc('id')
            ->limit(15)
            ->get()
            ->map(fn (YuktiRewardLedgerEntry $row) => $this->mapLedgerRow($row));

        return [
            'engine_enabled' => (bool) config('yuktione_rewards.engine_enabled'),
            'redemption_enabled' => (bool) config('yuktione_rewards.redemption.enabled'),
            'week_timezone' => (string) config('yuktione_rewards.week_timezone', 'Asia/Kolkata'),
            'current_week_id' => YuktiWeekId::for(),
            'wallet_count' => (int) ($walletAgg->wallet_count ?? 0),
            'available_points' => (int) ($walletAgg->available_points ?? 0),
            'pending_points' => (int) ($walletAgg->pending_points ?? 0),
            'credited_points' => (int) ($ledgerAgg->credited ?? 0),
            'debited_points' => (int) ($ledgerAgg->debited ?? 0),
            'referrals' => [
                'joined' => (int) ($referralCounts['joined'] ?? 0),
                'activated' => (int) ($referralCounts['activated'] ?? 0),
                'rewarded' => (int) ($referralCounts['rewarded'] ?? 0),
                'rejected' => (int) ($referralCounts['rejected'] ?? 0),
                'invited' => (int) ($referralCounts['invited'] ?? 0),
                'pending_activation' => (int) ($referralCounts['joined'] ?? 0),
            ],
            'fraud_flag_count' => (int) YuktiFraudFlag::query()->count(),
            'recent_transactions' => $recent,
            'redemption' => YuktiRedemptionArchitecture::snapshot(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function configuration(): array
    {
        $rows = YuktiRewardConfig::query()->orderBy('key')->get();

        $rewardKeys = [
            'weekly_login',
            'tool_completion',
            'tool_referral',
            'expert_referral',
            'social_share',
        ];

        $rewards = [];
        foreach ($rewardKeys as $key) {
            $row = $rows->firstWhere('key', $key);
            $rewards[] = [
                'key' => $key,
                'label' => is_array($row?->meta) ? ($row->meta['label'] ?? $key) : $key,
                'points' => $row?->points,
                'weekly_cap_count' => $row?->weekly_cap_count,
                'weekly_cap_points' => $row?->weekly_cap_points,
                'is_active' => (bool) ($row?->is_active ?? false),
                'approval_status' => $row?->approval_status,
            ];
        }

        $unresolved = $rows
            ->filter(fn (YuktiRewardConfig $row) => (string) $row->approval_status === 'pending_business_rule'
                || $row->points === null)
            ->map(fn (YuktiRewardConfig $row) => [
                'key' => $row->key,
                'approval_status' => $row->approval_status,
                'is_active' => (bool) $row->is_active,
                'note' => is_array($row->meta) ? ($row->meta['note'] ?? null) : null,
            ])
            ->values()
            ->all();

        return [
            'engine_enabled' => (bool) config('yuktione_rewards.engine_enabled'),
            'redemption_enabled' => (bool) config('yuktione_rewards.redemption.enabled'),
            'week_timezone' => (string) config('yuktione_rewards.week_timezone', 'Asia/Kolkata'),
            'referral_accounting_week' => (string) config('yuktione_rewards.referral_accounting_week', 'activation_at'),
            'eligible_tool_completion_slugs' => config('yuktione_rewards.eligible_tool_completion_slugs', []),
            'rewards' => $rewards,
            'unresolved_business_rules' => $unresolved,
            'redemption' => YuktiRedemptionArchitecture::snapshot(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function wallets(array $filters, int $perPage = 20)
    {
        $q = YuktiWallet::query()
            ->with(['user:id,name,email'])
            ->when($filters['q'] ?? null, function ($query, $term) {
                $term = trim((string) $term);
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function ($outer) use ($like, $term) {
                    $outer->whereHas('user', function ($inner) use ($like) {
                        $inner->where('email', 'like', $like)->orWhere('name', 'like', $like);
                    });
                    if (ctype_digit($term)) {
                        $outer->orWhere('user_id', (int) $term);
                    }
                });
            })
            ->orderByDesc('available_points')
            ->orderByDesc('id');

        return $q->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function ledger(array $filters, int $perPage = 25)
    {
        return YuktiRewardLedgerEntry::query()
            ->with(['user:id,name,email'])
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', (int) $id))
            ->when($filters['event_type'] ?? null, fn ($q, $type) => $q->where('event_type', $type))
            ->when($filters['direction'] ?? null, fn ($q, $dir) => $q->where('direction', $dir))
            ->when($filters['week_id'] ?? null, fn ($q, $week) => $q->where('week_id', $week))
            ->when($filters['q'] ?? null, function ($query, $term) {
                $like = '%'.addcslashes((string) $term, '%_\\').'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('idempotency_key', 'like', $like)
                        ->orWhere('event_type', 'like', $like)
                        ->orWhereHas('user', function ($u) use ($like) {
                            $u->where('email', 'like', $like)->orWhere('name', 'like', $like);
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (YuktiRewardLedgerEntry $row) => $this->mapLedgerRow($row));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function referrals(array $filters, int $perPage = 25)
    {
        return ReferralAttribution::query()
            ->with([
                'referrer:id,name,email',
                'referee:id,name,email',
            ])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['referral_type'] ?? null, fn ($q, $type) => $q->where('referral_type', $type))
            ->when($filters['q'] ?? null, function ($query, $term) {
                $like = '%'.addcslashes((string) $term, '%_\\').'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('referral_code', 'like', $like)
                        ->orWhereHas('referrer', fn ($u) => $u->where('email', 'like', $like)->orWhere('name', 'like', $like))
                        ->orWhereHas('referee', fn ($u) => $u->where('email', 'like', $like)->orWhere('name', 'like', $like));
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function fraudFlags(array $filters, int $perPage = 25)
    {
        return YuktiFraudFlag::query()
            ->with(['user:id,name,email', 'reviewedByAdmin:id,name,email'])
            ->when($filters['severity'] ?? null, fn ($q, $sev) => $q->where('severity', $sev))
            ->when($filters['reason'] ?? null, fn ($q, $reason) => $q->where('reason', 'like', '%'.addcslashes((string) $reason, '%_\\').'%'))
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', (int) $id))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return list<string>
     */
    public function distinctLedgerEventTypes(): array
    {
        return YuktiRewardLedgerEntry::query()
            ->select('event_type')
            ->distinct()
            ->orderBy('event_type')
            ->pluck('event_type')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapLedgerRow(YuktiRewardLedgerEntry $row): array
    {
        return [
            'id' => (int) $row->id,
            'user_id' => (int) $row->user_id,
            'user_name' => $row->user?->name,
            'user_email' => $row->user?->email,
            'direction' => (string) $row->direction,
            'points' => (int) $row->points,
            'available_after' => $row->available_after !== null ? (int) $row->available_after : null,
            'pending_after' => $row->pending_after !== null ? (int) $row->pending_after : null,
            'event_type' => (string) $row->event_type,
            'week_id' => $row->week_id,
            'status' => (string) $row->status,
            'idempotency_key' => (string) $row->idempotency_key,
            'source_type' => $row->source_type,
            'source_id' => $row->source_id !== null ? (int) $row->source_id : null,
            'rewarded_at' => optional($row->rewarded_at)?->toDateTimeString(),
            'created_at' => optional($row->created_at)?->toDateTimeString(),
        ];
    }
}
