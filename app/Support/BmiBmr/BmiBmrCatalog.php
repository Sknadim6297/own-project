<?php

namespace App\Support\BmiBmr;

class BmiBmrCatalog
{
    public const FORMULA_VERSION = 'bmi-who-adult-v1+mifflin-st-jeor-v1';

    public const ADULT_MIN_AGE = 18;

    public const AGE_MIN = 1;

    public const AGE_MAX = 120;

    public const HEIGHT_CM_MIN = 50.0;

    public const HEIGHT_CM_MAX = 250.0;

    public const WEIGHT_KG_MIN = 10.0;

    public const WEIGHT_KG_MAX = 400.0;

    public const SEX_MALE = 'male';

    public const SEX_FEMALE = 'female';

    public const SEX_UNSPECIFIED = 'unspecified';

    /**
     * Activity multipliers (Harris–Benedict style factors). Rough estimates only.
     */
    public const ACTIVITIES = [
        'sedentary' => [
            'key' => 'sedentary',
            'label' => 'Sedentary',
            'description' => 'Little or no exercise',
            'factor' => 1.2,
            'emoji' => '🪑',
        ],
        'lightly-active' => [
            'key' => 'lightly-active',
            'label' => 'Lightly active',
            'description' => 'Light exercise 1–3 days/week',
            'factor' => 1.375,
            'emoji' => '🚶',
        ],
        'moderately-active' => [
            'key' => 'moderately-active',
            'label' => 'Moderately active',
            'description' => 'Moderate exercise 3–5 days/week',
            'factor' => 1.55,
            'emoji' => '🚴',
        ],
        'very-active' => [
            'key' => 'very-active',
            'label' => 'Very active',
            'description' => 'Hard exercise 6–7 days/week',
            'factor' => 1.725,
            'emoji' => '🏃',
        ],
        'extra-active' => [
            'key' => 'extra-active',
            'label' => 'Extra active',
            'description' => 'Very hard exercise & physical job',
            'factor' => 1.9,
            'emoji' => '🏋️',
        ],
    ];

    /** Concise educational blurbs for result cards (not medical advice). */
    public const RESULT_BLURBS = [
        'bmi' => 'Body Mass Index compares weight to height. It is a population screening ratio—not a diagnosis of health or body fat.',
        'bmr' => 'Basal Metabolic Rate estimates calories your body may use at rest. Mifflin–St Jeor is an equation estimate, not a lab measurement.',
        'tdee' => 'Total Daily Energy Expenditure multiplies BMR by an activity factor to approximate daily calorie needs. Factors are rough guides only.',
    ];

    /** Adult BMI bands — classify with unrounded BMI; round only for display. */
    public const BMI_CATEGORIES = [
        [
            'key' => 'underweight',
            'label' => 'Underweight',
            'min' => null,
            'max_exclusive' => 18.5,
        ],
        [
            'key' => 'healthy',
            'label' => 'Healthy weight',
            'min' => 18.5,
            'max_exclusive' => 25.0,
        ],
        [
            'key' => 'overweight',
            'label' => 'Overweight',
            'min' => 25.0,
            'max_exclusive' => 30.0,
        ],
        [
            'key' => 'obesity',
            'label' => 'Obesity',
            'min' => 30.0,
            'max_exclusive' => null,
        ],
    ];

    public static function activityKeys(): array
    {
        return array_keys(self::ACTIVITIES);
    }

    public static function sexKeys(): array
    {
        return [self::SEX_MALE, self::SEX_FEMALE, self::SEX_UNSPECIFIED];
    }
}
