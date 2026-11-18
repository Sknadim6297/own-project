<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeJoinTestimonialsShowcaseTest extends TestCase
{
    public function test_join_as_professional_band_renders_the_mockup_copy_and_cta(): void
    {
        $html = view('frontend.home.partials.professional-showcase')->render();

        $this->assertStringContainsString('yk-pro__strip', $html);
        $this->assertStringContainsString('Join as <span>Professional</span>', $html);
        $this->assertStringContainsString('Join as a Professional', $html);
        $this->assertStringContainsString('Showcase your expertise, reach the right audience and grow your practice with YuktiOne.', $html);
        $this->assertStringContainsString('Create your professional profile', $html);
        $this->assertStringContainsString('Get client bookings and visibility', $html);
        $this->assertStringContainsString('Be part of a trusted expert network', $html);
        $this->assertStringContainsString('professional-ribbon-banner.png', $html);
        $this->assertStringContainsString(route('professional.register'), $html);
        $this->assertStringContainsString(route('professional.login'), $html);
    }

    public function test_testimonials_render_the_three_mockup_stories(): void
    {
        $html = view('frontend.home.partials.testimonials-showcase')->render();

        $this->assertStringContainsString('Testimonials', $html);
        $this->assertStringContainsString('Real stories from our growing community', $html);

        foreach ([
            'YuktiOne helped me manage my anxiety and build a healthier routine. I feel more balanced and in control.',
            'The career counselling session gave me the clarity and confidence to switch to a role I truly enjoy.',
            'A simple, trusted platform with great experts and practical tools. It has become my go-to for overall wellbeing.',
            'Ritika S.', 'Marketing Professional',
            'Arjun M.', 'Product Manager',
            'Neha P.', 'Entrepreneur',
        ] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }

        $this->assertSame(3, substr_count($html, 'class="yk-voice__stars"'));
        $this->assertSame(15, substr_count($html, '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m12 2.8'));
        $this->assertSame(3, substr_count($html, 'Rated 5 out of 5'));
    }

    public function test_assets_exist_on_disk(): void
    {
        foreach ([
            'frontend/assets/img/professional-ribbon-banner.png',
            'frontend/assets/img/testimonials-showcase/avatar-ritika.jpg',
            'frontend/assets/img/testimonials-showcase/avatar-arjun.jpg',
            'frontend/assets/img/testimonials-showcase/avatar-neha.jpg',
            'frontend/assets/css/yuktione-join-professional.css',
            'frontend/assets/css/yuktione-join-testimonials.css',
        ] as $path) {
            $this->assertFileExists(public_path($path));
        }
    }
}
