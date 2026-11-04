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
        Schema::create('professional_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id'); 
            $table->unsignedBigInteger('service_id'); 
            $table->string('service_name'); 
            $table->integer('duration');
            $table->string('image_path')->nullable();
            $table->text('description');
            $table->json('features')->nullable();
            $table->string('tags')->nullable();
            $table->text('requirements')->nullable();
            // Included here so fresh installs still get columns from the earlier
            // 2025_05_01 alter that cannot run before this table exists.
            $table->json('most_requested_services')->nullable();
            $table->json('most_requested_services_upto_price')->nullable();
            $table->timestamps();

            $table->foreign('professional_id')->references('id')->on('professionals')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('professional_services');
    }
};
