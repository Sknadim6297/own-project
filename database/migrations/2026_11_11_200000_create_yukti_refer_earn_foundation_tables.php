<?php

/**
 * Phase 3B — YuktiPoints / Refer & Earn database foundation.
 *
 * Does NOT activate rewards, attribution capture, or redemption flows.
 * Default reward amounts are seeded for later phases; engine stays inactive
 * (see config/yuktione_rewards.php engine_enabled=false).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            throw new \RuntimeException(
                'Cannot create Refer & Earn foundation: users table is missing.'
            );
        }

        $this->createReferralCodes();
        $this->createReferralAttributions();
        $this->createWallets();
        $this->createRewardLedger();
        $this->createWeeklyCounters();
        $this->createRedemptions();
        $this->createRewardConfigs();
        $this->createSocialShareClaims();
        $this->createFraudFlags();
        $this->seedRewardConfigs();
    }

    public function down(): void
    {
        Schema::dropIfExists('yukti_fraud_flags');
        Schema::dropIfExists('yukti_social_share_claims');
        Schema::dropIfExists('yukti_reward_configs');
        Schema::dropIfExists('yukti_redemptions');
        Schema::dropIfExists('yukti_weekly_counters');
        Schema::dropIfExists('yukti_reward_ledger');
        Schema::dropIfExists('yukti_wallets');
        Schema::dropIfExists('referral_attributions');
        Schema::dropIfExists('referral_codes');
    }

    private function createReferralCodes(): void
    {
        if (Schema::hasTable('referral_codes')) {
            return;
        }

        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('code', 32);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('user_id');
            $table->unique('code');
            $table->index(['is_active', 'code']);
        });
    }

    private function createReferralAttributions(): void
    {
        if (Schema::hasTable('referral_attributions')) {
            return;
        }

        Schema::create('referral_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referee_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('referral_code', 32);
            $table->string('referral_type', 20); // general | tool | expert
            $table->string('target_type', 40)->nullable(); // tool | professional | null
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('status', 20)->default('invited'); // invited|joined|activated|rewarded|rejected
            $table->string('idempotency_key', 120);
            $table->timestamp('signed_up_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();

            $table->unique('referee_user_id');
            $table->unique('idempotency_key');
            $table->index(['referrer_user_id', 'status']);
            $table->index(['referral_type', 'status']);
            $table->index(['target_type', 'target_id']);
        });

        // Schema-level self-referral guard (MySQL 8+ / SQLite).
        $this->addCheckConstraint(
            'referral_attributions',
            'referral_attributions_no_self_referral',
            'referrer_user_id <> referee_user_id'
        );
    }

    private function createWallets(): void
    {
        if (Schema::hasTable('yukti_wallets')) {
            return;
        }

        Schema::create('yukti_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('available_points')->default(0);
            $table->unsignedInteger('pending_points')->default(0);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    private function createRewardLedger(): void
    {
        if (Schema::hasTable('yukti_reward_ledger')) {
            return;
        }

        Schema::create('yukti_reward_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('direction', 10); // credit | debit
            $table->unsignedInteger('points');
            $table->unsignedInteger('available_after')->nullable();
            $table->unsignedInteger('pending_after')->nullable();
            $table->string('event_type', 40);
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('week_id', 16)->nullable(); // e.g. 2026-W46
            $table->string('status', 20)->default('completed'); // pending|completed|rejected|reversed
            $table->string('idempotency_key', 160);
            $table->json('meta')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique('idempotency_key');
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'week_id', 'event_type']);
            $table->index(['event_type', 'status']);
            $table->index(['source_type', 'source_id']);
        });

        $this->addCheckConstraint(
            'yukti_reward_ledger',
            'yukti_reward_ledger_points_positive',
            'points > 0'
        );
    }

    private function createWeeklyCounters(): void
    {
        if (Schema::hasTable('yukti_weekly_counters')) {
            return;
        }

        Schema::create('yukti_weekly_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('week_id', 16); // Monday-based ISO week in configured timezone
            $table->string('metric', 40); // login|tool_complete|tool_referral|expert_referral|social_share
            $table->unsignedInteger('count')->default(0);
            $table->unsignedInteger('points_awarded')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'week_id', 'metric'], 'yukti_weekly_counters_user_week_metric_uq');
            $table->index(['week_id', 'metric']);
        });
    }

    private function createRedemptions(): void
    {
        if (Schema::hasTable('yukti_redemptions')) {
            return;
        }

        Schema::create('yukti_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('points');
            $table->string('status', 20)->default('pending'); // pending|approved|rejected|completed|cancelled
            $table->string('offering_type', 40)->nullable();
            $table->unsignedBigInteger('offering_id')->nullable();
            $table->string('idempotency_key', 160);
            $table->unsignedBigInteger('reviewed_by_admin_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('idempotency_key');
            $table->index(['user_id', 'status']);
            $table->index(['offering_type', 'offering_id']);
            $table->index('reviewed_by_admin_id');
        });

        $this->addOptionalAdminForeignKey('yukti_redemptions', 'reviewed_by_admin_id');

        $this->addCheckConstraint(
            'yukti_redemptions',
            'yukti_redemptions_points_positive',
            'points > 0'
        );
    }

    private function createRewardConfigs(): void
    {
        if (Schema::hasTable('yukti_reward_configs')) {
            return;
        }

        Schema::create('yukti_reward_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80);
            $table->unsignedInteger('points')->nullable();
            $table->unsignedInteger('weekly_cap_count')->nullable();
            $table->unsignedInteger('weekly_cap_points')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('approval_status', 30)->default('approved'); // approved|pending_business_rule
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('updated_by_admin_id')->nullable();
            $table->timestamps();

            $table->unique('key');
            $table->index(['is_active', 'approval_status']);
            $table->index('updated_by_admin_id');
        });

        $this->addOptionalAdminForeignKey('yukti_reward_configs', 'updated_by_admin_id');
    }

    private function createSocialShareClaims(): void
    {
        if (Schema::hasTable('yukti_social_share_claims')) {
            return;
        }

        Schema::create('yukti_social_share_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('week_id', 16);
            $table->string('channel', 40)->nullable(); // whatsapp|instagram|facebook|copy|other
            $table->string('verification_status', 20)->default('pending'); // pending|verified|rejected
            $table->string('idempotency_key', 160);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique('idempotency_key');
            $table->unique(['user_id', 'week_id'], 'yukti_social_share_user_week_uq');
            $table->index(['week_id', 'verification_status']);
        });
    }

    private function createFraudFlags(): void
    {
        if (Schema::hasTable('yukti_fraud_flags')) {
            return;
        }

        Schema::create('yukti_fraud_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 80);
            $table->string('severity', 20)->default('open'); // open|reviewed|dismissed|confirmed
            $table->json('payload')->nullable(); // non-sensitive context only
            $table->unsignedBigInteger('reviewed_by_admin_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'severity']);
            $table->index(['reason', 'severity']);
            $table->index('reviewed_by_admin_id');
        });

        $this->addOptionalAdminForeignKey('yukti_fraud_flags', 'reviewed_by_admin_id');
    }

    private function addOptionalAdminForeignKey(string $table, string $column): void
    {
        if (! Schema::hasTable('admins')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->foreign($column)->references('id')->on('admins')->nullOnDelete();
        });
    }

    private function seedRewardConfigs(): void
    {
        if (! Schema::hasTable('yukti_reward_configs')) {
            return;
        }

        $now = now();

        $rows = [
            [
                'key' => 'weekly_login',
                'points' => 50,
                'weekly_cap_count' => 1,
                'weekly_cap_points' => 50,
                'is_active' => true,
                'approval_status' => 'approved',
                'meta' => json_encode([
                    'label' => 'Weekly login',
                    'source' => 'pdf_phase_3a',
                    'note' => 'Values stored only; granting engine not activated in Phase 3B.',
                ]),
            ],
            [
                'key' => 'tool_completion',
                'points' => 25,
                'weekly_cap_count' => 1,
                'weekly_cap_points' => 25,
                'is_active' => true,
                'approval_status' => 'approved',
                'meta' => json_encode([
                    'label' => 'Tool completion',
                    'source' => 'pdf_phase_3a',
                    'note' => 'Qualifying tool allowlist still requires business approval.',
                ]),
            ],
            [
                'key' => 'tool_referral',
                'points' => 50,
                'weekly_cap_count' => 2,
                'weekly_cap_points' => 100,
                'is_active' => true,
                'approval_status' => 'approved',
                'meta' => json_encode([
                    'label' => 'Tool referral',
                    'source' => 'pdf_phase_3a',
                ]),
            ],
            [
                'key' => 'expert_referral',
                'points' => 200,
                'weekly_cap_count' => 1,
                'weekly_cap_points' => 200,
                'is_active' => true,
                'approval_status' => 'approved',
                'meta' => json_encode([
                    'label' => 'Expert referral',
                    'source' => 'pdf_phase_3a',
                    'note' => 'Paid vs completed booking trigger still requires approval.',
                ]),
            ],
            [
                'key' => 'social_share',
                'points' => 25,
                'weekly_cap_count' => 1,
                'weekly_cap_points' => 25,
                'is_active' => true,
                'approval_status' => 'approved',
                'meta' => json_encode([
                    'label' => 'Verified social share',
                    'source' => 'pdf_phase_3a',
                ]),
            ],
            [
                'key' => 'general_invite',
                'points' => null,
                'weekly_cap_count' => null,
                'weekly_cap_points' => null,
                'is_active' => false,
                'approval_status' => 'pending_business_rule',
                'meta' => json_encode([
                    'label' => 'General invite reward',
                    'source' => 'pdf_underspecified',
                    'note' => 'PDF says use standard referral reward; amount and activation undefined.',
                ]),
            ],
            [
                'key' => 'redemption_rupee_per_point',
                'points' => null,
                'weekly_cap_count' => null,
                'weekly_cap_points' => null,
                'is_active' => false,
                'approval_status' => 'pending_business_rule',
                'meta' => json_encode([
                    'label' => 'Redemption conversion (INR per point)',
                    'source' => 'pdf_business_config_required',
                    'note' => 'Exact rupee value must be configured before launch.',
                ]),
            ],
            [
                'key' => 'social_verification_method',
                'points' => null,
                'weekly_cap_count' => null,
                'weekly_cap_points' => null,
                'is_active' => false,
                'approval_status' => 'pending_business_rule',
                'meta' => json_encode([
                    'label' => 'Social share verification method',
                    'source' => 'pdf_underspecified',
                    'note' => 'Verification mechanism not defined in PDF.',
                ]),
            ],
            [
                'key' => 'global_weekly_cap',
                'points' => null,
                'weekly_cap_count' => null,
                'weekly_cap_points' => null,
                'is_active' => false,
                'approval_status' => 'pending_business_rule',
                'meta' => json_encode([
                    'label' => 'Global weekly points cap',
                    'source' => 'not_in_pdf',
                    'note' => 'No global weekly cap documented; per-activity caps only.',
                ]),
            ],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('yukti_reward_configs')->where('key', $row['key'])->exists();
            if ($exists) {
                continue;
            }

            DB::table('yukti_reward_configs')->insert(array_merge($row, [
                'updated_by_admin_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    private function addCheckConstraint(string $table, string $name, string $expression): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'pgsql', 'sqlite'], true)) {
            return;
        }

        try {
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
            } elseif ($driver === 'pgsql') {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
            } else {
                // SQLite: CHECK must be in CREATE TABLE; skip ALTER when unsupported.
            }
        } catch (\Throwable) {
            // Older MySQL / missing privilege: unique indexes still protect integrity.
        }
    }
};
