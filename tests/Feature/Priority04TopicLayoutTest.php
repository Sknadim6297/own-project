<?php

namespace Tests\Feature;

use App\Support\Homepage\TopicLayout;
use Tests\TestCase;

/**
 * Priority 4 — reversible topic layout contracts (default remains single-row).
 */
class Priority04TopicLayoutTest extends TestCase
{
    public function test_default_layout_is_single_row_and_unconfirmed_modes_are_opt_in(): void
    {
        $this->assertSame(TopicLayout::SINGLE_ROW, TopicLayout::resolve(null));
        $this->assertSame(TopicLayout::SINGLE_ROW, TopicLayout::resolve('nope'));
        $this->assertSame('', TopicLayout::solutionsClasses(TopicLayout::SINGLE_ROW));
        $this->assertSame('', TopicLayout::toolsClasses(TopicLayout::SINGLE_ROW));

        $this->assertSame('yk-home-cats--two-row', TopicLayout::solutionsClasses(TopicLayout::TWO_ROW));
        $this->assertSame('yk-home-cats--label-wrap', TopicLayout::solutionsClasses(TopicLayout::LABEL_WRAP));
        $this->assertSame('yk-tools-strip--two-row', TopicLayout::toolsClasses(TopicLayout::TWO_ROW));
        $this->assertSame('yk-tools-strip--label-wrap', TopicLayout::toolsClasses(TopicLayout::LABEL_WRAP));
    }

    public function test_partials_default_to_single_row_without_modifier_classes(): void
    {
        $cats = view('frontend.home.partials.categories', [
            'homepageCategories' => collect([
                (object) [
                    'slug' => 'mental-wellness',
                    'name' => 'Mental Wellness',
                    'image' => null,
                    'short_description' => 'Calm',
                ],
            ]),
            'topicLayout' => TopicLayout::SINGLE_ROW,
        ])->render();

        $this->assertStringContainsString('data-topic-layout="single-row"', $cats);
        $this->assertStringNotContainsString('yk-home-cats--two-row', $cats);
        $this->assertStringNotContainsString('yk-home-cats--label-wrap', $cats);

        $tools = view('frontend.home.partials.tools', [
            'topicLayout' => TopicLayout::SINGLE_ROW,
        ])->render();
        $this->assertStringContainsString('data-topic-layout="single-row"', $tools);
        $this->assertStringNotContainsString('yk-tools-strip--two-row', $tools);
    }

    public function test_two_row_and_label_wrap_modifiers_render_when_requested(): void
    {
        $twoRow = view('frontend.home.partials.categories', [
            'homepageCategories' => collect([
                (object) [
                    'slug' => 'nutrition',
                    'name' => 'Nutrition',
                    'image' => null,
                    'short_description' => 'Eat well',
                ],
            ]),
            'topicLayout' => TopicLayout::TWO_ROW,
        ])->render();
        $this->assertStringContainsString('yk-home-cats--two-row', $twoRow);
        $this->assertStringContainsString('data-topic-layout="two-row"', $twoRow);

        $wrap = view('frontend.home.partials.tools', [
            'topicLayout' => TopicLayout::LABEL_WRAP,
        ])->render();
        $this->assertStringContainsString('yk-tools-strip--label-wrap', $wrap);
    }

    public function test_css_ships_scoped_alternatives_without_removing_task_55_default(): void
    {
        $catCss = file_get_contents(public_path('frontend/assets/css/yuktione-categories.css'));
        $toolCss = file_get_contents(public_path('frontend/assets/css/yuktione-tools.css'));
        $this->assertIsString($catCss);
        $this->assertIsString($toolCss);

        $this->assertStringContainsString('.yk-home-cats--two-row', $catCss);
        $this->assertStringContainsString('grid-auto-flow: column', $catCss);
        $this->assertStringContainsString('.yk-home-cats--label-wrap', $catCss);
        $this->assertStringContainsString('.yk-tools-strip--two-row', $toolCss);
        $this->assertStringContainsString('.yk-tools-strip--label-wrap', $toolCss);

        // Default Task 55 nowrap remains for the non-opt-in rich rail.
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 768px\)[\s\S]*?\.yk-home-cats--rich \.yk-home-cats__card--photo \.yk-home-cats__label\s*\{[^}]*white-space:\s*nowrap/s',
            $catCss
        );
    }

    public function test_config_and_query_candidates_resolve_without_persisting_default(): void
    {
        config(['yuktione.topic_layout' => TopicLayout::SINGLE_ROW]);
        $this->assertSame(TopicLayout::SINGLE_ROW, TopicLayout::resolve(null));
        $this->assertSame(TopicLayout::TWO_ROW, TopicLayout::resolve('two-row'));
        $this->assertSame(TopicLayout::LABEL_WRAP, TopicLayout::resolve('label-wrap'));

        config(['yuktione.topic_layout' => TopicLayout::TWO_ROW]);
        $this->assertSame(TopicLayout::TWO_ROW, TopicLayout::resolve(null));
        // Explicit query still wins over config when valid.
        $this->assertSame(TopicLayout::LABEL_WRAP, TopicLayout::resolve('label-wrap'));
        // Invalid query falls back to config.
        $this->assertSame(TopicLayout::TWO_ROW, TopicLayout::resolve('invalid'));
    }
}
