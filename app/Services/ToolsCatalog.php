<?php

namespace App\Services;

use App\Models\NutritionFood;
use App\Models\Tool;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ToolsCatalog
{
    public const TOPICS = ['Anxiety', 'Meditation', 'Sleep', 'Self Care', 'Career', 'Nutrition'];

    private const PER_PAGE = 12;

    private const PRIVATE_ROUTE_MARKERS = ['history', 'complete', 'reflection', 'finish', 'emotions'];

    /**
     * Client-requested Explore Tools categories (display order is array order).
     * Keys are tools-catalogue identifiers; tool routes/slugs stay unchanged.
     *
     * @var array<string, array{label: string, tone: string, icon: string}>
     */
    public const CATEGORIES = [
        'mental-wellness' => ['label' => 'Mental Wellness', 'tone' => 'mint', 'icon' => 'lotus'],
        'career-counselling' => ['label' => 'Career Counselling', 'tone' => 'lilac', 'icon' => 'career'],
        'nutrition' => ['label' => 'Nutrition', 'tone' => 'mint', 'icon' => 'nutrition'],
        'fitness' => ['label' => 'Fitness', 'tone' => 'mint', 'icon' => 'fitness'],
        'astrology' => ['label' => 'Astrology', 'tone' => 'lavender', 'icon' => 'moon'],
        'tarot' => ['label' => 'Tarot', 'tone' => 'lavender', 'icon' => 'tarot'],
        'business-consulting' => ['label' => 'Business Consulting', 'tone' => 'lilac', 'icon' => 'chart'],
        'tax-matters' => ['label' => 'Tax Matters', 'tone' => 'cream', 'icon' => 'coins'],
        'investments' => ['label' => 'Investments', 'tone' => 'cream', 'icon' => 'finance'],
    ];

    /**
     * Legacy /tools?category=… values still accepted and normalized to one
     * tools-catalogue key (bookmarks keep working).
     *
     * @var array<string, string>
     */
    public const CATEGORY_ALIASES = [
        'mental-health' => 'mental-wellness',
        'career-business' => 'career-counselling',
        'money-finance' => 'investments',
        'parenting' => 'mental-wellness',
        'spirituality' => 'mental-wellness',
        'health-wellness' => 'fitness',
    ];

    /**
     * Legacy compound Explore keys that span more than one tools category.
     *
     * @var array<string, list<string>>
     */
    public const CATEGORY_GROUP_ALIASES = [
        'nutrition-fitness' => ['nutrition', 'fitness'],
        'astrology-tarot' => ['astrology', 'tarot'],
    ];

    /**
     * Explicit tool → tools-catalogue category + All Categories hub key.
     * Hub keys continue to use ExploreCatalog::CATEGORIES for experts.
     *
     * @var array<string, array{tools: string, hub: string}>
     */
    private const TOOL_PLACEMENT = [
        'daily-mood-check-in' => ['tools' => 'mental-wellness', 'hub' => 'mental-health'],
        'digital-journal' => ['tools' => 'mental-wellness', 'hub' => 'mental-health'],
        'gratitude-journal' => ['tools' => 'mental-wellness', 'hub' => 'mental-health'],
        'daily-mindfulness-calm' => ['tools' => 'mental-wellness', 'hub' => 'spirituality'],
        'purpose-values-finder' => ['tools' => 'mental-wellness', 'hub' => 'spirituality'],
        'parenting-stress-check' => ['tools' => 'mental-wellness', 'hub' => 'parenting'],
        'child-routine-planner' => ['tools' => 'mental-wellness', 'hub' => 'parenting'],
        'parent-child-conversation-guide' => ['tools' => 'mental-wellness', 'hub' => 'parenting'],
        'career-interest-test' => ['tools' => 'career-counselling', 'hub' => 'career-business'],
        'skills-strengths-assessment' => ['tools' => 'career-counselling', 'hub' => 'career-business'],
        'water-intake-tracker' => ['tools' => 'nutrition', 'hub' => 'nutrition-fitness'],
        'calorie-nutrition-tracker' => ['tools' => 'nutrition', 'hub' => 'nutrition-fitness'],
        'bmi-bmr-calculator' => ['tools' => 'fitness', 'hub' => 'nutrition-fitness'],
        'weight-journey' => ['tools' => 'fitness', 'hub' => 'nutrition-fitness'],
        'tarot-reflection' => ['tools' => 'tarot', 'hub' => 'astrology-tarot'],
        'monthly-money-planner' => ['tools' => 'investments', 'hub' => 'money-finance'],
        'net-worth-calculator' => ['tools' => 'investments', 'hub' => 'money-finance'],
        'emergency-fund-calculator' => ['tools' => 'investments', 'hub' => 'money-finance'],
        'financial-goal-calculator' => ['tools' => 'investments', 'hub' => 'money-finance'],
        'debt-emi-calculator' => ['tools' => 'investments', 'hub' => 'money-finance'],
        'retirement-calculator' => ['tools' => 'investments', 'hub' => 'money-finance'],
        'inflation-calculator' => ['tools' => 'investments', 'hub' => 'money-finance'],
        'savings-investment-calculator' => ['tools' => 'investments', 'hub' => 'money-finance'],
    ];

    public function browse(Request $request): array
    {
        $filters = $this->filters($request);
        $published = $this->published();
        $matched = $published
            ->filter(fn (array $card) => $this->matches($card, $filters))
            ->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = new LengthAwarePaginator(
            $matched->forPage($page, self::PER_PAGE)->values(),
            $matched->count(),
            self::PER_PAGE,
            $page,
            [
                'path' => route('tools.index'),
                'query' => $request->query(),
            ]
        );

        return [
            'items' => $items,
            'filters' => $filters,
            'categories' => self::CATEGORIES,
            'counts' => $this->counts($published, $filters),
            'topics' => self::TOPICS,
            'catalogueAvailable' => $published->isNotEmpty(),
        ];
    }

    /**
     * Launchable tools for a tools-catalogue category key, or for an
     * Explore / All Categories hub key (experts hub slug).
     *
     * Hub keys must use TOOL_PLACEMENT hub mappings — never remap them through
     * tools-catalogue aliases (those aliases are only for /tools?category= bookmarks).
     * Remapping hub keys hid tools (e.g. Astrology & Tarot → empty) and flooded
     * others with the wrong catalogue (e.g. Parenting showing all Mental Wellness).
     */
    public function forExploreCategory(string $categoryKey): Collection
    {
        $categoryKey = trim($categoryKey);

        if ($categoryKey === '') {
            return collect();
        }

        // Exact nine-category tools-catalogue key.
        if (array_key_exists($categoryKey, self::CATEGORIES)) {
            return $this->published()
                ->filter(fn (array $card) => ($card['category'] ?? null) === $categoryKey)
                ->values();
        }

        // Explore / All Categories hub key — filter by hub_category placement.
        if (array_key_exists($categoryKey, ExploreCatalog::CATEGORIES)) {
            return $this->published()
                ->filter(fn (array $card) => ($card['hub_category'] ?? null) === $categoryKey)
                ->values();
        }

        // Legacy tools-catalogue aliases / compound bookmarks only.
        if (isset(self::CATEGORY_GROUP_ALIASES[$categoryKey])) {
            $group = self::CATEGORY_GROUP_ALIASES[$categoryKey];

            return $this->published()
                ->filter(fn (array $card) => in_array($card['category'] ?? null, $group, true))
                ->values();
        }

        $normalized = $this->normalizeCategoryKey($categoryKey);
        if ($normalized !== null && array_key_exists($normalized, self::CATEGORIES)) {
            return $this->published()
                ->filter(fn (array $card) => ($card['category'] ?? null) === $normalized)
                ->values();
        }

        return collect();
    }

    /**
     * Tool counts keyed by tools-catalogue category (nine client categories).
     *
     * @return array<string, int>
     */
    public function categoryCounts(): array
    {
        return $this->counts($this->published(), ['q' => '', 'category' => '']);
    }

    /**
     * Tool counts keyed by Explore/All Categories hub slug.
     *
     * @return array<string, int>
     */
    public function hubCategoryCounts(): array
    {
        $counts = array_fill_keys(array_keys(ExploreCatalog::CATEGORIES), 0);

        foreach ($this->published() as $card) {
            $hub = $card['hub_category'] ?? null;
            if (is_string($hub) && array_key_exists($hub, $counts)) {
                $counts[$hub]++;
            }
        }

        return $counts;
    }

    private function filters(Request $request): array
    {
        $q = trim(strip_tags((string) $request->query('q', '')));
        $q = Str::limit($q, 100, '');

        $rawCategory = trim((string) $request->query('category', ''));
        $categoryAny = self::CATEGORY_GROUP_ALIASES[$rawCategory] ?? null;
        $category = $categoryAny
            ? ''
            : ($this->normalizeCategoryKey($rawCategory) ?? '');

        return [
            'q' => $q,
            'category' => $category,
            'category_any' => $categoryAny,
            'category_raw' => $rawCategory,
        ];
    }

    public function normalizeCategoryKey(?string $category): ?string
    {
        $category = trim((string) $category);
        if ($category === '') {
            return null;
        }

        if (array_key_exists($category, self::CATEGORIES)) {
            return $category;
        }

        if (isset(self::CATEGORY_GROUP_ALIASES[$category])) {
            return self::CATEGORY_GROUP_ALIASES[$category][0] ?? null;
        }

        return self::CATEGORY_ALIASES[$category] ?? null;
    }

    private function published(): Collection
    {
        try {
            if (! Schema::hasTable('tools')) {
                return collect();
            }

            return Tool::query()
                ->with('category')
                ->publiclyAvailable()
                ->orderBy('display_order')
                ->orderBy('id')
                ->get()
                ->map(fn (Tool $tool) => $this->present($tool))
                ->filter()
                ->values();
        } catch (QueryException) {
            return collect();
        }
    }

    /**
     * The /tools catalogue card for one tool, or null when the website does not list it.
     */
    public function cardFor(Tool $tool): ?array
    {
        return $this->present($tool);
    }

    private function present(Tool $tool): ?array
    {
        if (! $this->isPublicEntry($tool)) {
            return null;
        }

        try {
            $url = route($tool->route_name);
        } catch (\Throwable) {
            return null;
        }

        $placement = $this->placementFor($tool);
        $category = $placement['tools'];
        $description = str_replace("\0", '', strip_tags((string) $tool->description));
        $cataloguePending = $tool->slug === 'calorie-nutrition-tracker'
            && $this->nutritionCataloguePending();

        if ($cataloguePending) {
            $description = 'Food logging unlocks after the verified catalogue is published. You can open the tracker to check status.';
        }

        return [
            'name' => $tool->name,
            'description' => Str::limit(trim($description), 140),
            'url' => $url,
            'cta' => $cataloguePending
                ? 'View status'
                : ($tool->slug === 'daily-mood-check-in' ? 'Start Check-in' : 'Open'),
            'access' => $tool->access_type === 'paid' ? 'Paid' : 'Free',
            'availability' => $cataloguePending ? 'Pending' : 'Available',
            'availability_state' => $cataloguePending ? 'pending' : 'available',
            'category' => $category,
            'category_label' => $category ? (self::CATEGORIES[$category]['label'] ?? '') : '',
            'hub_category' => $placement['hub'],
            'icon' => $this->iconKey($tool),
            'slug' => $tool->slug,
        ];
    }

    /**
     * @return array{tools: string, hub: string}
     */
    private function placementFor(Tool $tool): array
    {
        if (isset(self::TOOL_PLACEMENT[$tool->slug])) {
            return self::TOOL_PLACEMENT[$tool->slug];
        }

        // Unknown tools stay visible under Mental Wellness until mapped explicitly.
        return [
            'tools' => 'mental-wellness',
            'hub' => ExploreCatalog::PSYCHOLOGY_CATEGORY,
        ];
    }

    /**
     * True when the Nutrition Tracker has no published catalogue foods.
     * Does not invent foods; pending clears automatically once foods are published.
     */
    private function nutritionCataloguePending(): bool
    {
        try {
            if (! Schema::hasTable('nutrition_foods')) {
                return true;
            }

            return NutritionFood::query()->catalogueVisible()->doesntExist();
        } catch (QueryException) {
            return true;
        }
    }

    public function iconKey(Tool $tool): string
    {
        return match ($tool->slug) {
            'daily-mood-check-in' => 'mood',
            'digital-journal' => 'journal',
            'gratitude-journal' => 'gratitude',
            'career-interest-test' => 'career',
            'skills-strengths-assessment' => 'skills',
            'bmi-bmr-calculator' => 'bmi',
            'water-intake-tracker' => 'water',
            'weight-journey' => 'weight',
            'calorie-nutrition-tracker' => 'nutrition',
            'monthly-money-planner',
            'net-worth-calculator',
            'emergency-fund-calculator',
            'debt-emi-calculator',
            'retirement-calculator',
            'inflation-calculator',
            'savings-investment-calculator' => 'finance',
            'financial-goal-calculator' => 'goal',
            'parenting-stress-check',
            'child-routine-planner',
            'parent-child-conversation-guide' => 'parenting',
            'daily-mindfulness-calm',
            'purpose-values-finder' => 'lotus',
            'tarot-reflection' => 'tarot',
            default => match ($tool->module_key) {
                'psychology' => 'mood',
                'career-guidance' => 'career',
                'nutrition' => 'nutrition',
                'money-finance' => 'finance',
                'parenting' => 'parenting',
                'spirituality' => 'lotus',
                default => 'leaf',
            },
        };
    }

    private function isPublicEntry(Tool $tool): bool
    {
        if (! $tool->canLaunch()) {
            return false;
        }

        $name = (string) $tool->route_name;
        foreach (self::PRIVATE_ROUTE_MARKERS as $marker) {
            if (str_contains($name, $marker)) {
                return false;
            }
        }

        return true;
    }

    private function matches(array $card, array $filters): bool
    {
        $allowed = $filters['category_any'] ?? null;
        if (is_array($allowed) && $allowed !== []) {
            if (! in_array($card['category'] ?? null, $allowed, true)) {
                return false;
            }
        } elseif (($filters['category'] ?? '') !== '' && ($card['category'] ?? null) !== $filters['category']) {
            return false;
        }

        if (($filters['q'] ?? '') === '') {
            return true;
        }

        $haystack = mb_strtolower($card['name'].' '.$card['description'].' '.$card['category_label']);

        return str_contains($haystack, mb_strtolower($filters['q']));
    }

    private function counts(Collection $published, array $filters): array
    {
        $search = $filters;
        $search['category'] = '';
        $counts = [];

        foreach (array_keys(self::CATEGORIES) as $slug) {
            $counts[$slug] = $published->filter(function (array $card) use ($search, $slug) {
                return $card['category'] === $slug && $this->matches($card, $search);
            })->count();
        }

        return $counts;
    }
}
