<?php

namespace App\Services\Pfm;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PfmDraft
{
    public const KEY_PREFIX = 'pfm.draft.';

    public const GUEST_KEY = 'pfm.guest';

    public const TTL_MINUTES = 60;

    public function store(string $toolKey, array $result, array $normalized): void
    {
        $this->write($toolKey, [
            'result' => $result,
            'normalized' => $normalized,
            'awaiting_login' => false,
        ]);
    }

    public function readable(string $toolKey): ?array
    {
        $draft = session($this->key($toolKey));
        if (! is_array($draft)) {
            return null;
        }
        if (($draft['expires_at'] ?? 0) < now()->getTimestamp()) {
            session()->forget($this->key($toolKey));

            return null;
        }
        if (($draft['owner'] ?? null) !== $this->owner()) {
            return null;
        }

        return $draft;
    }

    public function result(string $toolKey): ?array
    {
        $draft = $this->readable($toolKey);
        $result = is_array($draft) ? ($draft['result'] ?? null) : null;

        return is_array($result) ? $result : null;
    }

    public function normalized(string $toolKey): ?array
    {
        $draft = $this->readable($toolKey);
        $normalized = is_array($draft) ? ($draft['normalized'] ?? null) : null;

        return is_array($normalized) ? $normalized : null;
    }

    public function completionKey(string $toolKey): ?string
    {
        $draft = $this->readable($toolKey);
        $key = is_array($draft) ? ($draft['completion_key'] ?? null) : null;

        return is_string($key) ? $key : null;
    }

    public function markAwaitingLogin(string $toolKey): void
    {
        $current = $this->readable($toolKey);
        if (! is_array($current) || ! is_array($current['result'] ?? null)) {
            return;
        }
        $current['awaiting_login'] = true;
        $this->write($toolKey, $current);
    }

    public function claimAllForCurrentUser(?int $userId = null): void
    {
        $id = $userId ?: Auth::guard('user')->id();
        $guest = session(self::GUEST_KEY);
        if (! $id || ! is_string($guest)) {
            return;
        }

        foreach (array_keys(session()->all()) as $sessionKey) {
            if (! is_string($sessionKey) || ! str_starts_with($sessionKey, self::KEY_PREFIX)) {
                continue;
            }
            $draft = session($sessionKey);
            if (! is_array($draft)) {
                continue;
            }
            if (($draft['owner'] ?? null) !== 'guest:'.$guest) {
                continue;
            }
            if (($draft['expires_at'] ?? 0) < now()->getTimestamp()) {
                session()->forget($sessionKey);
                continue;
            }
            $draft['owner'] = 'user:'.$id;
            session([$sessionKey => $draft]);
        }
    }

    public function forget(string $toolKey): void
    {
        session()->forget($this->key($toolKey));
    }

    private function write(string $toolKey, array $payload): void
    {
        $key = $this->key($toolKey);
        $current = session($key);
        $owned = is_array($current) && ($current['owner'] ?? null) === $this->owner();
        $completionKey = $owned && is_string($current['completion_key'] ?? null)
            ? $current['completion_key']
            : (string) Str::uuid();

        session([
            $key => [
                'result' => $payload['result'] ?? null,
                'normalized' => $payload['normalized'] ?? null,
                'completion_key' => $completionKey,
                'awaiting_login' => (bool) ($payload['awaiting_login'] ?? false),
                'owner' => $this->owner(),
                'expires_at' => now()->addMinutes(self::TTL_MINUTES)->getTimestamp(),
            ],
        ]);
    }

    private function key(string $toolKey): string
    {
        return self::KEY_PREFIX.$toolKey;
    }

    private function owner(): string
    {
        $id = Auth::guard('user')->id();
        if ($id) {
            return 'user:'.$id;
        }
        if (! session()->has(self::GUEST_KEY)) {
            session([self::GUEST_KEY => (string) Str::uuid()]);
        }

        return 'guest:'.session(self::GUEST_KEY);
    }
}
