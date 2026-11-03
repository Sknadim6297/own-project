<?php

namespace App\Services;

use App\Support\BmiBmr\BmiBmrCatalog;
use InvalidArgumentException;

class BmiBmrCalculator
{
    /**
     * @param  array{
     *     age: int,
     *     height_cm: float,
     *     weight_kg: float,
     *     sex: string,
     *     activity: string,
     *     excluded_population?: bool
     * }  $input  Already normalized metric values
     */
    public function calculate(array $input): array
    {
        $age = (int) $input['age'];
        $heightCm = (float) $input['height_cm'];
        $weightKg = (float) $input['weight_kg'];
        $sex = (string) $input['sex'];
        $activityKey = (string) $input['activity'];
        $excluded = (bool) ($input['excluded_population'] ?? false);

        $this->assertFinitePositive($heightCm, 'height');
        $this->assertFinitePositive($weightKg, 'weight');
        if ($age < BmiBmrCatalog::AGE_MIN || $age > BmiBmrCatalog::AGE_MAX) {
            throw new InvalidArgumentException('Age is outside the supported range.');
        }
        if ($heightCm < BmiBmrCatalog::HEIGHT_CM_MIN || $heightCm > BmiBmrCatalog::HEIGHT_CM_MAX) {
            throw new InvalidArgumentException('Height is outside the supported range.');
        }
        if ($weightKg < BmiBmrCatalog::WEIGHT_KG_MIN || $weightKg > BmiBmrCatalog::WEIGHT_KG_MAX) {
            throw new InvalidArgumentException('Weight is outside the supported range.');
        }
        if (! in_array($sex, BmiBmrCatalog::sexKeys(), true)) {
            throw new InvalidArgumentException('Sex input is invalid.');
        }
        if (! isset(BmiBmrCatalog::ACTIVITIES[$activityKey])) {
            throw new InvalidArgumentException('Activity level is invalid.');
        }

        $heightM = $heightCm / 100;
        $bmiExact = $weightKg / ($heightM * $heightM);
        $isAdult = $age >= BmiBmrCatalog::ADULT_MIN_AGE;
        $category = null;
        if ($isAdult) {
            $category = $this->classifyBmi($bmiExact);
        }

        $activity = BmiBmrCatalog::ACTIVITIES[$activityKey];
        $bmrExact = null;
        $tdeeExact = null;
        $energyEligible = $isAdult
            && ! $excluded
            && in_array($sex, [BmiBmrCatalog::SEX_MALE, BmiBmrCatalog::SEX_FEMALE], true);

        if ($energyEligible) {
            $base = (10 * $weightKg) + (6.25 * $heightCm) - (5 * $age);
            $bmrExact = $sex === BmiBmrCatalog::SEX_MALE ? ($base + 5) : ($base - 161);
            $tdeeExact = $bmrExact * $activity['factor'];
        }

        return [
            'formula_version' => BmiBmrCatalog::FORMULA_VERSION,
            'inputs' => [
                'age' => $age,
                'height_cm' => round($heightCm, 2),
                'weight_kg' => round($weightKg, 2),
                'sex' => $sex,
                'activity' => $activityKey,
                'activity_factor' => $activity['factor'],
                'activity_label' => $activity['label'],
                'excluded_population' => $excluded,
            ],
            'bmi' => [
                'exact' => $bmiExact,
                'display' => round($bmiExact, 1),
                'unit' => 'kg/m²',
                'is_adult' => $isAdult,
                'category' => $category,
            ],
            'bmr' => [
                'eligible' => $energyEligible,
                'exact' => $bmrExact,
                'display' => $bmrExact === null ? null : (int) round($bmrExact),
                'unit' => 'kcal/day',
                'equation' => 'Mifflin–St Jeor',
            ],
            'tdee' => [
                'eligible' => $energyEligible,
                'exact' => $tdeeExact,
                'display' => $tdeeExact === null ? null : (int) round($tdeeExact),
                'unit' => 'kcal/day',
            ],
            'flags' => [
                'minor' => ! $isAdult,
                'excluded_population' => $excluded,
                'sex_unspecified' => $sex === BmiBmrCatalog::SEX_UNSPECIFIED,
            ],
            'guidance' => $this->guidance($isAdult, $excluded, $energyEligible, $category, $tdeeExact),
        ];
    }

    public function classifyBmi(float $bmiExact): array
    {
        if (! is_finite($bmiExact)) {
            throw new InvalidArgumentException('BMI is not a finite number.');
        }

        foreach (BmiBmrCatalog::BMI_CATEGORIES as $band) {
            $minOk = $band['min'] === null || $bmiExact >= $band['min'];
            $maxOk = $band['max_exclusive'] === null || $bmiExact < $band['max_exclusive'];
            if ($minOk && $maxOk) {
                return [
                    'key' => $band['key'],
                    'label' => $band['label'],
                ];
            }
        }

        throw new InvalidArgumentException('BMI could not be classified.');
    }

    private function assertFinitePositive(float $value, string $label): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new InvalidArgumentException(ucfirst($label).' must be a finite positive number.');
        }
    }

    private function guidance(bool $isAdult, bool $excluded, bool $energyEligible, ?array $category, ?float $tdeeExact): array
    {
        $notes = [
            'BMI is a population screening measure based on height and weight. It does not measure body fat, metabolic health, or fitness.',
            'BMR and TDEE from Mifflin–St Jeor are estimates from a binary-sex equation model—not laboratory measurements.',
            'Activity multipliers are rough and may overestimate or underestimate real energy needs.',
            'This tool does not diagnose conditions or replace advice from a qualified healthcare professional.',
        ];

        $examples = null;
        if ($energyEligible && $tdeeExact !== null) {
            $maintain = (int) round($tdeeExact);
            $examples = [
                'disclaimer' => 'Illustrative examples only—not prescriptions. Do not use restrictive targets without professional guidance.',
                'maintain' => $maintain,
                'gentle_deficit_example' => max(1200, $maintain - 300),
                'gentle_surplus_example' => $maintain + 200,
            ];
        }

        return [
            'notes' => $notes,
            'adult_category_applicable' => $isAdult && $category !== null,
            'energy_applicable' => $energyEligible,
            'minor_notice' => $isAdult ? null : 'Standard adult BMI categories and adult calorie estimates are not applied for ages under 18. Speak with a paediatric or healthcare professional for age-appropriate guidance.',
            'excluded_notice' => $excluded
                ? 'Pregnancy, breastfeeding, or similar circumstances fall outside the intended use of these adult estimates. Routine adult calorie targets are not presented as personalised recommendations.'
                : null,
            'sex_notice' => null,
            'examples' => $examples,
            'category_blurb' => $category['label'] ?? null,
        ];
    }
}
