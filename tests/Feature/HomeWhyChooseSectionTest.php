<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeWhyChooseSectionTest extends TestCase
{
    public function test_why_choose_panel_keeps_existing_copy_and_brand_name(): void
    {
        config(['yuktione.name' => 'YuktiOne']);

        $html = view('frontend.home.partials.platform-experience')->render();

        $this->assertStringContainsString('class="yukti-why-choose"', $html);
        $this->assertStringContainsString('id="yk-home-experience-title">Why Choose <span>YuktiOne?</span></h2>', $html);
        $this->assertStringContainsString('A trusted marketplace that brings expert guidance, practical tools, and supportive community into one calm experience.', $html);
        $this->assertStringContainsString('Glossy%20Pink%20Lotus%20Flower.png', $html);
        $this->assertStringContainsString('alt=""', $html);

        foreach ([
            'Expert-Led Guidance',
            'Connect with verified professionals across wellbeing, career, health, and more.',
            'All-in-One Growth Platform',
            'Tools, experts, and insights together — so progress feels simple and connected.',
            'Supportive Community',
            'Learn, share, and grow with people who care about the same goals you do.',
            'Safe &amp; Private',
            'Your conversations and data stay protected with secure booking and privacy-first design.',
        ] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }

        $this->assertSame(4, substr_count($html, 'class="yukti-why-feature"'));
        $this->assertLessThan(strpos($html, 'All-in-One Growth Platform'), strpos($html, 'Expert-Led Guidance'));
        $this->assertLessThan(strpos($html, 'Safe &amp; Private'), strpos($html, 'Supportive Community'));
    }

    public function test_heading_follows_the_configured_brand_name(): void
    {
        config(['yuktione.name' => 'Acme']);

        $html = view('frontend.home.partials.platform-experience')->render();

        $this->assertStringContainsString('Why Choose <span>Acme?</span>', $html);
        $this->assertStringContainsString('Why Acme', $html);
    }

    public function test_assets_exist_on_disk(): void
    {
        $this->assertFileExists(public_path('frontend/assets/img/Glossy Pink Lotus Flower.png'));
        $this->assertFileExists(public_path('frontend/assets/css/yuktione-why-choose.css'));
    }
}
