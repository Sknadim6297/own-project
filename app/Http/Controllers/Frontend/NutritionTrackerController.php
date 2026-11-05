<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\NutritionFood;
use App\Models\NutritionFoodCategory;
use App\Models\NutritionMealEntry;
use App\Models\Tool;
use App\Services\NutritionPortionCalculator;
use App\Services\NutritionTrackerService;
use App\Support\Nutrition\NutritionCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class NutritionTrackerController extends Controller
{
    public function __construct(
        private NutritionTrackerService $tracker,
        private NutritionPortionCalculator $calculator,
    ) {
    }

    public function dashboard(Request $request)
    {
        if (! $this->isOpen()) {
            return view('frontend.sections.nutrition.closed');
        }
        if (! Auth::guard('user')->check()) {
            return view('frontend.sections.nutrition.welcome', [
                'open' => true,
                'catalogueCount' => $this->catalogueCount(),
            ]);
        }

        $date = $this->resolveDate($request->query('date'));
        $userId = (int) Auth::guard('user')->id();
        $summary = $this->tracker->daySummary($userId, $date);
        $goals = $this->tracker->goalsFor($userId);

        return view('frontend.sections.nutrition.dashboard', [
            'summary' => $summary,
            'goals' => $goals,
            'meals' => NutritionCatalog::MEAL_TYPES,
            'catalogueCount' => $this->catalogueCount(),
            'importWorkbooksMissing' => NutritionCatalog::missingImportWorkbooks() !== [],
            'prev' => CarbonImmutable::createFromFormat('Y-m-d', $date, NutritionCatalog::TIMEZONE)->subDay()->toDateString(),
            'next' => CarbonImmutable::createFromFormat('Y-m-d', $date, NutritionCatalog::TIMEZONE)->addDay()->toDateString(),
            'isToday' => $date === $this->tracker->today(),
            'status' => session('status'),
        ]);
    }

    public function search(Request $request)
    {
        if (! $this->isOpen()) {
            return view('frontend.sections.nutrition.closed');
        }

        $q = trim((string) $request->query('q', ''));
        $category = $request->query('category');
        $state = $request->query('state');

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
            ->paginate(12)
            ->withQueryString();

        return view('frontend.sections.nutrition.search', [
            'foods' => $foods,
            'q' => $q,
            'category' => $category,
            'state' => $state,
            'categories' => NutritionFoodCategory::query()->where('is_active', true)->orderBy('display_order')->orderBy('name')->get(),
            'states' => NutritionCatalog::PREPARATION_STATES,
            'meal' => $request->query('meal', 'breakfast'),
            'authenticated' => Auth::guard('user')->check(),
            'catalogueCount' => $this->catalogueCount(),
            'importWorkbooksMissing' => NutritionCatalog::missingImportWorkbooks() !== [],
        ]);
    }

    public function portion(Request $request, NutritionFood $food)
    {
        if (! $this->isOpen()) {
            return view('frontend.sections.nutrition.closed');
        }
        abort_unless($food->is_published && $food->verification_status !== 'rejected', 404);
        $food->load('servings', 'category');

        $preview = null;
        $error = null;
        if ($request->filled('quantity') && $request->filled('unit')) {
            try {
                $preview = $this->calculator->estimate(
                    $food,
                    (string) $request->query('quantity'),
                    (string) $request->query('unit'),
                    $request->query('serving_id') ? (int) $request->query('serving_id') : null
                );
            } catch (InvalidArgumentException $exception) {
                $error = $exception->getMessage();
            }
        }

        return view('frontend.sections.nutrition.portion', [
            'food' => $food,
            'preview' => $preview,
            'error' => $error,
            'meal' => $request->query('meal', 'breakfast'),
            'authenticated' => Auth::guard('user')->check(),
            'meals' => NutritionCatalog::MEAL_TYPES,
        ]);
    }

    public function storeEntry(Request $request)
    {
        $this->requireOpenAuth();
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

        try {
            $at = ! empty($data['consumed_at'])
                ? CarbonImmutable::parse($data['consumed_at'], NutritionCatalog::TIMEZONE)
                : null;
            $this->tracker->addMealEntry(
                (int) Auth::guard('user')->id(),
                $food,
                $data['meal_type'],
                (string) $data['quantity'],
                (string) $data['unit'],
                $data['idempotency_key'],
                $at,
                isset($data['serving_id']) ? (int) $data['serving_id'] : null
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return redirect()->route('tools.nutrition.meal', [
            'date' => $this->tracker->today(),
            'meal' => $data['meal_type'],
        ])->with('status', 'Food added to meal.');
    }

    public function meal(Request $request)
    {
        $this->requireOpenAuth();
        $date = $this->resolveDate($request->query('date'));
        $meal = $request->query('meal', 'breakfast');
        if (! isset(NutritionCatalog::MEAL_TYPES[$meal])) {
            $meal = 'breakfast';
        }
        $summary = $this->tracker->daySummary((int) Auth::guard('user')->id(), $date);

        return view('frontend.sections.nutrition.meal', [
            'summary' => $summary,
            'meal' => $meal,
            'meals' => NutritionCatalog::MEAL_TYPES,
            'timezone' => NutritionCatalog::TIMEZONE,
        ]);
    }

    public function updateEntry(Request $request, NutritionMealEntry $entry)
    {
        $this->requireOpenAuth();
        $this->authorizeEntry($entry);
        $data = $request->validate([
            'quantity' => ['required'],
            'unit' => ['required', 'string', 'max:40'],
            'meal_type' => ['nullable', Rule::in(array_keys(NutritionCatalog::MEAL_TYPES))],
            'serving_id' => ['nullable', 'integer'],
        ]);
        $food = $entry->food ?: NutritionFood::query()->find($entry->nutrition_food_id);
        if (! $food) {
            return back()->withErrors(['quantity' => 'The original food is no longer available for recalculation.']);
        }
        try {
            $this->tracker->updateMealEntry(
                $entry,
                $food,
                (string) $data['quantity'],
                (string) $data['unit'],
                isset($data['serving_id']) ? (int) $data['serving_id'] : null,
                $data['meal_type'] ?? null
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return back()->with('status', 'Entry updated.');
    }

    public function destroyEntry(NutritionMealEntry $entry)
    {
        $this->requireOpenAuth();
        $this->authorizeEntry($entry);
        $date = $entry->consumed_at->timezone(NutritionCatalog::TIMEZONE)->toDateString();
        $meal = $entry->meal_type;
        $this->tracker->deleteMealEntry($entry);

        return redirect()->route('tools.nutrition.meal', ['date' => $date, 'meal' => $meal])
            ->with('status', 'Entry removed.');
    }

    public function insights(Request $request)
    {
        $this->requireOpenAuth();
        $end = $this->resolveDate($request->query('end'));
        $userId = (int) Auth::guard('user')->id();
        $days = $this->tracker->weekTrends($userId, $end);
        $goals = $this->tracker->goalsFor($userId);
        $today = $this->tracker->daySummary($userId, $end);

        return view('frontend.sections.nutrition.insights', [
            'days' => $days,
            'goals' => $goals,
            'today' => $today,
            'end' => $end,
            'prevEnd' => CarbonImmutable::createFromFormat('Y-m-d', $end, NutritionCatalog::TIMEZONE)->subDays(7)->toDateString(),
            'meals' => NutritionCatalog::MEAL_TYPES,
        ]);
    }

    public function goalsForm()
    {
        $this->requireOpenAuth();

        return view('frontend.sections.nutrition.goals', [
            'goals' => $this->tracker->goalsFor((int) Auth::guard('user')->id()),
        ]);
    }

    public function updateGoals(Request $request)
    {
        $this->requireOpenAuth();
        $data = $request->validate([
            'track_only' => ['sometimes', 'boolean'],
            'calories_kcal' => ['nullable', 'integer', 'min:500', 'max:10000'],
            'protein_g' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'carbs_g' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'fat_g' => ['nullable', 'numeric', 'min:0', 'max:500'],
        ]);
        $data['track_only'] = $request->boolean('track_only');
        $this->tracker->updateGoals((int) Auth::guard('user')->id(), $data);

        return redirect()->route('tools.nutrition')->with('status', 'Nutrition goals updated.');
    }

    private function resolveDate(?string $date): string
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

    private function requireOpenAuth(): void
    {
        if (! $this->isOpen()) {
            abort(403);
        }
        if (! Auth::guard('user')->check()) {
            abort(403);
        }
    }

    private function authorizeEntry(NutritionMealEntry $entry): void
    {
        if ((int) $entry->user_id !== (int) Auth::guard('user')->id()) {
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

    private function catalogueCount(): int
    {
        try {
            return (int) NutritionFood::query()->catalogueVisible()->count();
        } catch (QueryException) {
            return 0;
        }
    }
}
