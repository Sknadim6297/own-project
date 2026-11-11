<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3H — register YuktiPoints admin menu hierarchy (read-only screens).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_menus')) {
            return;
        }

        $now = now();
        $parentId = $this->ensureMenu([
            'name' => 'yukti_points',
            'display_name' => 'YuktiPoints',
            'route_name' => null,
            'icon' => 'ri-coin-line',
            'parent_id' => null,
            'order' => 95,
        ], $now);

        $children = [
            [
                'name' => 'yukti.dashboard',
                'display_name' => 'Rewards Dashboard',
                'route_name' => 'admin.yukti-points.dashboard',
                'order' => 1,
            ],
            [
                'name' => 'yukti.config',
                'display_name' => 'Reward Configuration',
                'route_name' => 'admin.yukti-points.configuration',
                'order' => 2,
            ],
            [
                'name' => 'yukti.wallets',
                'display_name' => 'Customer Wallets',
                'route_name' => 'admin.yukti-points.wallets',
                'order' => 3,
            ],
            [
                'name' => 'yukti.ledger',
                'display_name' => 'Reward Ledger',
                'route_name' => 'admin.yukti-points.ledger',
                'order' => 4,
            ],
            [
                'name' => 'yukti.referrals',
                'display_name' => 'Referrals',
                'route_name' => 'admin.yukti-points.referrals',
                'order' => 5,
            ],
            [
                'name' => 'yukti.fraud',
                'display_name' => 'Fraud Flags',
                'route_name' => 'admin.yukti-points.fraud',
                'order' => 6,
            ],
        ];

        foreach ($children as $child) {
            $this->ensureMenu([
                'name' => $child['name'],
                'display_name' => $child['display_name'],
                'route_name' => $child['route_name'],
                'icon' => null,
                'parent_id' => $parentId,
                'order' => $child['order'],
            ], $now);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_menus')) {
            return;
        }

        $names = [
            'yukti.dashboard',
            'yukti.config',
            'yukti.wallets',
            'yukti.ledger',
            'yukti.referrals',
            'yukti.fraud',
            'yukti_points',
        ];

        DB::table('admin_menus')->whereIn('name', $names)->delete();
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function ensureMenu(array $attrs, $now): int
    {
        $existing = DB::table('admin_menus')->where('name', $attrs['name'])->first();
        if ($existing) {
            DB::table('admin_menus')->where('id', $existing->id)->update([
                'display_name' => $attrs['display_name'],
                'route_name' => $attrs['route_name'],
                'icon' => $attrs['icon'],
                'parent_id' => $attrs['parent_id'],
                'order' => $attrs['order'],
                'updated_at' => $now,
            ]);

            return (int) $existing->id;
        }

        return (int) DB::table('admin_menus')->insertGetId([
            'name' => $attrs['name'],
            'display_name' => $attrs['display_name'],
            'route_name' => $attrs['route_name'],
            'icon' => $attrs['icon'],
            'parent_id' => $attrs['parent_id'],
            'order' => $attrs['order'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
