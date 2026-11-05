<?php

use App\Models\Tool;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Tool::query()->where('slug', 'calorie-nutrition-tracker')->update([
            'status' => 'active',
            'is_implemented' => true,
            'route_name' => 'tools.nutrition',
            'description' => 'Log meals from the published food catalogue with optional calorie and macro goals.',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Tool::query()->where('slug', 'calorie-nutrition-tracker')->update([
            'status' => 'coming_soon',
            'is_implemented' => false,
            'route_name' => null,
            'description' => 'Log meals against a verified food database. Coming soon.',
            'updated_at' => now(),
        ]);
    }
};
