<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BmiBmrDraft
{
    public const KEY = 'nutrition.bmi_bmr_draft';

    public const TTL_MINUTES = 30;

    public function storeResult(array $result, array $normalized): void
    {
        $this->write([
            'result' => $result,
            'normalized' => $normalized,
            'awaiting_login' => false,
        ]);
    }

    public function readable(): ?array
    {
        $draft = session(self::KEY);
        if (! is_array($draft)) {
            return null;
        }
        if (($draft['expires_at'] ?? 0) < now()->getTimestamp()) {
            session()->forget(self::KEY);

            return null;
        }
        if (($draft['owner'] ?? null) !== $this->owner()) {
            return null;
        }

        return $draft;
    }

    public function result(): ?array
    {
        $draft = $this->readable();
        $result = is_array($draft) ? ($draft['result'] ?? null) : null;

        return is_array($result) ? $result : null;
    }

    public function normalized(): ?array
    {
        $draft = $this->readable();
        $normalized = is_array($draft) ? ($draft['normalized'] ?? null) : null;

        return is_array($normalized) ? $normalized : null;
    }

    public function completionKey(): ?string
    {
        $draft = $this->readable();
        $key = is_array($draft) ? ($draft['completion_key'] ?? null) : null;

        return is_string($key) ? $key : null;
    }

    public function markAwaitingLogin(): void
    {
        $current = $this->readable();
        if (! is_array($current) || ! is_array($current['result'] ?? null)) {
            return;
        }
        $current['awaiting_login'] = true;
        $this->write($current);
    }

    public function claimForCurrentUser(?int $userId = null): void
    {
        $id = $userId ?: Auth::guard('user')->id();
        $guest = session('nutrition.bmi_bmr_guest');
        $draft = session(self::KEY);
        if (! $id || ! is_string($guest) || ! is_array($draft)) {
            return;
        }
        if (($draft['owner'] ?? null) !== 'guest:'.$guest) {
            return;
        }
        if (($draft['expires_at'] ?? 0) < now()->getTimestamp()) {
            session()->forget(self::KEY);

            return;
        }
        $draft['owner'] = 'user:'.$id;
        session([self::KEY => $draft]);
    }

    public function forget(): void
    {
        session()->forget(self::KEY);
    }

    private function write(array $payload): void
    {
        $current = session(self::KEY);
        $owned = is_array($current) && ($current['owner'] ?? null) === $this->owner();
        $key = $owned && is_string($current['completion_key'] ?? null)
            ? $current['completion_key']
            : (string) Str::uuid();

        session([
            self::KEY => [
                'result' => $payload['result'] ?? null,
                'normalized' => $payload['normalized'] ?? null,
                'completion_key' => $key,
                'awaiting_login' => (bool) ($payload['awaiting_login'] ?? false),
                'owner' => $this->owner(),
                'expires_at' => now()->addMinutes(self::TTL_MINUTES)->getTimestamp(),
            ],
        ]);
    }

    private function owner(): string
    {
        $id = Auth::guard('user')->id();
        if ($id) {
            return 'user:'.$id;
        }
        if (! session()->has('nutrition.bmi_bmr_guest')) {
            session(['nutrition.bmi_bmr_guest' => (string) Str::uuid()]);
        }

        return 'guest:'.session('nutrition.bmi_bmr_guest');
    }
}
