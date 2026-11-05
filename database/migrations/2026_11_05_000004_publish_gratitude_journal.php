<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tools')) {
            return;
        }

        $row = DB::table('tools')->where('slug', 'gratitude-journal')->first();
        if (!$row) {
            return;
        }

        $description = $row->description;
        if (!is_string($description)
            || trim($description) === ''
            || str_contains($description, 'not built yet')) {
            $description = 'Notice and appreciate the good in your day.';
        }

        DB::table('tools')->where('id', $row->id)->update([
            'status' => 'active',
            'is_implemented' => true,
            'route_name' => 'tools.psychology.gratitude',
            'description' => $description,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('tools')) {
            return;
        }

        $row = DB::table('tools')->where('slug', 'gratitude-journal')->first();
        if (!$row) {
            return;
        }

        $description = $row->description === 'Notice and appreciate the good in your day.'
            ? 'A private gratitude record. The workflow is not built yet.'
            : $row->description;

        DB::table('tools')->where('id', $row->id)->update([
            'status' => 'coming_soon',
            'is_implemented' => false,
            'route_name' => null,
            'description' => $description,
            'updated_at' => now(),
        ]);
    }
};
