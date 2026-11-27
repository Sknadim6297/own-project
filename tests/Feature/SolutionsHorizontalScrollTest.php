<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Task 52 — Homepage Solutions (Explore by Need) horizontal scroll structure.
 * Scrolling behaviour is verified in the local browser; this suite locks markup/CSS contracts.
 * After Priority 2 the rail is database-driven (not a hardcoded 11-card Explore list).
 */
class SolutionsHorizontalScrollTest extends TestCase
{
    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function sampleHomepageCategories(int $count = 9)
    {
        $all = [
            ['slug' => 'mental-wellness', 'name' => 'Mental Wellness', 'short_description' => 'Calm guidance'],
            ['slug' => 'career-counselling', 'name' => 'Career Counselling', 'short_description' => 'Grow at work'],
            ['slug' => 'nutrition', 'name' => 'Nutrition', 'short_description' => 'Eat well'],
            ['slug' => 'fitness', 'name' => 'Fitness', 'short_description' => 'Move well'],
            ['slug' => 'astrology', 'name' => 'Astrology', 'short_description' => 'Sky insight'],
            ['slug' => 'tarot', 'name' => 'Tarot', 'short_description' => 'Reflect'],
            ['slug' => 'business-consulting', 'name' => 'Business Consulting', 'short_description' => 'Grow business'],
            ['slug' => 'tax-matters', 'name' => 'Tax Matters', 'short_description' => 'Stay compliant'],
            ['slug' => 'investments', 'name' => 'Investments', 'short_description' => 'Grow wealth'],
            ['slug' => 'extra-long-category', 'name' => 'Extra Long Category Name For Overflow', 'short_description' => 'Tenth'],
        ];

        return collect(array_slice($all, 0, $count))->map(fn (array $row) => (object) array_merge($row, [
            'image' => null,
        ]));
    }

    public function test_solutions_rail_renders_all_category_cards_and_controls(): void
    {
        $categories = $this->sampleHomepageCategories(9);
        $html = view('frontend.home.partials.categories', [
            'homepageCategories' => $categories,
        ])->render();

        $this->assertStringContainsString('data-yk-solutions-rail', $html);
        $this->assertStringContainsString('data-categories-carousel', $html);
        $this->assertStringContainsString('data-categories-track', $html);
        $this->assertStringContainsString('data-categories-prev', $html);
        $this->assertStringContainsString('data-categories-next', $html);
        $this->assertStringContainsString('aria-label="Previous categories"', $html);
        $this->assertStringContainsString('aria-label="Next categories"', $html);
        $this->assertStringContainsString('Explore', $html);
        $this->assertStringContainsString('by Need', $html);

        $this->assertStringContainsString('Mental Wellness', $html);
        $this->assertStringContainsString('Career Counselling', $html);
        $this->assertStringContainsString('Nutrition', $html);
        $this->assertStringContainsString('Fitness', $html);
        $this->assertStringContainsString('Astrology', $html);
        $this->assertStringContainsString('Tarot', $html);
        $this->assertStringContainsString('Investments', $html);

        preg_match('/data-categories-track[^>]*>(.*?)<\/ul>/s', $html, $track);
        $this->assertNotEmpty($track[1] ?? null);
        $this->assertSame(9, substr_count($track[1], '<li>'));
    }

    public function test_category_card_destinations_remain_unchanged(): void
    {
        $categories = $this->sampleHomepageCategories(9);
        $html = view('frontend.home.partials.categories', [
            'homepageCategories' => $categories,
        ])->render();

        preg_match_all(
            '/class="yk-home-cats__card[^"]*"[^>]*href="([^"]+)"/',
            $html,
            $matches
        );

        $this->assertCount(9, $matches[1]);
        foreach ($matches[1] as $href) {
            $this->assertStringContainsString('/categories/', $href);
            $this->assertStringNotContainsString('/tools/', $href);
        }

        $this->assertStringContainsString('categories/mental-wellness', $html);
        $this->assertStringContainsString('categories/nutrition', $html);
    }

    public function test_scroll_css_keeps_all_cards_reachable_on_small_screens(): void
    {
        $css = file_get_contents(public_path('frontend/assets/css/yuktione-categories.css'));
        $this->assertIsString($css);

        $this->assertStringContainsString('data-yk-solutions-rail', file_get_contents(
            resource_path('views/frontend/home/partials/categories.blade.php')
        ));
        $this->assertStringContainsString('scroll-snap-type: x proximity', $css);
        $this->assertStringContainsString('overflow-x: auto', $css);
        $this->assertStringContainsString('flex-wrap: nowrap', $css);
        $this->assertStringNotContainsString("nth-child(n + 7) {\n        display: none;", $css);
        $this->assertStringNotContainsString("nth-child(n + 7) {\r\n        display: none;", $css);
        $this->assertStringContainsString('.yk-home-cats:not(.is-carousel-scrollable) .yk-home-cats__nav', $css);

        $this->assertMatchesRegularExpression(
            '/@media \\(max-width: 1023\\.98px\\)[\\s\\S]*?display:\\s*flex;[\\s\\S]*?overflow-x:\\s*auto;/',
            $css
        );
    }

    public function test_carousel_helper_scrolls_by_item_offsets(): void
    {
        $js = file_get_contents(public_path('frontend/assets/js/yuktione-home.js'));
        $this->assertIsString($js);
        $this->assertStringContainsString('bindHomeCarousel', $js);
        $this->assertStringContainsString('itemOffsets', $js);
        $this->assertStringContainsString("behavior: useSmooth ? 'smooth' : 'auto'", $js);
        $this->assertStringContainsString("track.scrollTo({ left: target, behavior: 'auto' })", $js);
        $this->assertStringContainsString("bindHomeCarousel('[data-categories-carousel]'", $js);
        $this->assertStringContainsString("bindHomeCarousel('[data-tools-carousel]'", $js);
        $this->assertStringContainsString('ArrowRight', $js);
        $this->assertStringContainsString('ArrowLeft', $js);
    }

    public function test_tools_and_hero_partials_remain_present_on_homepage_composition(): void
    {
        $index = file_get_contents(resource_path('views/frontend/index.blade.php'));
        $this->assertIsString($index);
        $this->assertStringContainsString('frontend.home.partials.categories', $index);
        $this->assertStringContainsString('frontend.home.partials.tools', $index);
        $this->assertStringContainsString('frontend.home.partials.hero', $index);

        $tools = view('frontend.home.partials.tools', ['tools' => collect()])->render();
        $this->assertStringContainsString('data-tools-carousel', $tools);
        $this->assertStringContainsString('Try', $tools);
        $this->assertStringContainsString('Our Tools', $tools);
        $this->assertStringNotContainsString('data-yk-solutions-rail', $tools);
    }
}
