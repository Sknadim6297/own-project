<?php

namespace Tests\Unit;

use App\Services\CareerInterestRecommender;
use App\Services\CareerInterestScorer;
use App\Support\CareerInterest\CareerInterestCatalog;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CareerInterestScorerTest extends TestCase
{
    public function test_scoring_convention_uses_sum_over_fifteen(): void
    {
        $answers = $this->baseAnswers([
            'i1' => 5, 'i2' => 5, 'i3' => 4, // 14 → 93%
            'e1' => 5, 'e2' => 5, 'e3' => 4, // 14 → 93%
            's1' => 4, 's2' => 4, 's3' => 3, // 11 → 73%
            'c1' => 4, 'c2' => 3, 'c3' => 4, // 11 → 73%
            'a1' => 3, 'a2' => 4, 'a3' => 3, // 10 → 67%
            'r1' => 2, 'r2' => 3, 'r3' => 2, // 7 → 47%
        ]);

        $scored = (new CareerInterestScorer)->score($answers);

        $this->assertSame(CareerInterestCatalog::SCORING_VERSION, $scored['scoring_version']);
        $this->assertSame(93, $scored['percentages']['investigative']);
        $this->assertSame(93, $scored['percentages']['enterprising']);
        $this->assertSame(73, $scored['percentages']['social']);
        $this->assertSame(73, $scored['percentages']['conventional']);
        $this->assertSame(67, $scored['percentages']['artistic']);
        $this->assertSame(47, $scored['percentages']['realistic']);
        $this->assertSame(['investigative', 'enterprising'], $scored['top']);
        $this->assertStringContainsString('investigative and enterprising', $scored['interpretation']);
        $this->assertStringContainsString('not measures of aptitude', $scored['interpretation']);
    }

    public function test_recommender_returns_deterministic_top_matches(): void
    {
        $answers = $this->baseAnswers([
            'i1' => 5, 'i2' => 5, 'i3' => 4,
            'e1' => 5, 'e2' => 5, 'e3' => 4,
            's1' => 4, 's2' => 4, 's3' => 3,
            'c1' => 4, 'c2' => 3, 'c3' => 4,
            'a1' => 3, 'a2' => 4, 'a3' => 3,
            'r1' => 2, 'r2' => 3, 'r3' => 2,
        ]);
        $scored = (new CareerInterestScorer)->score($answers);
        $first = (new CareerInterestRecommender)->recommend($scored, [
            'age_group' => '19-24',
            'education' => 'undergraduate',
            'situation' => 'student',
            'geography' => 'india',
        ]);
        $second = (new CareerInterestRecommender)->recommend($scored, [
            'age_group' => '19-24',
            'education' => 'undergraduate',
            'situation' => 'student',
            'geography' => 'india',
        ]);

        $this->assertSame($first, $second);
        $this->assertNotEmpty($first['items']);
        $this->assertLessThanOrEqual(5, count($first['items']));
        $titles = array_column($first['items'], 'title');
        $this->assertContains('Financial Analysis', $titles);
        $this->assertContains('Data & Business Analytics', $titles);
    }

    public function test_missing_answer_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CareerInterestScorer)->score(['r1' => 3]);
    }

    public function test_catalog_has_eighteen_exact_questions(): void
    {
        $questions = CareerInterestCatalog::questions();
        $this->assertCount(18, $questions);
        $this->assertSame(
            'I enjoy building, assembling or creating things using my hands.',
            $questions['r1']['text']
        );
        $this->assertSame(
            'I prefer activities that involve planning, accuracy, clear procedures and attention to detail.',
            $questions['c3']['text']
        );
        $this->assertSame([
            1 => 'Not at all',
            2 => 'A little',
            3 => 'Somewhat',
            4 => 'Very much',
            5 => 'Love it',
        ], CareerInterestCatalog::SCALE);
        $this->assertSame([
            1 => '😣',
            2 => '🙁',
            3 => '😐',
            4 => '😊',
            5 => '🤩',
        ], CareerInterestCatalog::SCALE_EMOJI);
        $this->assertSame(1, CareerInterestCatalog::scaleOptions()[1]['value']);
        $this->assertSame('Not at all', CareerInterestCatalog::scaleOptions()[1]['label']);
        $this->assertSame('😣', CareerInterestCatalog::scaleOptions()[1]['emoji']);
        $this->assertSame(18, CareerInterestCatalog::questionCount());
    }

    private function baseAnswers(array $overrides): array
    {
        $answers = [];
        foreach (CareerInterestCatalog::questionIds() as $id) {
            $answers[$id] = 3;
        }

        return array_merge($answers, $overrides);
    }
}
