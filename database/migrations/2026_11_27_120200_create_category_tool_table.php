<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Priority 2: admin-managed mapping between Categories (services) and tools.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('category_tool')) {
            return;
        }

        Schema::create('category_tool', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['service_id', 'tool_id']);
            $table->index(['service_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_tool');
    }
};
