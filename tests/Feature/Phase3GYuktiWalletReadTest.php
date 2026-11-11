<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckActiveSession;
use App\Models\Admin;
use App\Models\Professional;
use App\Models\ReferralAttribution;
use App\Models\User;
use App\Models\YuktiRewardLedgerEntry;
use App\Models\YuktiSocialShareClaim;
use App\Models\YuktiWallet;
use App\Models\YuktiWeeklyCounter;
use App\Services\YuktiRewards\YuktiRedemptionArchitecture;
use App\Services\YuktiRewards\YuktiWalletReadService;
use App\Services\YuktiRewards\YuktiWeekId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase3GYuktiWalletReadTest extends TestCase
{
    private object $foundation;

    private object $activationMeta;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->boolean('registration_completed')->default(true);
            $table->timestamps();
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('approved');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('active_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
        });

        $this->foundation = require database_path(
            'migrations/2026_11_11_200000_create_yukti_refer_earn_foundation_tables.php'
        );
        $this->foundation->up();

        $this->activationMeta = require database_path(
            'migrations/2026_11_11_210000_add_activation_meta_to_referral_attributions.php'
        );
        $this->activationMeta->up();

        Config::set('yuktione_rewards.week_timezone', 'Asia/Kolkata');
        Config::set('yuktione_rewards.engine_enabled', false);

        // Wallet ownership tests are independent of multi-device session tracking.
        $this->withoutMiddleware(CheckActiveSession::class);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_empty_wallet_summary_is_zero_while_engine_disabled(): void
    {
        $user = $this->user('empty@example.com');
        $summary = app(YuktiWalletReadService::class)->summary((int) $user->id);

        $this->assertFalse($summary['engine_enabled']);
        $this->assertFalse($summary['has_wallet_row']);
        $this->assertSame(0, $summary['available_points']);
        $this->assertSame(0, $summary['pending_points']);
        $this->assertSame(0, $summary['total_earned_points']);
        $this->assertSame(0, $summary['total_redeemed_points']);
        $this->assertFalse($summary['redemption']['executable']);
        $this->assertNull($summary['redemption']['rupee_per_point']);
    }

    public function test_available_and_pending_are_separated_and_earned_aggregated(): void
    {
        $user = $this->user('balanced@example.com');
        YuktiWallet::query()->create([
            'user_id' => $user->id,
            'available_points' => 75,
            'pending_points' => 200,
        ]);

        $this->ledger($user->id, 'credit', 50, 'weekly_login', 'completed', availableAfter: 50, pendingAfter: 0);
        $this->ledger($user->id, 'credit', 25, 'tool_completion', 'completed', availableAfter: 75, pendingAfter: 0);
        $this->ledger($user->id, 'credit', 200, 'expert_referral', 'completed', availableAfter: 75, pendingAfter: 200);
        $this->ledger($user->id, 'debit', 10, 'redemption', 'completed', availableAfter: 65, pendingAfter: 200);

        $summary = app(YuktiWalletReadService::class)->summary((int) $user->id);

        $this->assertSame(75, $summary['available_points']);
        $this->assertSame(200, $summary['pending_points']);
        $this->assertSame(275, $summary['total_earned_points']);
        $this->assertSame(10, $summary['total_redeemed_points']);
    }

    public function test_ledger_history_is_paginated_and_owner_scoped(): void
    {
        $owner = $this->user('owner@example.com');
        $other = $this->user('other@example.com');

        for ($i = 1; $i <= 25; $i++) {
            $this->ledger($owner->id, 'credit', 1, 'weekly_login', 'completed', availableAfter: $i);
        }
        $this->ledger($other->id, 'credit', 999, 'weekly_login', 'completed', availableAfter: 999);

        $page1 = app(YuktiWalletReadService::class)->history((int) $owner->id, page: 1, perPage: 10);
        $page3 = app(YuktiWalletReadService::class)->history((int) $owner->id, page: 3, perPage: 10);
        $capped = app(YuktiWalletReadService::class)->history((int) $owner->id, page: 1, perPage: 500);

        $this->assertSame(25, $page1->total());
        $this->assertCount(10, $page1->items());
        $this->assertCount(5, $page3->items());
        $this->assertSame(YuktiWalletReadService::HISTORY_MAX_PER_PAGE, $capped->perPage());
        $this->assertFalse(collect($page1->items())->contains(fn ($row) => (int) $row['points'] === 999));
    }

    public function test_weekly_progress_and_social_share_not_earned_without_ledger(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-11 12:00:00', 'Asia/Kolkata'));
        Carbon::setTestNow(CarbonImmutable::parse('2026-11-11 12:00:00', 'Asia/Kolkata'));

        $user = $this->user('week@example.com');
        $weekId = YuktiWeekId::for();

        YuktiWeeklyCounter::query()->create([
            'user_id' => $user->id,
            'week_id' => $weekId,
            'metric' => 'login',
            'count' => 1,
            'points_awarded' => 50,
        ]);
        YuktiWeeklyCounter::query()->create([
            'user_id' => $user->id,
            'week_id' => $weekId,
            'metric' => 'tool_complete',
            'count' => 1,
            'points_awarded' => 25,
        ]);
        YuktiSocialShareClaim::query()->create([
            'user_id' => $user->id,
            'week_id' => $weekId,
            'channel' => 'whatsapp',
            'verification_status' => 'pending',
            'idempotency_key' => 'social:'.$user->id.':'.$weekId,
        ]);

        $progress = app(YuktiWalletReadService::class)->weeklyProgress((int) $user->id);

        $this->assertSame($weekId, $progress['week_id']);
        $this->assertSame(1, $progress['metrics']['login']['count']);
        $this->assertSame(0, $progress['metrics']['login']['remaining_count']);
        $this->assertSame(1, $progress['metrics']['tool_complete']['count']);
        $this->assertSame(2, $progress['metrics']['tool_referral']['remaining_count']);
        $this->assertSame(1, $progress['metrics']['expert_referral']['remaining_count']);
        $this->assertFalse($progress['social_share']['earned']);
        $this->assertSame('pending', $progress['social_share']['verification_status']);
        $this->assertFalse($progress['social_share']['eligible_to_earn']);
    }

    public function test_referral_activity_shows_lifecycle_without_adding_estimates_to_available(): void
    {
        $referrer = $this->user('referrer@example.com');
        $joined = $this->user('joined@example.com');
        $activated = $this->user('activated@example.com');
        $rewarded = $this->user('rewarded@example.com');

        YuktiWallet::query()->create([
            'user_id' => $referrer->id,
            'available_points' => 50,
            'pending_points' => 0,
        ]);

        ReferralAttribution::query()->create([
            'referrer_user_id' => $referrer->id,
            'referee_user_id' => $joined->id,
            'referral_code' => 'CODEJOIN01',
            'referral_type' => 'tool',
            'target_type' => 'tool',
            'target_id' => 1,
            'status' => 'joined',
            'idempotency_key' => 'signup:'.$joined->id,
            'signed_up_at' => now(),
        ]);
        ReferralAttribution::query()->create([
            'referrer_user_id' => $referrer->id,
            'referee_user_id' => $activated->id,
            'referral_code' => 'CODEACT001',
            'referral_type' => 'tool',
            'target_type' => 'tool',
            'target_id' => 1,
            'status' => 'activated',
            'idempotency_key' => 'signup:'.$activated->id,
            'signed_up_at' => now()->subDay(),
            'activated_at' => now(),
            'activation_meta' => ['kind' => 'tool_completion', 'activated_week_id' => '2026-W46'],
        ]);
        ReferralAttribution::query()->create([
            'referrer_user_id' => $referrer->id,
            'referee_user_id' => $rewarded->id,
            'referral_code' => 'CODEREW001',
            'referral_type' => 'expert',
            'target_type' => 'professional',
            'target_id' => 9,
            'status' => 'rewarded',
            'idempotency_key' => 'signup:'.$rewarded->id,
            'signed_up_at' => now()->subDays(2),
            'activated_at' => now()->subDay(),
            'rewarded_at' => now(),
        ]);

        $svc = app(YuktiWalletReadService::class);
        $activity = $svc->referralActivity((int) $referrer->id);
        $summary = $svc->summary((int) $referrer->id);

        $this->assertCount(3, $activity['as_referrer']);
        $statuses = collect($activity['as_referrer'])->pluck('status')->sort()->values()->all();
        $this->assertSame(['activated', 'joined', 'rewarded'], $statuses);

        $activatedRow = collect($activity['as_referrer'])->firstWhere('status', 'activated');
        $this->assertSame(50, $activatedRow['estimate_points']);
        $this->assertStringContainsString('Estimate only', $activatedRow['estimate_label']);
        $this->assertFalse($activatedRow['counts_toward_available_balance']);

        $this->assertSame(50, $activity['activated_estimate_points']);
        $this->assertSame(50, $summary['available_points']);
        $this->assertNotSame(
            $summary['available_points'] + $activity['activated_estimate_points'],
            $summary['available_points'],
            'Estimates must not be folded into available points.'
        );
    }

    public function test_authenticated_customer_can_read_own_wallet_json(): void
    {
        $user = $this->user('api@example.com');
        YuktiWallet::query()->create([
            'user_id' => $user->id,
            'available_points' => 12,
            'pending_points' => 0,
        ]);

        $this->actingAs($user, 'user')
            ->getJson(route('user.yukti-points.index'))
            ->assertOk()
            ->assertJsonPath('summary.available_points', 12)
            ->assertJsonPath('summary.user_id', $user->id)
            ->assertJsonPath('redemption_architecture.executable', false);

        $this->actingAs($user, 'user')
            ->getJson(route('user.yukti-points.history'))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total']]);

        $this->actingAs($user, 'user')
            ->getJson(route('user.yukti-points.referrals'))
            ->assertOk();

        $this->actingAs($user, 'user')
            ->getJson(route('user.yukti-points.weekly'))
            ->assertOk()
            ->assertJsonStructure(['week_id', 'metrics', 'social_share']);
    }

    public function test_guest_and_other_guards_cannot_access_wallet_routes(): void
    {
        $this->getJson(route('user.yukti-points.index'))->assertUnauthorized();

        $admin = Admin::query()->create(['name' => 'Admin', 'email' => 'admin@example.com']);
        $this->actingAs($admin, 'admin')
            ->getJson(route('user.yukti-points.index'))
            ->assertUnauthorized();

        $pro = Professional::query()->create([
            'name' => 'Pro',
            'email' => 'pro@example.com',
            'status' => 'approved',
            'active' => true,
        ]);
        $this->actingAs($pro, 'professional')
            ->getJson(route('user.yukti-points.index'))
            ->assertUnauthorized();
    }

    public function test_no_wallet_mutation_or_redeem_routes_exist(): void
    {
        $uris = collect(Route::getRoutes())->map(fn ($r) => strtolower($r->uri()));
        $methods = collect(Route::getRoutes())->map(fn ($r) => [
            'uri' => strtolower($r->uri()),
            'methods' => $r->methods(),
        ]);

        $this->assertTrue($uris->contains('customer/yukti-points'));
        $mutating = $methods->filter(function ($row) {
            if (! str_contains($row['uri'], 'yukti-points') && ! str_contains($row['uri'], 'yukti')) {
                return false;
            }
            $write = array_intersect($row['methods'], ['POST', 'PUT', 'PATCH', 'DELETE']);

            return $write !== [];
        });

        $this->assertCount(0, $mutating);
        $this->assertFalse($uris->contains(fn ($uri) => str_contains($uri, 'redeem')));
        $this->assertFalse($uris->contains(fn ($uri) => str_contains($uri, 'grant')));
    }

    public function test_redemption_architecture_documents_blockers_without_rupee_rate(): void
    {
        $snap = YuktiRedemptionArchitecture::snapshot();
        $this->assertFalse($snap['executable']);
        $this->assertContains('conversion_rate_unapproved', $snap['blockers']);
        $this->assertContains('booking_refund_model_missing', $snap['blockers']);
        $this->assertSame('activation_at', config('yuktione_rewards.referral_accounting_week'));
        $this->assertFalse(config('yuktione_rewards.redemption.enabled'));
    }

    public function test_service_ignores_request_user_id_spoofing_via_controller_binding(): void
    {
        $owner = $this->user('real@example.com');
        $victim = $this->user('victim@example.com');
        YuktiWallet::query()->create(['user_id' => $owner->id, 'available_points' => 5, 'pending_points' => 0]);
        YuktiWallet::query()->create(['user_id' => $victim->id, 'available_points' => 9000, 'pending_points' => 0]);

        $this->actingAs($owner, 'user')
            ->getJson(route('user.yukti-points.index', ['user_id' => $victim->id]))
            ->assertOk()
            ->assertJsonPath('summary.available_points', 5)
            ->assertJsonPath('summary.user_id', $owner->id);
    }

    private function ledger(
        int $userId,
        string $direction,
        int $points,
        string $eventType,
        string $status,
        ?int $availableAfter = null,
        ?int $pendingAfter = null,
    ): void {
        YuktiRewardLedgerEntry::query()->create([
            'user_id' => $userId,
            'direction' => $direction,
            'points' => $points,
            'available_after' => $availableAfter,
            'pending_after' => $pendingAfter,
            'event_type' => $eventType,
            'source_type' => 'test',
            'source_id' => null,
            'week_id' => YuktiWeekId::for(),
            'status' => $status,
            'idempotency_key' => uniqid($eventType.':'.$userId.':', true),
            'meta' => null,
            'rewarded_at' => now(),
            'created_at' => now(),
        ]);
    }

    private function user(string $email): User
    {
        return User::query()->create([
            'name' => 'QA',
            'email' => $email,
            'password' => bcrypt('secret'),
            'registration_completed' => true,
        ]);
    }
}
