<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Historical migration retained for migration-history compatibility.
     *
     * The unused header_feature_services pivot (and its services FK) has been
     * retired. Table removal is handled by a later drop migration.
     */
    public function up(): void
    {
        // no-op: feature retired
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // no-op: feature retired
    }
};
