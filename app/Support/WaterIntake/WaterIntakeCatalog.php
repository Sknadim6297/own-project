<?php

namespace App\Support\WaterIntake;

class WaterIntakeCatalog
{
    public const TIMEZONE = 'Asia/Kolkata';

    /** Default personal preference only — not a medical prescription. */
    public const DEFAULT_GOAL_ML = 2000;

    public const GOAL_MIN_ML = 250;

    public const GOAL_MAX_ML = 10000;

    public const ENTRY_MIN_ML = 1;

    public const ENTRY_MAX_ML = 5000;

    /**
     * UI presets only — not medical recommendations.
     * Amounts preserved; emoji/labels are presentation metadata.
     *
     * @var list<array{ml: int, emoji: string, label: string}>
     */
    public const QUICK_ADDS = [
        ['ml' => 150, 'emoji' => '🥛', 'label' => 'Small glass'],
        ['ml' => 250, 'emoji' => '💧', 'label' => 'Regular glass'],
        ['ml' => 500, 'emoji' => '🚰', 'label' => 'Water bottle'],
        ['ml' => 750, 'emoji' => '🧴', 'label' => 'Large bottle'],
    ];

    /** @deprecated Prefer QUICK_ADDS; kept for callers that expect bare ml list. */
    public const QUICK_ADD_ML = [150, 250, 500, 750];

    public const DISPLAY_UNITS = ['ml', 'L'];
}
