<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Idempotent for fresh installs where create_service_details already
     * includes service_id (+ FK). service_name is added only when missing
     * (later removed by 2025_04_29_102600).
     */
    public function up(): void
    {
        if (!Schema::hasTable('service_details')) {
            return;
        }

        Schema::table('service_details', function (Blueprint $table) {
            if (!Schema::hasColumn('service_details', 'service_id')) {
                $table->unsignedBigInteger('service_id');
                $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            }

            if (!Schema::hasColumn('service_details', 'service_name')) {
                $table->string('service_name');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('service_details')) {
            return;
        }

        Schema::table('service_details', function (Blueprint $table) {
            // Only drop columns this migration may have added historically.
            // Avoid dropping service_id on fresh installs where create owns it.
            if (Schema::hasColumn('service_details', 'service_name')) {
                $table->dropColumn('service_name');
            }
        });
    }
};
