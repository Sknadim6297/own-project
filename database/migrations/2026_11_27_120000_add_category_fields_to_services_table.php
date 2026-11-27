<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Priority 2: additive Category fields on the existing services table.
 * Product terminology: services rows are Categories (parents).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'short_description')) {
                $table->text('short_description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('services', 'icon_name')) {
                $table->string('icon_name')->nullable()->after('image');
            }
            if (! Schema::hasColumn('services', 'icon_path')) {
                $table->string('icon_path')->nullable()->after('icon_name');
            }
            if (! Schema::hasColumn('services', 'display_order')) {
                $table->unsignedInteger('display_order')->default(0)->after('status');
            }
            if (! Schema::hasColumn('services', 'show_on_homepage')) {
                $table->boolean('show_on_homepage')->default(false)->after('display_order');
            }
            if (! Schema::hasColumn('services', 'show_in_explore_tools')) {
                $table->boolean('show_in_explore_tools')->default(true)->after('show_on_homepage');
            }
            if (! Schema::hasColumn('services', 'discovery_needles')) {
                $table->json('discovery_needles')->nullable()->after('show_in_explore_tools');
            }
            if (! Schema::hasColumn('services', 'tone')) {
                $table->string('tone', 32)->nullable()->after('discovery_needles');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $columns = [
                'short_description',
                'icon_name',
                'icon_path',
                'display_order',
                'show_on_homepage',
                'show_in_explore_tools',
                'discovery_needles',
                'tone',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
