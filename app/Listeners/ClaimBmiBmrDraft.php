<?php

namespace App\Listeners;

use App\Services\BmiBmrDraft;
use Illuminate\Auth\Events\Login;

class ClaimBmiBmrDraft
{
    public function handle(Login $event): void
    {
        if ($event->guard !== 'user') {
            return;
        }

        app(BmiBmrDraft::class)->claimForCurrentUser((int) $event->user->getAuthIdentifier());
    }
}
