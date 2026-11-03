<?php

namespace App\Services\Nutrition;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NutritionFoodWorkbookReader
{
    /** @var array<string, list<string>> */
    private const HEADER_ALIASES = [
        'name' => ['name', 'food name', 'food', 'item', 'dish', 'product name', 'food_name'],
        'brand' => ['brand', 'manufacturer', 'brand name', 'brand_name'],
        'variant' => ['variant', 'flavour', 'flavor', 'product variant', 'variety'],
        'category' => ['category', 'food category', 'group', 'type', 'food group'],
        'preparation_state' => ['preparation', 'preparation state', 'state', 'form', 'cooked/raw', 'raw_cooked'],
        'reference_basis' => ['basis', 'reference basis', 'nutrition basis', 'per', 'reference', 'unit basis'],
        'calories_kcal' => ['calories', 'calorie', 'energy', 'energy_kcal', 'kcal', 'energy (kcal)', 'calories (kcal)'],
        'protein_g' => ['protein', 'protein_g', 'protein (g)'],
        'carbs_g' => ['carbohydrates', 'carbs', 'carbohydrate', 'carbs_g', 'carbohydrates (g)'],
        'fat_g' => ['fat', 'total fat', 'fat_g', 'fat (g)'],
        'fibre_g' => ['fibre', 'fiber', 'fibre_g', 'fiber_g', 'dietary fibre', 'dietary fiber'],
        'sugar_g' => ['sugar', 'sugars', 'sugar_g', 'sugars (g)'],
        'sodium_mg' => ['sodium', 'sodium_mg', 'sodium (mg)', 'salt'],
        'saturated_fat_g' => ['saturated fat', 'sat fat', 'saturated_fat', 'saturated fat (g)'],
        'serving_label' => ['serving', 'serving label', 'serving description', 'portion label'],
        'serving_quantity' => ['serving quantity', 'serving qty', 'portion quantity', 'qty'],
        'serving_unit' => ['serving unit', 'portion unit', 'unit'],
        'serving_grams' => ['serving grams', 'grams equivalent', 'g equivalent', 'weight g', 'portion grams'],
        'source_name' => ['source', 'source name', 'data source', 'reference source'],
        'source_url' => ['source url', 'url', 'reference url', 'label url'],
        'source_product_id' => ['product id', 'sku', 'barcode', 'gtin', 'source id', 'food id'],
        'verification_flag' => ['verified', 'verification', 'verification status', 'review status'],
    ];

    public function assertReadable(string $absolutePath): void
    {
        if (! is_file($absolutePath)) {
            throw new InvalidArgumentException('Workbook not found: '.$absolutePath);
        }
        if (! is_readable($absolutePath)) {
            throw new InvalidArgumentException('Workbook is not readable: '.$absolutePath);
        }
    }

    public function checksum(string $absolutePath): string
    {
        $this->assertReadable($absolutePath);
        $hash = hash_file('sha256', $absolutePath);
        if ($hash === false) {
            throw new InvalidArgumentException('Unable to checksum workbook.');
        }

        return $hash;
    }

    /**
     * @return array{
     *     worksheet: string,
     *     headers: array<int, string>,
     *     mapped: array<string, int>,
     *     unmapped_headers: array<int, string>,
     *     rows: list<array<string, mixed>>
     * }
     */
    public function read(string $absolutePath, ?string $preferredSheet = null): array
    {
        $this->assertReadable($absolutePath);
        $spreadsheet = IOFactory::load($absolutePath);
        $sheet = $preferredSheet
            ? $spreadsheet->getSheetByName($preferredSheet)
            : $spreadsheet->getSheet(0);
        if (! $sheet instanceof Worksheet) {
            throw new InvalidArgumentException('Unable to open worksheet.');
        }

        $matrix = $sheet->toArray(null, true, true, false);
        if ($matrix === []) {
            return [
                'worksheet' => $sheet->getTitle(),
                'headers' => [],
                'mapped' => [],
                'unmapped_headers' => [],
                'rows' => [],
            ];
        }

        $headerRowIndex = $this->detectHeaderRow($matrix);
        $rawHeaders = array_map(fn ($h) => trim((string) $h), $matrix[$headerRowIndex] ?? []);
        $mapped = $this->mapHeaders($rawHeaders);
        $unmapped = [];
        foreach ($rawHeaders as $index => $header) {
            if ($header !== '' && ! in_array($index, $mapped, true)) {
                $unmapped[] = $header;
            }
        }

        $rows = [];
        for ($i = $headerRowIndex + 1; $i < count($matrix); $i++) {
            $line = $matrix[$i];
            if (! is_array($line) || $this->rowIsEmpty($line)) {
                continue;
            }
            $assoc = ['worksheet_row' => $i + 1];
            foreach ($mapped as $field => $col) {
                $assoc[$field] = $line[$col] ?? null;
            }
            if (trim((string) ($assoc['name'] ?? '')) === '') {
                continue;
            }
            $rows[] = $assoc;
        }

        return [
            'worksheet' => $sheet->getTitle(),
            'headers' => $rawHeaders,
            'mapped' => $mapped,
            'unmapped_headers' => $unmapped,
            'rows' => $rows,
            'sheet_names' => $spreadsheet->getSheetNames(),
        ];
    }

    /**
     * @param  list<array<int, mixed>>  $matrix
     */
    private function detectHeaderRow(array $matrix): int
    {
        $limit = min(15, count($matrix));
        $bestIndex = 0;
        $bestScore = -1;
        for ($i = 0; $i < $limit; $i++) {
            $headers = array_map(fn ($h) => trim((string) $h), $matrix[$i] ?? []);
            $mapped = $this->mapHeaders($headers);
            $score = count($mapped);
            if (isset($mapped['name'])) {
                $score += 5;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIndex = $i;
            }
        }

        return $bestIndex;
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<string, int>
     */
    private function mapHeaders(array $headers): array
    {
        $mapped = [];
        foreach ($headers as $index => $header) {
            $normalized = $this->normalizeHeader($header);
            if ($normalized === '') {
                continue;
            }
            foreach (self::HEADER_ALIASES as $field => $aliases) {
                if (isset($mapped[$field])) {
                    continue;
                }
                if (in_array($normalized, $aliases, true)) {
                    $mapped[$field] = $index;
                }
            }
        }

        return $mapped;
    }

    private function normalizeHeader(string $header): string
    {
        $header = mb_strtolower(trim($header));
        $header = preg_replace('/\s+/u', ' ', $header) ?? '';

        return $header;
    }

    /**
     * @param  array<int, mixed>  $line
     */
    private function rowIsEmpty(array $line): bool
    {
        foreach ($line as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
