<?php

return [

    /*
    |--------------------------------------------------------------------------
    | YuktiPoints / Refer & Earn
    |--------------------------------------------------------------------------
    |
    | Phase 3B foundation + Phase 3C engine. Granting stays disabled until a
    | later phase explicitly enables it. Point amounts live in
    | yukti_reward_configs. Override only in isolated tests via Config::set
    | or YUKTI_REWARDS_ENGINE_ENABLED=true (never enable in production yet).
    |
    */

    'engine_enabled' => (bool) env('YUKTI_REWARDS_ENGINE_ENABLED', false),

    /*
    | MySQL UNSIGNED INT ceiling for wallet/ledger point columns.
    */
    'points_column_max' => 4294967295,

    /*
    | Monday-based weekly counters use this timezone when computing week_id.
    | Default is documented as Asia/Kolkata (IST) for YuktiOne operations.
    | Change via YUKTI_REWARDS_WEEK_TIMEZONE — do not hard-code elsewhere.
    */
    'week_timezone' => env('YUKTI_REWARDS_WEEK_TIMEZONE', 'Asia/Kolkata'),

    /*
    | week_id format: ISO-8601 year-week in week_timezone, e.g. 2026-W46
    */
    'week_id_format' => 'o-\WW',

    /*
    | Phase 3D — temporary referral capture (session + encrypted cookie backup).
    | First-touch wins: a second referral link does not overwrite a still-valid capture.
    | Capture TTL is in minutes (default 72 hours). No points are granted on click.
    */
    'referral_capture_ttl_minutes' => (int) env('YUKTI_REFERRAL_CAPTURE_TTL_MINUTES', 4320),

    'referral_code_length' => 10,

    /*
    | Phase 3E/3F — tools with trustworthy server completion_key persistence.
    | Used for weekly tool-completion rewards AND tool-referral activation.
    | Incremental loggers (water / weight / nutrition meal entries) are excluded
    | until business defines what “completion” means for those tools.
    */
    'eligible_tool_completion_slugs' => [
        'daily-mood-check-in',
        'digital-journal',
        'gratitude-journal',
        'career-interest-test',
        'skills-strengths-assessment',
        'bmi-bmr-calculator',
        'tarot-reflection',
    ],

    /*
    | Phase 3F — weekly accounting for referral settlement uses attribution
    | activated_at (activation week), not later wall-clock settlement time.
    | Documented policy: do not silently switch weeks on resume after engine enable.
    | Cap-blocked activated attributions stay activated; no auto-retry/forfeit.
    */
    'referral_accounting_week' => 'activation_at',

    /*
    | Phase 3G — redemption preparation only. Executable debit stays off.
    | rupee conversion and minimum threshold require business approval
    | (yukti_reward_configs.redemption_rupee_per_point is pending_business_rule).
    */
    'redemption' => [
        'enabled' => false,
        'minimum_points' => null,
        'allow_client_offering_ids' => false,
    ],

];
