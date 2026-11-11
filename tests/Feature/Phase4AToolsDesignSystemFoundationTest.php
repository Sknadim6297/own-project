<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Phase 4A — design-system foundation exists and is route-scoped (opt-in classes only).
 */
class Phase4AToolsDesignSystemFoundationTest extends TestCase
{
    public function test_tools_design_system_stylesheet_exists_with_core_tokens(): void
    {
        $path = public_path('frontend/assets/css/yuktione-tools-ds.css');
        $this->assertFileExists($path);

        $css = file_get_contents($path);
        $this->assertIsString($css);
        $this->assertStringContainsString('--yk-ds-navy', $css);
        $this->assertStringContainsString('--yk-ds-teal', $css);
        $this->assertStringContainsString('--yk-ds-cream', $css);
        $this->assertStringContainsString('--yk-ds-gold', $css);
        $this->assertStringContainsString('.yk-tools-ds', $css);
        $this->assertStringContainsString('prefers-reduced-motion', $css);
        $this->assertStringContainsString('--yk-ds-touch', $css);
    }

    public function test_tools_ds_partial_and_chrome_partials_exist(): void
    {
        $this->assertTrue(view()->exists('frontend.sections.partials.tools-ds-primitives'));
        $this->assertTrue(view()->exists('frontend.sections.partials.tool-back-to-tools'));
        $this->assertTrue(view()->exists('frontend.sections.partials.tool-icon'));
    }

    public function test_all_ten_tool_primary_routes_are_registered(): void
    {
        $names = [
            'tools.psychology.mood',
            'tools.psychology.journal',
            'tools.psychology.gratitude',
            'tools.career-interest',
            'tools.skills-strengths',
            'tools.bmi-bmr',
            'tools.tarot.intro',
            'tools.water-intake',
            'tools.weight-journey',
            'tools.nutrition',
            'tools.index',
        ];

        foreach ($names as $name) {
            $this->assertTrue(\Illuminate\Support\Facades\Route::has($name), "Missing route [{$name}]");
        }
    }
}
