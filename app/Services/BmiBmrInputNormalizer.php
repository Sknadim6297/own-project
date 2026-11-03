<?php

namespace App\Services;

use App\Support\BmiBmr\BmiBmrCatalog;
use InvalidArgumentException;

class BmiBmrInputNormalizer
{
    /**
     * @param  array<string, mixed>  $raw
     * @return array{
     *     age: int,
     *     height_cm: float,
     *     weight_kg: float,
     *     sex: string,
     *     activity: string,
     *     excluded_population: bool,
     *     height_unit: string,
     *     weight_unit: string,
     *     display_inputs: array<string, mixed>
     * }
     */
    public function normalize(array $raw): array
    {
        $age = $this->requireNumber($raw['age'] ?? null, 'age');
        if ($age != (int) $age) {
            throw new InvalidArgumentException('Age must be a whole number of years.');
        }
        $age = (int) $age;

        $heightUnit = (string) ($raw['height_unit'] ?? 'cm');
        $weightUnit = (string) ($raw['weight_unit'] ?? 'kg');
        if (! in_array($heightUnit, ['cm', 'ft_in'], true)) {
            throw new InvalidArgumentException('Height unit is invalid.');
        }
        if (! in_array($weightUnit, ['kg', 'lb'], true)) {
            throw new InvalidArgumentException('Weight unit is invalid.');
        }

        if ($heightUnit === 'cm') {
            $heightCm = $this->requireNumber($raw['height_cm'] ?? null, 'height');
        } else {
            $feet = $this->requireNumber($raw['height_ft'] ?? null, 'height (feet)');
            $inches = $this->requireNumber($raw['height_in'] ?? 0, 'height (inches)');
            if ($inches < 0 || $inches >= 12) {
                throw new InvalidArgumentException('Inches must be between 0 and 11.99.');
            }
            if ($feet <= 0) {
                throw new InvalidArgumentException('Feet must be greater than zero.');
            }
            $heightCm = (($feet * 12) + $inches) * 2.54;
        }

        if ($weightUnit === 'kg') {
            $weightKg = $this->requireNumber($raw['weight_kg'] ?? null, 'weight');
        } else {
            $lb = $this->requireNumber($raw['weight_lb'] ?? null, 'weight');
            $weightKg = $lb * 0.45359237;
        }

        $sex = (string) ($raw['sex'] ?? '');
        if (! in_array($sex, BmiBmrCatalog::sexKeys(), true)) {
            throw new InvalidArgumentException('Please choose a calculation sex option.');
        }

        $activity = (string) ($raw['activity'] ?? '');
        if (! in_array($activity, BmiBmrCatalog::activityKeys(), true)) {
            throw new InvalidArgumentException('Please choose an activity level.');
        }

        $excluded = filter_var($raw['excluded_population'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return [
            'age' => $age,
            'height_cm' => $heightCm,
            'weight_kg' => $weightKg,
            'sex' => $sex,
            'activity' => $activity,
            'excluded_population' => $excluded,
            'height_unit' => $heightUnit,
            'weight_unit' => $weightUnit,
            'display_inputs' => [
                'age' => $age,
                'height_unit' => $heightUnit,
                'weight_unit' => $weightUnit,
                'height_cm' => $raw['height_cm'] ?? null,
                'height_ft' => $raw['height_ft'] ?? null,
                'height_in' => $raw['height_in'] ?? null,
                'weight_kg' => $raw['weight_kg'] ?? null,
                'weight_lb' => $raw['weight_lb'] ?? null,
                'sex' => $sex,
                'activity' => $activity,
                'excluded_population' => $excluded,
            ],
        ];
    }

    private function requireNumber(mixed $value, string $label): float
    {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException(ucfirst($label).' is required.');
        }
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '' || ! is_numeric($value)) {
                throw new InvalidArgumentException(ucfirst($label).' must be numeric.');
            }
        }
        if (! is_numeric($value)) {
            throw new InvalidArgumentException(ucfirst($label).' must be numeric.');
        }
        $number = (float) $value;
        if (! is_finite($number)) {
            throw new InvalidArgumentException(ucfirst($label).' must be a finite number.');
        }
        if ($number <= 0 && $label !== 'height (inches)') {
            throw new InvalidArgumentException(ucfirst($label).' must be greater than zero.');
        }

        return $number;
    }
}
