<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent seed of PDF default + pending business-rule configs.
 * Prefer running the foundation migration; this seeder re-applies missing keys safely.
 */
class YuktiRewardConfigSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('yukti_reward_configs')) {
            throw new \RuntimeException(
                'yukti_reward_configs is missing. Run 2026_11_11_200000_create_yukti_refer_earn_foundation_tables first.'
            );
        }

        $now = now();
        $rows = [
            ['key' => 'weekly_login', 'points' => 50, 'weekly_cap_count' => 1, 'weekly_cap_points' => 50, 'is_active' => true, 'approval_status' => 'approved'],
            ['key' => 'tool_completion', 'points' => 25, 'weekly_cap_count' => 1, 'weekly_cap_points' => 25, 'is_active' => true, 'approval_status' => 'approved'],
            ['key' => 'tool_referral', 'points' => 50, 'weekly_cap_count' => 2, 'weekly_cap_points' => 100, 'is_active' => true, 'approval_status' => 'approved'],
            ['key' => 'expert_referral', 'points' => 200, 'weekly_cap_count' => 1, 'weekly_cap_points' => 200, 'is_active' => true, 'approval_status' => 'approved'],
            ['key' => 'social_share', 'points' => 25, 'weekly_cap_count' => 1, 'weekly_cap_points' => 25, 'is_active' => true, 'approval_status' => 'approved'],
            ['key' => 'general_invite', 'points' => null, 'weekly_cap_count' => null, 'weekly_cap_points' => null, 'is_active' => false, 'approval_status' => 'pending_business_rule'],
            ['key' => 'redemption_rupee_per_point', 'points' => null, 'weekly_cap_count' => null, 'weekly_cap_points' => null, 'is_active' => false, 'approval_status' => 'pending_business_rule'],
            ['key' => 'social_verification_method', 'points' => null, 'weekly_cap_count' => null, 'weekly_cap_points' => null, 'is_active' => false, 'approval_status' => 'pending_business_rule'],
            ['key' => 'global_weekly_cap', 'points' => null, 'weekly_cap_count' => null, 'weekly_cap_points' => null, 'is_active' => false, 'approval_status' => 'pending_business_rule'],
        ];

        foreach ($rows as $row) {
            if (DB::table('yukti_reward_configs')->where('key', $row['key'])->exists()) {
                continue;
            }

            DB::table('yukti_reward_configs')->insert(array_merge($row, [
                'meta' => null,
                'updated_by_admin_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }
}
