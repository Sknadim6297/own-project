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

        $row = DB::table('tools')->where('slug', 'career-interest-test')->first();
        if (! $row) {
            return;
        }

        $description = $row->description;
        if (! is_string($description) || trim($description) === '' || str_contains(strtolower($description), 'coming soon')) {
            $description = 'Discover career directions that match your interests.';
        }

        DB::table('tools')->where('id', $row->id)->update([
            'status' => 'active',
            'is_implemented' => true,
            'route_name' => 'tools.career-interest',
            'description' => $description,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        $row = DB::table('tools')->where('slug', 'career-interest-test')->first();
        if (! $row) {
            return;
        }

        $description = $row->description === 'Discover career directions that match your interests.'
            ? 'Discover career directions that match your interests. Coming soon.'
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
