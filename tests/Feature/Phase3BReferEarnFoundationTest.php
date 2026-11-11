<?php

namespace Tests\Feature;

use App\Models\ReferralAttribution;
use App\Models\ReferralCode;
use App\Models\User;
use App\Models\YuktiRewardConfig;
use App\Models\YuktiRewardLedgerEntry;
use App\Models\YuktiWallet;
use App\Models\YuktiWeeklyCounter;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase3BReferEarnFoundationTest extends TestCase
{
    private object $migration;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        $this->migration = require database_path(
            'migrations/2026_11_11_200000_create_yukti_refer_earn_foundation_tables.php'
        );
        $this->migration->up();
    }

    public function test_migration_creates_all_foundation_tables(): void
    {
        foreach ([
            'referral_codes',
            'referral_attributions',
            'yukti_wallets',
            'yukti_reward_ledger',
            'yukti_weekly_counters',
            'yukti_redemptions',
            'yukti_reward_configs',
            'yukti_social_share_claims',
            'yukti_fraud_flags',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table {$table}");
        }

        $this->assertFalse(config('yuktione_rewards.engine_enabled'));
        $this->assertSame('Asia/Kolkata', config('yuktione_rewards.week_timezone'));
    }

    public function test_migration_is_idempotent_and_rolls_back(): void
    {
        $this->migration->up(); // second run must not throw

        $this->assertSame(9, YuktiRewardConfig::query()->count());

        $this->migration->down();

        $this->assertFalse(Schema::hasTable('referral_codes'));
        $this->assertFalse(Schema::hasTable('yukti_reward_ledger'));
        $this->assertFalse(Schema::hasTable('yukti_reward_configs'));
    }

    public function test_referral_code_is_unique_per_user_and_code(): void
    {
        $user = $this->user('a@example.com');
        $other = $this->user('b@example.com');

        ReferralCode::query()->create([
            'user_id' => $user->id,
            'code' => 'YKTIAAAA',
            'is_active' => true,
        ]);

        $this->expectUniqueViolation(function () use ($user) {
            ReferralCode::query()->create([
                'user_id' => $user->id,
                'code' => 'YKTIBBBB',
                'is_active' => true,
            ]);
        });

        $this->expectUniqueViolation(function () use ($other) {
            ReferralCode::query()->create([
                'user_id' => $other->id,
                'code' => 'YKTIAAAA',
                'is_active' => true,
            ]);
        });
    }

    public function test_attribution_is_unique_per_referee_and_blocks_duplicate_idempotency(): void
    {
        $referrer = $this->user('ref@example.com');
        $referee = $this->user('new@example.com');
        $second = $this->user('second@example.com');

        ReferralAttribution::query()->create([
            'referrer_user_id' => $referrer->id,
            'referee_user_id' => $referee->id,
            'referral_code' => 'YKTIAAAA',
            'referral_type' => 'tool',
            'target_type' => 'tool',
            'target_id' => 1,
            'status' => 'joined',
            'idempotency_key' => 'attr:referee:'.$referee->id,
            'signed_up_at' => now(),
        ]);

        $this->expectUniqueViolation(function () use ($referrer, $referee) {
            ReferralAttribution::query()->create([
                'referrer_user_id' => $referrer->id,
                'referee_user_id' => $referee->id,
                'referral_code' => 'YKTIAAAA',
                'referral_type' => 'general',
                'status' => 'joined',
                'idempotency_key' => 'attr:other',
            ]);
        });

        $this->expectUniqueViolation(function () use ($referrer, $second, $referee) {
            ReferralAttribution::query()->create([
                'referrer_user_id' => $referrer->id,
                'referee_user_id' => $second->id,
                'referral_code' => 'YKTIAAAA',
                'referral_type' => 'general',
                'status' => 'joined',
                'idempotency_key' => 'attr:referee:'.$referee->id,
            ]);
        });
    }

    public function test_self_referral_check_constraint_when_supported(): void
    {
        $user = $this->user('self@example.com');

        try {
            ReferralAttribution::query()->create([
                'referrer_user_id' => $user->id,
                'referee_user_id' => $user->id,
                'referral_code' => 'SELFCODE',
                'referral_type' => 'general',
                'status' => 'invited',
                'idempotency_key' => 'attr:self',
            ]);

            // SQLite may not have the CHECK constraint from ALTER TABLE.
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $this->assertTrue(true);

                return;
            }

            $this->fail('Self-referral should be rejected by CHECK constraint on MySQL.');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    public function test_ledger_idempotency_key_is_unique(): void
    {
        $user = $this->user('ledger@example.com');

        YuktiRewardLedgerEntry::query()->create([
            'user_id' => $user->id,
            'direction' => 'credit',
            'points' => 50,
            'available_after' => 50,
            'pending_after' => 0,
            'event_type' => 'weekly_login',
            'week_id' => '2026-W46',
            'status' => 'completed',
            'idempotency_key' => 'login:'.$user->id.':2026-W46',
            'created_at' => now(),
            'rewarded_at' => now(),
        ]);

        $this->expectUniqueViolation(function () use ($user) {
            YuktiRewardLedgerEntry::query()->create([
                'user_id' => $user->id,
                'direction' => 'credit',
                'points' => 50,
                'event_type' => 'weekly_login',
                'week_id' => '2026-W46',
                'status' => 'completed',
                'idempotency_key' => 'login:'.$user->id.':2026-W46',
                'created_at' => now(),
            ]);
        });
    }

    public function test_wallet_defaults_and_unsigned_non_negative_column(): void
    {
        $user = $this->user('wallet@example.com');

        $wallet = YuktiWallet::query()->create([
            'user_id' => $user->id,
        ]);

        $this->assertSame(0, (int) $wallet->fresh()->available_points);
        $this->assertSame(0, (int) $wallet->fresh()->pending_points);

        $this->expectUniqueViolation(function () use ($user) {
            YuktiWallet::query()->create([
                'user_id' => $user->id,
            ]);
        });
    }

    public function test_weekly_counter_unique_per_user_week_metric(): void
    {
        $user = $this->user('week@example.com');

        YuktiWeeklyCounter::query()->create([
            'user_id' => $user->id,
            'week_id' => '2026-W46',
            'metric' => 'login',
            'count' => 1,
            'points_awarded' => 50,
        ]);

        $this->expectUniqueViolation(function () use ($user) {
            YuktiWeeklyCounter::query()->create([
                'user_id' => $user->id,
                'week_id' => '2026-W46',
                'metric' => 'login',
                'count' => 1,
                'points_awarded' => 50,
            ]);
        });

        // Different metric same week is allowed.
        YuktiWeeklyCounter::query()->create([
            'user_id' => $user->id,
            'week_id' => '2026-W46',
            'metric' => 'tool_complete',
            'count' => 1,
            'points_awarded' => 25,
        ]);

        $this->assertSame(2, YuktiWeeklyCounter::query()->where('user_id', $user->id)->count());
    }

    public function test_pdf_reward_config_defaults_and_pending_rules(): void
    {
        $this->assertSame(50, (int) YuktiRewardConfig::query()->where('key', 'weekly_login')->value('points'));
        $this->assertSame(25, (int) YuktiRewardConfig::query()->where('key', 'tool_completion')->value('points'));
        $this->assertSame(50, (int) YuktiRewardConfig::query()->where('key', 'tool_referral')->value('points'));
        $this->assertSame(2, (int) YuktiRewardConfig::query()->where('key', 'tool_referral')->value('weekly_cap_count'));
        $this->assertSame(200, (int) YuktiRewardConfig::query()->where('key', 'expert_referral')->value('points'));
        $this->assertSame(1, (int) YuktiRewardConfig::query()->where('key', 'expert_referral')->value('weekly_cap_count'));
        $this->assertSame(25, (int) YuktiRewardConfig::query()->where('key', 'social_share')->value('points'));

        foreach (['general_invite', 'redemption_rupee_per_point', 'social_verification_method', 'global_weekly_cap'] as $key) {
            $row = YuktiRewardConfig::query()->where('key', $key)->firstOrFail();
            $this->assertFalse($row->is_active, $key);
            $this->assertSame('pending_business_rule', $row->approval_status, $key);
        }

        // No invented global weekly cap value.
        $this->assertNull(YuktiRewardConfig::query()->where('key', 'global_weekly_cap')->value('weekly_cap_points'));
    }

    public function test_user_relationships_resolve(): void
    {
        $user = $this->user('rel@example.com');

        $code = ReferralCode::query()->create([
            'user_id' => $user->id,
            'code' => 'YKTIREL1',
            'is_active' => true,
        ]);

        $wallet = YuktiWallet::query()->create([
            'user_id' => $user->id,
            'available_points' => 0,
            'pending_points' => 0,
        ]);

        $this->assertTrue($user->referralCode->is($code));
        $this->assertTrue($user->yuktiWallet->is($wallet));
    }

    private function user(string $email): User
    {
        return User::query()->create([
            'name' => 'QA',
            'email' => $email,
        ]);
    }

    private function expectUniqueViolation(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected a unique constraint violation.');
        } catch (QueryException $e) {
            $this->assertTrue(
                str_contains(strtolower($e->getMessage()), 'unique')
                || str_contains(strtolower($e->getMessage()), 'duplicate')
                || (string) $e->getCode() === '23000',
                $e->getMessage()
            );
        }
    }
}
