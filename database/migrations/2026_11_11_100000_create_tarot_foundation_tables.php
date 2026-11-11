<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tarot_cards')) {
            Schema::create('tarot_cards', function (Blueprint $table) {
                $table->id();
                $table->string('card_id', 40)->unique();
                $table->string('card_name');
                $table->string('arcana', 20); // major | minor
                $table->string('suit', 20)->nullable(); // cups|wands|swords|pentacles; null for major
                $table->string('number', 20)->nullable();
                $table->text('upright_meaning');
                $table->text('reversed_meaning');
                $table->string('upright_keywords')->nullable();
                $table->string('reversed_keywords')->nullable();
                $table->string('image_path')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['arcana', 'suit']);
                $table->index('sort_order');
            });
        }

        if (! Schema::hasTable('tarot_readings')) {
            Schema::create('tarot_readings', function (Blueprint $table) {
                $table->id();
                // Ownership is required: guest persistence is intentionally not supported yet.
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->uuid('completion_key')->unique();
                $table->string('category', 80);
                $table->text('question');
                $table->string('spread_type', 20); // one | three
                $table->boolean('reversed_enabled')->default(false);
                $table->text('overall_reflection')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index('category');
                $table->index('spread_type');
            });
        }

        if (! Schema::hasTable('tarot_reading_cards')) {
            Schema::create('tarot_reading_cards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tarot_reading_id')->constrained('tarot_readings')->cascadeOnDelete();
                $table->foreignId('tarot_card_id')->constrained('tarot_cards')->restrictOnDelete();
                $table->string('position', 40);
                $table->unsignedTinyInteger('position_order');
                $table->boolean('is_reversed')->default(false);
                $table->text('insight_text')->nullable();
                $table->timestamp('drawn_at')->nullable();
                $table->timestamps();

                $table->unique(['tarot_reading_id', 'position_order']);
                $table->unique(['tarot_reading_id', 'tarot_card_id']);
                $table->index(['tarot_reading_id', 'position']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tarot_reading_cards');
        Schema::dropIfExists('tarot_readings');
        Schema::dropIfExists('tarot_cards');
    }
};
