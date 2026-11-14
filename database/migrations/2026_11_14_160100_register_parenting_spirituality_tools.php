<?php

use App\Support\ParentingSpirituality\PsCatalog;
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
        $categories = [
            'parenting' => [
                'name' => 'Parenting',
                'description' => 'Practical parenting reflection and planning tools. Saved results stay private.',
                'icon' => 'ri-parent-line',
                'display_order' => 6,
            ],
            'spirituality' => [
                'name' => 'Spirituality',
                'description' => 'Secular mindfulness and values tools for calm reflection. Saved results stay private.',
                'icon' => 'ri-leaf-line',
                'display_order' => 7,
            ],
        ];

        $categoryIds = [];
        foreach ($categories as $slug => $meta) {
            $id = DB::table('tool_categories')->where('slug', $slug)->value('id');
            if (! $id) {
                $id = DB::table('tool_categories')->insertGetId([
                    'name' => $meta['name'],
                    'slug' => $slug,
                    'description' => $meta['description'],
                    'icon' => $meta['icon'],
                    'is_active' => true,
                    'display_order' => $meta['display_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $categoryIds[$slug] = $id;
        }

        foreach (PsCatalog::TOOLS as $tool) {
            $categoryId = $categoryIds[$tool['category']] ?? null;
            if (! $categoryId) {
                continue;
            }

            $existing = DB::table('tools')->where('slug', $tool['slug'])->first();
            $payload = [
                'tool_category_id' => $categoryId,
                'name' => $tool['name'],
                'module_key' => $tool['module_key'],
                'description' => $tool['description'],
                'icon' => $tool['icon'],
                'status' => 'active',
                'is_implemented' => true,
                'route_name' => $tool['route'],
                'display_order' => $tool['display_order'],
                'updated_at' => $now,
            ];

            if ($existing) {
                DB::table('tools')->where('id', $existing->id)->update($payload);
                continue;
            }

            DB::table('tools')->insert(array_merge($payload, [
                'slug' => $tool['slug'],
                'access_type' => 'free',
                'created_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        DB::table('tools')->whereIn('slug', array_keys(PsCatalog::TOOLS))->delete();
    }
};
