<?php

namespace Tests\Feature;

use App\Services\ExploreCatalog;
use App\Services\ToolsCatalog;
use App\Support\Tools\ToolsSpotlight;
use Tests\TestCase;

/**
 * Task 54 — Homepage Tools strip scroller contracts.
 * Scrolling behaviour is verified in the local browser; this suite locks markup/CSS.
 */
class ToolsScrollerTest extends TestCase
{
    public function test_tools_rail_renders_track_controls_and_view_all(): void
    {
        $html = view('frontend.home.partials.tools')->render();

        $this->assertStringContainsString('data-yk-tools-rail', $html);
        $this->assertStringContainsString('data-tools-carousel', $html);
        $this->assertStringContainsString('data-tools-track', $html);
        $this->assertStringContainsString('data-tools-prev', $html);
        $this->assertStringContainsString('data-tools-next', $html);
        $this->assertStringContainsString('aria-label="Previous tools"', $html);
        $this->assertStringContainsString('aria-label="Next tools"', $html);
        $this->assertStringContainsString('View All Tools', $html);
        $this->assertStringContainsString(route('tools.index'), $html);
        $this->assertStringContainsString('Try', $html);
        $this->assertStringContainsString('Our Tools', $html);
    }

    public function test_tools_spotlight_preserves_preview_limit_and_destinations(): void
    {
        $cards = ToolsSpotlight::cards(12);
        $this->assertNotEmpty($cards);
        $this->assertLessThanOrEqual(12, count($cards));

        $html = view('frontend.home.partials.tools')->render();
        preg_match('/data-tools-track[^>]*>(.*?)<\/ul>/s', $html, $track);
        $this->assertNotEmpty($track[1] ?? null);
        $this->assertSame(count($cards), substr_count($track[1], 'class="yk-tool-tile"'));

        foreach ($cards as $card) {
            $this->assertNotSame('', $card['label']);
            $this->assertNotSame('', $card['url']);
            $this->assertStringContainsString($card['url'], $html);
        }
    }

    public function test_tools_scroller_css_shows_native_scrollbar_when_overflowing(): void
    {
        $css = file_get_contents(public_path('frontend/assets/css/yuktione-tools.css'));
        $this->assertIsString($css);

        $this->assertStringContainsString('scrollbar-width: thin', $css);
        $this->assertStringContainsString('scrollbar-color: rgba(138, 15, 77, 0.45) #f8eaf1', $css);
        $this->assertStringContainsString('::-webkit-scrollbar', $css);
        $this->assertStringContainsString('::-webkit-scrollbar-thumb', $css);
        $this->assertStringContainsString('is-carousel-scrollable', $css);
        $this->assertStringContainsString('scroll-snap-type: x proximity', $css);
        $this->assertStringContainsString('scroll-behavior: auto', $css);
        $this->assertStringContainsString('safe-area-inset-right', $css);

        // Base track rule must expose a thin scroller (hide only when not overflow).
        $this->assertMatchesRegularExpression(
            '/\.yk-tools-strip__track\s*\{[^}]*scrollbar-width:\s*thin/s',
            $css
        );
        $this->assertStringContainsString(
            '.yk-tools-strip:not(.is-carousel-scrollable) .yk-tools-strip__track',
            $css
        );
        $this->assertStringNotContainsString(
            ".yk-tools-strip__track::-webkit-scrollbar {\n    display: none;",
            $css
        );

        // Mobile keeps previous/next controls (Task 54 discoverability).
        $this->assertDoesNotMatchRegularExpression(
            '/@media \(max-width: 767px\)[\s\S]*?\.yk-tools-strip__nav\s*\{\s*display:\s*none/s',
            $css
        );
    }

    public function test_task_52_solutions_rail_and_shared_carousel_remain_intact(): void
    {
        $homepage = collect([
            (object) [
                'slug' => 'mental-wellness',
                'name' => 'Mental Wellness',
                'image' => null,
                'short_description' => 'Calm guidance',
            ],
            (object) [
                'slug' => 'nutrition',
                'name' => 'Nutrition',
                'image' => null,
                'short_description' => 'Eat well',
            ],
            (object) [
                'slug' => 'investments',
                'name' => 'Investments',
                'image' => null,
                'short_description' => 'Grow wealth',
            ],
        ]);

        $cats = view('frontend.home.partials.categories', [
            'homepageCategories' => $homepage,
        ])->render();
        $this->assertStringContainsString('data-yk-solutions-rail', $cats);
        preg_match('/data-categories-track[^>]*>(.*?)<\/ul>/s', $cats, $track);
        $this->assertNotEmpty($track[1] ?? null);
        $this->assertSame(3, substr_count($track[1], '<li>'));
        $this->assertCount(11, ExploreCatalog::CATEGORIES);

        $js = file_get_contents(public_path('frontend/assets/js/yuktione-home.js'));
        $this->assertIsString($js);
        $this->assertStringContainsString("bindHomeCarousel('[data-tools-carousel]'", $js);
        $this->assertStringContainsString("bindHomeCarousel('[data-categories-carousel]'", $js);
        $this->assertStringContainsString('itemOffsets', $js);
        $this->assertStringContainsString('ArrowRight', $js);
    }

    public function test_task_53_hub_category_mappings_remain_correct(): void
    {
        $this->assertCount(9, ToolsCatalog::CATEGORIES);

        // Without a tools table the registry may be empty in SQLite tests;
        // assert the resolution rules themselves (hub keys are not remapped).
        $catalog = app(ToolsCatalog::class);
        $ref = new \ReflectionClass($catalog);
        $method = $ref->getMethod('forExploreCategory');
        $this->assertTrue($method->isPublic());

        // Explore hub keys stay distinct from tools-catalogue aliases.
        $this->assertArrayHasKey('astrology-tarot', ExploreCatalog::CATEGORIES);
        $this->assertArrayNotHasKey('astrology-tarot', ToolsCatalog::CATEGORIES);
        $this->assertArrayHasKey('parenting', ExploreCatalog::CATEGORIES);
        $this->assertSame('mental-wellness', ToolsCatalog::CATEGORY_ALIASES['parenting']);
    }
}
