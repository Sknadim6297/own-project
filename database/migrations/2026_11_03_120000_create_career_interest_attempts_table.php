<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('career_interest_attempts')) {
            return;
        }

        Schema::create('career_interest_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('completion_key')->unique();
            $table->string('assessment_version', 40);
            $table->string('scoring_version', 40);
            $table->string('catalogue_version', 40);
            $table->string('age_group', 40);
            $table->string('education', 40);
            $table->string('situation', 40);
            $table->string('geography', 40);
            $table->json('answers');
            $table->json('category_totals');
            $table->json('category_percentages');
            $table->json('top_categories');
            $table->text('interpretation')->nullable();
            $table->json('recommendations');
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->index(['user_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_interest_attempts');
    }
};
