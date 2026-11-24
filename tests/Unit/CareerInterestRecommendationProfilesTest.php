<?php

namespace Tests\Unit;

use App\Services\CareerInterestRecommender;
use App\Services\CareerInterestScorer;
use App\Support\CareerInterest\CareerInterestCatalog;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Task 21 — distinct RIASEC profiles must yield distinct, deterministic recommendations.
 */
class CareerInterestRecommendationProfilesTest extends TestCase
{
    public function test_technical_investigative_profile_ranks_analytics_and_research(): void
    {
        $scored = $this->scoreProfile('investigative');
        $titles = $this->titles($scored);

        $this->assertSame(100, $scored['percentages']['investigative']);
        $this->assertSame(['investigative'], $scored['top']);
        $this->assertContains('Research & Applied Science', $titles);
        $this->assertTrue(
            in_array('Data & Business Analytics', $titles, true)
            || in_array('Financial Analysis', $titles, true)
            || in_array('Engineering & Applied Technology', $titles, true)
        );
        $this->assertNotContains('Creative Media & Design', $titles);
        $this->assertNotContains('Education & Counselling', $titles);
    }

    public function test_creative_artistic_profile_ranks_creative_media_first(): void
    {
        $scored = $this->scoreProfile('artistic');
        $items = $this->recommend($scored)['items'];

        $this->assertSame(100, $scored['percentages']['artistic']);
        $this->assertSame('Creative Media & Design', $items[0]['title']);
        $this->assertStringContainsString('Artistic', $items[0]['why']);
        $this->assertNotContains('Financial Analysis', array_column($items, 'title'));
        $this->assertNotContains('Operations & Administration', array_column($items, 'title'));
    }

    public function test_social_people_profile_ranks_education_and_healthcare(): void
    {
        $scored = $this->scoreProfile('social');
        $titles = $this->titles($scored);

        $this->assertSame('Education & Counselling', $titles[0]);
        $this->assertContains('Healthcare Support & Helping Roles', $titles);
        $this->assertNotContains('Financial Analysis', $titles);
        $this->assertNotContains('Skilled Trades & Technical Work', $titles);
    }

    public function test_business_leadership_profile_ranks_enterprising_careers(): void
    {
        $scored = $this->scoreProfile('enterprising');
        $titles = $this->titles($scored);

        $this->assertTrue(
            in_array($titles[0], ['Entrepreneurship', 'Business Management', 'Marketing & Communications', 'Management Consulting'], true)
        );
        $this->assertNotContains('Creative Media & Design', $titles);
        $this->assertNotContains('Education & Counselling', $titles);
    }

    public function test_practical_hands_on_profile_ranks_realistic_careers(): void
    {
        $scored = $this->scoreProfile('realistic');
        $titles = $this->titles($scored);

        $this->assertContains('Skilled Trades & Technical Work', $titles);
        $this->assertContains('Engineering & Applied Technology', $titles);
        $this->assertTrue(in_array($titles[0], [
            'Skilled Trades & Technical Work',
            'Engineering & Applied Technology',
            'Healthcare Support & Helping Roles',
        ], true));
        $this->assertNotContains('Creative Media & Design', $titles);
        $this->assertNotContains('Marketing & Communications', $titles);
    }

    public function test_administrative_conventional_profile_ranks_operations_and_finance(): void
    {
        $scored = $this->scoreProfile('conventional');
        $titles = $this->titles($scored);

        $this->assertContains('Operations & Administration', $titles);
        $this->assertTrue(
            in_array('Financial Analysis', $titles, true)
            || in_array('Business Management', $titles, true)
            || in_array('Data & Business Analytics', $titles, true)
        );
        $this->assertNotContains('Creative Media & Design', $titles);
        $this->assertNotContains('Education & Counselling', $titles);
    }

    public function test_balanced_profile_is_deterministic_and_capped(): void
    {
        $answers = $this->fillAll(3);
        $scored = (new CareerInterestScorer)->score($answers);
        $first = $this->recommend($scored);
        $second = $this->recommend($scored);

        $this->assertSame($first, $second);
        $this->assertSame([60, 60, 60, 60, 60, 60], array_values($scored['percentages']));
        $this->assertLessThanOrEqual(5, count($first['items']));
        $this->assertNotEmpty($first['items']);
        $this->assertSame(count($first['items']), count(array_unique(array_column($first['items'], 'id'))));
        $this->assertSame(CareerInterestRecommender::CATALOGUE_VERSION, $first['catalogue_version']);
    }

    public function test_tied_top_interests_break_deterministically(): void
    {
        $answers = $this->fillAll(2);
        $answers = array_merge($answers, [
            'a1' => 5, 'a2' => 5, 'a3' => 5,
            's1' => 5, 's2' => 5, 's3' => 5,
        ]);
        $scored = (new CareerInterestScorer)->score($answers);

        $this->assertSame([100, 100], [
            $scored['percentages']['artistic'],
            $scored['percentages']['social'],
        ]);
        $this->assertSame(['artistic', 'social'], $scored['top']);

        $titles = $this->titles($scored);
        $this->assertContains('Creative Media & Design', $titles);
        $this->assertContains('Education & Counselling', $titles);
        $this->assertSame($titles, $this->titles($scored));
    }

