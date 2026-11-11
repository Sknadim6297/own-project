<?php

namespace App\Listeners;

use App\Services\YuktiRewards\YuktiCustomerRewardHooks;
use Illuminate\Auth\Events\Login;

/**
 * Customer-guard weekly login reward. Never blocks authentication.
 */
class AwardWeeklyLoginYuktiPoints
{
    public function __construct(
        private readonly YuktiCustomerRewardHooks $hooks,
    ) {
    }

    public function handle(Login $event): void
    {
        if ($event->guard !== 'user') {
            return;
        }

        $userId = (int) $event->user->getAuthIdentifier();
        if ($userId <= 0) {
            return;
        }

        $this->hooks->onCustomerLogin($userId);
    }
}
