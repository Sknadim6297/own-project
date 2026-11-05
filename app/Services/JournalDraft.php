<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class JournalDraft
{
    public const KEY = 'psychology.journal_draft';

    public const TTL_MINUTES = 30;

    public function promptKey(): ?string
    {
        $draft = $this->readable();
        $key = is_array($draft) ? ($draft['prompt_key'] ?? null) : null;

        return is_string($key) ? $key : null;
    }

    public function title(): ?string
    {
        $draft = $this->readable();
        $title = is_array($draft) ? ($draft['title'] ?? null) : null;

        return is_string($title) ? $title : null;
    }

    public function body(): ?string
    {
        $draft = $this->readable();
        $body = is_array($draft) ? ($draft['body'] ?? null) : null;

        return is_string($body) ? $body : null;
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

    public function remember(string $promptKey, ?string $title, string $body): void
    {
        $this->write($promptKey, $title, $body, false);
    }

    public function markAwaitingLogin(): void
    {
        $current = $this->readable();
        if (!is_array($current) || !is_string($current['body'] ?? null)) {
            return;
        }

        $this->write(
            is_string($current['prompt_key'] ?? null) ? $current['prompt_key'] : '',
            is_string($current['title'] ?? null) ? $current['title'] : null,
            $current['body'],
            true
        );
    }

    public function claimForCurrentUser(?int $userId = null): void
    {
        $id = $userId ?: Auth::guard('user')->id();
        $guest = session('psychology.journal_guest');
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

    private function write(string $promptKey, ?string $title, string $body, bool $awaiting): void
    {
        $current = session(self::KEY);
        $ownedCurrent = is_array($current) && ($current['owner'] ?? null) === $this->owner();
        $key = $ownedCurrent && is_string($current['completion_key'] ?? null)
            ? $current['completion_key']
            : (string) Str::uuid();

        session([
            self::KEY => [
                'step' => 'compose',
                'prompt_key' => $promptKey !== '' ? $promptKey : null,
                'title' => $title,
                'body' => $body,
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

        if (!session()->has('psychology.journal_guest')) {
            session(['psychology.journal_guest' => (string) Str::uuid()]);
        }

        return 'guest:'.session('psychology.journal_guest');
    }
}
