<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Idempotent: create_event_details already includes map_link on fresh installs.
     */
    public function up(): void
    {
        if (!Schema::hasTable('event_details') || Schema::hasColumn('event_details', 'map_link')) {
            return;
        }

        Schema::table('event_details', function (Blueprint $table) {
            $table->string('map_link')->nullable()->after('starting_fees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('event_details') || !Schema::hasColumn('event_details', 'map_link')) {
            return;
        }

        Schema::table('event_details', function (Blueprint $table) {
            $table->dropColumn('map_link');
        });
    }
};
