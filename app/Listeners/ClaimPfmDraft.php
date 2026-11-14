<?php

namespace App\Listeners;

use App\Services\Pfm\PfmDraft;
use Illuminate\Auth\Events\Login;

class ClaimPfmDraft
{
    public function handle(Login $event): void
    {
        if (($event->guard ?? null) !== 'user') {
            return;
        }

        app(PfmDraft::class)->claimAllForCurrentUser((int) $event->user->getAuthIdentifier());
    }
}
