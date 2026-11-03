<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nutrition_food_categories')) {
            Schema::create('nutrition_food_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('nutrition_foods')) {
            Schema::create('nutrition_foods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('nutrition_food_category_id')->nullable()->constrained('nutrition_food_categories')->nullOnDelete();
                $table->string('name');
                $table->string('brand')->nullable();
                $table->string('variant')->nullable();
                $table->string('preparation_state', 20)->default('unknown');
                $table->string('reference_basis', 20)->default('per_100g');
                $table->decimal('calories_kcal', 10, 4)->nullable();
                $table->decimal('protein_g', 10, 4)->nullable();
                $table->decimal('carbs_g', 10, 4)->nullable();
                $table->decimal('fat_g', 10, 4)->nullable();
                $table->decimal('fibre_g', 10, 4)->nullable();
                $table->string('source_name')->nullable();
                $table->string('source_url')->nullable();
                $table->string('verification_status', 20)->default('unverified');
                $table->boolean('is_published')->default(false);
                $table->string('import_batch', 80)->nullable();
                $table->string('import_row_ref', 80)->nullable();
                $table->timestamps();

                $table->index(['is_published', 'verification_status']);
                $table->index(['name', 'brand']);
                $table->index('preparation_state');
            });
        }

        if (! Schema::hasTable('nutrition_food_servings')) {
            Schema::create('nutrition_food_servings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('nutrition_food_id')->constrained('nutrition_foods')->cascadeOnDelete();
                $table->string('label');
                $table->decimal('quantity', 10, 4);
                $table->string('unit', 40);
                $table->decimal('grams_equivalent', 10, 4)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('nutrition_user_goals')) {
            Schema::create('nutrition_user_goals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->unique();
                $table->boolean('track_only')->default(true);
                $table->unsignedInteger('calories_kcal')->nullable();
                $table->decimal('protein_g', 10, 2)->nullable();
                $table->decimal('carbs_g', 10, 2)->nullable();
                $table->decimal('fat_g', 10, 2)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('nutrition_meal_entries')) {
            Schema::create('nutrition_meal_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('nutrition_food_id')->nullable()->constrained('nutrition_foods')->nullOnDelete();
                $table->string('meal_type', 20);
                $table->decimal('quantity', 10, 4);
                $table->string('unit', 40);
                $table->decimal('portion_grams', 10, 4)->nullable();
                $table->timestamp('consumed_at');
                $table->string('food_name_snapshot');
                $table->string('brand_snapshot')->nullable();
                $table->decimal('calories_kcal_snapshot', 10, 4)->nullable();
                $table->decimal('protein_g_snapshot', 10, 4)->nullable();
                $table->decimal('carbs_g_snapshot', 10, 4)->nullable();
                $table->decimal('fat_g_snapshot', 10, 4)->nullable();
                $table->decimal('fibre_g_snapshot', 10, 4)->nullable();
                $table->boolean('incomplete_nutrients')->default(false);
                $table->string('idempotency_key', 64);
                $table->timestamps();

                $table->unique(['user_id', 'idempotency_key']);
                $table->index(['user_id', 'consumed_at']);
                $table->index(['user_id', 'meal_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nutrition_meal_entries');
        Schema::dropIfExists('nutrition_user_goals');
        Schema::dropIfExists('nutrition_food_servings');
        Schema::dropIfExists('nutrition_foods');
        Schema::dropIfExists('nutrition_food_categories');
    }
};
