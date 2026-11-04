<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Historical migration retained for migration-history compatibility.
     *
     * The unused header_services pivot also depended on services and blocked
     * fresh installs with the same foreign-key failure mode. It is retired
     * alongside header_feature_services; any leftover table is dropped later.
     */
    public function up(): void
    {
        // no-op: unused header/services pivot retired
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // no-op: unused header/services pivot retired
    }
};
