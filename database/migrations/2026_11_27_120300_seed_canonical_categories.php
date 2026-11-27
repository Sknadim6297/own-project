<?php

use Database\Seeders\CanonicalCategorySeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Runs the idempotent CanonicalCategorySeeder once on migrate.
 * Safe to re-run the seeder manually afterward.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new CanonicalCategorySeeder)->run();
    }

    public function down(): void
    {
        // Non-destructive: seeded Category rows and tool mappings are retained.
    }
};
