<?php

namespace App\Services\YuktiRewards;

use App\Models\Professional;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Phase 3I — assemble customer rewards UX data from existing read services.
 * No grants, debits, or invented balances.
 */
class YuktiRewardsHubPresenter
{
    public function __construct(
        private readonly YuktiWalletReadService $wallets,
        private readonly ReferralLinkBuilder $links,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function hub(User $user): array
    {
        $userId = (int) $user->id;
        $summary = $this->wallets->summary($userId);
        $weekly = $this->wallets->weeklyProgress($userId);
        $referrals = $this->wallets->referralActivity($userId);
        $history = $this->wallets->history($userId, 1, 5);

        $engineOn = (bool) $summary['engine_enabled'];
        $eligibleTools = $this->eligibleTools();
        $experts = $this->publicExperts(12);

        $toolLinks = $eligibleTools->map(fn (Tool $tool) => [
            'id' => (int) $tool->id,
            'slug' => $tool->slug,
            'name' => $tool->name,
            'url' => $this->links->toolUrl($user, $tool),
            'destination' => $this->links->resolveToolDestination($tool),
        ])->values()->all();

        $expertLinks = $experts->map(fn (Professional $pro) => [
            'id' => (int) $pro->id,
            'name' => $pro->name,
            'url' => $this->links->expertUrl($user, $pro),
        ])->values()->all();

        return [
            'customer' => [
                'id' => $userId,
                'name' => (string) ($user->name ?: 'there'),
                'first_name' => (string) (explode(' ', trim((string) ($user->name ?: 'there')))[0] ?: 'there'),
            ],
            'summary' => $summary,
            'weekly' => $weekly,
            'weekly_cards' => $this->weeklyCards($weekly, $engineOn),
            'referrals' => $referrals,
            'recent_activity' => $history->items(),
            'share' => [
                'general_url' => $this->links->generalUrl($user),
                'code' => $this->links->codeFor($user)->code,
                'tools' => $toolLinks,
                'experts' => $expertLinks,
            ],
            'engine_enabled' => $engineOn,
            'redemption' => $summary['redemption'],
            'routes' => [
                'wallet' => route('user.yukti-points.wallet'),
                'activity' => route('user.yukti-points.activity'),
                'tools' => route('tools.index'),
                'experts' => route('professionals'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function walletPage(User $user, int $page = 1): array
    {
        $userId = (int) $user->id;
        $summary = $this->wallets->summary($userId);
        $history = $this->wallets->history($userId, $page, YuktiWalletReadService::HISTORY_DEFAULT_PER_PAGE);

        return [
            'customer' => [
                'name' => (string) ($user->name ?: 'there'),
                'first_name' => (string) (explode(' ', trim((string) ($user->name ?: 'there')))[0] ?: 'there'),
            ],
            'summary' => $summary,
            'history' => $history,
            'engine_enabled' => (bool) $summary['engine_enabled'],
            'redemption' => $summary['redemption'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function activityPage(User $user, ?string $status = null, ?string $type = null): array
    {
        $userId = (int) $user->id;
        $activity = $this->wallets->referralActivity($userId);
        $rows = collect($activity['as_referrer']);

        if ($status) {
            $rows = $rows->where('status', $status)->values();
        }
        if ($type) {
            $rows = $rows->where('referral_type', $type)->values();
        }

        return [
            'customer' => [
                'first_name' => (string) (explode(' ', trim((string) ($user->name ?: 'there')))[0] ?: 'there'),
            ],
            'activity' => $activity,
            'rows' => $rows->all(),
            'filters' => [
                'status' => $status,
                'referral_type' => $type,
            ],
            'share' => [
                'general_url' => $this->links->generalUrl($user),
                'code' => $this->links->codeFor($user)->code,
            ],
            'engine_enabled' => (bool) config('yuktione_rewards.engine_enabled'),
        ];
    }

    /**
     * @param  array<string, mixed>  $weekly
     * @return list<array<string, mixed>>
     */
    private function weeklyCards(array $weekly, bool $engineOn): array
    {
        $labels = [
            'login' => [
                'title' => 'Weekly login',
                'blurb' => 'Sign in once this week.',
                'cta_label' => null,
                'cta_url' => null,
            ],
            'tool_complete' => [
                'title' => 'Tool completion',
                'blurb' => 'Finish one eligible tool.',
                'cta_label' => 'Explore tools',
                'cta_url' => route('tools.index'),
            ],
            'tool_referral' => [
                'title' => 'Tool referral',
                'blurb' => 'A friend joins and completes the referred tool.',
                'cta_label' => 'Share a tool',
                'cta_url' => '#yk-share-tool',
            ],
            'expert_referral' => [
                'title' => 'Expert referral',
                'blurb' => 'A friend books and completes a paid session.',
                'cta_label' => 'Share an expert',
                'cta_url' => '#yk-share-expert',
            ],
            'social_share' => [
                'title' => 'Social share',
                'blurb' => 'Verified shares only — not counted until confirmed.',
                'cta_label' => null,
                'cta_url' => null,
            ],
        ];

        $cards = [];
        foreach ($labels as $metric => $meta) {
            $row = $weekly['metrics'][$metric] ?? null;
            if (! is_array($row)) {
                continue;
            }

            $earned = (bool) ($row['earned'] ?? false);
            if ($metric === 'social_share') {
                $earned = (bool) ($weekly['social_share']['earned'] ?? false);
            }

            $cards[] = [
                'metric' => $metric,
                'title' => $meta['title'],
                'blurb' => $meta['blurb'],
                'points_per_event' => $row['points_per_event'],
                'count' => (int) ($row['count'] ?? 0),
                'cap_count' => $row['cap_count'],
                'points_awarded' => (int) ($row['points_awarded'] ?? 0),
                'remaining_count' => $row['remaining_count'],
                'earned' => $earned,
                'engine_enabled' => $engineOn,
                'cta_label' => $engineOn ? $meta['cta_label'] : null,
                'cta_url' => $engineOn ? $meta['cta_url'] : null,
                'status_label' => ! $engineOn
                    ? 'Not active yet'
                    : ($earned ? 'Earned this week' : ($row['remaining_count'] === 0 ? 'Weekly limit reached' : 'Available')),
            ];
        }

        return $cards;
    }

    /**
     * @return Collection<int, Tool>
     */
    private function eligibleTools(): Collection
    {
        $slugs = config('yuktione_rewards.eligible_tool_completion_slugs', []);
        if (! is_array($slugs) || $slugs === []) {
            return collect();
        }

        return Tool::query()
            ->publiclyAvailable()
            ->whereIn('slug', $slugs)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (Tool $tool) => $tool->canLaunch())
            ->values();
    }

    /**
     * @return Collection<int, Professional>
     */
    private function publicExperts(int $limit): Collection
    {
        return Professional::query()
            ->where('status', 'accepted')
            ->where('active', true)
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }
}
