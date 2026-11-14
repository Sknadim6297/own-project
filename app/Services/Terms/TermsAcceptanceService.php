<?php

namespace App\Services\Terms;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TermsAcceptanceService
{
    public function currentVersion(): string
    {
        return (string) config('terms.current_version', '1.0');
    }

    public function isTrackingAvailable(): bool
    {
        return Schema::hasTable('users')
            && Schema::hasColumn('users', 'terms_accepted_at')
            && Schema::hasColumn('users', 'terms_version');
    }

    public function shouldEnforceAfterLogin(): bool
    {
        return (bool) config('terms.enforce_after_login', false)
            && $this->isTrackingAvailable();
    }

    public function hasAcceptedCurrent(User $user): bool
    {
        if (! $this->isTrackingAvailable()) {
            return true;
        }

        // Post-login Terms gate disabled: treat as accepted for routing.
        if (! $this->shouldEnforceAfterLogin()) {
            return true;
        }

        if ($user->terms_accepted_at === null) {
            return false;
        }

        if (! config('terms.require_reacceptance_on_version_change', false)) {
            return true;
        }

        return (string) $user->terms_version === $this->currentVersion();
    }

    /**
     * Persist acceptance for the authenticated user.
     * Preserves the original acceptance timestamp on duplicate submissions.
     */
    public function accept(User $user): User
    {
        if (! $this->isTrackingAvailable()) {
            return $user;
        }

        $version = $this->currentVersion();

        DB::transaction(function () use ($user, $version) {
            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $payload = [
                'terms_version' => $version,
            ];

            if ($locked->terms_accepted_at === null) {
                $payload['terms_accepted_at'] = now();
            }

            // Version bump re-acceptance path: refresh timestamp only when policy requires it
            // and the stored version no longer matches.
            if (
                config('terms.require_reacceptance_on_version_change', false)
                && (string) $locked->terms_version !== $version
            ) {
                $payload['terms_accepted_at'] = now();
            }

            $locked->forceFill($payload)->save();
            $user->refresh();
        });

        return $user;
    }
}
