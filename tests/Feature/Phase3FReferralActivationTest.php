<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingTimedate;
use App\Models\ReferralAttribution;
use App\Models\ReferralCode;
use App\Models\Tool;
use App\Models\User;
use App\Models\YuktiRewardLedgerEntry;
use App\Models\YuktiWallet;
use App\Models\YuktiWeeklyCounter;
use App\Services\YuktiRewards\ReferralActivationService;
use App\Services\YuktiRewards\YuktiRewardEngine;
use App\Services\YuktiRewards\YuktiRewardResult;
use App\Services\YuktiRewards\YuktiWeekId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase3FReferralActivationTest extends TestCase
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
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->boolean('email_verified')->default(false);
            $table->boolean('registration_completed')->default(false);
            $table->string('registration_status')->nullable();
            $table->timestamp('password_set_at')->nullable();
            $table->timestamps();
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('module_key')->nullable();
            $table->string('status')->default('coming_soon');
            $table->string('route_name')->nullable();
            $table->boolean('is_implemented')->default(false);
            $table->timestamps();
        });

        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('approved');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('professional_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('status')->default('pending');
            $table->string('customer_name')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_timedates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->date('date')->nullable();
            $table->string('time_slot')->nullable();
            $table->string('status')->default('pending');
            $table->text('remarks')->nullable();
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
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function freezeAt(string $istDateTime): void
    {
        $at = CarbonImmutable::parse($istDateTime, 'Asia/Kolkata');
        CarbonImmutable::setTestNow($at);
        Carbon::setTestNow($at);
    }

    public function test_valid_tool_referral_activates_but_does_not_reward_when_engine_disabled(): void
    {
        [$referrer, $referee, $tool] = $this->toolReferralFixture();

        $result = app(ReferralActivationService::class)->activateToolReferral(
            (int) $referee->id,
            'daily-mood-check-in',
            'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            101,
        );

        $this->assertSame('activated_engine_disabled', $result['status']);
        $this->assertSame('activated', $result['attribution']->status);
        $this->assertNotNull($result['attribution']->activated_at);
        $this->assertSame('tool_completion', $result['attribution']->activation_meta['kind']);
        $this->assertSame(YuktiRewardResult::STATUS_DISABLED, $result['reward']->status);
        $this->assertSame(0, YuktiRewardLedgerEntry::query()->count());
        $this->assertSame(0, YuktiWallet::query()->count());
    }

    public function test_valid_tool_referral_rewards_when_engine_enabled(): void
    {
        $this->enableEngine();
        $this->freezeAt('2026-11-11 12:00:00');
        [$referrer, $referee, $tool] = $this->toolReferralFixture();

        $result = app(ReferralActivationService::class)->activateToolReferral(
            (int) $referee->id,
            'daily-mood-check-in',
            'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            102,
        );

        $this->assertSame('rewarded', $result['status']);
        $this->assertSame('rewarded', $result['attribution']->status);
        $this->assertTrue($result['reward']->granted);
        $this->assertSame(50, $result['reward']->points);
        $this->assertSame(50, (int) YuktiWallet::query()->where('user_id', $referrer->id)->value('available_points'));
        $this->assertSame(1, YuktiWeeklyCounter::query()->where('metric', 'tool_referral')->count());
    }

    public function test_wrong_tool_is_rejected(): void
    {
        $this->enableEngine();
        [$referrer, $referee] = $this->toolReferralFixture();
        Tool::query()->create([
            'name' => 'Journal',
            'slug' => 'digital-journal',
            'status' => 'active',
            'route_name' => 'tools.psychology.journal',
            'is_implemented' => true,
        ]);

        $result = app(ReferralActivationService::class)->activateToolReferral(
            (int) $referee->id,
            'digital-journal',
            'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
            103,
        );

        $this->assertSame('wrong_tool', $result['status']);
        $this->assertSame('joined', ReferralAttribution::query()->where('referee_user_id', $referee->id)->value('status'));
        $this->assertSame(0, YuktiRewardLedgerEntry::query()->count());
    }

    public function test_wrong_customer_has_no_attribution(): void
    {
        $this->enableEngine();
        [$referrer, $referee] = $this->toolReferralFixture();
        $stranger = $this->completedUser('stranger@example.com');

        $result = app(ReferralActivationService::class)->activateToolReferral(
            (int) $stranger->id,
            'daily-mood-check-in',
            'dddddddd-dddd-4ddd-8ddd-dddddddddddd',
            104,
        );

        $this->assertSame('no_attribution', $result['status']);
        $this->assertSame(0, YuktiRewardLedgerEntry::query()->count());
    }

    public function test_duplicate_tool_completion_does_not_double_reward(): void
    {
        $this->enableEngine();
        $this->freezeAt('2026-11-11 13:00:00');
        [$referrer, $referee] = $this->toolReferralFixture();
        $svc = app(ReferralActivationService::class);

        $first = $svc->activateToolReferral((int) $referee->id, 'daily-mood-check-in', 'key-1', 1);
        $second = $svc->activateToolReferral((int) $referee->id, 'daily-mood-check-in', 'key-2', 2);

        $this->assertSame('rewarded', $first['status']);
        $this->assertTrue(in_array($second['status'], ['already_rewarded', 'rewarded'], true));
        $this->assertSame(1, YuktiRewardLedgerEntry::query()->count());
        $this->assertSame(50, (int) YuktiWallet::query()->where('user_id', $referrer->id)->value('available_points'));
    }

    public function test_tool_referral_weekly_cap_of_two(): void
    {
        $this->enableEngine();
        $this->freezeAt('2026-11-11 14:00:00');
        $referrer = $this->completedUser('cap-ref@example.com');
        ReferralCode::query()->create(['user_id' => $referrer->id, 'code' => 'CAPTOOL001', 'is_active' => true]);
        $tool = $this->moodTool();

        $results = [];
        for ($i = 1; $i <= 3; $i++) {
            $referee = $this->completedUser("cap-ref{$i}@example.com");
            $this->joinedToolAttribution($referrer, $referee, $tool, "signup:{$referee->id}");
            $results[] = app(ReferralActivationService::class)->activateToolReferral(
                (int) $referee->id,
                'daily-mood-check-in',
                "key-cap-{$i}",
                $i,
            );
        }

        $this->assertSame('rewarded', $results[0]['status']);
        $this->assertSame('rewarded', $results[1]['status']);
        $this->assertSame('activated_weekly_cap', $results[2]['status']);
        $this->assertSame('activated', $results[2]['attribution']->status);
        $this->assertSame(100, (int) YuktiWallet::query()->where('user_id', $referrer->id)->value('available_points'));
        $this->assertSame(2, YuktiRewardLedgerEntry::query()->count());
    }

    public function test_ineligible_water_tracker_does_not_activate(): void
    {
        $this->enableEngine();
        [$referrer, $referee] = $this->toolReferralFixture();
        Tool::query()->create([
            'name' => 'Water',
            'slug' => 'water-intake-tracker',
            'status' => 'active',
            'route_name' => 'tools.water-intake',
            'is_implemented' => true,
        ]);

        // Re-point attribution at water for a realistic wrong-eligibility case.
        $attr = ReferralAttribution::query()->where('referee_user_id', $referee->id)->firstOrFail();
        $water = Tool::query()->where('slug', 'water-intake-tracker')->firstOrFail();
        $attr->target_id = $water->id;
        $attr->save();

        $result = app(ReferralActivationService::class)->activateToolReferral(
            (int) $referee->id,
            'water-intake-tracker',
            'water-key',
            9,
        );

        $this->assertSame('ineligible_tool', $result['status']);
        $this->assertSame('joined', $attr->fresh()->status);
        $this->assertSame(0, YuktiRewardLedgerEntry::query()->count());
    }

    public function test_disabled_engine_preserves_activation_and_settle_resumes(): void
    {
        $this->freezeAt('2026-11-11 15:00:00');
        [$referrer, $referee] = $this->toolReferralFixture();
        $svc = app(ReferralActivationService::class);

        $disabled = $svc->activateToolReferral((int) $referee->id, 'daily-mood-check-in', 'resume-key', 11);
        $this->assertSame('activated_engine_disabled', $disabled['status']);
        $this->assertSame(0, YuktiRewardLedgerEntry::query()->count());

        $this->enableEngine();
        $settled = $svc->settleActivated($disabled['attribution']->fresh());

        $this->assertSame('rewarded', $settled['status']);
        $this->assertSame('rewarded', $settled['attribution']->status);
        $this->assertSame(50, (int) YuktiWallet::query()->where('user_id', $referrer->id)->value('available_points'));
        $this->assertSame(
            YuktiWeekId::for(CarbonImmutable::parse('2026-11-11 15:00:00', 'Asia/Kolkata')),
            YuktiWeeklyCounter::query()->where('metric', 'tool_referral')->value('week_id')
        );
    }

    public function test_valid_paid_and_completed_expert_booking_rewards(): void
    {
        $this->enableEngine();
        $this->freezeAt('2026-11-11 16:00:00');
        [$referrer, $referee, $booking] = $this->expertReferralFixture(payment: 'paid', sessionStatus: 'completed');

        $result = app(ReferralActivationService::class)->activateExpertReferral((int) $booking->id);

        $this->assertSame('rewarded', $result['status']);
        $this->assertSame(200, $result['reward']->points);
        $this->assertSame(200, (int) YuktiWallet::query()->where('user_id', $referrer->id)->value('available_points'));
        $this->assertSame(1, YuktiWeeklyCounter::query()->where('metric', 'expert_referral')->count());
    }

    public function test_paid_but_pending_session_is_rejected(): void
    {
        $this->enableEngine();
        [$referrer, $referee, $booking] = $this->expertReferralFixture(payment: 'paid', sessionStatus: 'pending');

        $result = app(ReferralActivationService::class)->activateExpertReferral((int) $booking->id);

        $this->assertSame('sessions_incomplete', $result['status']);
        $this->assertSame('joined', ReferralAttribution::query()->where('referee_user_id', $referee->id)->value('status'));
        $this->assertSame(0, YuktiRewardLedgerEntry::query()->count());
    }

    public function test_completed_but_unpaid_booking_is_rejected(): void
    {
        $this->enableEngine();
        [$referrer, $referee, $booking] = $this->expertReferralFixture(payment: 'pending', sessionStatus: 'completed');

        $result = app(ReferralActivationService::class)->activateExpertReferral((int) $booking->id);

        $this->assertSame('payment_not_paid', $result['status']);
        $this->assertSame(0, YuktiRewardLedgerEntry::query()->count());
    }

    public function test_failed_payment_booking_is_rejected(): void
    {
        $this->enableEngine();
        [$referrer, $referee, $booking] = $this->expertReferralFixture(payment: 'failed', sessionStatus: 'completed');

        $result = app(ReferralActivationService::class)->activateExpertReferral((int) $booking->id);

        $this->assertSame('payment_not_paid', $result['status']);
        $this->assertSame(0, YuktiRewardLedgerEntry::query()->count());
    }

    public function test_wrong_expert_is_rejected(): void
    {
        $this->enableEngine();
        [$referrer, $referee, $booking] = $this->expertReferralFixture(payment: 'paid', sessionStatus: 'completed');
        $otherPro = \App\Models\Professional::query()->create([
            'name' => 'Other',
            'email' => 'other-pro@example.com',
            'status' => 'approved',
            'active' => true,
        ]);
        $booking->professional_id = $otherPro->id;
        $booking->save();

        $result = app(ReferralActivationService::class)->activateExpertReferral((int) $booking->id);

        $this->assertSame('wrong_expert', $result['status']);
        $this->assertSame(0, YuktiRewardLedgerEntry::query()->count());
    }

    public function test_duplicate_expert_activation_is_idempotent(): void
    {
        $this->enableEngine();
        $this->freezeAt('2026-11-11 17:00:00');
        [$referrer, $referee, $booking] = $this->expertReferralFixture(payment: 'paid', sessionStatus: 'completed');
        $svc = app(ReferralActivationService::class);

        $first = $svc->activateExpertReferral((int) $booking->id);
        $second = $svc->activateExpertReferral((int) $booking->id);

        $this->assertSame('rewarded', $first['status']);
        $this->assertTrue(in_array($second['status'], ['already_rewarded', 'rewarded'], true));
        $this->assertSame(1, YuktiRewardLedgerEntry::query()->count());
        $this->assertSame(200, (int) YuktiWallet::query()->where('user_id', $referrer->id)->value('available_points'));
    }

    public function test_expert_referral_weekly_cap_of_one(): void
    {
        $this->enableEngine();
        $this->freezeAt('2026-11-11 18:00:00');
        $referrer = $this->completedUser('expert-cap@example.com');
        ReferralCode::query()->create(['user_id' => $referrer->id, 'code' => 'CAPEXPERT1', 'is_active' => true]);

        $first = $this->expertReferralFixture(payment: 'paid', sessionStatus: 'completed', referrer: $referrer, emailPrefix: 'e1');
        $second = $this->expertReferralFixture(payment: 'paid', sessionStatus: 'completed', referrer: $referrer, emailPrefix: 'e2');

        $r1 = app(ReferralActivationService::class)->activateExpertReferral((int) $first[2]->id);
        $r2 = app(ReferralActivationService::class)->activateExpertReferral((int) $second[2]->id);

        $this->assertSame('rewarded', $r1['status']);
        $this->assertSame('activated_weekly_cap', $r2['status']);
        $this->assertSame(200, (int) YuktiWallet::query()->where('user_id', $referrer->id)->value('available_points'));
        $this->assertSame(1, YuktiRewardLedgerEntry::query()->count());
    }

    public function test_monday_week_boundary_for_tool_referral_caps(): void
    {
        $this->enableEngine();
        $referrer = $this->completedUser('week-ref@example.com');
        ReferralCode::query()->create(['user_id' => $referrer->id, 'code' => 'WEEKTOOL01', 'is_active' => true]);
        $tool = $this->moodTool();

        $this->freezeAt('2026-11-08 23:59:00');
        $weekA = YuktiWeekId::for();
        $r1 = $this->completedUser('week-a1@example.com');
        $this->joinedToolAttribution($referrer, $r1, $tool, 'signup:'.$r1->id);
        $a1 = app(ReferralActivationService::class)->activateToolReferral((int) $r1->id, 'daily-mood-check-in', 'wa1', 1);
        $r2 = $this->completedUser('week-a2@example.com');
        $this->joinedToolAttribution($referrer, $r2, $tool, 'signup:'.$r2->id);
        $a2 = app(ReferralActivationService::class)->activateToolReferral((int) $r2->id, 'daily-mood-check-in', 'wa2', 2);

        $this->freezeAt('2026-11-09 00:00:00');
        $weekB = YuktiWeekId::for();
        $this->assertNotSame($weekA, $weekB);
        $r3 = $this->completedUser('week-b1@example.com');
        $this->joinedToolAttribution($referrer, $r3, $tool, 'signup:'.$r3->id);
        $b1 = app(ReferralActivationService::class)->activateToolReferral((int) $r3->id, 'daily-mood-check-in', 'wb1', 3);

        $this->assertSame('rewarded', $a1['status']);
        $this->assertSame('rewarded', $a2['status']);
        $this->assertSame('rewarded', $b1['status']);
        $this->assertSame(150, (int) YuktiWallet::query()->where('user_id', $referrer->id)->value('available_points'));
        $this->assertSame(2, (int) YuktiWeeklyCounter::query()->where('week_id', $weekA)->where('metric', 'tool_referral')->value('count'));
        $this->assertSame(1, (int) YuktiWeeklyCounter::query()->where('week_id', $weekB)->where('metric', 'tool_referral')->value('count'));
    }

    public function test_multi_session_booking_requires_all_sessions_completed(): void
    {
        $this->enableEngine();
        [$referrer, $referee, $booking] = $this->expertReferralFixture(payment: 'paid', sessionStatus: 'completed');
        BookingTimedate::query()->create([
            'booking_id' => $booking->id,
            'date' => '2026-11-20',
            'time_slot' => '11:00',
            'status' => 'pending',
        ]);

        $result = app(ReferralActivationService::class)->activateExpertReferral((int) $booking->id);

        $this->assertSame('sessions_incomplete', $result['status']);
        $this->assertSame(0, YuktiRewardLedgerEntry::query()->count());
    }

    /**
     * @return array{0:User,1:User,2:Tool}
     */
    private function toolReferralFixture(): array
    {
        $referrer = $this->completedUser('tool-referrer@example.com');
        $referee = $this->completedUser('tool-referee@example.com');
        ReferralCode::query()->create([
            'user_id' => $referrer->id,
            'code' => 'TOOLREF001',
            'is_active' => true,
        ]);
        $tool = $this->moodTool();
        $this->joinedToolAttribution($referrer, $referee, $tool, 'signup:'.$referee->id);

        return [$referrer, $referee, $tool];
    }

    /**
     * @return array{0:User,1:User,2:Booking}
     */
    private function expertReferralFixture(
        string $payment,
        string $sessionStatus,
        ?User $referrer = null,
        string $emailPrefix = 'expert',
    ): array {
        $referrer = $referrer ?? $this->completedUser($emailPrefix.'-referrer@example.com');
        $referee = $this->completedUser($emailPrefix.'-referee@example.com');
        if (! ReferralCode::query()->where('user_id', $referrer->id)->exists()) {
            ReferralCode::query()->create([
                'user_id' => $referrer->id,
                'code' => strtoupper(substr($emailPrefix.'CODE0000', 0, 10)),
                'is_active' => true,
            ]);
        }

        $pro = \App\Models\Professional::query()->create([
            'name' => 'Expert '.$emailPrefix,
            'email' => $emailPrefix.'-pro@example.com',
            'status' => 'approved',
            'active' => true,
        ]);

        ReferralAttribution::query()->create([
            'referrer_user_id' => $referrer->id,
            'referee_user_id' => $referee->id,
            'referral_code' => ReferralCode::query()->where('user_id', $referrer->id)->value('code'),
            'referral_type' => 'expert',
            'target_type' => 'professional',
            'target_id' => $pro->id,
            'status' => 'joined',
            'idempotency_key' => 'signup:'.$referee->id,
            'signed_up_at' => now()->subHour(),
        ]);

        $booking = Booking::query()->create([
            'user_id' => $referee->id,
            'professional_id' => $pro->id,
            'payment_status' => $payment,
            'status' => 'pending',
            'customer_name' => 'Referee',
        ]);

        BookingTimedate::query()->create([
            'booking_id' => $booking->id,
            'date' => '2026-11-15',
            'time_slot' => '10:00',
            'status' => $sessionStatus,
        ]);

        return [$referrer, $referee, $booking->fresh('timedates')];
    }

    private function moodTool(): Tool
    {
        return Tool::query()->firstOrCreate(
            ['slug' => 'daily-mood-check-in'],
            [
                'name' => 'Daily Mood Check-in',
                'status' => 'active',
                'route_name' => 'tools.psychology.mood',
                'is_implemented' => true,
            ]
        );
    }

    private function joinedToolAttribution(User $referrer, User $referee, Tool $tool, string $key): ReferralAttribution
    {
        return ReferralAttribution::query()->create([
            'referrer_user_id' => $referrer->id,
            'referee_user_id' => $referee->id,
            'referral_code' => ReferralCode::query()->where('user_id', $referrer->id)->value('code') ?? 'FALLBACK01',
            'referral_type' => 'tool',
            'target_type' => 'tool',
            'target_id' => $tool->id,
            'status' => 'joined',
            'idempotency_key' => $key,
            'signed_up_at' => now()->subHour(),
        ]);
    }

    private function enableEngine(): void
    {
        Config::set('yuktione_rewards.engine_enabled', true);
    }

    private function completedUser(string $email): User
    {
        return User::query()->create([
            'name' => 'QA',
            'email' => $email,
            'password' => bcrypt('secret'),
            'registration_completed' => true,
            'registration_status' => 'completed',
            'password_set_at' => now(),
        ]);
    }
}
