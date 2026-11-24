<?php

namespace Tests\Feature;

use Tests\TestCase;

class GratitudeJournalNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_compose_keeps_next_visible_but_disabled_until_answer(): void
    {
        $response = $this->get(route('tools.psychology.gratitude'));

        $response->assertOk()
            ->assertSee('data-gratitude-next', false)
            ->assertSee('data-gratitude-skip', false)
            ->assertSee('data-gratitude-nav', false)
            ->assertSee('yuktione-gratitude.js', false);

        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/data-gratitude-next[^>]*\bdisabled\b/i',
            $html,
            'Next should render disabled before an answer is entered.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/data-gratitude-next[^>]*\bhidden\b/i',
            $html,
            'Next must stay in the layout instead of starting hidden.'
        );
        $this->assertMatchesRegularExpression(
            '/aria-disabled="true"/i',
            $html
        );
    }

    public function test_gratitude_script_enables_next_on_input_and_keeps_nav_reachable(): void
    {
        $path = public_path('frontend/assets/js/yuktione-gratitude.js');
        $this->assertFileExists($path);
        $js = file_get_contents($path);

        $this->assertStringContainsString("addEventListener('input'", $js);
        $this->assertStringContainsString("addEventListener('change'", $js);
        $this->assertStringContainsString("addEventListener('compositionend'", $js);
        $this->assertStringContainsString('nextBtn.disabled = !hasAnswer', $js);
        $this->assertStringContainsString('ensureNavVisible', $js);
        $this->assertStringContainsString('scrollIntoView', $js);
        $this->assertStringContainsString('syncNavToVisualViewport', $js);
        $this->assertStringContainsString('visualViewport', $js);
        $this->assertStringContainsString('navLockUntil', $js);
        $this->assertStringContainsString("addEventListener('pageshow'", $js);
    }

    public function test_gratitude_css_styles_disabled_next_and_reachable_nav(): void
    {
        $path = public_path('frontend/assets/css/yuktione-gratitude.css');
        $this->assertFileExists($path);
        $css = file_get_contents($path);

        $this->assertStringContainsString('.yk-gratitude-nav', $css);
        $this->assertStringContainsString('position: sticky', $css);
        $this->assertStringContainsString('position: fixed', $css);
        $this->assertStringContainsString('.yk-gratitude-nav__next:disabled', $css);
        $this->assertStringContainsString('cursor: not-allowed', $css);
        $this->assertStringNotContainsString('--yk-lavender', $css);
    }
}
