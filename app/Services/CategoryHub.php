<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Public All Categories hub built on existing ExploreCatalog + ToolsCatalog mappings.
 * Does not invent DB relationships; reuses needle-based category keys already used on the site.
 */
class CategoryHub
{
    /**
     * Public display labels (screenshot-aligned). Keys match ExploreCatalog::CATEGORIES.
     */
    public const DISPLAY_LABELS = [
        'mental-health' => 'Mental Wellbeing',
        'astrology-tarot' => 'Astrology & Tarot',
        'relationships' => 'Relationships',
        'career-business' => 'Career',
        'money-finance' => 'Finance',
        'health-wellness' => 'Health & Wellness',
        'parenting' => 'Parenting',
        'lifestyle' => 'Lifestyle & More',
        'spirituality' => 'Spirituality',
        'creativity' => 'Creativity',
        'nutrition-fitness' => 'Nutrition & Fitness',
    ];

    /**
     * Short, brand-safe taglines for category detail headers.
     * Keys must match ExploreCatalog::CATEGORIES.
     */
    public const TAGLINES = [
        'mental-health' => 'Support for a calmer, stronger you',
        'astrology-tarot' => 'Reflective insight for clarity and direction',
        'relationships' => 'Guidance for healthier, more connected bonds',
        'career-business' => 'Clarity for work, growth, and next steps',
        'money-finance' => 'Practical support for calmer money decisions',
        'health-wellness' => 'Everyday wellness for a brighter balance',
        'parenting' => 'Support for raising children with confidence',
        'lifestyle' => 'Inspiration for living with intention',
        'spirituality' => 'Space for grounding, meaning, and stillness',
        'creativity' => 'Spark ideas and creative momentum',
        'nutrition-fitness' => 'Fuel and movement for a healthier you',
    ];

    public function __construct(
        private readonly ExploreCatalog $explore,
        private readonly ToolsCatalog $tools,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(?string $search = null): array
    {
        $search = Str::lower(trim(strip_tags((string) $search)));
        $toolCounts = $this->tools->categoryCounts();
        $expertCounts = $this->expertCountsByCategory();
        $programmeCounts = $this->programmeCountsByCategory();

        $rows = [];
        foreach (ExploreCatalog::CATEGORIES as $slug => $meta) {
            $label = self::DISPLAY_LABELS[$slug] ?? (string) ($meta['label'] ?? $slug);
            if ($search !== '' && ! str_contains(Str::lower($label.' '.$slug.' '.($meta['label'] ?? '')), $search)) {
                continue;
            }

            $tools = (int) ($toolCounts[$slug] ?? 0);
            $experts = (int) ($expertCounts[$slug] ?? 0);
            $programmes = (int) ($programmeCounts[$slug] ?? 0);

            $rows[] = [
                'slug' => $slug,
                'label' => $label,
                'tone' => $meta['tone'] ?? 'mint',
                'icon' => $meta['icon'] ?? 'leaf',
                'tagline' => self::TAGLINES[$slug] ?? '',
                'url' => route('categories.show', $slug),
                'counts' => [
                    'tools' => $tools,
                    'experts' => $experts,
                    'programmes' => $programmes,
                    'total' => $tools + $experts + $programmes,
                ],
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function show(string $slug, string $tab = 'tools'): array
    {
        if (! array_key_exists($slug, ExploreCatalog::CATEGORIES)) {
            abort(404);
        }

        $meta = ExploreCatalog::CATEGORIES[$slug];
        $tab = $this->normalizeTab($tab);

        $tools = $this->tools->forExploreCategory($slug);
        $experts = $this->expertsForCategory($slug);
        $programmes = $this->programmesForCategory($slug);

        return [
            'category' => [
                'slug' => $slug,
                'label' => self::DISPLAY_LABELS[$slug] ?? $meta['label'],
                'tone' => $meta['tone'] ?? 'mint',
                'icon' => $meta['icon'] ?? 'leaf',
                'tagline' => self::TAGLINES[$slug] ?? '',
            ],
            'tab' => $tab,
            'tabs' => $this->tabs(),
            'counts' => [
                'tools' => $tools->count(),
                'experts' => $experts->count(),
                'programmes' => $programmes->count(),
            ],
            'tools' => $tools,
            'experts' => $experts,
            'programmes' => $programmes,
            'programmesEnabled' => (bool) config('yuktione.programmes_enabled'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function tabs(): array
    {
        return [
            'tools' => 'Tools',
            'experts' => 'Experts',
            'programmes' => 'Programmes',
        ];
    }

    private function normalizeTab(string $tab): string
    {
        $tab = Str::lower(trim($tab));
        $allowed = array_keys($this->tabs());

        return in_array($tab, $allowed, true) ? $tab : 'tools';
    }

    private function expertsForCategory(string $slug): Collection
    {
        return $this->collectBrowseCards([
            'type' => 'experts',
            'types' => ['experts'],
            'category' => $slug,
            'sort' => 'relevant',
        ], 'expert');
    }

    private function programmesForCategory(string $slug): Collection
    {
        if (! config('yuktione.programmes_enabled')) {
            return collect();
        }

        return $this->collectBrowseCards([
            'type' => 'programmes',
            'types' => ['programmes'],
            'category' => $slug,
            'sort' => 'newest',
        ], 'programme');
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function collectBrowseCards(array $query, string $kind): Collection
    {
        $all = collect();
        $page = 1;
        $original = app('request');

        try {
            do {
                $request = Request::create('/gridlisting', 'GET', array_merge($query, ['page' => $page]));
                // LengthAwarePaginator resolves the page from the container request.
                app()->instance('request', $request);

                $browse = $this->explore->browse($request);
                /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $items */
                $items = $browse['items'];
                $batch = collect($items->items())
                    ->filter(fn ($card) => ($card['kind'] ?? null) === $kind)
                    ->values();
                $all = $all->concat($batch);
                $hasMore = $items->hasMorePages();
                $page++;
            } while ($hasMore && $page <= 12);
        } finally {
            app()->instance('request', $original);
        }

        return $all
            ->unique(fn ($card) => (string) ($card['url'] ?? ($card['title'] ?? '').'|'.($card['kind'] ?? '')))
            ->values();
    }

    /**
     * @return array<string, int>
     */
    private function expertCountsByCategory(): array
    {
        $out = array_fill_keys(array_keys(ExploreCatalog::CATEGORIES), 0);

        foreach (array_keys(ExploreCatalog::CATEGORIES) as $slug) {
            $request = Request::create('/gridlisting', 'GET', [
                'type' => 'experts',
                'types' => ['experts'],
                'category' => $slug,
            ]);
            $browse = $this->explore->browse($request);
            /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $items */
            $items = $browse['items'];
            $out[$slug] = (int) $items->total();
        }

        return $out;
    }

    /**
     * @return array<string, int>
     */
    private function programmeCountsByCategory(): array
    {
        $out = array_fill_keys(array_keys(ExploreCatalog::CATEGORIES), 0);

        if (! config('yuktione.programmes_enabled')) {
            return $out;
        }

        $request = Request::create('/gridlisting', 'GET', [
            'type' => 'programmes',
            'types' => ['programmes'],
        ]);
        $browse = $this->explore->browse($request);
        $counts = $browse['counts']['categories'] ?? [];

        foreach ($out as $slug => $_) {
            $out[$slug] = (int) ($counts[$slug] ?? 0);
        }

        return $out;
    }
}
