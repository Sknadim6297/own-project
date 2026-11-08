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
            'categories' => ExploreCatalog::CATEGORIES,
            'counts' => $this->counts($published, $filters),
            'topics' => self::TOPICS,
            'catalogueAvailable' => $published->isNotEmpty(),
        ];
    }

    private function filters(Request $request): array
    {
        $q = trim(strip_tags((string) $request->query('q', '')));
        $q = Str::limit($q, 100, '');

        $category = (string) $request->query('category', '');
        if (!array_key_exists($category, ExploreCatalog::CATEGORIES)) {
            $category = '';
        }

        return [
            'q' => $q,
            'category' => $category,
        ];
    }

    private function published(): Collection
    {
        try {
            if (!Schema::hasTable('tools')) {
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

    private function present(Tool $tool): ?array
    {
        if (!$this->isPublicEntry($tool)) {
            return null;
        }

        try {
            $url = route($tool->route_name);
        } catch (\Throwable) {
            return null;
        }

        $category = $this->exploreCategory($tool);
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
            'category_label' => $category ? ExploreCatalog::CATEGORIES[$category]['label'] : '',
            'icon' => $this->iconKey($tool),
            'slug' => $tool->slug,
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

    private function iconKey(Tool $tool): string
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
            default => match ($tool->module_key) {
                'psychology' => 'mood',
                'career-guidance' => 'career',
                'nutrition' => 'nutrition',
                default => 'leaf',
            },
        };
    }

    private function isPublicEntry(Tool $tool): bool
    {
        if (!$tool->canLaunch()) {
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

    private function exploreCategory(Tool $tool): ?string
    {
        $haystack = mb_strtolower(implode(' ', array_filter([
            $tool->name,
            $tool->description,
            $tool->module_key,
            $tool->category?->name,
            $tool->category?->slug,
        ])));

        foreach (ExploreCatalog::CATEGORIES as $slug => $category) {
            foreach ($category['needles'] as $needle) {
                if ($needle !== '' && str_contains($haystack, mb_strtolower($needle))) {
                    return $slug;
                }
            }
        }

        return null;
    }

    private function matches(array $card, array $filters): bool
    {
        if ($filters['category'] !== '' && $card['category'] !== $filters['category']) {
            return false;
        }

        if ($filters['q'] === '') {
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

        foreach (array_keys(ExploreCatalog::CATEGORIES) as $slug) {
            $counts[$slug] = $published->filter(function (array $card) use ($search, $slug) {
                return $card['category'] === $slug && $this->matches($card, $search);
            })->count();
        }

        return $counts;
    }
}
