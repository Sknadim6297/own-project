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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'terms_accepted_at')) {
                $table->timestamp('terms_accepted_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'terms_version')) {
                $table->string('terms_version', 32)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('users', 'terms_accepted_at')) {
                $columns[] = 'terms_accepted_at';
            }

            if (Schema::hasColumn('users', 'terms_version')) {
                $columns[] = 'terms_version';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
