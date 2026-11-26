<?php

namespace Tests\Feature;

use App\Services\CategoryHub;
use App\Services\ExploreCatalog;
use App\Services\ToolsCatalog;
use App\Support\Tools\ToolsSpotlight;
use Tests\TestCase;

/**
 * Task 55 — Topic label formatting contracts (Solutions rail + Tools strip).
 * Wrapping behaviour is verified in the local browser; this suite locks structure.
 */
class TopicLabelFormattingTest extends TestCase
{
    public function test_solutions_rail_preserves_topic_names_counts_and_destinations(): void
    {
        $html = view('frontend.home.partials.categories')->render();

        $this->assertStringContainsString('data-yk-solutions-rail', $html);
        $this->assertStringContainsString('data-categories-track', $html);
        $this->assertStringContainsString('Explore', $html);
        $this->assertStringContainsString('by Need', $html);

        preg_match('/data-categories-track[^>]*>(.*?)<\/ul>/s', $html, $track);
        $this->assertNotEmpty($track[1] ?? null);
        $this->assertSame(11, substr_count($track[1], '<li>'));
        $this->assertSame(11, substr_count($track[1], 'yk-home-cats__label'));

        foreach (CategoryHub::DISPLAY_LABELS as $slug => $label) {
            $this->assertStringContainsString(e($label), $html);
            $this->assertStringContainsString(route('categories.show', $slug), $html);
        }

        $this->assertCount(11, ExploreCatalog::CATEGORIES);
        $this->assertSame(array_keys(ExploreCatalog::CATEGORIES), array_keys(CategoryHub::DISPLAY_LABELS));
    }

    public function test_topic_label_css_removes_ch_width_caps_and_scopes_nowrap(): void
    {
        $css = file_get_contents(public_path('frontend/assets/css/yuktione-categories.css'));
        $this->assertIsString($css);

        // Artificial ch caps that forced two-line topic wraps must stay removed.
        $this->assertStringNotContainsString('max-width: 12ch', $css);
        $this->assertStringNotContainsString('max-width: 14ch', $css);
        $this->assertStringNotContainsString('max-width: 16ch', $css);

        $this->assertStringContainsString('width: 100%', $css);
        $this->assertStringContainsString('max-width: 100%', $css);
        $this->assertStringContainsString('align-items: stretch', $css);

        // nowrap is scoped to rich Solutions cards from tablet up — not global body text.
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 768px\)[\s\S]*?\.yk-home-cats--rich \.yk-home-cats__card--photo \.yk-home-cats__label\s*\{[^}]*white-space:\s*nowrap/s',
            $css
        );

        // Phone keeps graceful wrap for long names / enlarged text.
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 1023\.98px\)[\s\S]*?\.yk-home-cats--rich \.yk-home-cats__card--photo \.yk-home-cats__label\s*\{[^}]*white-space:\s*normal/s',
            $css
        );

        // Task 52: cards 7–11 must remain reachable (no display:none hide).
        $this->assertDoesNotMatchRegularExpression(
            '/\.yk-home-cats--rich \.yk-home-cats__track > li:nth-child\(n \+ 7\)\s*\{\s*display:\s*none/s',
            $css
        );
    }

    public function test_tools_strip_preserves_preview_and_scroller_markers(): void
    {
        $html = view('frontend.home.partials.tools')->render();

        $this->assertStringContainsString('data-yk-tools-rail', $html);
        $this->assertStringContainsString('data-tools-track', $html);
        $this->assertStringContainsString('View All Tools', $html);

        $cards = ToolsSpotlight::cards(12);
        $this->assertLessThanOrEqual(12, count($cards));
        preg_match('/data-tools-track[^>]*>(.*?)<\/ul>/s', $html, $track);
        $this->assertSame(count($cards), substr_count($track[1] ?? '', 'class="yk-tool-tile"'));

        $css = file_get_contents(public_path('frontend/assets/css/yuktione-tools.css'));
        $this->assertIsString($css);
        $this->assertStringContainsString('scrollbar-width: thin', $css);
        $this->assertDoesNotMatchRegularExpression(
            '/@media \(max-width: 767px\)[\s\S]*?\.yk-tools-strip__nav\s*\{\s*display:\s*none/s',
            $css
        );
    }

    public function test_task_53_hub_mappings_and_catalogue_remain_intact(): void
    {
        $this->assertCount(9, ToolsCatalog::CATEGORIES);
        $this->assertArrayHasKey('astrology-tarot', ExploreCatalog::CATEGORIES);
        $this->assertArrayNotHasKey('astrology-tarot', ToolsCatalog::CATEGORIES);
        $this->assertSame('mental-wellness', ToolsCatalog::CATEGORY_ALIASES['parenting']);
    }

    public function test_directory_stats_and_browse_facets_signature_remain_available(): void
    {
        $this->assertTrue(method_exists(ExploreCatalog::class, 'directoryStats'));

        $ref = new \ReflectionMethod(ExploreCatalog::class, 'browse');
        $params = $ref->getParameters();
        $this->assertGreaterThanOrEqual(2, count($params));
        $this->assertSame('withFacets', $params[1]->getName());

        // Route remains registered; full HTTP/DB browse is verified in the local browser.
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('professionals'));
    }
}
