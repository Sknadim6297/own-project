<?php

namespace Database\Seeders;

use App\Models\CategoryTool;
use App\Models\Service;
use App\Models\Tool;
use App\Services\CategoryCatalog;
use App\Services\ToolsCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent seed of the nine client-required Categories (services rows)
 * and initial category_tool mappings from ToolsCatalog::TOOL_PLACEMENT.
 */
class CanonicalCategorySeeder extends Seeder
{
    /**
     * @var list<array{order: int, name: string, slug: string}>
     */
    public const CANONICAL = [
        ['order' => 1, 'name' => 'Mental Wellness', 'slug' => 'mental-wellness'],
        ['order' => 2, 'name' => 'Career Counselling', 'slug' => 'career-counselling'],
        ['order' => 3, 'name' => 'Nutrition', 'slug' => 'nutrition'],
        ['order' => 4, 'name' => 'Fitness', 'slug' => 'fitness'],
        ['order' => 5, 'name' => 'Astrology', 'slug' => 'astrology'],
        ['order' => 6, 'name' => 'Tarot', 'slug' => 'tarot'],
        ['order' => 7, 'name' => 'Business Consulting', 'slug' => 'business-consulting'],
        ['order' => 8, 'name' => 'Tax Matters', 'slug' => 'tax-matters'],
        ['order' => 9, 'name' => 'Investments', 'slug' => 'investments'],
    ];

    public function run(): void
    {
        if (! Schema::hasTable('services')) {
            return;
        }

        foreach (self::CANONICAL as $row) {
            $this->upsertCategory($row);
        }

        $this->seedToolMappings();
        Service::clearCatalogCaches();
    }

    /**
     * @param  array{order: int, name: string, slug: string}  $row
     */
    private function upsertCategory(array $row): void
    {
        $category = Service::query()->where('slug', $row['slug'])->first();

        if (! $category) {
            $category = Service::query()
                ->whereRaw('LOWER(name) = ?', [strtolower($row['name'])])
                ->first();
        }

        $presentation = CategoryCatalog::DEFAULT_PRESENTATION[$row['slug']] ?? ['tone' => 'mint', 'icon' => 'leaf'];
        $needles = CategoryCatalog::DEFAULT_NEEDLES[$row['slug']] ?? [];

        if (! $category) {
            Service::query()->create([
                'name' => $row['name'],
                'slug' => $row['slug'],
                'status' => 'active',
                'display_order' => $row['order'],
                'show_on_homepage' => true,
                'show_in_explore_tools' => true,
                'discovery_needles' => $needles,
                'tone' => $presentation['tone'],
                'icon_name' => $presentation['icon'],
                'short_description' => null,
            ]);

            return;
        }

        $updates = [];

        // Never overwrite manually edited identity fields when already set.
        if ($category->slug !== $row['slug'] && blank($category->slug)) {
            $updates['slug'] = $row['slug'];
        }
        if ($category->slug !== $row['slug'] && $category->slug === null) {
            $updates['slug'] = $row['slug'];
        }
        if ($category->name === '' || $category->name === null) {
            $updates['name'] = $row['name'];
        }
        if ((int) $category->display_order === 0) {
            $updates['display_order'] = $row['order'];
        }
        if ($category->status === null || $category->status === '') {
            $updates['status'] = 'active';
        }
        if (Schema::hasColumn('services', 'discovery_needles')
            && (blank($category->discovery_needles) || $category->discovery_needles === [])) {
            $updates['discovery_needles'] = $needles;
        }
        if (Schema::hasColumn('services', 'tone') && blank($category->tone)) {
            $updates['tone'] = $presentation['tone'];
        }
        if (Schema::hasColumn('services', 'icon_name') && blank($category->icon_name)) {
            $updates['icon_name'] = $presentation['icon'];
        }
        // Ensure flags exist with sensible defaults only when columns were just added (null).
        if (Schema::hasColumn('services', 'show_on_homepage') && $category->getAttributes()['show_on_homepage'] === null) {
            $updates['show_on_homepage'] = true;
        }
        if (Schema::hasColumn('services', 'show_in_explore_tools') && $category->getAttributes()['show_in_explore_tools'] === null) {
            $updates['show_in_explore_tools'] = true;
        }

        // Always sync slug when matching by name to the canonical slug if the row
        // still uses a non-canonical slug that aliases to this category.
        if ($category->slug !== $row['slug']) {
            $aliasTarget = app(CategoryCatalog::class)->canonicalizeSlug((string) $category->slug);
            if ($aliasTarget === $row['slug'] || blank($category->slug)) {
                $updates['slug'] = $row['slug'];
            }
        }

        if ($updates !== []) {
            $category->fill($updates);
            $category->save();
        }
    }

    private function seedToolMappings(): void
    {
        if (! Schema::hasTable('category_tool') || ! Schema::hasTable('tools')) {
            return;
        }

        $categoriesBySlug = Service::query()->pluck('id', 'slug');
        $toolsBySlug = Tool::query()->pluck('id', 'slug');

        $orderByCategory = [];

        foreach (ToolsCatalog::legacyToolPlacement() as $toolSlug => $meta) {
            $categorySlug = $meta['tools'];
            $categoryId = $categoriesBySlug[$categorySlug] ?? null;
            $toolId = $toolsBySlug[$toolSlug] ?? null;

            if (! $categoryId || ! $toolId) {
                continue;
            }

            $orderByCategory[$categoryId] = ($orderByCategory[$categoryId] ?? 0) + 1;

            CategoryTool::query()->updateOrCreate(
                [
                    'service_id' => $categoryId,
                    'tool_id' => $toolId,
                ],
                [
                    'display_order' => $orderByCategory[$categoryId],
                    'is_visible' => true,
                ]
            );
        }
    }
}
