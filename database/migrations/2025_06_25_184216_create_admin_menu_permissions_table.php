<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * admin_menu_permissions is already created in 2025_06_14_200000.
     * On fresh installs this becomes a no-op; on older installs without that
     * earlier create, build the table here.
     */
    public function up(): void
    {
        if (Schema::hasTable('admin_menu_permissions')) {
            return;
        }

        Schema::create('admin_menu_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('admin_menu_id');
            $table->timestamps();

            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('admin_menu_id')->references('id')->on('admin_menus')->onDelete('cascade');
            $table->unique(['admin_id', 'admin_menu_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do not drop here: earlier migration owns the table on fresh installs.
    }
};
