<?php

namespace App\Services\Nutrition;

class NutritionFoodNormalizer
{
    public function normalizeName(?string $name): string
    {
        $name = mb_strtolower(trim((string) $name));
        $name = preg_replace('/\s+/u', ' ', $name) ?? '';
        $name = preg_replace('/[^\p{L}\p{N}\s\-\/]/u', '', $name) ?? '';

        return trim($name);
    }

    public function importIdentity(array $row, string $sourceKey): string
    {
        $parts = [
            $sourceKey,
            $this->normalizeName($row['name'] ?? ''),
            $this->normalizeName($row['brand'] ?? ''),
            $this->normalizeName($row['variant'] ?? ''),
            strtolower((string) ($row['preparation_state'] ?? 'unknown')),
            strtolower((string) ($row['reference_basis'] ?? '')),
            strtolower((string) ($row['source_product_id'] ?? '')),
            (string) ($row['worksheet_row'] ?? ''),
        ];

        return hash('sha256', implode('|', $parts));
    }

    public function duplicateKey(array $row): string
    {
        return implode('|', [
            $this->normalizeName($row['name'] ?? ''),
            $this->normalizeName($row['brand'] ?? ''),
            $this->normalizeName($row['variant'] ?? ''),
            strtolower((string) ($row['preparation_state'] ?? 'unknown')),
            strtolower((string) ($row['reference_basis'] ?? '')),
        ]);
    }
}
