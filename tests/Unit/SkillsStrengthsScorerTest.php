<?php

namespace Tests\Unit;

use App\Services\SkillsStrengthsScorer;
use App\Support\SkillsStrengths\SkillsStrengthsCatalog;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SkillsStrengthsScorerTest extends TestCase
{
    public function test_catalog_has_twenty_four_exact_questions_and_eight_categories(): void
    {
        $questions = SkillsStrengthsCatalog::questions();
        $this->assertCount(24, $questions);
        $this->assertCount(8, SkillsStrengthsCatalog::CATEGORY_ORDER);
        $this->assertSame('I can clearly express my ideas and opinions.', $questions['c1']['text']);
        $this->assertSame('I feel confident presenting an idea and explaining its benefits.', $questions['b3']['text']);
        $this->assertSame([
            1 => 'Strongly Disagree',
            2 => 'Disagree',
            3 => 'Neutral',
            4 => 'Agree',
            5 => 'Strongly Agree',
        ], SkillsStrengthsCatalog::SCALE);

        foreach (SkillsStrengthsCatalog::CATEGORY_ORDER as $category) {
            $this->assertCount(3, SkillsStrengthsCatalog::questionIdsFor($category), $category);
        }
    }

    public function test_scoring_boundaries_and_convention(): void
    {
        $min = $this->fill(1);
        $max = $this->fill(5);
        $scorer = new SkillsStrengthsScorer;

        $low = $scorer->score($min);
        $high = $scorer->score($max);

        foreach (SkillsStrengthsCatalog::CATEGORY_ORDER as $category) {
            $this->assertSame(0, $low['percentages'][$category]);
            $this->assertSame(100, $high['percentages'][$category]);
        }
        $this->assertSame(SkillsStrengthsCatalog::SCORING_VERSION, $low['scoring_version']);
    }

    public function test_discrete_percentage_example_and_ties(): void
    {
        $answers = $this->fill(3);
        // communication 15 → 100%, creativity 14 → 92%, problem-solving 13 → 83%
        foreach (['c1', 'c2', 'c3'] as $id) {
            $answers[$id] = 5;
        }
        foreach (['cr1', 'cr2', 'cr3'] as $id) {
            $answers[$id] = 5;
        }
        $answers['cr3'] = 4; // 14
        foreach (['p1', 'p2', 'p3'] as $id) {
            $answers[$id] = 5;
        }
        $answers['p3'] = 3; // 13
        // leadership and business lowest tied at sum 6 → 25%
        foreach (['l1', 'l2', 'l3', 'b1', 'b2', 'b3'] as $id) {
            $answers[$id] = 2;
        }

        $scored = (new SkillsStrengthsScorer)->score($answers);

        $this->assertSame(100, $scored['percentages']['communication']);
        $this->assertSame(92, $scored['percentages']['creativity']);
        $this->assertSame(83, $scored['percentages']['problem-solving']);
        $this->assertSame(25, $scored['percentages']['leadership']);
        $this->assertSame(25, $scored['percentages']['business']);

        // PDF-style dashboard: up to three strongest areas (ties at the cut-off expand).
        $strengthKeys = array_column($scored['strengths'], 'key');
        $this->assertSame(['communication', 'creativity', 'problem-solving'], $strengthKeys);
        $developKeys = array_column($scored['develop'], 'key');
        $this->assertContains('leadership', $developKeys);
        $this->assertContains('business', $developKeys);
        $this->assertCount(2, $developKeys);
        $this->assertStringContainsString('communication', strtolower($scored['summary']));
        $this->assertStringContainsString('self-ratings', strtolower($scored['meaning']));
    }

    public function test_missing_and_invalid_answers_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SkillsStrengthsScorer)->score(['c1' => 3]);
    }

    public function test_invalid_scale_rejected(): void
    {
        $answers = $this->fill(3);
        $answers['c1'] = 9;
        $this->expectException(InvalidArgumentException::class);
        (new SkillsStrengthsScorer)->score($answers);
    }

    private function fill(int $value): array
    {
        $answers = [];
        foreach (SkillsStrengthsCatalog::questionIds() as $id) {
            $answers[$id] = $value;
        }

        return $answers;
    }
}
