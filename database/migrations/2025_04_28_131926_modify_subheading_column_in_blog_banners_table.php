<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historical tweak: make blog_banners.subheading nullable.
     *
     * Fresh installs already create `subheading` as nullable in
     * 2025_04_24_082907_create_blog_banners_table.php, so this change is a
     * no-op. Avoid ->change() here (requires doctrine/dbal and breaks SQLite
     * RefreshDatabase tests).
     */
    public function up(): void
    {
        if (! Schema::hasTable('blog_banners')) {
            return;
        }

        // Intentionally empty: create migration already defines nullable subheading.
    }

    public function down(): void
    {
        // No-op: original create migration remains the source of truth for fresh installs.
    }
};
