<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('psychology_gratitude_entries')) {
            return;
        }

        Schema::create('psychology_gratitude_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('completion_key')->unique();
            $table->text('something_good_today')->nullable();
            $table->text('someone_i_appreciate')->nullable();
            $table->text('something_about_myself')->nullable();
            $table->timestamp('written_at')->useCurrent();
            $table->timestamps();
            $table->index(['user_id', 'written_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psychology_gratitude_entries');
    }
};
