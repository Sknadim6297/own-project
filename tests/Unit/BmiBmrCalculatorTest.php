<?php

namespace Tests\Unit;

use App\Services\BmiBmrCalculator;
use App\Services\BmiBmrInputNormalizer;
use App\Support\BmiBmr\BmiBmrCatalog;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BmiBmrCalculatorTest extends TestCase
{
    public function test_bmi_and_mifflin_male_fixture(): void
    {
        $result = (new BmiBmrCalculator)->calculate([
            'age' => 30,
            'height_cm' => 180,
            'weight_kg' => 80,
            'sex' => 'male',
            'activity' => 'sedentary',
        ]);

        $this->assertEqualsWithDelta(24.691358, $result['bmi']['exact'], 0.00001);
        $this->assertSame(24.7, $result['bmi']['display']);
        $this->assertSame('healthy', $result['bmi']['category']['key']);
        $this->assertSame(1780, $result['bmr']['display']);
        $this->assertSame(2136, $result['tdee']['display']);
        $this->assertTrue($result['bmr']['eligible']);
    }

    public function test_mifflin_female_fixture(): void
    {
        $result = (new BmiBmrCalculator)->calculate([
            'age' => 25,
            'height_cm' => 165,
            'weight_kg' => 60,
            'sex' => 'female',
            'activity' => 'moderately-active',
        ]);

        $this->assertEqualsWithDelta(1345.25, $result['bmr']['exact'], 0.001);
        $this->assertSame(1345, $result['bmr']['display']);
        $this->assertSame((int) round(1345.25 * 1.55), $result['tdee']['display']);
    }

    public function test_category_boundaries_use_unrounded_bmi(): void
    {
        $calc = new BmiBmrCalculator;
        $this->assertSame('healthy', $calc->classifyBmi(18.5)['key']);
        $this->assertSame('underweight', $calc->classifyBmi(18.4999)['key']);
        $this->assertSame('healthy', $calc->classifyBmi(24.999)['key']);
        $this->assertSame('overweight', $calc->classifyBmi(25.0)['key']);
        $this->assertSame('overweight', $calc->classifyBmi(29.999)['key']);
        $this->assertSame('obesity', $calc->classifyBmi(30.0)['key']);
    }

    public function test_activity_multipliers(): void
    {
        $this->assertSame(1.2, BmiBmrCatalog::ACTIVITIES['sedentary']['factor']);
        $this->assertSame(1.375, BmiBmrCatalog::ACTIVITIES['lightly-active']['factor']);
        $this->assertSame(1.55, BmiBmrCatalog::ACTIVITIES['moderately-active']['factor']);
        $this->assertSame(1.725, BmiBmrCatalog::ACTIVITIES['very-active']['factor']);
        $this->assertSame(1.9, BmiBmrCatalog::ACTIVITIES['extra-active']['factor']);
    }

    public function test_unit_conversions(): void
    {
        $normalized = (new BmiBmrInputNormalizer)->normalize([
            'age' => 28,
            'height_unit' => 'ft_in',
            'height_ft' => 5,
            'height_in' => 10,
            'weight_unit' => 'lb',
            'weight_lb' => 200,
            'sex' => 'male',
            'activity' => 'sedentary',
        ]);

        $this->assertEqualsWithDelta(177.8, $normalized['height_cm'], 0.01);
        $this->assertEqualsWithDelta(90.718474, $normalized['weight_kg'], 0.0001);
    }

    public function test_rejects_zero_negative_and_nan(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new BmiBmrInputNormalizer)->normalize([
            'age' => 30,
            'height_unit' => 'cm',
            'height_cm' => 0,
            'weight_unit' => 'kg',
            'weight_kg' => 70,
            'sex' => 'female',
            'activity' => 'sedentary',
        ]);
    }

    public function test_minor_skips_adult_category_and_energy(): void
    {
        $result = (new BmiBmrCalculator)->calculate([
            'age' => 16,
            'height_cm' => 170,
            'weight_kg' => 60,
            'sex' => 'female',
            'activity' => 'sedentary',
        ]);

        $this->assertTrue($result['flags']['minor']);
        $this->assertNull($result['bmi']['category']);
        $this->assertFalse($result['bmr']['eligible']);
        $this->assertNull($result['guidance']['examples']);
        $this->assertNotNull($result['guidance']['minor_notice']);
    }

    public function test_excluded_population_and_unspecified_sex_skip_energy(): void
    {
        $excluded = (new BmiBmrCalculator)->calculate([
            'age' => 30,
            'height_cm' => 170,
            'weight_kg' => 65,
            'sex' => 'female',
            'activity' => 'sedentary',
            'excluded_population' => true,
        ]);
        $this->assertFalse($excluded['bmr']['eligible']);
        $this->assertNotNull($excluded['guidance']['excluded_notice']);

        $unspecified = (new BmiBmrCalculator)->calculate([
            'age' => 30,
            'height_cm' => 170,
            'weight_kg' => 65,
            'sex' => 'unspecified',
            'activity' => 'sedentary',
        ]);
        $this->assertFalse($unspecified['bmr']['eligible']);
        $this->assertTrue($unspecified['bmi']['is_adult']);
        $this->assertNotNull($unspecified['bmi']['category']);
    }

    public function test_reproducible_for_same_inputs(): void
    {
        $input = [
            'age' => 40,
            'height_cm' => 175.5,
            'weight_kg' => 72.3,
            'sex' => 'male',
            'activity' => 'very-active',
        ];
        $a = (new BmiBmrCalculator)->calculate($input);
        $b = (new BmiBmrCalculator)->calculate($input);
        $this->assertSame($a['bmi']['exact'], $b['bmi']['exact']);
        $this->assertSame($a['bmr']['exact'], $b['bmr']['exact']);
        $this->assertSame($a['tdee']['exact'], $b['tdee']['exact']);
        $this->assertSame(BmiBmrCatalog::FORMULA_VERSION, $a['formula_version']);
    }
}
