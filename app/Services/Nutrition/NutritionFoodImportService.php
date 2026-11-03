<?php

namespace App\Services\Nutrition;

use App\Models\NutritionDuplicateReview;
use App\Models\NutritionFood;
use App\Models\NutritionFoodCategory;
use App\Models\NutritionImportBatch;
use App\Models\NutritionImportRow;
use App\Support\Nutrition\NutritionCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class NutritionFoodImportService
{
    public function __construct(
        private NutritionFoodWorkbookReader $reader,
        private NutritionFoodRowValidator $validator,
        private NutritionFoodNormalizer $normalizer,
    ) {
    }

    /**
     * @return list<string>
     */
    public function missingSourcePaths(?string $source = 'all'): array
    {
        $missing = [];
        foreach ($this->resolveSources($source) as $key) {
            $path = NutritionCatalog::importAbsolutePath($key);
            if (! is_file($path)) {
                $missing[] = $path;
            }
        }

        return $missing;
    }

    /**
     * @return array<string, mixed>
     */
    public function run(?string $source = 'all', bool $dryRun = true, string $actor = 'system'): array
    {
        $missing = $this->missingSourcePaths($source);
        if ($missing !== []) {
            throw new InvalidArgumentException(
                "Required workbook(s) missing:\n- ".implode("\n- ", $missing)
            );
        }

        $reports = [];
        foreach ($this->resolveSources($source) as $key) {
            $reports[$key] = $this->importSource($key, $dryRun, $actor);
        }

        return [
            'dry_run' => $dryRun,
            'sources' => $reports,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function importSource(string $sourceKey, bool $dryRun, string $actor): array
    {
        $meta = NutritionCatalog::IMPORT_SOURCES[$sourceKey];
        $path = NutritionCatalog::importAbsolutePath($sourceKey);
        $checksum = $this->reader->checksum($path);
        $parsed = $this->reader->read($path);

        $existingBatch = NutritionImportBatch::query()
            ->where('source_key', $sourceKey)
            ->where('checksum_sha256', $checksum)
            ->where('is_dry_run', false)
            ->whereIn('status', ['completed', 'completed_with_warnings'])
            ->first();

        $batch = new NutritionImportBatch([
            'source_key' => $sourceKey,
            'source_filename' => $meta['filename'],
            'checksum_sha256' => $checksum,
            'worksheet_name' => $parsed['worksheet'],
            'actor' => $actor,
            'total_rows' => count($parsed['rows']),
            'status' => 'running',
            'is_dry_run' => $dryRun,
            'started_at' => now(),
            'summary' => [
                'sheet_names' => $parsed['sheet_names'] ?? [$parsed['worksheet']],
                'headers' => $parsed['headers'],
                'mapped_fields' => array_keys($parsed['mapped']),
                'unmapped_headers' => $parsed['unmapped_headers'],
                'prior_completed_batch_id' => $existingBatch?->id,
            ],
        ]);

        if (! $dryRun) {
            $batch->save();
        }

        $stats = [
            'valid_rows' => 0,
            'rejected_rows' => 0,
            'duplicate_candidates' => 0,
            'imported_drafts' => 0,
            'skipped_existing' => 0,
            'conflicts' => 0,
            'warnings' => 0,
        ];
        $rowSamples = ['rejected' => [], 'duplicates' => [], 'conflicts' => []];

        foreach ($parsed['rows'] as $raw) {
            $result = $this->validator->validate($raw, $sourceKey, (bool) $meta['nutrients_expected']);
            $identity = $result['data']['import_identity'] ?? $this->normalizer->importIdentity($raw, $sourceKey);

            if (! $result['ok']) {
                $stats['rejected_rows']++;
                $this->recordRow($batch, $dryRun, $raw, $identity, 'rejected', $result, null, null);
                if (count($rowSamples['rejected']) < 20) {
                    $rowSamples['rejected'][] = [
                        'row' => $raw['worksheet_row'] ?? null,
                        'name' => $raw['name'] ?? null,
                        'errors' => $result['errors'],
                    ];
                }
                continue;
            }

            $stats['valid_rows']++;
            if ($result['warnings'] !== []) {
                $stats['warnings']++;
            }

            $existingByIdentity = NutritionFood::query()->where('import_identity', $identity)->first();
            if ($existingByIdentity) {
                if ($existingByIdentity->isVerified()) {
                    $stats['conflicts']++;
                    $this->recordRow($batch, $dryRun, $raw, $identity, 'conflict_verified', $result, $existingByIdentity->id, null);
                    if (count($rowSamples['conflicts']) < 20) {
                        $rowSamples['conflicts'][] = [
                            'row' => $raw['worksheet_row'] ?? null,
                            'food_id' => $existingByIdentity->id,
                            'message' => 'Verified food protected from overwrite.',
                        ];
                    }
                } else {
                    $stats['skipped_existing']++;
                    $this->recordRow($batch, $dryRun, $raw, $identity, 'skipped_existing', $result, $existingByIdentity->id, null);
                }
                continue;
            }

            $duplicate = $this->findDuplicateCandidate($result['data']);
            $duplicateId = $duplicate['food']?->id;
            if ($duplicate['food']) {
                $stats['duplicate_candidates']++;
                if (count($rowSamples['duplicates']) < 20) {
                    $rowSamples['duplicates'][] = [
                        'row' => $raw['worksheet_row'] ?? null,
                        'name' => $result['data']['name'],
                        'candidate_id' => $duplicateId,
                        'reason' => $duplicate['reason'],
                    ];
                }
            }

            if ($dryRun) {
                $this->recordRow($batch, true, $raw, $identity, $duplicateId ? 'duplicate_candidate' : 'would_import', $result, null, $duplicateId);
                if (! $duplicateId) {
                    $stats['imported_drafts']++;
                }
                continue;
            }

            try {
                $food = DB::transaction(function () use ($batch, $result, $sourceKey, $raw, $duplicate) {
                    $food = $this->createDraftFood($batch, $result['data'], $sourceKey, $raw);
                    if ($duplicate['food']) {
                        NutritionDuplicateReview::query()->firstOrCreate(
                            [
                                'nutrition_food_id' => $food->id,
                                'candidate_food_id' => $duplicate['food']->id,
                            ],
                            [
                                'match_reason' => $duplicate['reason'],
                                'score' => $duplicate['score'],
                                'decision' => 'pending',
                            ]
                        );
                    }

                    return $food;
                });
                $stats['imported_drafts']++;
                $this->recordRow($batch, false, $raw, $identity, $duplicateId ? 'imported_duplicate_flagged' : 'imported', $result, $food->id, $duplicateId);
            } catch (Throwable $e) {
                $stats['rejected_rows']++;
                $result['errors'][] = $e->getMessage();
                $this->recordRow($batch, false, $raw, $identity, 'rejected', $result, null, null);
            }
        }

        $status = $stats['rejected_rows'] > 0 || $stats['conflicts'] > 0 || $stats['warnings'] > 0
            ? 'completed_with_warnings'
            : 'completed';

        $summary = array_merge($batch->summary ?? [], [
            'stats' => $stats,
            'samples' => $rowSamples,
            'nutrients_expected' => (bool) $meta['nutrients_expected'],
        ]);

        if (! $dryRun) {
            $batch->fill([
                'valid_rows' => $stats['valid_rows'],
                'rejected_rows' => $stats['rejected_rows'],
                'duplicate_candidates' => $stats['duplicate_candidates'],
                'imported_drafts' => $stats['imported_drafts'],
                'skipped_existing' => $stats['skipped_existing'],
                'conflicts' => $stats['conflicts'],
                'published_count' => 0,
                'status' => $status,
                'summary' => $summary,
                'finished_at' => now(),
            ])->save();
        } else {
            $batch->fill([
                'valid_rows' => $stats['valid_rows'],
                'rejected_rows' => $stats['rejected_rows'],
                'duplicate_candidates' => $stats['duplicate_candidates'],
                'imported_drafts' => $stats['imported_drafts'],
                'skipped_existing' => $stats['skipped_existing'],
                'conflicts' => $stats['conflicts'],
                'status' => $status,
                'summary' => $summary,
                'finished_at' => now(),
            ]);
        }

        return [
            'batch_id' => $dryRun ? null : $batch->id,
            'source_key' => $sourceKey,
            'filename' => $meta['filename'],
            'checksum' => $checksum,
            'worksheet' => $parsed['worksheet'],
            'sheet_names' => $parsed['sheet_names'] ?? [],
            'headers' => $parsed['headers'],
            'mapped_fields' => array_keys($parsed['mapped']),
            'unmapped_headers' => $parsed['unmapped_headers'],
            'stats' => $stats,
            'samples' => $rowSamples,
            'prior_completed_batch_id' => $existingBatch?->id,
            'dry_run' => $dryRun,
            'status' => $status,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $raw
     */
    private function createDraftFood(NutritionImportBatch $batch, array $data, string $sourceKey, array $raw): NutritionFood
    {
        $categoryId = null;
        if (! empty($data['category_name'])) {
            $slug = Str::slug($data['category_name']);
            $category = NutritionFoodCategory::query()->firstOrCreate(
                ['slug' => $slug !== '' ? $slug : 'uncategorized'],
                ['name' => $data['category_name'], 'is_active' => true]
            );
            $categoryId = $category->id;
        }

        $food = NutritionFood::query()->create([
            'nutrition_food_category_id' => $categoryId,
            'name' => $data['name'],
            'normalized_name' => $data['normalized_name'],
            'brand' => $data['brand'],
            'variant' => $data['variant'],
            'preparation_state' => $data['preparation_state'],
            'reference_basis' => $data['reference_basis'],
            'calories_kcal' => $data['calories_kcal'],
            'protein_g' => $data['protein_g'],
            'carbs_g' => $data['carbs_g'],
            'fat_g' => $data['fat_g'],
            'fibre_g' => $data['fibre_g'],
            'sugar_g' => $data['sugar_g'],
            'sodium_mg' => $data['sodium_mg'],
            'saturated_fat_g' => $data['saturated_fat_g'],
            'source_name' => $data['source_name'],
            'source_url' => $data['source_url'],
            'verification_status' => $data['verification_status'],
            'nutrient_completeness' => $data['nutrient_completeness'],
            'is_published' => false,
            'import_batch' => $sourceKey.'#'.$batch->id,
            'import_row_ref' => (string) ($raw['worksheet_row'] ?? ''),
            'import_identity' => $data['import_identity'],
            'nutrition_import_batch_id' => $batch->id,
        ]);

        if (! empty($data['serving']['label'])) {
            $food->servings()->create([
                'label' => $data['serving']['label'],
                'quantity' => $data['serving']['quantity'],
                'unit' => $data['serving']['unit'],
                'grams_equivalent' => $data['serving']['grams_equivalent'],
            ]);
        }

        return $food;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{food:?NutritionFood,reason:string,score:float}
     */
    private function findDuplicateCandidate(array $data): array
    {
        $query = NutritionFood::query()
            ->where('normalized_name', $data['normalized_name']);

        if ($data['brand']) {
            $query->where(function ($q) use ($data) {
                $q->whereNull('brand')->orWhere('brand', $data['brand']);
            });
        }

        $candidates = $query->limit(20)->get();
        foreach ($candidates as $candidate) {
            if (
                strtolower((string) $candidate->preparation_state) === strtolower((string) $data['preparation_state'])
                && strtolower((string) ($candidate->brand ?? '')) === strtolower((string) ($data['brand'] ?? ''))
                && strtolower((string) ($candidate->variant ?? '')) === strtolower((string) ($data['variant'] ?? ''))
            ) {
                return [
                    'food' => $candidate,
                    'reason' => 'exact_name_brand_variant_prep',
                    'score' => 1.0,
                ];
            }
            if (
                strtolower((string) $candidate->preparation_state) !== strtolower((string) $data['preparation_state'])
                && strtolower((string) ($candidate->brand ?? '')) === strtolower((string) ($data['brand'] ?? ''))
            ) {
                return [
                    'food' => $candidate,
                    'reason' => 'same_name_different_preparation',
                    'score' => 0.7,
                ];
            }
        }

        $near = $candidates->first();
        if ($near) {
            return [
                'food' => $near,
                'reason' => 'normalized_name_match',
                'score' => 0.55,
            ];
        }

        return ['food' => null, 'reason' => '', 'score' => 0.0];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, mixed>  $result
     */
    private function recordRow(
        NutritionImportBatch $batch,
        bool $dryRun,
        array $raw,
        string $identity,
        string $status,
        array $result,
        ?int $foodId,
        ?int $duplicateId
    ): void {
        if ($dryRun || ! $batch->exists) {
            return;
        }

        NutritionImportRow::query()->create([
            'nutrition_import_batch_id' => $batch->id,
            'worksheet_row' => (int) ($raw['worksheet_row'] ?? 0),
            'import_identity' => $identity,
            'status' => $status,
            'name' => $result['data']['name'] ?? ($raw['name'] ?? null),
            'brand' => $result['data']['brand'] ?? null,
            'variant' => $result['data']['variant'] ?? null,
            'payload' => $result['data'] ?? $raw,
            'warnings' => array_values(array_merge($result['warnings'] ?? [], $result['errors'] ?? [])),
            'message' => implode('; ', array_merge($result['errors'] ?? [], $result['warnings'] ?? [])),
            'nutrition_food_id' => $foodId,
            'duplicate_of_food_id' => $duplicateId,
        ]);
    }

    /**
     * @return list<string>
     */
    private function resolveSources(?string $source): array
    {
        $source = $source ?: 'all';
        if ($source === 'all') {
            return array_keys(NutritionCatalog::IMPORT_SOURCES);
        }
        if (! isset(NutritionCatalog::IMPORT_SOURCES[$source])) {
            throw new InvalidArgumentException('Unknown --source value. Use 350, 700, or all.');
        }

        return [$source];
    }
}
