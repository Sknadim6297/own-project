<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('service_details') || !Schema::hasColumn('service_details', 'service_name')) {
            return;
        }

        Schema::table('service_details', function (Blueprint $table) {
            $table->dropColumn('service_name');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('service_details') || Schema::hasColumn('service_details', 'service_name')) {
            return;
        }

        Schema::table('service_details', function (Blueprint $table) {
            $table->string('service_name');
        });
    }
};
