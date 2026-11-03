<?php

namespace Tests\Unit;

use App\Models\NutritionFood;
use App\Models\NutritionFoodServing;
use App\Services\NutritionPortionCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class NutritionPortionCalculatorTest extends TestCase
{
    public function test_per_100g_scales_with_bcmath_and_keeps_nulls(): void
    {
        $food = new NutritionFood([
            'reference_basis' => 'per_100g',
            'calories_kcal' => '165.0000',
            'protein_g' => '31.0000',
            'carbs_g' => null,
            'fat_g' => '3.6000',
            'fibre_g' => null,
        ]);
        $food->setRelation('servings', collect());

        $result = (new NutritionPortionCalculator)->estimate($food, '50', 'g');

        $this->assertSame('50.0000', $result['portion_grams']);
        $this->assertSame('82.5000', $result['calories_kcal']);
        $this->assertSame('15.5000', $result['protein_g']);
        $this->assertNull($result['carbs_g']);
        $this->assertSame('1.8000', $result['fat_g']);
        $this->assertNull($result['fibre_g']);
        $this->assertTrue($result['incomplete']);
        $this->assertContains('carbohydrates', $result['missing']);
    }

    public function test_serving_with_grams_equivalent(): void
    {
        $food = new NutritionFood([
            'reference_basis' => 'per_100g',
            'calories_kcal' => '100',
            'protein_g' => '10',
            'carbs_g' => '20',
            'fat_g' => '5',
            'fibre_g' => '2',
        ]);
        $serving = new NutritionFoodServing([
            'label' => 'bowl',
            'quantity' => '1',
            'unit' => 'bowl',
            'grams_equivalent' => '150',
        ]);
        $serving->id = 7;
        $food->setRelation('servings', collect([$serving]));

        $result = (new NutritionPortionCalculator)->estimate($food, '1', 'bowl', 7);

        $this->assertSame('150.0000', $result['portion_grams']);
        $this->assertSame('150.0000', $result['calories_kcal']);
        $this->assertFalse($result['incomplete']);
    }

    public function test_rejects_unsupported_unit_and_invented_equivalents(): void
    {
        $food = new NutritionFood([
            'reference_basis' => 'per_100g',
            'calories_kcal' => '100',
            'protein_g' => '10',
            'carbs_g' => '20',
            'fat_g' => '5',
        ]);
        $serving = new NutritionFoodServing([
            'label' => 'piece',
            'quantity' => '1',
            'unit' => 'piece',
            'grams_equivalent' => null,
        ]);
        $serving->id = 1;
        $food->setRelation('servings', collect([$serving]));

        $this->expectException(InvalidArgumentException::class);
        (new NutritionPortionCalculator)->estimate($food, '1', 'cup');
    }

    public function test_per_serving_basis_multiplier(): void
    {
        $food = new NutritionFood([
            'reference_basis' => 'per_serving',
            'calories_kcal' => '250',
            'protein_g' => '12',
            'carbs_g' => '30',
            'fat_g' => '8',
            'fibre_g' => '4',
        ]);
        $serving = new NutritionFoodServing([
            'label' => 'pack',
            'quantity' => '1',
            'unit' => 'pack',
            'grams_equivalent' => '40',
        ]);
        $serving->id = 3;
        $food->setRelation('servings', collect([$serving]));

        $result = (new NutritionPortionCalculator)->estimate($food, '2', 'pack', 3);

        $this->assertSame('80.0000', $result['portion_grams']);
        $this->assertSame('500.0000', $result['calories_kcal']);
        $this->assertSame('24.0000', $result['protein_g']);
    }

    public function test_per_100ml_scales_and_rejects_mass_without_density(): void
    {
        $food = new NutritionFood([
            'reference_basis' => 'per_100ml',
            'calories_kcal' => '58',
            'protein_g' => '3.1',
            'carbs_g' => '4.7',
            'fat_g' => '3',
        ]);
        $food->setRelation('servings', collect());

        $result = (new NutritionPortionCalculator)->estimate($food, '200', 'ml');
        $this->assertSame('116.0000', $result['calories_kcal']);

        $this->expectException(InvalidArgumentException::class);
        (new NutritionPortionCalculator)->estimate($food, '200', 'g');
    }
}

