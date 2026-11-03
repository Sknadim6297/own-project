<?php

use App\Services\ToolRegistryRegistrar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tool_categories') || ! Schema::hasTable('tools')) {
            return;
        }

        // Idempotent: firstOrCreate for Career Guidance + Nutrition tools.
        // Psychology rows are left unchanged when they already exist.
        app(ToolRegistryRegistrar::class)->register();
    }

    public function down(): void
    {
        // Registry rows are product data. Do not drop categories/tools here.
        // Unpublishing unfinished tools is handled by status/is_implemented flags.
    }
};
