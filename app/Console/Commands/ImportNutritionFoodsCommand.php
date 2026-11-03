<?php

namespace App\Console\Commands;

use App\Services\Nutrition\NutritionFoodImportService;
use App\Support\Nutrition\NutritionCatalog;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class ImportNutritionFoodsCommand extends Command
{
    protected $signature = 'nutrition:import-foods
                            {--source=all : Import source key: 350, 700, or all}
                            {--dry-run : Validate and report without writing foods}
                            {--actor=system : Actor label recorded on the import batch}';

    protected $description = 'Import YuktiOne nutrition Excel workbooks into draft/unverified catalogue records';

    public function handle(NutritionFoodImportService $importer): int
    {
        $source = (string) $this->option('source');
        $dryRun = (bool) $this->option('dry-run');
        $actor = (string) $this->option('actor');

        $this->info('YuktiOne nutrition import');
        $this->line('Mode: '.($dryRun ? 'DRY RUN (no food writes)' : 'IMPORT'));
        $this->line('Source: '.$source);

        $missing = $importer->missingSourcePaths($source);
        if ($missing !== []) {
            $this->error('Import stopped. Missing workbook file(s):');
            foreach ($missing as $path) {
                $this->line(' - '.$path);
            }
            $this->newLine();
            $this->warn('Place the Excel files under storage/app/'.NutritionCatalog::IMPORT_PATH.'/ and re-run.');
            $this->line('Expected:');
            foreach (NutritionCatalog::IMPORT_SOURCES as $meta) {
                $this->line(' - '.$meta['filename']);
            }

            return self::FAILURE;
        }

        try {
            $report = $importer->run($source, $dryRun, $actor);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error('Import failed: '.$e->getMessage());

            return self::FAILURE;
        }

        foreach ($report['sources'] as $key => $sourceReport) {
            $this->newLine();
            $this->info('Source '.$key.' — '.$sourceReport['filename']);
            $this->line('Worksheet: '.$sourceReport['worksheet']);
            $this->line('Checksum: '.$sourceReport['checksum']);
            $this->line('Mapped fields: '.implode(', ', $sourceReport['mapped_fields']));
            if ($sourceReport['unmapped_headers'] !== []) {
                $this->warn('Unmapped headers: '.implode(', ', $sourceReport['unmapped_headers']));
            }
            $stats = $sourceReport['stats'];
            $this->table(
                ['Metric', 'Count'],
                collect($stats)->map(fn ($v, $k) => [$k, $v])->values()->all()
            );
            if (! empty($sourceReport['samples']['rejected'])) {
                $this->warn('Rejected sample rows: '.count($sourceReport['samples']['rejected']));
            }
            if (! empty($sourceReport['samples']['duplicates'])) {
                $this->warn('Duplicate candidate samples: '.count($sourceReport['samples']['duplicates']));
            }
            if (! empty($sourceReport['samples']['conflicts'])) {
                $this->warn('Verified-data conflicts: '.count($sourceReport['samples']['conflicts']));
            }
            if ($sourceReport['batch_id']) {
                $this->line('Batch ID: '.$sourceReport['batch_id']);
            }
            if ($sourceReport['prior_completed_batch_id']) {
                $this->line('Prior completed batch for same checksum: '.$sourceReport['prior_completed_batch_id']);
            }
        }

        $this->newLine();
        $this->info('Imported foods remain unpublished and unverified until admin review.');
        $this->line('calorie-nutrition-tracker publication is unchanged by this command.');

        return self::SUCCESS;
    }
}
