<?php

namespace App\Services;

use App\Models\CategoryTool;
use App\Models\Service;
use App\Models\Tool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Database-driven Category queries for homepage, Explore Tools, and discovery.
 * Product Category rows live in the `services` table.
 */
class CategoryCatalog
{
    /**
     * Fallback presentation when DB categories are empty (pre-seed / tests).
     *
     * @var array<string, array{label: string, tone: string, icon: string}>
     */
    public const FALLBACK_CATEGORIES = ToolsCatalog::CATEGORIES;

    /**
     * Legacy public hub / tools slugs → canonical Category slug.
     *
     * @var array<string, string>
     */
    public const SLUG_ALIASES = [
        'mental-health' => 'mental-wellness',
        'career-business' => 'career-counselling',
        'money-finance' => 'investments',
        'parenting' => 'mental-wellness',
        'spirituality' => 'mental-wellness',
        'health-wellness' => 'fitness',
        'relationships' => 'mental-wellness',
        'lifestyle' => 'mental-wellness',
        'creativity' => 'mental-wellness',
        'nutrition-fitness' => 'nutrition',
        'astrology-tarot' => 'astrology',
    ];

    /**
     * Compound legacy keys that expand to multiple categories.
     *
     * @var array<string, list<string>>
     */
    public const GROUP_ALIASES = ToolsCatalog::CATEGORY_GROUP_ALIASES;

    /**
     * Default discovery needles for the nine client categories.
     *
     * @var array<string, list<string>>
     */
    public const DEFAULT_NEEDLES = [
        'mental-wellness' => ['mental', 'psycholog', 'psychiatr', 'mindset', 'therap', 'parent', 'child', 'spirit', 'meditat'],
        'career-counselling' => ['career', 'job', 'coach', 'counsel'],
        'nutrition' => ['nutrition', 'diet', 'food'],
        'fitness' => ['fitness', 'workout', 'trainer', 'yoga'],
        'astrology' => ['astro', 'horoscope', 'vastu'],
        'tarot' => ['tarot'],
        'business-consulting' => ['business', 'consult'],
        'tax-matters' => ['tax', 'account'],
        'investments' => ['finance', 'money', 'invest', 'estate'],
    ];

    /**
     * Default tones / icons for presentation when DB fields are empty.
     *
     * @var array<string, array{tone: string, icon: string}>
     */
    public const DEFAULT_PRESENTATION = [
        'mental-wellness' => ['tone' => 'mint', 'icon' => 'lotus'],
        'career-counselling' => ['tone' => 'lilac', 'icon' => 'career'],
        'nutrition' => ['tone' => 'mint', 'icon' => 'nutrition'],
        'fitness' => ['tone' => 'mint', 'icon' => 'fitness'],
        'astrology' => ['tone' => 'lavender', 'icon' => 'moon'],
        'tarot' => ['tone' => 'lavender', 'icon' => 'tarot'],
        'business-consulting' => ['tone' => 'lilac', 'icon' => 'chart'],
        'tax-matters' => ['tone' => 'cream', 'icon' => 'coins'],
        'investments' => ['tone' => 'cream', 'icon' => 'finance'],
    ];

    public function categoriesAvailable(): bool
    {
        return Schema::hasTable('services')
            && Schema::hasColumn('services', 'show_on_homepage');
    }

    /**
     * @return Collection<int, Service>
     */
    public function allActive(): Collection
    {
        if (! $this->categoriesAvailable()) {
            return collect();
        }

        return Cache::remember('yuktione.catalog.categories', 300, function () {
            return Service::query()->active()->ordered()->get();
        });
    }

    /**
     * @return Collection<int, Service>
     */
    public function forHomepage(): Collection
    {
        if (! $this->categoriesAvailable()) {
            return collect();
        }

        return Cache::remember('yuktione.catalog.homepage-categories', 300, function () {
            return Service::query()->homepage()->get();
        });
    }

    /**
     * @return Collection<int, Service>
     */
    public function forExploreTools(): Collection
    {
        if (! $this->categoriesAvailable()) {
            return collect();
        }

        return Cache::remember('yuktione.catalog.explore-tool-categories', 300, function () {
            return Service::query()->exploreTools()->get();
        });
    }

    /**
     * Presentation map keyed by slug for ToolsCatalog / views.
     *
     * @return array<string, array{label: string, tone: string, icon: string, id?: int}>
     */
    public function toolsCategoryMap(): array
    {
        $rows = $this->forExploreTools();
        if ($rows->isEmpty()) {
            return self::FALLBACK_CATEGORIES;
        }

        $map = [];
        foreach ($rows as $category) {
            $presentation = self::DEFAULT_PRESENTATION[$category->slug] ?? ['tone' => 'mint', 'icon' => 'leaf'];
            $map[$category->slug] = [
                'label' => $category->name,
                'tone' => $category->tone ?: $presentation['tone'],
                'icon' => $category->icon_name ?: $presentation['icon'],
                'id' => $category->id,
            ];
        }

        return $map;
    }

