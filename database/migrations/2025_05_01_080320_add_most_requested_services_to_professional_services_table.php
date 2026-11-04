<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Runs before create_professional_services on timestamp order. On fresh
     * installs the table does not exist yet; columns are defined on create.
     * On existing installs where the table already exists, add missing columns.
     */
    public function up(): void
    {
        if (!Schema::hasTable('professional_services')) {
            return;
        }

        Schema::table('professional_services', function (Blueprint $table) {
            if (!Schema::hasColumn('professional_services', 'most_requested_services')) {
                $table->json('most_requested_services')->nullable()->after('requirements');
            }
            if (!Schema::hasColumn('professional_services', 'most_requested_services_upto_price')) {
                $table->json('most_requested_services_upto_price')->nullable()->after('most_requested_services');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('professional_services')) {
            return;
        }

        Schema::table('professional_services', function (Blueprint $table) {
            if (Schema::hasColumn('professional_services', 'most_requested_services_upto_price')) {
                $table->dropColumn('most_requested_services_upto_price');
            }
            if (Schema::hasColumn('professional_services', 'most_requested_services')) {
                $table->dropColumn('most_requested_services');
            }
        });
    }
};
