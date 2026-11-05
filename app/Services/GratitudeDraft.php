<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GratitudeDraft
{
    public const KEY = 'psychology.gratitude_draft';

    public const TTL_MINUTES = 30;

    /**
     * @return array<string, string>
     */
    public function answers(): array
    {
        $draft = $this->readable();
        if ($draft === null) {
            return [];
        }

        $answers = $draft['answers'] ?? [];
        if (!is_array($answers)) {
            return [];
        }

        $clean = [];
        foreach ($answers as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                continue;
            }
            $text = trim(str_replace("\0", '', $value));
            if ($text !== '') {
                $clean[$key] = $text;
            }
        }

        return $clean;
    }

    public function completionKey(): ?string
    {
        $draft = $this->readable();
        $key = is_array($draft) ? ($draft['completion_key'] ?? null) : null;

        return is_string($key) ? $key : null;
    }

    public function awaitingLogin(): bool
    {
        $draft = $this->readable();

        return is_array($draft) && ($draft['awaiting_login'] ?? false) === true;
    }

    /**
     * @param  array<string, string>  $answers
     */
    public function remember(array $answers): void
    {
        $this->write($answers, false);
    }

    public function markAwaitingLogin(): void
    {
        $current = $this->readable();
        if (!is_array($current) || !is_array($current['answers'] ?? null)) {
            return;
        }

        $this->write($this->answers(), true);
    }

    public function claimForCurrentUser(?int $userId = null): void
    {
        $id = $userId ?: Auth::guard('user')->id();
        $guest = session('psychology.gratitude_guest');
        $draft = session(self::KEY);
        if (!$id || !is_string($guest) || !is_array($draft)) {
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

    private function readable(): ?array
    {
        $draft = session(self::KEY);
        if (!is_array($draft) || ($draft['step'] ?? null) !== 'compose') {
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

    /**
     * @param  array<string, string>  $answers
     */
    private function write(array $answers, bool $awaiting): void
    {
        $current = session(self::KEY);
        $ownedCurrent = is_array($current) && ($current['owner'] ?? null) === $this->owner();
        $key = $ownedCurrent && is_string($current['completion_key'] ?? null)
            ? $current['completion_key']
            : (string) Str::uuid();

        session([
            self::KEY => [
                'step' => 'compose',
                'answers' => $answers,
                'completion_key' => $key,
                'awaiting_login' => $awaiting,
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

        if (!session()->has('psychology.gratitude_guest')) {
            session(['psychology.gratitude_guest' => (string) Str::uuid()]);
        }

        return 'guest:'.session('psychology.gratitude_guest');
    }
}
