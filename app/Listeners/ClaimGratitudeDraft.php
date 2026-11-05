<?php

namespace App\Listeners;

use App\Services\GratitudeDraft;
use Illuminate\Auth\Events\Login;

class ClaimGratitudeDraft
{
    public function handle(Login $event): void
    {
        if ($event->guard !== 'user') {
            return;
        }

        app(GratitudeDraft::class)->claimForCurrentUser((int) $event->user->getAuthIdentifier());
    }
}
