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

        $row = DB::table('tools')->where('slug', 'bmi-bmr-calculator')->first();
        if (! $row) {
            return;
        }

        $description = $row->description;
        if (! is_string($description) || trim($description) === '' || str_contains(strtolower($description), 'coming soon')) {
            $description = 'Estimate adult BMI and Mifflin–St Jeor BMR/TDEE from your measurements.';
        }

        DB::table('tools')->where('id', $row->id)->update([
            'status' => 'active',
            'is_implemented' => true,
            'route_name' => 'tools.bmi-bmr',
            'description' => $description,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        $row = DB::table('tools')->where('slug', 'bmi-bmr-calculator')->first();
        if (! $row) {
            return;
        }

        $description = $row->description === 'Estimate adult BMI and Mifflin–St Jeor BMR/TDEE from your measurements.'
            ? 'Estimate BMI and BMR from your measurements. Coming soon.'
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
