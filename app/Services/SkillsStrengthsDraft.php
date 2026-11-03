<?php

namespace App\Services;

use App\Support\SkillsStrengths\SkillsStrengthsCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SkillsStrengthsDraft
{
    public const KEY = 'skills.strengths_draft';

    public const TTL_MINUTES = 30;

    public function start(): void
    {
        $this->write([
            'step' => 'intro',
            'intro_seen' => false,
            'answers' => [],
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

    public function introSeen(): bool
    {
        $draft = $this->readable();

        return is_array($draft) && ($draft['intro_seen'] ?? false) === true;
    }

    public function answers(): array
    {
        $draft = $this->readable();
        $answers = is_array($draft) ? ($draft['answers'] ?? []) : [];
        if (! is_array($answers)) {
            return [];
        }

        $clean = [];
        foreach ($answers as $id => $value) {
            if (in_array($id, SkillsStrengthsCatalog::questionIds(), true)) {
                $clean[$id] = (int) $value;
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

    public function rememberIntro(): void
    {
        $current = $this->readable() ?? $this->blank();
        $current['intro_seen'] = true;
        $current['step'] = 'questions';
        $this->write($current);
    }

    public function rememberAnswers(string $category, array $answers): void
    {
        $current = $this->readable();
        if (! is_array($current) || ! ($current['intro_seen'] ?? false)) {
            return;
        }
        $merged = is_array($current['answers'] ?? null) ? $current['answers'] : [];
        foreach (SkillsStrengthsCatalog::questionIdsFor($category) as $id) {
            $merged[$id] = (int) $answers[$id];
        }
        $current['answers'] = $merged;
        $current['step'] = 'questions';
        $this->write($current);
    }

    public function isCategoryComplete(string $category): bool
    {
        $answers = $this->answers();
        foreach (SkillsStrengthsCatalog::questionIdsFor($category) as $id) {
            if (! isset($answers[$id])) {
                return false;
            }
        }

        return true;
    }

    public function isAssessmentComplete(): bool
    {
        if (! $this->introSeen()) {
            return false;
        }
        $answers = $this->answers();
        foreach (SkillsStrengthsCatalog::questionIds() as $id) {
            if (! isset($answers[$id])) {
                return false;
            }
        }

        return true;
    }

    public function markAwaitingLogin(): void
    {
        $current = $this->readable();
        if (! is_array($current) || ! $this->isAssessmentComplete()) {
            return;
        }
        $current['awaiting_login'] = true;
        $this->write($current);
    }

    public function claimForCurrentUser(?int $userId = null): void
    {
        $id = $userId ?: Auth::guard('user')->id();
        $guest = session('skills.strengths_guest');
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

    private function blank(): array
    {
        return [
            'step' => 'intro',
            'intro_seen' => false,
            'answers' => [],
            'awaiting_login' => false,
        ];
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
                'step' => $payload['step'] ?? 'intro',
                'intro_seen' => (bool) ($payload['intro_seen'] ?? false),
                'answers' => $payload['answers'] ?? [],
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
        if (! session()->has('skills.strengths_guest')) {
            session(['skills.strengths_guest' => (string) Str::uuid()]);
        }

        return 'guest:'.session('skills.strengths_guest');
    }
}
