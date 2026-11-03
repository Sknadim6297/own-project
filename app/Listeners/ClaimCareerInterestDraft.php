<?php

namespace App\Listeners;

use App\Services\CareerInterestDraft;
use Illuminate\Auth\Events\Login;

class ClaimCareerInterestDraft
{
    public function handle(Login $event): void
    {
        if ($event->guard !== 'user') {
            return;
        }

        app(CareerInterestDraft::class)->claimForCurrentUser((int) $event->user->getAuthIdentifier());
    }
}
