<?php

use App\Services\ToolRegistryRegistrar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tool_categories') || ! Schema::hasTable('tools')) {
            throw new \RuntimeException(
                'Cannot register Tarot Reflection: tool_categories/tools tables are missing. Apply the tool registry foundation migrations first.'
            );
        }

        // Idempotent: adds Astrology & Tarot category + tarot-reflection (unpublished).
        app(ToolRegistryRegistrar::class)->register();

        if (! DB::table('tools')->where('slug', 'tarot-reflection')->exists()) {
            throw new \RuntimeException(
                'Tarot Reflection registration failed: tools.slug=tarot-reflection was not created.'
            );
        }
    }

    public function down(): void
    {
        // Registry rows are product data. Leave them in place on rollback.
    }
};
