<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('weight_goals')) {
            Schema::create('weight_goals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('direction', 20);
                $table->decimal('starting_weight_kg', 8, 3);
                $table->decimal('target_weight_kg', 8, 3)->nullable();
                $table->date('target_date')->nullable();
                $table->string('display_unit', 8)->default('kg');
                $table->string('status', 20)->default('active');
                $table->timestamp('effective_from');
                $table->timestamp('effective_to')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['user_id', 'effective_from']);
            });
        }

        if (! Schema::hasTable('weight_measurements')) {
            Schema::create('weight_measurements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('weight_kg', 8, 3);
                $table->timestamp('recorded_at');
                $table->string('note', 240)->nullable();
                $table->string('display_unit', 8)->default('kg');
                $table->string('idempotency_key', 64);
                $table->timestamps();

                $table->unique(['user_id', 'idempotency_key']);
                $table->index(['user_id', 'recorded_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('weight_measurements');
        Schema::dropIfExists('weight_goals');
    }
};
