<?php

namespace Tests\Unit;

use App\Services\Pfm\PfmCalculator;
use App\Support\Pfm\GoalProjection;
use PHPUnit\Framework\TestCase;

class GoalProjectionTest extends TestCase
{
    private function plan(array $input): array
    {
        return (new PfmCalculator())->calculate('financial-goal-calculator', $input);
    }

    public function test_projection_ends_exactly_at_the_calculator_results(): void
    {
        $payload = $this->plan([
            'goal_type' => 'home',
            'current_cost' => 2500000,
            'years' => 10,
            'inflation_percent' => 6,
            'expected_return_percent' => 10,
            'existing_savings' => 300000,
        ]);

        $projection = GoalProjection::build($payload['normalized'], $payload['result']);

        $this->assertNotNull($projection);
        $this->assertSame(120, $projection['months']);
        $last = end($projection['points']);
        $this->assertSame(120, $last['month']);
        $this->assertEqualsWithDelta($payload['result']['required_corpus'], $last['cost'], 0.01);
        $this->assertEqualsWithDelta(
            $payload['result']['future_value_existing'] + $payload['result']['funding_gap'],
            $last['savings'],
            0.01
        );
        $this->assertSame(0, $projection['points'][0]['month']);
        $this->assertEqualsWithDelta(300000.0, $projection['points'][0]['savings'], 0.01);
        $this->assertEqualsWithDelta(2500000.0, $projection['points'][0]['cost'], 0.01);
    }

    public function test_savings_line_rises_and_stays_close_to_the_final_figure(): void
    {
        $payload = $this->plan([
            'goal_type' => 'car',
            'current_cost' => 800000,
            'years' => 5,
            'inflation_percent' => 5,
            'expected_return_percent' => 8,
            'existing_savings' => 0,
        ]);

        $projection = GoalProjection::build($payload['normalized'], $payload['result']);
        $points = $projection['points'];

        for ($i = 1; $i < count($points); $i++) {
            $this->assertGreaterThanOrEqual($points[$i - 1]['savings'], $points[$i]['savings']);
        }

        // The penultimate month should already be near the end value (no jump from the snap).
        $previous = $points[count($points) - 2];
        $last = end($points);
        $this->assertLessThan($last['savings'] * 0.1, $last['savings'] - $previous['savings']);
    }

    public function test_zero_return_and_long_horizons_stay_within_the_point_budget(): void
    {
        $payload = $this->plan([
            'goal_type' => 'custom',
            'current_cost' => 120000,
            'years' => 40,
            'inflation_percent' => 0,
            'expected_return_percent' => 0,
            'existing_savings' => 0,
        ]);

        $projection = GoalProjection::build($payload['normalized'], $payload['result'], 48);

        $this->assertLessThanOrEqual(50, count($projection['points']));
        $this->assertEqualsWithDelta(120000.0, end($projection['points'])['savings'], 0.01);
    }

    public function test_fully_funded_goal_reports_no_extra_savings_needed(): void
    {
        $payload = $this->plan([
            'goal_type' => 'vacation',
            'current_cost' => 100000,
            'years' => 3,
            'inflation_percent' => 5,
            'expected_return_percent' => 12,
            'existing_savings' => 500000,
        ]);

        $this->assertSame(0.0, $payload['result']['funding_gap']);
        $projection = GoalProjection::build($payload['normalized'], $payload['result']);
        $last = end($projection['points']);

        $this->assertEqualsWithDelta($payload['result']['future_value_existing'], $last['savings'], 0.01);
        $this->assertGreaterThan($last['cost'], $last['savings']);
    }

    public function test_no_projection_without_a_timeline(): void
    {
        $this->assertNull(GoalProjection::build(['years' => 0], []));
    }
}
