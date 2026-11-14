<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pfm_calculations')) {
            return;
        }

        Schema::create('pfm_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tool_key', 64)->index();
            $table->string('title', 160);
            $table->json('inputs');
            $table->json('outputs');
            $table->string('summary', 255)->nullable();
            $table->string('content_version', 64)->nullable();
            $table->uuid('completion_key')->unique();
            $table->boolean('is_draft')->default(false)->index();
            $table->timestamps();

            $table->index(['user_id', 'tool_key', 'created_at']);
            $table->index(['user_id', 'is_draft', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pfm_calculations');
    }
};
