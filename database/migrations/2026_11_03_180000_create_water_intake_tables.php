<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('water_intake_settings')) {
            Schema::create('water_intake_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->unique();
                $table->unsignedInteger('daily_goal_ml')->default(2000);
                $table->unsignedInteger('previous_goal_ml')->nullable();
                $table->timestamp('goal_changed_at')->nullable();
                $table->string('display_unit', 8)->default('ml');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('water_intake_entries')) {
            Schema::create('water_intake_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedInteger('amount_ml');
                $table->timestamp('consumed_at');
                $table->string('idempotency_key', 64);
                $table->timestamps();

                $table->unique(['user_id', 'idempotency_key']);
                $table->index(['user_id', 'consumed_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('water_intake_entries');
        Schema::dropIfExists('water_intake_settings');
    }
};
