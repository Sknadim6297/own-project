<?php

namespace App\Providers;

use App\Listeners\AwardWeeklyLoginYuktiPoints;
use App\Listeners\ClaimBmiBmrDraft;
use App\Listeners\ClaimCareerInterestDraft;
use App\Listeners\ClaimGratitudeDraft;
use App\Listeners\ClaimJournalDraft;
use App\Listeners\ClaimMoodCheckinDraft;
use App\Listeners\ClaimPfmDraft;
use App\Listeners\ClaimPsDraft;
use App\Listeners\ClaimSkillsStrengthsDraft;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Mail\Events\MessageSent;
use App\Listeners\SaveSentEmailToImap;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        Login::class => [
            ClaimMoodCheckinDraft::class,
            ClaimJournalDraft::class,
            ClaimGratitudeDraft::class,
            ClaimCareerInterestDraft::class,
            ClaimSkillsStrengthsDraft::class,
            ClaimBmiBmrDraft::class,
            ClaimPfmDraft::class,
            ClaimPsDraft::class,
            AwardWeeklyLoginYuktiPoints::class,
        ],
        MessageSent::class => [
            SaveSentEmailToImap::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
