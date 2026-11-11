<?php

namespace Tests\Feature;

use App\Listeners\AwardWeeklyLoginYuktiPoints;
use App\Models\Admin;
use App\Models\Professional;
use App\Models\PsychologyMoodCheckin;
use App\Models\User;
use App\Models\YuktiRewardLedgerEntry;
use App\Models\YuktiWallet;
use App\Models\YuktiWeeklyCounter;
use App\Services\MoodCheckinDraft;
use App\Services\MoodCheckinRecorder;
use App\Services\YuktiRewards\YuktiCustomerRewardHooks;
use App\Services\YuktiRewards\YuktiRewardEngine;
use App\Services\YuktiRewards\YuktiRewardIdempotency;
use App\Services\YuktiRewards\YuktiRewardResult;
use App\Services\YuktiRewards\YuktiRewardSafeDispatcher;
use App\Services\YuktiRewards\YuktiWeekId;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class Phase3EWeeklyLoginAndToolRewardsTest extends TestCase
{
    private object $migration;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
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
            $table->string('status')->default('pending');
            $table->boolean('active')->default(false);
            $table->timestamps();
        });

        Schema::create('psychology_mood_checkins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->uuid('completion_key')->unique();
            $table->string('mood_key');
            $table->json('emotion_keys');
            $table->text('reflection')->nullable();
            $table->timestamp('checked_in_at');
            $table->timestamps();
        });

        $this->migration = require database_path(
            'migrations/2026_11_11_200000_create_yukti_refer_earn_foundation_tables.php'
        );
        $this->migration->up();

        Config::set('yuktione_rewards.week_timezone', 'Asia/Kolkata');
        Config::set('yuktione_rewards.engine_enabled', false);
        Config::set('yuktione_rewards.eligible_tool_completion_slugs', [
            'daily-mood-check-in',
            'digital-journal',
            'gratitude-journal',
            'career-interest-test',
            'skills-strengths-assessment',
            'bmi-bmr-calculator',
            'tarot-reflection',
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_engine_remains_disabled_by_default_config(): void
    {
        $this->assertFalse((bool) config('yuktione_rewards.engine_enabled'));
    }

    public function test_disabled_engine_login_and_tool_hooks_credit_nothing(): void
    {
        $user = $this->user('disabled-hooks@example.com');

        event(new Login('user', $user, false));

        $hooks = app(YuktiCustomerRewardHooks::class);
        $loginResult = $hooks->onCustomerLogin((int) $user->id);
        $toolResult = $hooks->onEligibleToolCompleted(
            (int) $user->id,
            'daily-mood-check-in',
            'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            1,
        );

        $this->assertNotNull($loginResult);
        $this->assertFalse($loginResult->granted);
        $this->assertSame(YuktiRewardResult::STATUS_DISABLED, $loginResult->status);
        $this->assertNotNull($toolResult);
        $this->assertFalse($toolResult->granted);
        $this->assertSame(YuktiRewardResult::STATUS_DISABLED, $toolResult->status);

        $this->assertSame(0, YuktiRewardLedgerEntry::query()->count());
        $this->assertSame(0, YuktiWallet::query()->count());
        $this->assertSame(0, YuktiWeeklyCounter::query()->count());
    }

    public function test_customer_login_awards_once_per_week_when_enabled(): void
    {
        $this->enableEngine();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-11 10:00:00', 'Asia/Kolkata'));

        $user = $this->user('login-once@example.com');
        $weekId = YuktiWeekId::for();

        event(new Login('user', $user, false));
        event(new Login('user', $user, false));

        $this->assertSame(1, YuktiRewardLedgerEntry::query()->count());
        $this->assertSame(50, (int) YuktiWallet::query()->where('user_id', $user->id)->value('available_points'));
        $this->assertSame(
            YuktiRewardIdempotency::weeklyLogin((int) $user->id, $weekId),
            YuktiRewardLedgerEntry::query()->value('idempotency_key')
        );
        $this->assertSame(1, (int) YuktiWeeklyCounter::query()
            ->where('user_id', $user->id)
            ->where('metric', 'login')
            ->where('week_id', $weekId)
            ->value('count'));
    }

    public function test_monday_rollover_allows_new_login_reward(): void
    {
        $this->enableEngine();
        $user = $this->user('login-rollover@example.com');

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-08 23:59:00', 'Asia/Kolkata'));
        $weekA = YuktiWeekId::for();
        event(new Login('user', $user, false));

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-09 00:00:00', 'Asia/Kolkata'));
        $weekB = YuktiWeekId::for();
        $this->assertNotSame($weekA, $weekB);

        event(new Login('user', $user, false));

        $this->assertSame(2, YuktiRewardLedgerEntry::query()->count());
        $this->assertSame(100, (int) YuktiWallet::query()->where('user_id', $user->id)->value('available_points'));
        $this->assertSame(1, YuktiWeeklyCounter::query()->where('week_id', $weekA)->where('metric', 'login')->count());
        $this->assertSame(1, YuktiWeeklyCounter::query()->where('week_id', $weekB)->where('metric', 'login')->count());
    }

    public function test_admin_and_professional_logins_are_excluded(): void
    {
        $this->enableEngine();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-11 12:00:00', 'Asia/Kolkata'));

        $customer = $this->user('customer-only@example.com');
        $admin = Admin::query()->create(['name' => 'Admin', 'email' => 'admin@example.com']);
        $pro = Professional::query()->create([
            'name' => 'Pro',
            'email' => 'pro@example.com',
            'status' => 'approved',
            'active' => true,
        ]);

        event(new Login('admin', $admin, false));
        event(new Login('professional', $pro, false));
        event(new Login('user', $customer, false));

        $this->assertSame(1, YuktiRewardLedgerEntry::query()->count());
        $this->assertSame(
            (int) $customer->id,
            (int) YuktiRewardLedgerEntry::query()->value('user_id')
        );
        $this->assertSame(50, (int) YuktiWallet::query()->where('user_id', $customer->id)->value('available_points'));
        $this->assertSame(1, YuktiWallet::query()->count());
    }

    public function test_award_weekly_login_listener_is_registered_for_login_event(): void
    {
        Event::fake([Login::class]);
        Event::assertListening(Login::class, AwardWeeklyLoginYuktiPoints::class);
    }

    public function test_valid_tool_completion_awards_once_per_week(): void
    {
        $this->enableEngine();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-11 14:00:00', 'Asia/Kolkata'));

        $user = $this->user('tool-once@example.com');
        $hooks = app(YuktiCustomerRewardHooks::class);
        $weekId = YuktiWeekId::for();

        $first = $hooks->onEligibleToolCompleted(
            (int) $user->id,
            'daily-mood-check-in',
            'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            11,
        );
        $secondSameTool = $hooks->onEligibleToolCompleted(
            (int) $user->id,
            'daily-mood-check-in',
            'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
            12,
        );
        $otherToolSameWeek = $hooks->onEligibleToolCompleted(
            (int) $user->id,
            'digital-journal',
            'dddddddd-dddd-4ddd-8ddd-dddddddddddd',
            13,
        );

        $this->assertTrue($first->granted);
        $this->assertSame(25, $first->points);
        $this->assertFalse($secondSameTool->granted);
        $this->assertSame(YuktiRewardResult::STATUS_DUPLICATE, $secondSameTool->status);
        $this->assertFalse($otherToolSameWeek->granted);
        $this->assertSame(YuktiRewardResult::STATUS_DUPLICATE, $otherToolSameWeek->status);

        $this->assertSame(1, YuktiRewardLedgerEntry::query()->count());
        $this->assertSame(25, (int) YuktiWallet::query()->where('user_id', $user->id)->value('available_points'));
        $this->assertSame(
            YuktiRewardIdempotency::toolCompletion((int) $user->id, $weekId),
            YuktiRewardLedgerEntry::query()->value('idempotency_key')
        );
        $this->assertSame(1, (int) YuktiWeeklyCounter::query()
            ->where('metric', 'tool_complete')
            ->where('week_id', $weekId)
            ->value('count'));
    }

    public function test_ineligible_slug_and_abandoned_flow_credit_nothing(): void
    {
        $this->enableEngine();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-11 15:00:00', 'Asia/Kolkata'));

        $user = $this->user('ineligible@example.com');
        $hooks = app(YuktiCustomerRewardHooks::class);

        $water = $hooks->onEligibleToolCompleted(
            (int) $user->id,
            'water-intake-tracker',
            'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',
            1,
        );
        $this->assertNull($water);

        try {
            app(MoodCheckinRecorder::class)->store(app(MoodCheckinDraft::class), null);
            $this->fail('Incomplete mood draft should throw.');
        } catch (InvalidArgumentException) {
            // expected abandoned / incomplete flow
        }

        $this->assertSame(0, PsychologyMoodCheckin::query()->count());
        $this->assertSame(0, YuktiRewardLedgerEntry::query()->count());
        $this->assertSame(0, YuktiWallet::query()->count());
    }

    public function test_mood_recorder_new_completion_awards_and_duplicate_key_does_not(): void
    {
        $this->enableEngine();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-11 16:00:00', 'Asia/Kolkata'));

        $user = $this->user('mood-recorder@example.com');
        $this->actingAs($user, 'user');

        $key = '11111111-1111-4111-8111-111111111111';
        session([
            MoodCheckinDraft::KEY => [
                'step' => 'reflection',
                'mood' => 'good',
                'emotions' => ['calm'],
                'reflection' => 'Phase 3E',
                'reflection_held' => true,
                'completion_key' => $key,
                'awaiting_login' => false,
                'owner' => 'user:'.$user->id,
                'expires_at' => now()->addMinutes(30)->getTimestamp(),
            ],
        ]);

        $recorder = app(MoodCheckinRecorder::class);
        $first = $recorder->store(app(MoodCheckinDraft::class), 'Phase 3E');
        $this->assertTrue($first->wasRecentlyCreated);

        session([
            MoodCheckinDraft::KEY => [
                'step' => 'reflection',
                'mood' => 'good',
                'emotions' => ['calm'],
                'reflection' => 'Phase 3E again',
                'reflection_held' => true,
                'completion_key' => $key,
                'awaiting_login' => false,
                'owner' => 'user:'.$user->id,
                'expires_at' => now()->addMinutes(30)->getTimestamp(),
            ],
        ]);
        $second = $recorder->store(app(MoodCheckinDraft::class), 'Phase 3E again');
        $this->assertFalse($second->wasRecentlyCreated);
        $this->assertSame(1, PsychologyMoodCheckin::query()->count());

        $this->assertSame(1, YuktiRewardLedgerEntry::query()->where('event_type', YuktiRewardEngine::TYPE_TOOL_COMPLETION)->count());
        $this->assertSame(25, (int) YuktiWallet::query()->where('user_id', $user->id)->value('available_points'));
    }

    public function test_customer_ownership_enforced_on_mood_completion_key(): void
    {
        $this->enableEngine();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-11 17:00:00', 'Asia/Kolkata'));

        $owner = $this->user('owner@example.com');
        $intruder = $this->user('intruder@example.com');
        $key = '22222222-2222-4222-8222-222222222222';

        $this->actingAs($owner, 'user');
        session([
            MoodCheckinDraft::KEY => [
                'step' => 'reflection',
                'mood' => 'good',
                'emotions' => ['calm'],
                'reflection' => null,
                'reflection_held' => true,
                'completion_key' => $key,
                'awaiting_login' => false,
                'owner' => 'user:'.$owner->id,
                'expires_at' => now()->addMinutes(30)->getTimestamp(),
            ],
        ]);
        app(MoodCheckinRecorder::class)->store(app(MoodCheckinDraft::class), null);

        $this->actingAs($intruder, 'user');
        session([
            MoodCheckinDraft::KEY => [
                'step' => 'reflection',
                'mood' => 'good',
                'emotions' => ['calm'],
                'reflection' => null,
                'reflection_held' => true,
                'completion_key' => $key,
                'awaiting_login' => false,
                'owner' => 'user:'.$intruder->id,
                'expires_at' => now()->addMinutes(30)->getTimestamp(),
            ],
        ]);

        try {
            app(MoodCheckinRecorder::class)->store(app(MoodCheckinDraft::class), null);
            $this->fail('Intruder must not claim another user completion_key.');
        } catch (RuntimeException) {
            // expected ownership enforcement
        }

        $this->assertSame(1, PsychologyMoodCheckin::query()->count());
        $this->assertSame(25, (int) YuktiWallet::query()->where('user_id', $owner->id)->value('available_points'));
        $this->assertSame(0, YuktiWallet::query()->where('user_id', $intruder->id)->count());
    }

    public function test_safe_dispatcher_logs_failures_without_throwing(): void
    {
        $this->enableEngine();
        $dispatcher = app(YuktiRewardSafeDispatcher::class);

        $result = $dispatcher->tryGrant(
            userId: 999999,
            rewardType: YuktiRewardEngine::TYPE_WEEKLY_LOGIN,
            idempotencyKey: 'login:999999:2026-W46',
        );

        $this->assertNotNull($result);
        $this->assertFalse($result->granted);
        $this->assertSame(YuktiRewardResult::STATUS_USER_NOT_FOUND, $result->status);
        $this->assertSame(0, YuktiRewardLedgerEntry::query()->count());
    }

    public function test_no_public_grant_routes_registered(): void
    {
        $routes = collect(app('router')->getRoutes())->map(fn ($route) => $route->uri());
        $this->assertFalse($routes->contains(fn ($uri) => str_contains($uri, 'yukti') && str_contains($uri, 'grant')));
        $this->assertFalse($routes->contains(fn ($uri) => str_contains($uri, 'rewards/grant')));
    }

    private function enableEngine(): void
    {
        Config::set('yuktione_rewards.engine_enabled', true);
    }

    private function user(string $email): User
    {
        return User::query()->create([
            'name' => 'QA',
            'email' => $email,
            'password' => bcrypt('secret'),
        ]);
    }
}
