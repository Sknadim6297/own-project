<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Current Terms & Conditions Version
    |--------------------------------------------------------------------------
    |
    | Bump this value when a new Terms revision must be re-accepted.
    | Users who already accepted this exact version are not prompted again.
    |
    */
    'current_version' => env('TERMS_CURRENT_VERSION', '1.0'),

    /*
    |--------------------------------------------------------------------------
    | Require Re-acceptance On Version Change
    |--------------------------------------------------------------------------
    |
    | When false, any non-null terms_accepted_at allows access even if the
    | stored version differs from current_version (safe default for rollout).
    | When true, users must re-accept whenever current_version changes.
    |
    */
    'require_reacceptance_on_version_change' => env('TERMS_REQUIRE_REACCEPTANCE', false),

    /*
    |--------------------------------------------------------------------------
    | Enforce Terms After Login
    |--------------------------------------------------------------------------
    |
    | When false, successful login goes straight to the customer dashboard /
    | intended route. Terms are handled on the login page checkbox instead.
    |
    */
    'enforce_after_login' => env('TERMS_ENFORCE_AFTER_LOGIN', false),

];
