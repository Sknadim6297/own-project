<?php

namespace Tests\Unit;

use App\Services\WaterIntakeService;
use App\Support\WaterIntake\WaterIntakeCatalog;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class WaterIntakeServiceTest extends TestCase
{
    public function test_amount_bounds_and_integer_ml(): void
    {
        $service = new WaterIntakeService;
        $this->assertSame(250, $service->normalizeAmountMl(250));
        $this->assertSame(250, $service->normalizeAmountMl('250'));

        $this->expectException(InvalidArgumentException::class);
        $service->normalizeAmountMl(0);
    }

    public function test_rejects_fractional_and_excessive_amounts(): void
    {
        $service = new WaterIntakeService;
        try {
            $service->normalizeAmountMl(12.5);
            $this->fail('Expected exception for fractional ml');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }

        $this->expectException(InvalidArgumentException::class);
        $service->normalizeAmountMl(WaterIntakeCatalog::ENTRY_MAX_ML + 1);
    }

    public function test_ml_and_litre_display(): void
    {
        $service = new WaterIntakeService;
        $this->assertSame('1,500 ml', $service->formatAmount(1500, 'ml'));
        $this->assertSame('1.5 L', $service->formatAmount(1500, 'L'));
        $this->assertSame('2 L', $service->formatAmount(2000, 'L'));
    }

    public function test_day_bounds_respect_kolkata_midnight(): void
    {
        $service = new WaterIntakeService;
        [$start, $end] = $service->dayBounds('2026-11-03');

        $this->assertSame(
            '2026-11-02 18:30:00',
            $start->format('Y-m-d H:i:s')
        );
        $this->assertSame(
            '2026-11-03 18:30:00',
            $end->format('Y-m-d H:i:s')
        );

        // Instant just before Kolkata midnight belongs to previous local day.
        $beforeMidnight = CarbonImmutable::parse('2026-11-02 18:29:59', 'UTC');
        $this->assertTrue($beforeMidnight->lt($start));
        $atMidnight = CarbonImmutable::parse('2026-11-02 18:30:00', 'UTC');
        $this->assertTrue($atMidnight->equalTo($start) || $atMidnight->gte($start));
    }
}
