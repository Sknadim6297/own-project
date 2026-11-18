<?php

namespace Tests\Unit;

use App\Support\ExpertDecoration;
use PHPUnit\Framework\TestCase;

class ExpertDecorationTest extends TestCase
{
    public function test_role_text_selects_matching_illustration(): void
    {
        $this->assertSame('astrology-moon', ExpertDecoration::resolve('Astrologers')['asset']);
        $this->assertSame('tarot-cards', ExpertDecoration::resolve('Tarot Reader')['asset']);
        $this->assertSame('psychology-brain', ExpertDecoration::resolve('Psychologists')['asset']);
        $this->assertSame('nutrition-apple', ExpertDecoration::resolve('Nutritionists')['asset']);
        $this->assertSame('fitness-dumbbell', ExpertDecoration::resolve('Physical Trainers')['asset']);
        $this->assertSame('wellness-sprout', ExpertDecoration::resolve('Influencers')['asset']);
        $this->assertSame('career-target', ExpertDecoration::resolve('Resume & Interview Coach')['asset']);
        $this->assertSame('career-growth', ExpertDecoration::resolve('Career Counsellor')['asset']);
        $this->assertSame('finance-coins', ExpertDecoration::resolve('Financial Advisor')['asset']);
    }

    public function test_falls_back_to_services_then_category_icon_then_neutral(): void
    {
        $this->assertSame('finance-coins', ExpertDecoration::resolve(null, ['Tax Planning'])['asset']);
        $this->assertSame('wellness-sprout', ExpertDecoration::resolve('Mentor', [], 'leaf')['asset']);
        $this->assertSame(ExpertDecoration::FALLBACK, ExpertDecoration::resolve('Mentor')['asset']);
    }

    public function test_assign_gives_each_card_a_unique_illustration(): void
    {
        $cards = [];
        foreach ([1 => 'Astrologers', 2 => 'Astrologers', 3 => 'Astrologers', 4 => 'Influencers', 5 => 'Influencers',
            6 => 'Psychologists', 7 => 'Psychologists', 8 => 'Mentor', 9 => 'Mentor'] as $id => $role) {
            $cards[] = ['id' => $id, 'role' => $role, 'topics' => [], 'chips' => [], 'icon' => 'spark'];
        }

        $map = ExpertDecoration::assign($cards);

        $this->assertCount(9, $map);
        $this->assertCount(9, array_unique(array_column($map, 'asset')));
        $this->assertSame('astrology-moon', $map[1]['asset']);
        $this->assertSame('psychology-brain', $map[6]['asset']);
    }

    public function test_every_resolvable_asset_exists_on_disk(): void
    {
        foreach (['wellness-sprout', 'career-growth', 'career-rocket', 'career-target', 'astrology-moon', 'tarot-cards',
            'psychology-brain', 'mental-health-heart', 'relationship-hearts', 'nutrition-apple', 'fitness-dumbbell',
            'business-briefcase', 'finance-coins', 'spirituality-lotus', 'meditation-stones', 'education-cap',
            'parenting-family', 'technology-laptop', 'productivity-bars', 'default-spark'] as $asset) {
            $this->assertFileExists(dirname(__DIR__, 2).'/public/assets/experts/decorations/'.$asset.'.svg');
        }
    }
}