    public function test_minimum_and_maximum_answer_scores(): void
    {
        $min = (new CareerInterestScorer)->score($this->fillAll(1));
        $max = (new CareerInterestScorer)->score($this->fillAll(5));

        foreach (CareerInterestCatalog::CATEGORY_ORDER as $category) {
            $this->assertSame(3, $min['totals'][$category]);
            $this->assertSame(20, $min['percentages'][$category]);
            $this->assertSame(15, $max['totals'][$category]);
            $this->assertSame(100, $max['percentages'][$category]);
        }

        $this->assertSame([], $this->recommend($min)['items']);
        $this->assertNotEmpty($this->recommend($max)['items']);
    }

    public function test_invalid_and_incomplete_answers_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CareerInterestScorer)->score(['r1' => 5, 'r2' => 5]);
    }

    public function test_invalid_answer_value_is_rejected(): void
    {
        $answers = $this->fillAll(3);
        $answers['r1'] = 9;
        $this->expectException(InvalidArgumentException::class);
        (new CareerInterestScorer)->score($answers);
    }

    public function test_identical_answers_produce_identical_results(): void
    {
        $answers = $this->dominantAnswers('investigative');
        $a = $this->recommend((new CareerInterestScorer)->score($answers));
        $b = $this->recommend((new CareerInterestScorer)->score($answers));
        $this->assertSame($a, $b);
    }

    public function test_meaningfully_different_profiles_produce_different_top_recommendations(): void
    {
        $sets = [];
        foreach (['investigative', 'artistic', 'social', 'enterprising', 'realistic', 'conventional'] as $dimension) {
            $sets[$dimension] = $this->titles($this->scoreProfile($dimension));
        }

        $this->assertNotSame($sets['artistic'][0], $sets['social'][0]);
        $this->assertNotSame($sets['artistic'][0], $sets['investigative'][0]);
        $this->assertNotSame($sets['realistic'][0], $sets['enterprising'][0]);
        $this->assertNotSame($sets['conventional'][0], $sets['artistic'][0]);
        $this->assertNotSame($sets['social'], $sets['investigative']);
    }

    public function test_changing_one_dimension_answers_changes_only_that_score(): void
    {
        $base = $this->fillAll(3);
        $changed = $base;
        $changed['a1'] = 5;
        $changed['a2'] = 5;
        $changed['a3'] = 5;

        $baseScored = (new CareerInterestScorer)->score($base);
        $changedScored = (new CareerInterestScorer)->score($changed);

        $this->assertSame(60, $baseScored['percentages']['artistic']);
        $this->assertSame(100, $changedScored['percentages']['artistic']);
        foreach (['realistic', 'investigative', 'social', 'enterprising', 'conventional'] as $category) {
            $this->assertSame(
                $baseScored['percentages'][$category],
                $changedScored['percentages'][$category]
            );
        }
    }

    public function test_explanations_and_fit_percent_are_grounded_in_scores(): void
    {
        $scored = $this->scoreProfile('artistic');
        $item = $this->recommend($scored)['items'][0];

        $this->assertSame('Creative Media & Design', $item['title']);
        $this->assertSame(100, $item['fit_percent']);
        $this->assertStringContainsString('Artistic (100%)', $item['why']);
        $this->assertStringContainsString('not a guaranteed fit', $item['why']);
    }

    public function test_catalogue_covers_each_riasec_dimension_as_primary(): void
    {
        $covered = [];
        foreach (CareerInterestCatalog::careers() as $career) {
            foreach ($career['primary'] as $category) {
                $covered[$category] = true;
            }
        }
        foreach (CareerInterestCatalog::CATEGORY_ORDER as $category) {
            $this->assertArrayHasKey($category, $covered);
        }
        $this->assertGreaterThanOrEqual(12, count(CareerInterestCatalog::careers()));
    }

    /**
     * @return array<string, mixed>
     */
    private function scoreProfile(string $dominant): array
    {
        return (new CareerInterestScorer)->score($this->dominantAnswers($dominant));
    }

    /**
     * @return array<string, int>
     */
    private function dominantAnswers(string $dominant): array
    {
        $answers = $this->fillAll(2);
        foreach (CareerInterestCatalog::questionIdsFor($dominant) as $id) {
            $answers[$id] = 5;
        }

        return $answers;
    }

    /**
     * @return array<string, int>
     */
    private function fillAll(int $value): array
    {
        $answers = [];
        foreach (CareerInterestCatalog::questionIds() as $id) {
            $answers[$id] = $value;
        }

        return $answers;
    }

    /**
     * @param  array<string, mixed>  $scored
     * @return array{catalogue_version:string,disclaimer:string,items:list<array<string,mixed>>}
     */
    private function recommend(array $scored): array
    {
        return (new CareerInterestRecommender)->recommend($scored, [
            'age_group' => '19-24',
            'education' => 'undergraduate',
            'situation' => 'student',
            'geography' => 'india',
        ]);
    }

    /**
     * @param  array<string, mixed>  $scored
     * @return list<string>
     */
    private function titles(array $scored): array
    {
        return array_values(array_column($this->recommend($scored)['items'], 'title'));
    }
}