    /**
     * Discovery map for ExploreCatalog-compatible consumers.
     *
     * @return array<string, array{label: string, tone: string, icon: string, needles: list<string>}>
     */
    public function discoveryCategoryMap(): array
    {
        $rows = $this->allActive();
        if ($rows->isEmpty()) {
            // Bridge legacy ExploreCatalog constants until seed runs.
            $legacy = [];
            foreach (ExploreCatalog::CATEGORIES as $slug => $meta) {
                $canonical = $this->canonicalizeSlug($slug) ?? $slug;
                if (! isset($legacy[$canonical])) {
                    $presentation = self::DEFAULT_PRESENTATION[$canonical] ?? ['tone' => $meta['tone'], 'icon' => $meta['icon']];
                    $legacy[$canonical] = [
                        'label' => self::FALLBACK_CATEGORIES[$canonical]['label'] ?? $meta['label'],
                        'tone' => $presentation['tone'],
                        'icon' => $presentation['icon'],
                        'needles' => self::DEFAULT_NEEDLES[$canonical] ?? ($meta['needles'] ?? []),
                    ];
                }
            }

            return $legacy;
        }

        $map = [];
        foreach ($rows as $category) {
            $presentation = self::DEFAULT_PRESENTATION[$category->slug] ?? ['tone' => 'mint', 'icon' => 'leaf'];
            $map[$category->slug] = [
                'label' => $category->name,
                'tone' => $category->tone ?: $presentation['tone'],
                'icon' => $category->icon_name ?: $presentation['icon'],
                'needles' => is_array($category->discovery_needles) && $category->discovery_needles !== []
                    ? $category->discovery_needles
                    : (self::DEFAULT_NEEDLES[$category->slug] ?? []),
            ];
        }

        return $map;
    }

    public function findBySlug(string $slug): ?Service
    {
        $canonical = $this->canonicalizeSlug($slug) ?? $slug;

        if (! $this->categoriesAvailable()) {
            return null;
        }

        return Service::query()->where('slug', $canonical)->first();
    }

    public function canonicalizeSlug(?string $slug): ?string
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        if (isset(self::GROUP_ALIASES[$slug])) {
            return self::GROUP_ALIASES[$slug][0] ?? null;
        }

        return self::SLUG_ALIASES[$slug] ?? $slug;
    }

    /**
     * Expand a request category (including compound aliases) to tool category slugs.
     *
     * @return list<string>
     */
    public function expandToToolSlugs(?string $slug): array
    {
        if ($slug === null || $slug === '') {
            return [];
        }

        if (isset(self::GROUP_ALIASES[$slug])) {
            return self::GROUP_ALIASES[$slug];
        }

        $canonical = $this->canonicalizeSlug($slug);

        return $canonical ? [$canonical] : [];
    }

    /**
     * tool slug => category slug from pivot (with TOOL_PLACEMENT fallback).
     *
     * @return array<string, string>
     */
    public function toolCategoryPlacement(): array
    {
        if (! Schema::hasTable('category_tool')) {
            return $this->legacyToolPlacement();
        }

        return Cache::remember('yuktione.catalog.category-tool-map', 300, function () {
            $pivot = CategoryTool::query()
                ->where('is_visible', true)
                ->with(['category:id,slug', 'tool:id,slug'])
                ->orderBy('display_order')
                ->get();

            if ($pivot->isEmpty()) {
                return $this->legacyToolPlacement();
            }

            $map = [];
            foreach ($pivot as $row) {
                if (! $row->tool || ! $row->category) {
                    continue;
                }
                // First assignment wins (lowest display_order).
                if (! isset($map[$row->tool->slug])) {
                    $map[$row->tool->slug] = $row->category->slug;
                }
            }

            return $map !== [] ? $map : $this->legacyToolPlacement();
        });
    }

    /**
     * @return array<string, string>
     */
    private function legacyToolPlacement(): array
    {
        $map = [];
        foreach (ToolsCatalog::legacyToolPlacement() as $toolSlug => $meta) {
            $map[$toolSlug] = $meta['tools'];
        }

        return $map;
    }

    public function redirectTargetForLegacySlug(string $slug): ?string
    {
        if (isset(self::GROUP_ALIASES[$slug])) {
            return self::GROUP_ALIASES[$slug][0] ?? null;
        }

        if (isset(self::SLUG_ALIASES[$slug])) {
            return self::SLUG_ALIASES[$slug];
        }

        return null;
    }
}
