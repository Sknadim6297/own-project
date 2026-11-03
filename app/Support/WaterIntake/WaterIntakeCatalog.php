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

    /** UI presets only — not medical recommendations. */
    public const QUICK_ADD_ML = [150, 250, 500, 750];

    public const DISPLAY_UNITS = ['ml', 'L'];
}
