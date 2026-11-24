<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExploreToolsFormattingTest extends TestCase
{
    public function test_tools_page_loads_brand_aligned_catalogue_assets(): void
    {
        $this->get(route('tools.index'))
            ->assertOk()
            ->assertSee('yuktione-tools-page.css', false)
            ->assertSee('yk-tools', false)
            ->assertSee('Try Our', false)
            ->assertSee('All Tools', false);
    }

    public function test_tools_page_css_uses_yuktione_brand_tokens(): void
    {
        $css = file_get_contents(public_path('frontend/assets/css/yuktione-tools-page.css'));

        $this->assertNotFalse($css);
        $this->assertStringContainsString('--yk-plum: var(--y1-plum, #8a0f4d)', $css);
        $this->assertStringContainsString('--yk-plum-soft: var(--y1-plum-soft, #f8eaf1)', $css);
        $this->assertStringContainsString('--yk-pearl: var(--y1-pearl, #fff8fb)', $css);
        $this->assertStringContainsString('.yk-tool-card__copy', $css);
        $this->assertStringContainsString('min-height: 2.4em', $css);
        $this->assertStringNotContainsString('max-width: 18ch', $css);
        $this->assertStringNotContainsString('color: #97004f', $css);
    }

    public function test_home_tools_strip_css_keeps_fixed_tile_media_slot(): void
    {
        $css = file_get_contents(public_path('frontend/assets/css/yuktione-tools.css'));

        $this->assertNotFalse($css);
        $this->assertStringContainsString('width: 92px', $css);
        $this->assertStringContainsString('height: 68px', $css);
        $this->assertStringContainsString('var(--y1-plum-soft, #f8eaf1)', $css);
        $this->assertStringContainsString('.yk-tool-tile__label', $css);
    }

    public function test_tool_card_partial_keeps_copy_wrapper_for_alignment(): void
    {
        $blade = file_get_contents(resource_path('views/frontend/sections/partials/tool-card.blade.php'));

        $this->assertNotFalse($blade);
        $this->assertStringContainsString('yk-tool-card__copy', $blade);
        $this->assertStringContainsString('yk-tool-card__title', $blade);
        $this->assertStringContainsString('yk-tool-card__desc', $blade);
        $this->assertStringContainsString('yk-tool-card__go', $blade);
    }
}
