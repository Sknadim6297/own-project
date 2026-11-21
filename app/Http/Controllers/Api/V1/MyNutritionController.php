<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NutritionFood;
use App\Models\NutritionFoodCategory;
use App\Models\NutritionMealEntry;
use App\Models\NutritionUserGoal;
use App\Models\Tool;
use App\Models\User;
use App\Services\NutritionPortionCalculator;
use App\Services\NutritionTrackerService;
use App\Support\Nutrition\NutritionCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Account-backed Calorie & Nutrition Tracker for the mobile app.
 * Reuses NutritionTrackerService / NutritionPortionCalculator and existing nutrition_* tables.
 */
class MyNutritionController extends Controller
{
    public function __construct(
        private NutritionTrackerService $tracker,
        private NutritionPortionCalculator $calculator,
    ) {
    }

    public function dashboard(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $date = $this->resolveDate($request->query('date'));
        $userId = (int) $user->id;
        $summary = $this->tracker->daySummary($userId, $date);
        $goals = $this->tracker->goalsFor($userId);
        $progress = $this->progressPayload($goals, $summary['totals']);

        return response()->json([
            'status' => 'success',
            'open' => true,
            'timezone' => NutritionCatalog::TIMEZONE,
            'date' => $date,
            'is_today' => $date === $this->tracker->today(),
            'prev_date' => CarbonImmutable::createFromFormat('Y-m-d', $date, NutritionCatalog::TIMEZONE)
                ->subDay()->toDateString(),
            'next_date' => CarbonImmutable::createFromFormat('Y-m-d', $date, NutritionCatalog::TIMEZONE)
                ->addDay()->toDateString(),
            'meals' => collect(NutritionCatalog::MEAL_TYPES)->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
            ])->values(),
            'catalogue_count' => $this->catalogueCount(),
            'goals' => $this->presentGoals($goals),
            'totals' => $this->presentTotals($summary['totals']),
            'progress' => $progress,
            'has_entries' => $summary['has_entries'],
            'has_incomplete' => $summary['has_incomplete'],
            'by_meal' => collect($summary['by_meal'])->map(fn (array $block, string $key) => [
                'key' => $key,
                'label' => $block['label'],
                'totals' => $this->presentTotals($block['totals']),
                'entry_count' => $block['entries']->count(),
                'entries' => $block['entries']->map(fn (NutritionMealEntry $entry) => $this->presentEntry($entry))->values(),
            ])->values(),
        ]);
    }

    public function insights(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $end = $this->resolveDate($request->query('end'));
        $userId = (int) $user->id;
        $days = $this->tracker->weekTrends($userId, $end);
        $goals = $this->tracker->goalsFor($userId);

        return response()->json([
            'status' => 'success',
            'open' => true,
            'timezone' => NutritionCatalog::TIMEZONE,
            'end' => $end,
            'prev_end' => CarbonImmutable::createFromFormat('Y-m-d', $end, NutritionCatalog::TIMEZONE)
                ->subDays(7)->toDateString(),
            'goals' => $this->presentGoals($goals),
            'days' => collect($days)->map(fn (array $day) => [
                'date' => $day['date'],
                'label' => $day['label'],
                'calories_kcal' => $day['calories'] !== null ? (string) $day['calories'] : null,
                'has_entries' => $day['has_entries'],
                'incomplete' => $day['incomplete'],
                'by_meal' => collect($day['by_meal'])->map(
                    fn ($kcal) => $kcal !== null ? (string) $kcal : null
                )->all(),
            ])->values(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $q = trim(strip_tags((string) $request->query('q', '')));
        $q = mb_substr($q, 0, 120);
        $category = $request->query('category');
        $state = $request->query('state');
        $page = max(1, (int) $request->query('page', 1));

        $foods = NutritionFood::query()
            ->catalogueVisible()
            ->with(['category', 'servings'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', '%'.$q.'%')
                        ->orWhere('brand', 'like', '%'.$q.'%')
                        ->orWhere('variant', 'like', '%'.$q.'%');
                });
            })
            ->when($category, fn ($query) => $query->where('nutrition_food_category_id', $category))
            ->when($state, fn ($query) => $query->where('preparation_state', $state))
            ->orderBy('name')
            ->paginate(12, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'open' => true,
            'q' => $q,
            'catalogue_count' => $this->catalogueCount(),
            'categories' => NutritionFoodCategory::query()
                ->where('is_active', true)
                ->orderBy('display_order')
                ->orderBy('name')
                ->get()
                ->map(fn (NutritionFoodCategory $cat) => [
                    'id' => (int) $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                ])->values(),
            'states' => collect(NutritionCatalog::PREPARATION_STATES)->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
            ])->values(),
            'meals' => collect(NutritionCatalog::MEAL_TYPES)->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
            ])->values(),
            'foods' => collect($foods->items())->map(fn (NutritionFood $food) => $this->presentFood($food))->values(),
            'meta' => [
                'current_page' => $foods->currentPage(),
                'last_page' => $foods->lastPage(),
                'per_page' => $foods->perPage(),
                'total' => $foods->total(),
            ],
        ]);
    }

    public function portion(Request $request, NutritionFood $food): JsonResponse
    {
        $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }
        if (! $food->is_published || $food->verification_status === 'rejected') {
            abort(404);
        }
        $food->load('servings', 'category');

        $preview = null;
        if ($request->filled('quantity') && $request->filled('unit')) {
            try {
                $preview = $this->calculator->estimate(
                    $food,
                    (string) $request->query('quantity'),
                    (string) $request->query('unit'),
                    $request->query('serving_id') ? (int) $request->query('serving_id') : null
                );
            } catch (InvalidArgumentException $exception) {
                throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
            }
        }

        return response()->json([
            'status' => 'success',
            'open' => true,
            'food' => $this->presentFood($food),
            'preview' => $preview ? [
                'portion_grams' => $preview['portion_grams'],
                'calories_kcal' => $preview['calories_kcal'],
                'protein_g' => $preview['protein_g'],
                'carbs_g' => $preview['carbs_g'],
                'fat_g' => $preview['fat_g'],
                'fibre_g' => $preview['fibre_g'],
                'incomplete' => $preview['incomplete'],
                'missing' => $preview['missing'],
            ] : null,
            'meals' => collect(NutritionCatalog::MEAL_TYPES)->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
            ])->values(),
            'mass_units' => NutritionCatalog::MASS_UNITS,
            'volume_units' => NutritionCatalog::VOLUME_UNITS,
        ]);
    }

    public function storeEntry(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $data = $request->validate([
            'food_id' => ['required', 'integer', 'exists:nutrition_foods,id'],
            'meal_type' => ['required', Rule::in(array_keys(NutritionCatalog::MEAL_TYPES))],
            'quantity' => ['required'],
            'unit' => ['required', 'string', 'max:40'],
            'serving_id' => ['nullable', 'integer'],
            'consumed_at' => ['nullable', 'date'],
            'idempotency_key' => ['required', 'string', 'max:64'],
        ]);

        $food = NutritionFood::query()->findOrFail($data['food_id']);
        $prior = NutritionMealEntry::query()
            ->where('user_id', (int) $user->id)
            ->where('idempotency_key', $data['idempotency_key'])
            ->first();

        try {
            $at = ! empty($data['consumed_at'])
                ? CarbonImmutable::parse($data['consumed_at'], NutritionCatalog::TIMEZONE)
                : null;
            $entry = $this->tracker->addMealEntry(
                (int) $user->id,
                $food,
                $data['meal_type'],
                (string) $data['quantity'],
                (string) $data['unit'],
                $data['idempotency_key'],
                $at,
                isset($data['serving_id']) ? (int) $data['serving_id'] : null
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        $created = $prior === null;
        $date = CarbonImmutable::parse($entry->consumed_at)
            ->timezone(NutritionCatalog::TIMEZONE)
            ->toDateString();

        return response()->json([
            'status' => 'success',
            'created' => $created,
            'entry' => $this->presentEntry($entry),
            'date' => $date,
            'dashboard' => $this->compactDay((int) $user->id, $date),
        ], $created ? 201 : 200);
    }

    public function updateEntry(Request $request, NutritionMealEntry $entry): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }
        $this->authorizeEntry($entry, $user);

        $data = $request->validate([
            'quantity' => ['required'],
            'unit' => ['required', 'string', 'max:40'],
            'meal_type' => ['nullable', Rule::in(array_keys(NutritionCatalog::MEAL_TYPES))],
            'serving_id' => ['nullable', 'integer'],
        ]);

        $food = $entry->food ?: NutritionFood::query()->find($entry->nutrition_food_id);
        if (! $food) {
            throw ValidationException::withMessages([
                'quantity' => 'The original food is no longer available for recalculation.',
            ]);
        }

        try {
            $entry = $this->tracker->updateMealEntry(
                $entry,
                $food,
                (string) $data['quantity'],
                (string) $data['unit'],
                isset($data['serving_id']) ? (int) $data['serving_id'] : null,
                $data['meal_type'] ?? null
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        $date = CarbonImmutable::parse($entry->consumed_at)
            ->timezone(NutritionCatalog::TIMEZONE)
            ->toDateString();

        return response()->json([
            'status' => 'success',
            'entry' => $this->presentEntry($entry),
            'dashboard' => $this->compactDay((int) $user->id, $date),
        ]);
    }

    public function destroyEntry(NutritionMealEntry $entry, Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }
        $this->authorizeEntry($entry, $user);

        $date = CarbonImmutable::parse($entry->consumed_at)
            ->timezone(NutritionCatalog::TIMEZONE)
            ->toDateString();
        $this->tracker->deleteMealEntry($entry);

        return response()->json([
            'status' => 'success',
            'deleted' => true,
            'dashboard' => $this->compactDay((int) $user->id, $date),
        ]);
    }

    public function updateGoals(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $data = $request->validate([
            'track_only' => ['sometimes', 'boolean'],
            'calories_kcal' => ['nullable', 'integer', 'min:500', 'max:10000'],
            'protein_g' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'carbs_g' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'fat_g' => ['nullable', 'numeric', 'min:0', 'max:500'],
        ]);
        $data['track_only'] = $request->boolean('track_only');
        $goals = $this->tracker->updateGoals((int) $user->id, $data);
        $date = $this->tracker->today();

        return response()->json([
            'status' => 'success',
            'goals' => $this->presentGoals($goals),
            'dashboard' => $this->compactDay((int) $user->id, $date),
        ]);
    }

    private function compactDay(int $userId, string $date): array
    {
        $summary = $this->tracker->daySummary($userId, $date);
        $goals = $this->tracker->goalsFor($userId);

        return [
            'date' => $date,
            'totals' => $this->presentTotals($summary['totals']),
            'progress' => $this->progressPayload($goals, $summary['totals']),
            'goals' => $this->presentGoals($goals),
            'has_entries' => $summary['has_entries'],
            'has_incomplete' => $summary['has_incomplete'],
        ];
    }

    private function progressPayload(NutritionUserGoal $goals, array $totals): array
    {
        $calGoal = (! $goals->track_only && $goals->calories_kcal) ? (int) $goals->calories_kcal : null;
        $calories = $totals['calories_kcal'];
        $percent = null;
        $remaining = null;
        if ($calGoal !== null && $calories !== null) {
            $percent = (int) round(((float) $calories / $calGoal) * 100);
            $remaining = (string) ((int) round($calGoal - (float) $calories));
        }

        return [
            'meaningful' => $calGoal !== null,
            'calories_goal_kcal' => $calGoal,
            'percent' => $percent,
            'ring_percent' => $percent !== null ? min(100, max(0, $percent)) : null,
            'remaining_kcal' => $remaining,
            'track_only' => (bool) $goals->track_only,
        ];
    }

    private function presentGoals(NutritionUserGoal $goals): array
    {
        return [
            'track_only' => (bool) $goals->track_only,
            'calories_kcal' => $goals->calories_kcal !== null ? (int) $goals->calories_kcal : null,
            'protein_g' => $goals->protein_g !== null ? (string) $goals->protein_g : null,
            'carbs_g' => $goals->carbs_g !== null ? (string) $goals->carbs_g : null,
            'fat_g' => $goals->fat_g !== null ? (string) $goals->fat_g : null,
        ];
    }

    private function presentTotals(array $totals): array
    {
        return [
            'calories_kcal' => $totals['calories_kcal'] !== null ? (string) $totals['calories_kcal'] : null,
            'protein_g' => $totals['protein_g'] !== null ? (string) $totals['protein_g'] : null,
            'carbs_g' => $totals['carbs_g'] !== null ? (string) $totals['carbs_g'] : null,
            'fat_g' => $totals['fat_g'] !== null ? (string) $totals['fat_g'] : null,
            'fibre_g' => $totals['fibre_g'] !== null ? (string) $totals['fibre_g'] : null,
        ];
    }

    private function presentEntry(NutritionMealEntry $entry): array
    {
        return [
            'id' => (int) $entry->id,
            'nutrition_food_id' => $entry->nutrition_food_id ? (int) $entry->nutrition_food_id : null,
            'meal_type' => $entry->meal_type,
            'meal_label' => NutritionCatalog::MEAL_TYPES[$entry->meal_type] ?? $entry->meal_type,
            'quantity' => (string) $entry->quantity,
            'unit' => $entry->unit,
            'portion_grams' => $entry->portion_grams !== null ? (string) $entry->portion_grams : null,
            'food_name' => $entry->food_name_snapshot,
            'brand' => $entry->brand_snapshot,
            'calories_kcal' => $entry->calories_kcal_snapshot !== null ? (string) $entry->calories_kcal_snapshot : null,
            'protein_g' => $entry->protein_g_snapshot !== null ? (string) $entry->protein_g_snapshot : null,
            'carbs_g' => $entry->carbs_g_snapshot !== null ? (string) $entry->carbs_g_snapshot : null,
            'fat_g' => $entry->fat_g_snapshot !== null ? (string) $entry->fat_g_snapshot : null,
            'fibre_g' => $entry->fibre_g_snapshot !== null ? (string) $entry->fibre_g_snapshot : null,
            'incomplete_nutrients' => (bool) $entry->incomplete_nutrients,
            'consumed_at' => CarbonImmutable::parse($entry->consumed_at)
                ->timezone(NutritionCatalog::TIMEZONE)
                ->toIso8601String(),
            'idempotency_key' => $entry->idempotency_key,
        ];
    }

    private function presentFood(NutritionFood $food): array
    {
        return [
            'id' => (int) $food->id,
            'name' => $food->name,
            'brand' => $food->brand,
            'variant' => $food->variant,
            'preparation_state' => $food->preparation_state,
            'reference_basis' => $food->reference_basis,
            'calories_kcal' => $food->calories_kcal !== null ? (string) $food->calories_kcal : null,
            'protein_g' => $food->protein_g !== null ? (string) $food->protein_g : null,
            'carbs_g' => $food->carbs_g !== null ? (string) $food->carbs_g : null,
            'fat_g' => $food->fat_g !== null ? (string) $food->fat_g : null,
            'fibre_g' => $food->fibre_g !== null ? (string) $food->fibre_g : null,
            'category' => $food->category ? [
                'id' => (int) $food->category->id,
                'name' => $food->category->name,
            ] : null,
            'servings' => $food->relationLoaded('servings')
                ? $food->servings->map(fn ($s) => [
                    'id' => (int) $s->id,
                    'label' => $s->label,
                    'quantity' => (string) $s->quantity,
                    'unit' => $s->unit,
                    'grams_equivalent' => $s->grams_equivalent !== null ? (string) $s->grams_equivalent : null,
                ])->values()
                : [],
        ];
    }

    private function resolveDate(mixed $date): string
    {
        if (! is_string($date) || $date === '') {
            return $this->tracker->today();
        }
        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $date, NutritionCatalog::TIMEZONE)->toDateString();
        } catch (\Throwable) {
            return $this->tracker->today();
        }
    }

    private function authorizeEntry(NutritionMealEntry $entry, User $user): void
    {
        if ((int) $entry->user_id !== (int) $user->id) {
            abort(403);
        }
    }

    private function isOpen(): bool
    {
        try {
            $tool = Tool::query()->where('slug', 'calorie-nutrition-tracker')->first();
        } catch (QueryException) {
            return false;
        }

        return $tool ? $tool->canLaunch() : false;
    }

    private function closed(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'open' => false,
            'message' => 'This tool is not available right now.',
        ], 403);
    }

    private function catalogueCount(): int
    {
        try {
            return (int) NutritionFood::query()->catalogueVisible()->count();
        } catch (QueryException) {
            return 0;
        }
    }

    private function customer(Request $request): User
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
