<?php

namespace App\Services\Nutrition;

use App\Support\Nutrition\NutritionCatalog;

class NutritionFoodRowValidator
{
    public function __construct(private NutritionFoodNormalizer $normalizer)
    {
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{
     *     ok: bool,
     *     status: string,
     *     warnings: list<string>,
     *     errors: list<string>,
     *     data: array<string, mixed>
     * }
     */
    public function validate(array $raw, string $sourceKey, bool $nutrientsExpected): array
    {
        $warnings = [];
        $errors = [];

        $name = trim((string) ($raw['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'Food name is required.';
        }

        $preparation = $this->mapPreparation($raw['preparation_state'] ?? null);
        $basis = $this->mapBasis($raw['reference_basis'] ?? null, $warnings);
        if ($basis === null) {
            $basis = $nutrientsExpected ? 'per_100g' : 'per_100g';
            if ($nutrientsExpected) {
                $warnings[] = 'Reference basis missing or ambiguous; defaulted to per_100g for structural draft only.';
            }
        }

        $nutrients = [];
        foreach (['calories_kcal', 'protein_g', 'carbs_g', 'fat_g', 'fibre_g', 'sugar_g', 'sodium_mg', 'saturated_fat_g'] as $field) {
            $parsed = $this->parseNutrient($raw[$field] ?? null, $field, $warnings, $errors);
            $nutrients[$field] = $parsed;
        }

        if (! $nutrientsExpected) {
            foreach ($nutrients as $field => $value) {
                if ($value !== null) {
                    $warnings[] = "Masterlist row unexpectedly includes {$field}; value retained as unverified draft only.";
                }
            }
        }

        $serving = $this->parseServing($raw, $warnings, $errors);
        $completeness = $this->completeness($nutrients);

        if ($nutrientsExpected && $completeness === 'incomplete') {
            $warnings[] = 'Core macros incomplete; nutrient coverage marked incomplete.';
        }

        $data = [
            'name' => $name,
            'normalized_name' => $this->normalizer->normalizeName($name),
            'brand' => $this->nullableString($raw['brand'] ?? null),
            'variant' => $this->nullableString($raw['variant'] ?? null),
            'category_name' => $this->nullableString($raw['category'] ?? null),
            'preparation_state' => $preparation,
            'reference_basis' => $basis,
            'calories_kcal' => $nutrients['calories_kcal'],
            'protein_g' => $nutrients['protein_g'],
            'carbs_g' => $nutrients['carbs_g'],
            'fat_g' => $nutrients['fat_g'],
            'fibre_g' => $nutrients['fibre_g'],
            'sugar_g' => $nutrients['sugar_g'],
            'sodium_mg' => $nutrients['sodium_mg'],
            'saturated_fat_g' => $nutrients['saturated_fat_g'],
            'source_name' => $this->nullableString($raw['source_name'] ?? null) ?: NutritionCatalog::IMPORT_SOURCES[$sourceKey]['label'],
            'source_url' => $this->nullableString($raw['source_url'] ?? null),
            'source_product_id' => $this->nullableString($raw['source_product_id'] ?? null),
            'serving' => $serving,
            'nutrient_completeness' => $completeness,
            'verification_status' => $this->initialStatus($nutrientsExpected, $completeness, $warnings),
            'worksheet_row' => (int) ($raw['worksheet_row'] ?? 0),
        ];

        $data['import_identity'] = $this->normalizer->importIdentity($data + ['source_product_id' => $data['source_product_id']], $sourceKey);

        return [
            'ok' => $errors === [],
            'status' => $errors === [] ? 'valid' : 'rejected',
            'warnings' => $warnings,
            'errors' => $errors,
            'data' => $data,
        ];
    }

    private function initialStatus(bool $nutrientsExpected, string $completeness, array $warnings): string
    {
        if (! $nutrientsExpected) {
            return 'unverified';
        }
        if ($completeness === 'incomplete' || $warnings !== []) {
            return 'needs_verification';
        }

        return 'needs_verification';
    }

    private function mapPreparation(mixed $value): string
    {
        $v = mb_strtolower(trim((string) $value));
        if ($v === '') {
            return 'unknown';
        }
        if (str_contains($v, 'raw')) {
            return 'raw';
        }
        if (str_contains($v, 'cook') || str_contains($v, 'boiled') || str_contains($v, 'fried')) {
            return 'cooked';
        }
        if (str_contains($v, 'pack') || str_contains($v, 'label') || str_contains($v, 'branded')) {
            return 'packaged';
        }

        return isset(NutritionCatalog::PREPARATION_STATES[$v]) ? $v : 'unknown';
    }

    /**
     * @param  list<string>  $warnings
     */
    private function mapBasis(mixed $value, array &$warnings): ?string
    {
        $v = mb_strtolower(trim((string) $value));
        if ($v === '') {
            return null;
        }
        if (str_contains($v, '100ml') || str_contains($v, '100 ml') || $v === 'per_100ml') {
            return 'per_100ml';
        }
        if (str_contains($v, '100g') || str_contains($v, '100 g') || $v === 'per_100g') {
            return 'per_100g';
        }
        if (str_contains($v, 'serving') || str_contains($v, 'portion')) {
            return 'per_serving';
        }
        $warnings[] = 'Unrecognized reference basis "'.$value.'".';

        return null;
    }

    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     */
    private function parseNutrient(mixed $value, string $field, array &$warnings, array &$errors): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $raw = trim((string) $value);
        $raw = str_replace([',', ' '], ['', ''], $raw);
        if (! is_numeric($raw)) {
            $errors[] = "{$field} is not numeric.";

            return null;
        }
        if (! is_finite((float) $raw)) {
            $errors[] = "{$field} is not finite.";

            return null;
        }
        if ((float) $raw < 0) {
            $errors[] = "{$field} is negative.";

            return null;
        }

        $decimal = bcadd($raw, '0', 4);
        $max = match ($field) {
            'calories_kcal' => '1200',
            'sodium_mg' => '10000',
            default => '300',
        };
        if (bccomp($decimal, $max, 4) > 0) {
            $warnings[] = "{$field}={$decimal} exceeds typical {$max} range and needs manual review.";
        }

        return $decimal;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     * @return array{label:?string,quantity:?string,unit:?string,grams_equivalent:?string}|null
     */
    private function parseServing(array $raw, array &$warnings, array &$errors): ?array
    {
        $label = $this->nullableString($raw['serving_label'] ?? null);
        $unit = $this->nullableString($raw['serving_unit'] ?? null);
        $qty = $raw['serving_quantity'] ?? null;
        $grams = $raw['serving_grams'] ?? null;

        if ($label === null && $unit === null && ($qty === null || trim((string) $qty) === '') && ($grams === null || trim((string) $grams) === '')) {
            return null;
        }

        $quantity = null;
        if ($qty !== null && trim((string) $qty) !== '') {
            if (! is_numeric($qty) || (float) $qty <= 0) {
                $errors[] = 'Serving quantity must be a positive number.';
            } else {
                $quantity = bcadd((string) $qty, '0', 4);
            }
        }

        $gramsEquivalent = null;
        if ($grams !== null && trim((string) $grams) !== '') {
            if (! is_numeric($grams) || (float) $grams <= 0) {
                $errors[] = 'Serving grams equivalent must be a positive number.';
            } else {
                $gramsEquivalent = bcadd((string) $grams, '0', 4);
            }
        }

        if ($unit !== null) {
            $u = strtolower($unit);
            if (
                ! in_array($u, NutritionCatalog::MASS_UNITS, true)
                && ! in_array($u, NutritionCatalog::VOLUME_UNITS, true)
                && $gramsEquivalent === null
            ) {
                $warnings[] = "Serving unit \"{$unit}\" has no verified gram equivalent; portion conversion unavailable until reviewed.";
            }
        }

        return [
            'label' => $label ?: ($unit ? 'Serving' : null),
            'quantity' => $quantity ?: '1.0000',
            'unit' => $unit ?: 'serving',
            'grams_equivalent' => $gramsEquivalent,
        ];
    }

    /**
     * @param  array<string, ?string>  $nutrients
     */
    private function completeness(array $nutrients): string
    {
        $core = ['calories_kcal', 'protein_g', 'carbs_g', 'fat_g'];
        $present = 0;
        foreach ($core as $field) {
            if ($nutrients[$field] !== null) {
                $present++;
            }
        }
        if ($present === 0) {
            return 'incomplete';
        }
        if ($present < 4) {
            return 'incomplete';
        }

        return ($nutrients['fibre_g'] !== null || $nutrients['sugar_g'] !== null)
            ? 'complete'
            : 'macros_present';
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, 180);
    }
}
