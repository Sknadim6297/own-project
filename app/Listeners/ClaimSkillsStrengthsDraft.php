<?php

namespace App\Listeners;

use App\Services\SkillsStrengthsDraft;
use Illuminate\Auth\Events\Login;

class ClaimSkillsStrengthsDraft
{
    public function handle(Login $event): void
    {
        if ($event->guard !== 'user') {
            return;
        }

        app(SkillsStrengthsDraft::class)->claimForCurrentUser((int) $event->user->getAuthIdentifier());
    }
}
