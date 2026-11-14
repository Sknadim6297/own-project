<?php

namespace App\Listeners;

use App\Services\Ps\PsDraft;
use Illuminate\Auth\Events\Login;

class ClaimPsDraft
{
    public function handle(Login $event): void
    {
        if (($event->guard ?? null) !== 'user') {
            return;
        }

        app(PsDraft::class)->claimAllForCurrentUser((int) $event->user->getAuthIdentifier());
    }
}
