<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historical structure tweak.
     *
     * Fresh installs already get compatible columns from the create migration.
     * Foreign key to all_events is added later (2025_04_29_123609) after the
     * parent table exists. Avoid ->change() (requires doctrine/dbal) and avoid
     * adding a premature FK to all_events before that table is created.
     */
    public function up(): void
    {
        if (!Schema::hasTable('event_details')) {
            return;
        }

        Schema::table('event_details', function (Blueprint $table) {
            if (Schema::hasColumn('event_details', 'meeting_link')) {
                $table->dropColumn('meeting_link');
            }
            if (Schema::hasColumn('event_details', 'landmark')) {
                $table->dropColumn('landmark');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('event_details')) {
            return;
        }

        Schema::table('event_details', function (Blueprint $table) {
            if (!Schema::hasColumn('event_details', 'meeting_link')) {
                $table->string('meeting_link', 255)->nullable();
            }
            if (!Schema::hasColumn('event_details', 'landmark')) {
                $table->string('landmark', 255)->nullable();
            }
        });
    }
};
