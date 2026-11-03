<?php

namespace App\Services;

use App\Models\NutritionFood;
use App\Models\NutritionFoodServing;
use App\Support\Nutrition\NutritionCatalog;
use InvalidArgumentException;

class NutritionPortionCalculator
{
    /**
     * @return array{
     *     portion_grams: ?string,
     *     multiplier: string,
     *     calories_kcal: ?string,
     *     protein_g: ?string,
     *     carbs_g: ?string,
     *     fat_g: ?string,
     *     fibre_g: ?string,
     *     incomplete: bool,
     *     missing: array<int, string>
     * }
     */
    public function estimate(NutritionFood $food, string $quantity, string $unit, ?int $servingId = null): array
    {
        if (! is_numeric($quantity) || ! is_finite((float) $quantity) || (float) $quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be a positive finite number.');
        }
        $qty = bcadd((string) $quantity, '0', 4);
        $unit = strtolower(trim($unit));

        $portionGrams = null;
        $multiplier = null;

        if ($food->reference_basis === 'per_100g') {
            $portionGrams = $this->resolveGrams($food, $qty, $unit, $servingId);
            $multiplier = bcdiv($portionGrams, '100', 8);
        } elseif ($food->reference_basis === 'per_100ml') {
            $portionMl = $this->resolveMillilitres($food, $qty, $unit, $servingId);
            $multiplier = bcdiv($portionMl, '100', 8);
        } elseif ($food->reference_basis === 'per_serving') {
            $serving = $this->requireServing($food, $servingId);
            if (strtolower($serving->unit) !== $unit && ! $this->isMassUnit($unit) && ! $this->isVolumeUnit($unit)) {
                throw new InvalidArgumentException('Unsupported unit for this serving definition.');
            }
            if ($this->isMassUnit($unit) && $serving->grams_equivalent) {
                $portionGrams = $qty;
                $multiplier = bcdiv($qty, (string) $serving->grams_equivalent, 8);
            } else {
                if (strtolower($serving->unit) !== $unit) {
                    throw new InvalidArgumentException('Unit must match the food serving unit.');
                }
                $servingQty = bcadd((string) $serving->quantity, '0', 4);
                if (bccomp($servingQty, '0', 4) <= 0) {
                    throw new InvalidArgumentException('Serving quantity is invalid.');
                }
                $multiplier = bcdiv($qty, $servingQty, 8);
                if ($serving->grams_equivalent) {
                    $portionGrams = bcmul($multiplier, (string) $serving->grams_equivalent, 4);
                }
            }
        } else {
            throw new InvalidArgumentException('Unknown nutrition reference basis.');
        }


        $scale = function (?string $value) use ($multiplier): ?string {
            if ($value === null) {
                return null;
            }

            return bcmul($value, $multiplier, 4);
        };

        $calories = $scale($food->calories_kcal !== null ? (string) $food->calories_kcal : null);
        $protein = $scale($food->protein_g !== null ? (string) $food->protein_g : null);
        $carbs = $scale($food->carbs_g !== null ? (string) $food->carbs_g : null);
        $fat = $scale($food->fat_g !== null ? (string) $food->fat_g : null);
        $fibre = $scale($food->fibre_g !== null ? (string) $food->fibre_g : null);

        $missing = [];
        foreach ([
            'calories' => $calories,
            'protein' => $protein,
            'carbohydrates' => $carbs,
            'fat' => $fat,
        ] as $label => $value) {
            if ($value === null) {
                $missing[] = $label;
            }
        }

        return [
            'portion_grams' => $portionGrams,
            'multiplier' => $multiplier,
            'calories_kcal' => $calories,
            'protein_g' => $protein,
            'carbs_g' => $carbs,
            'fat_g' => $fat,
            'fibre_g' => $fibre,
            'incomplete' => $missing !== [],
            'missing' => $missing,
        ];
    }

    private function resolveGrams(NutritionFood $food, string $qty, string $unit, ?int $servingId): string
    {
        if ($this->isVolumeUnit($unit)) {
            throw new InvalidArgumentException('Volume units cannot be converted to grams without a verified density. Use grams or a serving with gram equivalent.');
        }
        if ($this->isMassUnit($unit)) {
            return bcadd($qty, '0', 4);
        }

        $serving = $this->requireServing($food, $servingId);
        if (strtolower($serving->unit) !== $unit) {
            throw new InvalidArgumentException('Unsupported unit for this food. Choose grams or a defined serving unit.');
        }
        if ($serving->grams_equivalent === null) {
            throw new InvalidArgumentException('This serving has no verified gram equivalent. Use grams instead.');
        }
        $servingQty = bcadd((string) $serving->quantity, '0', 4);
        if (bccomp($servingQty, '0', 4) <= 0) {
            throw new InvalidArgumentException('Serving quantity is invalid.');
        }
        $portions = bcdiv($qty, $servingQty, 8);

        return bcmul($portions, (string) $serving->grams_equivalent, 4);
    }

    private function resolveMillilitres(NutritionFood $food, string $qty, string $unit, ?int $servingId): string
    {
        if ($this->isMassUnit($unit)) {
            throw new InvalidArgumentException('Mass units cannot be converted to millilitres without a verified density. Use ml or a volume serving.');
        }
        if ($this->isVolumeUnit($unit)) {
            return bcadd($qty, '0', 4);
        }

        $serving = $this->requireServing($food, $servingId);
        if (strtolower($serving->unit) !== $unit) {
            throw new InvalidArgumentException('Unsupported unit for this food. Choose ml or a defined serving unit.');
        }
        if (! $this->isVolumeUnit(strtolower((string) $serving->unit))) {
            throw new InvalidArgumentException('Cannot map this serving to millilitres without an explicit volume unit.');
        }
        $servingQty = bcadd((string) $serving->quantity, '0', 4);
        if (bccomp($servingQty, '0', 4) <= 0) {
            throw new InvalidArgumentException('Serving quantity is invalid.');
        }

        return bcmul(bcdiv($qty, $servingQty, 8), (string) $serving->quantity, 4);
    }



    private function requireServing(NutritionFood $food, ?int $servingId): NutritionFoodServing
    {
        if ($food->relationLoaded('servings')) {
            $serving = $servingId
                ? $food->servings->firstWhere('id', $servingId)
                : $food->servings->sortBy('id')->first();
            if ($serving) {
                return $serving;
            }
        }

        $query = $food->servings();
        $serving = $servingId
            ? $query->whereKey($servingId)->first()
            : $query->orderBy('id')->first();
        if (! $serving) {
            throw new InvalidArgumentException('No serving definition is available for this food.');
        }

        return $serving;
    }


    private function isMassUnit(string $unit): bool
    {
        return in_array($unit, NutritionCatalog::MASS_UNITS, true);
    }

    private function isVolumeUnit(string $unit): bool
    {
        return in_array($unit, NutritionCatalog::VOLUME_UNITS, true);
    }
}

