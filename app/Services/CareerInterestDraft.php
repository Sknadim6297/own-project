<?php

namespace App\Services;

use App\Support\CareerInterest\CareerInterestCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CareerInterestDraft
{
    public const KEY = 'career.interest_draft';

    public const TTL_MINUTES = 30;

    public function start(): void
    {
        $this->write([
            'step' => 'about',
            'about' => null,
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

    public function about(): ?array
    {
        $draft = $this->readable();
        $about = is_array($draft) ? ($draft['about'] ?? null) : null;

        return is_array($about) ? $about : null;
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
            if (in_array($id, CareerInterestCatalog::questionIds(), true)) {
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

    public function rememberAbout(array $about): void
    {
        $current = $this->readable() ?? $this->blank();
        $current['step'] = 'questions';
        $current['about'] = $about;
        $this->write($current);
    }

    public function rememberAnswers(string $category, array $answers): void
    {
        $current = $this->readable();
        if (! is_array($current) || ! is_array($current['about'] ?? null)) {
            return;
        }
        $merged = is_array($current['answers'] ?? null) ? $current['answers'] : [];
        foreach (CareerInterestCatalog::questionIdsFor($category) as $id) {
            $merged[$id] = (int) $answers[$id];
        }
        $current['answers'] = $merged;
        $current['step'] = 'questions';
        $this->write($current);
    }

    public function isCategoryComplete(string $category): bool
    {
        $answers = $this->answers();
        foreach (CareerInterestCatalog::questionIdsFor($category) as $id) {
            if (! isset($answers[$id])) {
                return false;
            }
        }

        return true;
    }

    public function isAssessmentComplete(): bool
    {
        if ($this->about() === null) {
            return false;
        }
        $answers = $this->answers();
        foreach (CareerInterestCatalog::questionIds() as $id) {
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
        $guest = session('career.interest_guest');
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
            'step' => 'about',
            'about' => null,
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
                'step' => $payload['step'] ?? 'about',
                'about' => $payload['about'] ?? null,
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
        if (! session()->has('career.interest_guest')) {
            session(['career.interest_guest' => (string) Str::uuid()]);
        }

        return 'guest:'.session('career.interest_guest');
    }
}
