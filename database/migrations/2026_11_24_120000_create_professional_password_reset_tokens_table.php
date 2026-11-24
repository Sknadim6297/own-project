<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Task 09: isolate Professional password-reset tokens from Customer tokens.
 *
 * Before: both brokers used password_reset_tokens (email primary key), so a
 * shared cross-role email could overwrite or collide on reset tokens.
 *
 * Outstanding reset links: all rows in password_reset_tokens are cleared on
 * upgrade so ambiguous legacy Professional tokens cannot be consumed as
 * Customer resets. Users must request a fresh reset link after deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Invalidate every outstanding shared-table token. Do not copy rows into
        // the Professional repository — prior tokens were role-ambiguous.
        if (Schema::hasTable('password_reset_tokens')) {
            DB::table('password_reset_tokens')->delete();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_password_reset_tokens');

        // Rollback does not restore cleared shared tokens. Brokers that again
        // share password_reset_tokens start empty; users request new links.
    }
};
