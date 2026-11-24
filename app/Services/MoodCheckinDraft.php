<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MoodCheckinDraft
{
    public const KEY = 'psychology.mood_draft';

    public const TTL_MINUTES = 30;

    public function mood(): ?string
    {
        $draft = $this->readable();
        if ($draft === null) {
            return null;
        }

        $mood = $draft['mood'] ?? null;

        return is_string($mood) ? $mood : null;
    }

    public function emotions(): array
    {
        $draft = $this->readable();
        if ($draft === null) {
            return [];
        }

        $emotions = $draft['emotions'] ?? [];
        if (!is_array($emotions)) {
            return [];
        }

        return array_values(array_filter($emotions, 'is_string'));
    }

    public function hasEmotions(): bool
    {
        $draft = $this->readable();

        return is_array($draft) && in_array($draft['step'] ?? null, ['emotions', 'reflection'], true);
    }

    public function reflection(): ?string
    {
        $draft = $this->readable();
        if ($draft === null || ($draft['reflection_held'] ?? false) !== true) {
            return null;
        }

        $text = $draft['reflection'] ?? null;

        return is_string($text) ? $text : null;
    }

    public function reflectionHeld(): bool
    {
        $draft = $this->readable();

        return is_array($draft) && ($draft['reflection_held'] ?? false) === true;
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

    public function markAwaitingLogin(): void
    {
        $current = $this->readable();
        if (!is_array($current) || !is_string($current['mood'] ?? null)) {
            return;
        }

        [$reflection, $held] = $this->carriedReflection($current);
        $this->write($current['step'], $current['mood'], $this->emotions(), $reflection, $held, true);
    }

    public function claimForCurrentUser(?int $userId = null): void
    {
        $id = $userId ?: Auth::guard('user')->id();
        $guest = session('psychology.mood_guest');
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

    public function remember(string $mood): void
    {
        $current = $this->readable();
        $previousMood = is_array($current) ? ($current['mood'] ?? null) : null;
        $sameMood = is_string($previousMood) && $previousMood === $mood;

        // Changing primary mood clears follow-up answers so stale emotions/notes
        // cannot be submitted with a different mood.
        $emotions = [];
        $reflection = null;
        $held = false;
        if ($sameMood) {
            if (is_array($current['emotions'] ?? null)) {
                $emotions = array_values(array_filter($current['emotions'], 'is_string'));
            }
            [$reflection, $held] = $this->carriedReflection($current);
        }

        $this->write('mood', $mood, $emotions, $reflection, $held);
    }

    public function hasFollowUpAnswers(): bool
    {
        if ($this->emotions() !== []) {
            return true;
        }

        return $this->reflectionHeld();
    }

    public function rememberEmotions(array $emotions): void
    {
        $current = $this->readable();
        $mood = is_array($current) ? ($current['mood'] ?? null) : null;
        if (!is_string($mood)) {
            return;
        }

        [$reflection, $held] = $this->carriedReflection($current);
        $this->write('emotions', $mood, array_values($emotions), $reflection, $held);
    }

    public function rememberReflection(string $reflection): void
    {
        $current = $this->readable();
        $mood = is_array($current) ? ($current['mood'] ?? null) : null;
        if (!is_string($mood) || !is_array($current) || !$this->hasEmotions()) {
            return;
        }

        $this->write('reflection', $mood, $this->emotions(), $reflection, true);
    }

    public function skipReflection(): void
    {
        $current = $this->readable();
        $mood = is_array($current) ? ($current['mood'] ?? null) : null;
        if (!is_string($mood) || !$this->hasEmotions()) {
            return;
        }

        $this->write('reflection', $mood, $this->emotions(), null, true);
    }

    private function readable(): ?array
    {
        $draft = session(self::KEY);
        if (!is_array($draft) || !in_array($draft['step'] ?? null, ['mood', 'emotions', 'reflection'], true)) {
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

    private function carriedReflection(?array $current): array
    {
        if (!is_array($current)) {
            return [null, false];
        }

        $text = $current['reflection'] ?? null;

        return [is_string($text) ? $text : null, ($current['reflection_held'] ?? false) === true];
    }

    private function write(string $step, string $mood, array $emotions, ?string $reflection, bool $held, ?bool $awaiting = null): void
    {
        $current = session(self::KEY);
        $ownedCurrent = is_array($current) && ($current['owner'] ?? null) === $this->owner();
        $key = $ownedCurrent && is_string($current['completion_key'] ?? null)
            ? $current['completion_key']
            : (string) Str::uuid();
        $awaitingLogin = $awaiting ?? ($ownedCurrent && ($current['awaiting_login'] ?? false) === true);

        session([
            self::KEY => [
                'step' => $step,
                'mood' => $mood,
                'emotions' => $emotions,
                'reflection' => $held ? $reflection : null,
                'reflection_held' => $held,
                'completion_key' => $key,
                'awaiting_login' => $awaitingLogin,
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

        if (!session()->has('psychology.mood_guest')) {
            session(['psychology.mood_guest' => (string) Str::uuid()]);
        }

        return 'guest:'.session('psychology.mood_guest');
    }
}
