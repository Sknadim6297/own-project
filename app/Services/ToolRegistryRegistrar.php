<?php

namespace App\Services;

use App\Models\Tool;
use App\Models\ToolCategory;
use Illuminate\Support\Facades\DB;

class ToolRegistryRegistrar
{
    public function register(): void
    {
        DB::transaction(function () {
            $this->registerPsychology();
            $this->registerCareerGuidance();
            $this->registerNutrition();
        });
    }

    private function registerPsychology(): void
    {
        $category = ToolCategory::query()->firstOrCreate(
            ['slug' => 'psychology'],
            [
                'name' => 'Psychology',
                'description' => 'Private wellbeing tools. User entries stay outside this registry.',
                'icon' => 'ri-mental-health-line',
                'is_active' => true,
                'display_order' => 1,
            ]
        );

        $this->registerTools($category, 'psychology', [
            [
                'slug' => 'daily-mood-check-in',
                'name' => 'Daily Mood Check-in',
                'description' => 'Understand and track your emotions.',
                'icon' => 'ri-emotion-line',
                'display_order' => 1,
                'status' => 'active',
                'route_name' => 'tools.psychology.mood',
                'is_implemented' => true,
            ],
            [
                'slug' => 'digital-journal',
                'name' => 'Digital Journal',
                'description' => 'Express your thoughts in your own words.',
                'icon' => 'ri-book-open-line',
                'display_order' => 2,
                'status' => 'active',
                'route_name' => 'tools.psychology.journal',
                'is_implemented' => true,
            ],
            [
                'slug' => 'gratitude-journal',
                'name' => 'Gratitude Journal',
                'description' => 'Notice and appreciate the good in your day.',
                'icon' => 'ri-heart-2-line',
                'display_order' => 3,
                'status' => 'active',
                'route_name' => 'tools.psychology.gratitude',
                'is_implemented' => true,
            ],
        ]);
    }

    private function registerCareerGuidance(): void
    {
        $category = ToolCategory::query()->firstOrCreate(
            ['slug' => 'career-guidance'],
            [
                'name' => 'Career Guidance',
                'description' => 'Career interest and strengths assessments. Results stay private to each user.',
                'icon' => 'ri-briefcase-4-line',
                'is_active' => true,
                'display_order' => 2,
            ]
        );

        $this->registerTools($category, 'career-guidance', [
            [
                'slug' => 'career-interest-test',
                'name' => 'Career Interest Test',
                'description' => 'Discover career directions that match your interests. Coming soon.',
                'icon' => 'ri-compass-3-line',
                'display_order' => 1,
            ],
            [
                'slug' => 'skills-strengths-assessment',
                'name' => 'Skills & Strengths Assessment',
                'description' => 'Map your strengths and transferable skills. Coming soon.',
                'icon' => 'ri-bar-chart-grouped-line',
                'display_order' => 2,
            ],
        ]);
    }

    private function registerNutrition(): void
    {
        $category = ToolCategory::query()->firstOrCreate(
            ['slug' => 'nutrition'],
            [
                'name' => 'Nutrition',
                'description' => 'Body metrics and nutrition trackers. Health entries stay private to each user.',
                'icon' => 'ri-restaurant-2-line',
                'is_active' => true,
                'display_order' => 3,
            ]
        );

        $this->registerTools($category, 'nutrition', [
            [
                'slug' => 'bmi-bmr-calculator',
                'name' => 'BMI & BMR Calculator',
                'description' => 'Estimate BMI and BMR from your measurements. Coming soon.',
                'icon' => 'ri-calculator-line',
                'display_order' => 1,
            ],
            [
                'slug' => 'water-intake-tracker',
                'name' => 'Water Intake Tracker',
                'description' => 'Track daily water intake against a personal target. Coming soon.',
                'icon' => 'ri-drop-line',
                'display_order' => 2,
            ],
            [
                'slug' => 'weight-journey',
                'name' => 'Weight Journey',
                'description' => 'Log weight over time and review progress privately. Coming soon.',
                'icon' => 'ri-scales-3-line',
                'display_order' => 3,
            ],
            [
                'slug' => 'calorie-nutrition-tracker',
                'name' => 'Calorie & Nutrition Tracker',
                'description' => 'Log meals from the published food catalogue with optional calorie and macro goals.',
                'icon' => 'ri-bowl-line',
                'display_order' => 4,
            ],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $tools
     */
    private function registerTools(ToolCategory $category, string $moduleKey, array $tools): void
    {
        foreach ($tools as $tool) {
            Tool::query()->firstOrCreate(
                ['slug' => $tool['slug']],
                [
                    'tool_category_id' => $category->id,
                    'name' => $tool['name'],
                    'module_key' => $moduleKey,
                    'description' => $tool['description'],
                    'icon' => $tool['icon'],
                    'access_type' => 'free',
                    'status' => $tool['status'] ?? 'coming_soon',
                    'display_order' => $tool['display_order'],
                    'route_name' => $tool['route_name'] ?? null,
                    'is_implemented' => $tool['is_implemented'] ?? false,
                ]
            );
        }
    }
}
