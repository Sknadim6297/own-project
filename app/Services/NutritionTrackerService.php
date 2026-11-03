<?php

namespace App\Services;

use App\Models\NutritionFood;
use App\Models\NutritionMealEntry;
use App\Models\NutritionUserGoal;
use App\Support\Nutrition\NutritionCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class NutritionTrackerService
{
    public function __construct(private NutritionPortionCalculator $calculator)
    {
    }

    public function timezone(): string
    {
        return NutritionCatalog::TIMEZONE;
    }

    public function today(): string
    {
        return CarbonImmutable::now($this->timezone())->toDateString();
    }

    public function dayBounds(string $localDate): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m-d', $localDate, $this->timezone())->startOfDay();

        return [$start->utc(), $start->addDay()->utc()];
    }

    public function goalsFor(int $userId): NutritionUserGoal
    {
        $goal = NutritionUserGoal::query()->where('user_id', $userId)->first();
        if ($goal) {
            return $goal;
        }
        $goal = new NutritionUserGoal(['track_only' => true]);
        $goal->user_id = $userId;
        $goal->save();

        return $goal;
    }

    public function updateGoals(int $userId, array $data): NutritionUserGoal
    {
        $goal = $this->goalsFor($userId);
        $trackOnly = (bool) ($data['track_only'] ?? false);
        $goal->track_only = $trackOnly;
        if ($trackOnly) {
            $goal->calories_kcal = null;
            $goal->protein_g = null;
            $goal->carbs_g = null;
            $goal->fat_g = null;
        } else {
            $goal->calories_kcal = isset($data['calories_kcal']) && $data['calories_kcal'] !== ''
                ? (int) $data['calories_kcal'] : null;
            $goal->protein_g = isset($data['protein_g']) && $data['protein_g'] !== ''
                ? $data['protein_g'] : null;
            $goal->carbs_g = isset($data['carbs_g']) && $data['carbs_g'] !== ''
                ? $data['carbs_g'] : null;
            $goal->fat_g = isset($data['fat_g']) && $data['fat_g'] !== ''
                ? $data['fat_g'] : null;
        }
        $goal->save();

        return $goal;
    }

    public function addMealEntry(
        int $userId,
        NutritionFood $food,
        string $mealType,
        string $quantity,
        string $unit,
        string $idempotencyKey,
        ?CarbonImmutable $consumedAt = null,
        ?int $servingId = null
    ): NutritionMealEntry {
        if (! isset(NutritionCatalog::MEAL_TYPES[$mealType])) {
            throw new InvalidArgumentException('Meal type is invalid.');
        }
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 64) {
            throw new InvalidArgumentException('Idempotency key is invalid.');
        }
        if (! $food->is_published || $food->verification_status === 'rejected') {
            throw new InvalidArgumentException('This food is not available for logging.');
        }

        $estimate = $this->calculator->estimate($food, $quantity, $unit, $servingId);
        $consumedAt = ($consumedAt ?? CarbonImmutable::now($this->timezone()))->timezone('UTC');

        return DB::transaction(function () use ($userId, $food, $mealType, $quantity, $unit, $idempotencyKey, $consumedAt, $estimate) {
            $existing = NutritionMealEntry::query()
                ->where('user_id', $userId)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                return $existing;
            }

            $entry = new NutritionMealEntry([
                'nutrition_food_id' => $food->id,
                'meal_type' => $mealType,
                'quantity' => $quantity,
                'unit' => $unit,
                'portion_grams' => $estimate['portion_grams'],
                'consumed_at' => $consumedAt,
                'food_name_snapshot' => $food->name,
                'brand_snapshot' => $food->brand,
                'calories_kcal_snapshot' => $estimate['calories_kcal'],
                'protein_g_snapshot' => $estimate['protein_g'],
                'carbs_g_snapshot' => $estimate['carbs_g'],
                'fat_g_snapshot' => $estimate['fat_g'],
                'fibre_g_snapshot' => $estimate['fibre_g'],
                'incomplete_nutrients' => $estimate['incomplete'],
                'idempotency_key' => $idempotencyKey,
            ]);
            $entry->user_id = $userId;
            $entry->save();

            return $entry;
        });
    }

    public function updateMealEntry(
        NutritionMealEntry $entry,
        NutritionFood $food,
        string $quantity,
        string $unit,
        ?int $servingId = null,
        ?string $mealType = null
    ): NutritionMealEntry {
        $estimate = $this->calculator->estimate($food, $quantity, $unit, $servingId);
        if ($mealType !== null) {
            if (! isset(NutritionCatalog::MEAL_TYPES[$mealType])) {
                throw new InvalidArgumentException('Meal type is invalid.');
            }
            $entry->meal_type = $mealType;
        }
        $entry->nutrition_food_id = $food->id;
        $entry->quantity = $quantity;
        $entry->unit = $unit;
        $entry->portion_grams = $estimate['portion_grams'];
        $entry->food_name_snapshot = $food->name;
        $entry->brand_snapshot = $food->brand;
        $entry->calories_kcal_snapshot = $estimate['calories_kcal'];
        $entry->protein_g_snapshot = $estimate['protein_g'];
        $entry->carbs_g_snapshot = $estimate['carbs_g'];
        $entry->fat_g_snapshot = $estimate['fat_g'];
        $entry->fibre_g_snapshot = $estimate['fibre_g'];
        $entry->incomplete_nutrients = $estimate['incomplete'];
        $entry->save();

        return $entry;
    }

    public function deleteMealEntry(NutritionMealEntry $entry): void
    {
        $entry->delete();
    }

    public function dayEntries(int $userId, string $localDate): Collection
    {
        [$start, $end] = $this->dayBounds($localDate);

        return NutritionMealEntry::query()
            ->where('user_id', $userId)
            ->where('consumed_at', '>=', $start)
            ->where('consumed_at', '<', $end)
            ->orderBy('consumed_at')
            ->orderBy('id')
            ->get();
    }

    public function daySummary(int $userId, string $localDate): array
    {
        $entries = $this->dayEntries($userId, $localDate);
        $totals = $this->sumEntries($entries);
        $byMeal = [];
        foreach (NutritionCatalog::MEAL_TYPES as $key => $label) {
            $mealEntries = $entries->where('meal_type', $key)->values();
            $byMeal[$key] = [
                'label' => $label,
                'entries' => $mealEntries,
                'totals' => $this->sumEntries($mealEntries),
            ];
        }

        return [
            'date' => $localDate,
            'entries' => $entries,
            'totals' => $totals,
            'by_meal' => $byMeal,
            'has_entries' => $entries->isNotEmpty(),
            'has_incomplete' => $entries->contains(fn ($e) => $e->incomplete_nutrients),
        ];
    }

    public function weekTrends(int $userId, string $endLocalDate): array
    {
        $end = CarbonImmutable::createFromFormat('Y-m-d', $endLocalDate, $this->timezone())->startOfDay();
        $start = $end->subDays(6);
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $start->addDays($i)->toDateString();
            $summary = $this->daySummary($userId, $date);
            $days[] = [
                'date' => $date,
                'label' => $start->addDays($i)->format('D'),
                'calories' => $summary['totals']['calories_kcal'],
                'has_entries' => $summary['has_entries'],
                'incomplete' => $summary['has_incomplete'],
                'by_meal' => collect($summary['by_meal'])->mapWithKeys(
                    fn ($m, $k) => [$k => $m['totals']['calories_kcal']]
                )->all(),
            ];
        }

        return $days;
    }

    private function sumEntries(Collection $entries): array
    {
        $sum = function (string $field) use ($entries): ?string {
            $values = $entries->pluck($field)->filter(fn ($v) => $v !== null);
            if ($values->isEmpty()) {
                return null;
            }
            $total = '0';
            foreach ($values as $value) {
                $total = bcadd($total, (string) $value, 4);
            }

            return $total;
        };

        return [
            'calories_kcal' => $sum('calories_kcal_snapshot'),
            'protein_g' => $sum('protein_g_snapshot'),
            'carbs_g' => $sum('carbs_g_snapshot'),
            'fat_g' => $sum('fat_g_snapshot'),
            'fibre_g' => $sum('fibre_g_snapshot'),
        ];
    }
}
