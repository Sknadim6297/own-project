<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bmi_bmr_calculations')) {
            return;
        }

        Schema::create('bmi_bmr_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('completion_key')->unique();
            $table->string('formula_version', 80);
            $table->unsignedSmallInteger('age');
            $table->decimal('height_cm', 8, 2);
            $table->decimal('weight_kg', 8, 2);
            $table->string('sex', 20);
            $table->string('activity', 40);
            $table->decimal('activity_factor', 5, 3);
            $table->boolean('excluded_population')->default(false);
            $table->decimal('bmi_exact', 10, 6);
            $table->decimal('bmi_display', 5, 1);
            $table->string('bmi_category_key', 40)->nullable();
            $table->string('bmi_category_label', 80)->nullable();
            $table->unsignedInteger('bmr_kcal')->nullable();
            $table->unsignedInteger('tdee_kcal')->nullable();
            $table->json('payload');
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index(['user_id', 'calculated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bmi_bmr_calculations');
    }
};
