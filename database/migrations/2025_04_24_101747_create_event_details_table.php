<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_details', function (Blueprint $table) {
            $table->id();
            $table->string('banner_image');
            // Parent table is all_events (created later). Keep the column here and
            // add the foreign key in 2025_04_29_123609 after all_events exists.
            // Historical reference to a non-existent `events` table blocked fresh installs.
            $table->unsignedBigInteger('event_id');
            $table->string('event_type');
            $table->text('event_details');
            $table->date('starting_date');
            $table->decimal('starting_fees', 10, 2);
            $table->json('event_gallery')->nullable();
            $table->string('map_link')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_details');
    }
};
