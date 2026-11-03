<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        $row = DB::table('tools')->where('slug', 'skills-strengths-assessment')->first();
        if (! $row) {
            return;
        }

        $description = $row->description;
        if (! is_string($description) || trim($description) === '' || str_contains(strtolower($description), 'coming soon')) {
            $description = 'Map your strengths and transferable skills across eight self-rated areas.';
        }

        DB::table('tools')->where('id', $row->id)->update([
            'status' => 'active',
            'is_implemented' => true,
            'route_name' => 'tools.skills-strengths',
            'description' => $description,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        $row = DB::table('tools')->where('slug', 'skills-strengths-assessment')->first();
        if (! $row) {
            return;
        }

        $description = $row->description === 'Map your strengths and transferable skills across eight self-rated areas.'
            ? 'Map your strengths and transferable skills. Coming soon.'
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
