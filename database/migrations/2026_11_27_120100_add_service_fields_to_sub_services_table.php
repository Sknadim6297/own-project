<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Priority 2: additive Service fields on the existing sub_services table.
 * Product terminology: sub_services rows are Services (children of Categories).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_services', function (Blueprint $table) {
            if (! Schema::hasColumn('sub_services', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            if (! Schema::hasColumn('sub_services', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('description');
            }
            if (! Schema::hasColumn('sub_services', 'meta_description')) {
                $table->string('meta_description', 500)->nullable()->after('meta_title');
            }
            if (! Schema::hasColumn('sub_services', 'display_order')) {
                $table->unsignedInteger('display_order')->default(0)->after('status');
            }
        });

        if (Schema::hasColumn('sub_services', 'slug') && ! $this->indexExists('sub_services', 'sub_services_slug_unique')) {
            Schema::table('sub_services', function (Blueprint $table) {
                $table->unique('slug');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('sub_services', 'sub_services_slug_unique')) {
            Schema::table('sub_services', function (Blueprint $table) {
                $table->dropUnique('sub_services_slug_unique');
            });
        }

        Schema::table('sub_services', function (Blueprint $table) {
            foreach (['slug', 'meta_title', 'meta_description', 'display_order'] as $column) {
                if (Schema::hasColumn('sub_services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        $result = $connection->selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
