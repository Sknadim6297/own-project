<?php

namespace Tests\Unit;

use App\Services\WeightJourneyService;
use App\Support\WeightJourney\WeightJourneyCatalog;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class WeightJourneyServiceTest extends TestCase
{
    public function test_lb_kg_round_trip_uses_documented_factor(): void
    {
        $svc = new WeightJourneyService;
        $this->assertSame(WeightJourneyCatalog::LB_PER_KG, '2.2046226218');
        $kg = $svc->normalizeWeightKg('154.324', 'lb');
        $this->assertSame('70.000', $kg);
        $this->assertSame('154.32', $svc->kgToLb('70.000'));
    }

    public function test_rejects_invalid_weights(): void
    {
        $svc = new WeightJourneyService;
        $this->expectException(InvalidArgumentException::class);
        $svc->normalizeWeightKg(0, 'kg');
    }

    public function test_rejects_out_of_bounds(): void
    {
        $svc = new WeightJourneyService;
        $this->expectException(InvalidArgumentException::class);
        $svc->normalizeWeightKg('15', 'kg');
    }

    public function test_format_weight(): void
    {
        $svc = new WeightJourneyService;
        $this->assertSame('70 kg', $svc->formatWeight('70.000', 'kg'));
        $this->assertSame('154.32 lb', $svc->formatWeight('70.000', 'lb'));
    }
}
