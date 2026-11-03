<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesToolRegistry;
use App\Http\Controllers\Controller;
use App\Models\NutritionFood;
use App\Models\NutritionFoodCategory;
use App\Services\Nutrition\NutritionFoodReviewService;
use App\Support\Nutrition\NutritionCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;


class NutritionFoodController extends Controller
{
    use AuthorizesToolRegistry;

    public function index(Request $request)
    {
        $this->authorizeToolRegistry('tools_management');

        $foods = NutritionFood::query()
            ->with(['category', 'importBatch'])
            ->when($request->query('q'), function ($query, $q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', '%'.$q.'%')
                        ->orWhere('brand', 'like', '%'.$q.'%');
                });
            })
            ->when($request->query('status'), fn ($q, $s) => $q->where('verification_status', $s))
            ->when($request->query('completeness'), fn ($q, $c) => $q->where('nutrient_completeness', $c))
            ->when($request->query('published'), function ($q, $p) {
                if ($p === '1') {
                    $q->where('is_published', true);
                } elseif ($p === '0') {
                    $q->where('is_published', false);
                }
            })
            ->when($request->query('batch_id'), fn ($q, $id) => $q->where('nutrition_import_batch_id', $id))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.nutrition.foods.index', [
            'foods' => $foods,
            'statuses' => NutritionCatalog::VERIFICATION_STATUSES,
            'completeness' => NutritionCatalog::NUTRIENT_COMPLETENESS,
        ]);
    }


    public function create()
    {
        $this->authorizeToolRegistry('tools_management');

        return view('admin.nutrition.foods.form', [
            'food' => new NutritionFood(['reference_basis' => 'per_100g', 'verification_status' => 'unverified']),
            'categories' => NutritionFoodCategory::query()->orderBy('name')->get(),
            'states' => NutritionCatalog::PREPARATION_STATES,
            'bases' => NutritionCatalog::REFERENCE_BASES,
            'statuses' => NutritionCatalog::VERIFICATION_STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeToolRegistry('tools_management');
        $data = $this->validated($request);
        $food = NutritionFood::query()->create($data);
        $this->syncDefaultServing($food, $request);

        return redirect()->route('admin.nutrition-foods.edit', $food)->with('success', 'Food created as unverified until reviewed.');
    }

    public function edit(NutritionFood $nutritionFood)
    {
        $this->authorizeToolRegistry('tools_management');
        $nutritionFood->load(['servings', 'category', 'reviewEvents' => fn ($q) => $q->latest()->limit(20), 'duplicateReviews.candidate']);

        return view('admin.nutrition.foods.form', [
            'food' => $nutritionFood,
            'categories' => NutritionFoodCategory::query()->orderBy('name')->get(),
            'states' => NutritionCatalog::PREPARATION_STATES,
            'bases' => NutritionCatalog::REFERENCE_BASES,
            'statuses' => NutritionCatalog::VERIFICATION_STATUSES,
            'completeness' => NutritionCatalog::NUTRIENT_COMPLETENESS,
        ]);
    }

    public function update(Request $request, NutritionFood $nutritionFood)
    {
        $this->authorizeToolRegistry('tools_management');
        if ($nutritionFood->isVerified() && ! $request->boolean('allow_verified_edit')) {
            return back()->withErrors(['verification_status' => 'Verified foods are protected. Confirm allow_verified_edit to change nutrient values.']);
        }
        $nutritionFood->update($this->validated($request));
        $this->syncDefaultServing($nutritionFood, $request);

        return redirect()->route('admin.nutrition-foods.edit', $nutritionFood)->with('success', 'Food updated.');
    }

    public function review(Request $request, NutritionFood $nutritionFood, NutritionFoodReviewService $reviews)
    {
        $this->authorizeToolRegistry('tools_management');
        $data = $request->validate([
            'action' => ['required', 'in:verify,needs_verification,reject,publish,unpublish'],
            'source_basis' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'publish_after_verify' => ['sometimes', 'boolean'],
        ]);

        try {
            $reviews->transition(
                $nutritionFood,
                $data['action'],
                auth('admin')->id(),
                $data['source_basis'] ?? null,
                $data['notes'] ?? null,
                null,
                $request->boolean('publish_after_verify') ? true : null
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }

        return back()->with('success', 'Review action recorded.');
    }


    public function storeCategory(Request $request)
    {
        $this->authorizeToolRegistry('tools_management');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);
        $slug = Str::slug($data['name']);
        NutritionFoodCategory::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $data['name'], 'is_active' => true]
        );

        return back()->with('success', 'Category saved.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'nutrition_food_category_id' => ['nullable', 'integer', 'exists:nutrition_food_categories,id'],
            'name' => ['required', 'string', 'max:180'],
            'brand' => ['nullable', 'string', 'max:120'],
            'variant' => ['nullable', 'string', 'max:120'],
            'preparation_state' => ['required', Rule::in(array_keys(NutritionCatalog::PREPARATION_STATES))],
            'reference_basis' => ['required', Rule::in(array_keys(NutritionCatalog::REFERENCE_BASES))],
            'calories_kcal' => ['nullable', 'numeric'],
            'protein_g' => ['nullable', 'numeric'],
            'carbs_g' => ['nullable', 'numeric'],
            'fat_g' => ['nullable', 'numeric'],
            'fibre_g' => ['nullable', 'numeric'],
            'sugar_g' => ['nullable', 'numeric'],
            'sodium_mg' => ['nullable', 'numeric'],
            'saturated_fat_g' => ['nullable', 'numeric'],
            'source_name' => ['nullable', 'string', 'max:180'],
            'source_url' => ['nullable', 'string', 'max:255'],
            'verification_status' => ['required', Rule::in(array_keys(NutritionCatalog::VERIFICATION_STATUSES))],
            'nutrient_completeness' => ['nullable', Rule::in(array_keys(NutritionCatalog::NUTRIENT_COMPLETENESS))],
            'is_published' => ['sometimes', 'boolean'],
        ]);
        $data['is_published'] = $request->boolean('is_published');
        if (($data['is_published'] ?? false) && ($data['verification_status'] ?? '') !== 'verified') {
            $data['is_published'] = false;
        }
        foreach (['calories_kcal', 'protein_g', 'carbs_g', 'fat_g', 'fibre_g', 'sugar_g', 'sodium_mg', 'saturated_fat_g'] as $field) {
            if (! array_key_exists($field, $data) || $data[$field] === '' || $data[$field] === null) {
                $data[$field] = null;
            }
        }
        $data['normalized_name'] = Str::of($data['name'])->lower()->trim()->toString();

        return $data;
    }


    private function syncDefaultServing(NutritionFood $food, Request $request): void
    {
        if (! $request->filled('serving_label')) {
            return;
        }
        $payload = $request->validate([
            'serving_label' => ['required', 'string', 'max:80'],
            'serving_quantity' => ['required', 'numeric', 'gt:0'],
            'serving_unit' => ['required', 'string', 'max:40'],
            'serving_grams' => ['nullable', 'numeric', 'gt:0'],
        ]);

        DB::transaction(function () use ($food, $payload) {
            $serving = $food->servings()->first();
            $attrs = [
                'label' => $payload['serving_label'],
                'quantity' => $payload['serving_quantity'],
                'unit' => $payload['serving_unit'],
                'grams_equivalent' => $payload['serving_grams'] ?? null,
            ];
            if ($serving) {
                $serving->update($attrs);
            } else {
                $food->servings()->create($attrs);
            }
        });
    }
}
