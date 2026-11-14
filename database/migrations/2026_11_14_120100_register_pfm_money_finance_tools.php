<?php

use App\Support\Pfm\PfmCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tools') || ! Schema::hasTable('tool_categories')) {
            return;
        }

        $now = now();
        $categoryId = DB::table('tool_categories')->where('slug', 'money-finance')->value('id');
        if (! $categoryId) {
            $categoryId = DB::table('tool_categories')->insertGetId([
                'name' => 'Money & Finance',
                'slug' => 'money-finance',
                'description' => 'Personal finance calculators. Saved results stay private to each user.',
                'icon' => 'ri-wallet-3-line',
                'is_active' => true,
                'display_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (PfmCatalog::TOOLS as $tool) {
            $existing = DB::table('tools')->where('slug', $tool['slug'])->first();
            if ($existing) {
                DB::table('tools')->where('id', $existing->id)->update([
                    'tool_category_id' => $categoryId,
                    'name' => $tool['name'],
                    'module_key' => PfmCatalog::MODULE_KEY,
                    'description' => $tool['description'],
                    'icon' => $tool['icon'],
                    'status' => 'active',
                    'is_implemented' => true,
                    'route_name' => $tool['route'],
                    'display_order' => $tool['display_order'],
                    'updated_at' => $now,
                ]);
                continue;
            }

            DB::table('tools')->insert([
                'tool_category_id' => $categoryId,
                'slug' => $tool['slug'],
                'name' => $tool['name'],
                'module_key' => PfmCatalog::MODULE_KEY,
                'description' => $tool['description'],
                'icon' => $tool['icon'],
                'access_type' => 'free',
                'status' => 'active',
                'is_implemented' => true,
                'route_name' => $tool['route'],
                'display_order' => $tool['display_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        DB::table('tools')->whereIn('slug', array_keys(PfmCatalog::TOOLS))->delete();
    }
};
