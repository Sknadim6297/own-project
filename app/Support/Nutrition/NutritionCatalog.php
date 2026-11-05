<?php

namespace App\Support\Nutrition;

class NutritionCatalog
{
    public const TIMEZONE = 'Asia/Kolkata';

    public const MEAL_TYPES = [
        'breakfast' => 'Breakfast',
        'lunch' => 'Lunch',
        'dinner' => 'Dinner',
        'snacks' => 'Snacks',
    ];

    public const PREPARATION_STATES = [
        'raw' => 'Raw',
        'cooked' => 'Cooked',
        'packaged' => 'Packaged',
        'unknown' => 'Unspecified',
    ];

    public const REFERENCE_BASES = [
        'per_100g' => 'Per 100 g',
        'per_100ml' => 'Per 100 ml',
        'per_serving' => 'Per defined serving',
    ];

    public const VERIFICATION_STATUSES = [
        'unverified' => 'Imported / unreviewed',
        'needs_verification' => 'Needs verification',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
    ];

    public const NUTRIENT_COMPLETENESS = [
        'incomplete' => 'Incomplete',
        'macros_present' => 'Macros present',
        'complete' => 'Complete (as supplied)',
    ];

    public const IMPORT_SOURCES = [
        '350' => [
            'key' => '350',
            'filename' => 'YuktiOne_350_Indian_Food_Masterlist.xlsx',
            'label' => '350 Indian Food Masterlist',
            'nutrients_expected' => false,
        ],
        '700' => [
            'key' => '700',
            'filename' => 'YuktiOne_700_Food_Database_Populated.xlsx',
            'label' => '700 Food Database Populated',
            'nutrients_expected' => true,
        ],
    ];

    public const IMPORT_PATH = 'nutrition/imports';

    /** Units that map 1:1 to grams when used as mass. */
    public const MASS_UNITS = ['g', 'gram', 'grams'];

    /** Units that map 1:1 to millilitres when used as volume. No density assumed. */
    public const VOLUME_UNITS = ['ml', 'millilitre', 'millilitres', 'milliliter', 'milliliters'];

    public static function importAbsolutePath(string $sourceKey): string
    {
        $meta = self::IMPORT_SOURCES[$sourceKey] ?? null;
        if (! $meta) {
            throw new \InvalidArgumentException('Unknown nutrition import source key.');
        }

        return storage_path('app/'.self::IMPORT_PATH.'/'.$meta['filename']);
    }

    /**
     * @return list<string> Absolute paths to missing approved workbook files.
     */
    public static function missingImportWorkbooks(): array
    {
        $missing = [];
        foreach (self::IMPORT_SOURCES as $meta) {
            $path = storage_path('app/'.self::IMPORT_PATH.'/'.$meta['filename']);
            if (! is_file($path)) {
                $missing[] = $path;
            }
        }

        return $missing;
    }
}
