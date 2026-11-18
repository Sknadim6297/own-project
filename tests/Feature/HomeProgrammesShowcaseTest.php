<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeProgrammesShowcaseTest extends TestCase
{
    public function test_static_programmes_showcase_renders_the_mockup_copy_and_links(): void
    {
        $html = view('frontend.home.partials.programmes-showcase')->render();

        $this->assertStringContainsString('yk-prog-show', $html);
        $this->assertStringContainsString('Programmes', $html);
        $this->assertStringContainsString('Structured guidance, practical learning and long-term transformation.', $html);
        $this->assertStringContainsString('View All Programmes', $html);
        $this->assertStringContainsString(route('programmes.index'), $html);

        foreach ([
            'Manage Anxiety &amp; Build Calm' => '4 Lessons',
            'Career Clarity &amp; Next Steps' => '6 Lessons',
            'Healthy Habits, Lasting Change' => '5 Lessons',
        ] as $title => $lessons) {
            $this->assertStringContainsString($title, $html);
            $this->assertStringContainsString($lessons, $html);
        }

        $this->assertSame(3, substr_count($html, 'Self-paced'));
    }

    public function test_showcase_images_exist_on_disk(): void
    {
        foreach (['programme-calm.jpg', 'programme-career.jpg', 'programme-habits.jpg'] as $file) {
            $this->assertFileExists(public_path('frontend/assets/img/programmes/'.$file));
        }

        $this->assertFileExists(public_path('frontend/assets/css/yuktione-programmes-showcase.css'));
    }
}
