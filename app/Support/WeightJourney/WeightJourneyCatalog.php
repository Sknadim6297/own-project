<?php

namespace App\Support\WeightJourney;

class WeightJourneyCatalog
{
    public const TIMEZONE = 'Asia/Kolkata';

    /** Exact conversion factor used for lb ↔ kg (NIST). Documented for reproducibility. */
    public const LB_PER_KG = '2.2046226218';

    public const WEIGHT_KG_MIN = '20.000';

    public const WEIGHT_KG_MAX = '400.000';

    public const NOTE_MAX = 240;

    public const DIRECTIONS = [
        'lose' => 'Lose weight',
        'gain' => 'Gain weight',
        'maintain' => 'Maintain weight',
        'track' => 'Track only',
    ];

    public const DISPLAY_UNITS = ['kg', 'lb'];

    public const PERIODS = [
        'week' => 7,
        'month' => 30,
        'quarter' => 90,
        'year' => 365,
    ];

    /**
     * Chart daily aggregation: when multiple measurements share a local calendar day,
     * the latest consumed_at on that day is used as the single chart point.
     * All raw measurements remain available in history.
     */
    public const CHART_AGGREGATION = 'latest_per_local_day';
}
