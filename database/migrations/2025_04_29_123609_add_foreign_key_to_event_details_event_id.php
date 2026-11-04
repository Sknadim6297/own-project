<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('event_details') || !Schema::hasTable('all_events')) {
            return;
        }

        Schema::table('event_details', function (Blueprint $table) {
            if (!Schema::hasColumn('event_details', 'event_id')) {
                $table->unsignedBigInteger('event_id')->after('id');
            }
        });

        if ($this->foreignKeyExists('event_details', 'event_details_event_id_foreign')) {
            return;
        }

        Schema::table('event_details', function (Blueprint $table) {
            $table->foreign('event_id')
                ->references('id')
                ->on('all_events')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('event_details')) {
            return;
        }

        if ($this->foreignKeyExists('event_details', 'event_details_event_id_foreign')) {
            Schema::table('event_details', function (Blueprint $table) {
                $table->dropForeign(['event_id']);
            });
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $database = Schema::getConnection()->getDatabaseName();

        $result = DB::selectOne(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ?
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = ?',
            [$database, $table, $constraint, 'FOREIGN KEY']
        );

        return $result !== null;
    }
};
