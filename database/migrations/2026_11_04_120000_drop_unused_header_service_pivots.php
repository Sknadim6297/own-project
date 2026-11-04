<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop unused header↔service pivot tables when present.
     *
     * Potential data loss: rows in header_feature_services / header_services
     * (admin-configured header feature ↔ service links only). Core services,
     * professional services, bookings and payments are unaffected.
     */
    public function up(): void
    {
        Schema::dropIfExists('header_feature_services');
        Schema::dropIfExists('header_services');
    }

    /**
     * Reverse the migrations.
     *
     * Intentionally empty: recreating the retired pivots is not supported.
     */
    public function down(): void
    {
        //
    }
};
