<?php

namespace App\Listeners;

use App\Services\JournalDraft;
use Illuminate\Auth\Events\Login;

class ClaimJournalDraft
{
    public function handle(Login $event): void
    {
        if ($event->guard !== 'user') {
            return;
        }

        app(JournalDraft::class)->claimForCurrentUser((int) $event->user->getAuthIdentifier());
    }
}
