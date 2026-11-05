<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('psychology_journal_entries')) {
            return;
        }

        Schema::create('psychology_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('completion_key')->unique();
            $table->string('prompt_key', 40)->nullable();
            $table->string('title', 120)->nullable();
            $table->text('body');
            $table->timestamp('written_at')->useCurrent();
            $table->timestamps();
            $table->index(['user_id', 'written_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psychology_journal_entries');
    }
};
