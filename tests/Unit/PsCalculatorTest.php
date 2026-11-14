<?php

namespace Tests\Unit;

use App\Services\Ps\PsCalculator;
use Tests\TestCase;

class PsCalculatorTest extends TestCase
{
    public function test_stress_check_scoring_and_levels(): void
    {
        $calculator = new PsCalculator();

        // All 1s: reverse Q5/Q6 become 5 → total = 6*1 + 2*5 = 16 → ((16-8)/(40-8))*100 = 25 → Low
        $low = $calculator->calculate('parenting-stress-check', [
            'q1' => 1, 'q2' => 1, 'q3' => 1, 'q4' => 1,
            'q5' => 1, 'q6' => 1, 'q7' => 1, 'q8' => 1,
        ]);
        $this->assertSame(25, $low['result']['score']);
        $this->assertSame('Low', $low['result']['level']);

        // All 5s: reverse Q5/Q6 become 1 → total = 6*5 + 2*1 = 32 → ((32-8)/32)*100 = 75 → High
        $high = $calculator->calculate('parenting-stress-check', [
            'q1' => 5, 'q2' => 5, 'q3' => 5, 'q4' => 5,
            'q5' => 5, 'q6' => 5, 'q7' => 5, 'q8' => 5,
        ]);
        $this->assertSame(75, $high['result']['score']);
        $this->assertSame('High', $high['result']['level']);
        $this->assertNotEmpty($high['result']['pressure_areas']);
        $this->assertStringContainsString('not a diagnosis', strtolower($high['result']['disclaimer']));
    }

    public function test_conversation_guide_screen_time_content(): void
    {
        $calculator = new PsCalculator();
        $payload = $calculator->calculate('parent-child-conversation-guide', [
            'situation' => 'screen_time',
            'age_band' => '8-12',
            'goal' => 'boundary',
            'context' => 'Child refused to stop playing a game after dinner.',
        ]);

        $this->assertSame('Screen time', $payload['result']['situation_label']);
        $this->assertStringContainsString('device', $payload['result']['opening']);
        $this->assertStringContainsString("You're addicted", $payload['result']['avoid']);
    }

    public function test_conversation_guide_flags_urgent_support(): void
    {
        $calculator = new PsCalculator();
        $payload = $calculator->calculate('parent-child-conversation-guide', [
            'situation' => 'anger',
            'age_band' => '8-12',
            'goal' => 'boundary',
            'context' => 'I am worried about self-harm language after the argument.',
        ]);

        $this->assertTrue($payload['result']['urgent_support']);
    }

    public function test_values_finder_top_values_and_tie_break(): void
    {
        $calculator = new PsCalculator();
        $input = [];
        // Pick option A for every pair.
        foreach (range(1, 16) as $q) {
            $pairs = \App\Support\ParentingSpirituality\PsContent::valuePairs();
            $pair = $pairs[$q - 1];
            $input['q'.$q] = $pair['a'];
        }
        $input['ranking'] = ['Peace', 'Freedom'];

        $payload = $calculator->calculate('purpose-values-finder', $input);
        $this->assertCount(5, $payload['result']['top_values']);
        $this->assertContains('Freedom', $payload['result']['top_values']);
        $this->assertNotEmpty($payload['result']['themes']);
        $this->assertNotEmpty($payload['result']['actions']);
    }

    public function test_routine_planner_places_school_and_detects_conflict(): void
    {
        $calculator = new PsCalculator();
        $payload = $calculator->calculate('child-routine-planner', [
            'child_age' => 10,
            'wake_time' => '07:00',
            'bed_time' => '21:00',
            'school_start' => '08:30',
            'school_end' => '14:30',
            'study_minutes' => 240,
            'play_minutes' => 180,
            'screen_minutes' => 120,
            'family_minutes' => 90,
            'commute_minutes' => 20,
        ]);

        $this->assertTrue($payload['result']['conflict']);
        $labels = array_column($payload['result']['blocks'], 'label');
        $this->assertContains('School / daycare', $labels);
    }

    public function test_mindfulness_maps_stressed_practice(): void
    {
        $calculator = new PsCalculator();
        $payload = $calculator->calculate('daily-mindfulness-calm', [
            'state' => 'stressed',
            'duration' => 3,
            'practice' => 'Breathing',
        ]);

        $this->assertSame('Breathing', $payload['result']['practice']);
        $this->assertStringContainsString('shoulders drop', $payload['result']['script']);
        $this->assertSame(180, $payload['result']['target_seconds']);
    }
}
