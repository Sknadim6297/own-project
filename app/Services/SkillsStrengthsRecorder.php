<?php

namespace App\Services;

use App\Models\SkillsStrengthsAttempt;
use App\Services\YuktiRewards\YuktiCustomerRewardHooks;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class SkillsStrengthsRecorder
{
    public function __construct(
        private SkillsStrengthsScorer $scorer,
    ) {
    }

    public function store(SkillsStrengthsDraft $draft): SkillsStrengthsAttempt
    {
        $userId = Auth::guard('user')->id();
        $key = $draft->completionKey();
        $answers = $draft->answers();

        if (! $userId || ! is_string($key) || ! $draft->isAssessmentComplete()) {
            throw new InvalidArgumentException('The skills assessment draft is incomplete.');
        }

        return $this->storeForUser((int) $userId, $key, $answers);
    }

    /**
     * Persist a completed assessment for a Sanctum (or session) customer.
     * Same scoring, idempotency and reward hooks as the website draft save.
     */
    public function storeForUser(int $userId, string $completionKey, array $answers): SkillsStrengthsAttempt
    {
        if ($completionKey === '' || strlen($completionKey) > 64) {
            throw new InvalidArgumentException('The completion key is invalid.');
        }

        $scored = $this->scorer->score($answers);

        try {
            $record = $this->persist($userId, $completionKey, $scored);
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                $record = $this->existingForUser($completionKey, $userId);
            } else {
                throw $exception;
            }
        }

        $this->awardIfNew($record);

        return $record;
    }

    private function awardIfNew(SkillsStrengthsAttempt $record): void
    {
        if (! $record->wasRecentlyCreated) {
            return;
        }

        app(YuktiCustomerRewardHooks::class)->onEligibleToolCompleted(
            (int) $record->user_id,
            'skills-strengths-assessment',
            (string) $record->completion_key,
            (int) $record->id,
        );
    }

    private function persist(int $userId, string $key, array $scored): SkillsStrengthsAttempt
    {
        return DB::transaction(function () use ($userId, $key, $scored) {
            $existing = SkillsStrengthsAttempt::query()->where('completion_key', $key)->lockForUpdate()->first();
            if ($existing) {
                return $this->owned($existing, $userId);
            }

            $record = new SkillsStrengthsAttempt([
                'completion_key' => $key,
                'assessment_version' => $scored['assessment_version'],
                'scoring_version' => $scored['scoring_version'],
                'answers' => $scored['answers'],
                'category_totals' => $scored['totals'],
                'category_percentages' => $scored['percentages'],
                'strengths' => $scored['strengths'],
                'develop' => $scored['develop'],
                'summary' => $scored['summary'],
                'meaning' => $scored['meaning'],
                'completed_at' => now(),
            ]);
            $record->user_id = $userId;
            $record->save();

            return $record;
        });
    }

    private function existingForUser(string $key, int $userId): SkillsStrengthsAttempt
    {
        $existing = SkillsStrengthsAttempt::query()->where('completion_key', $key)->first();
        if (! $existing) {
            throw new RuntimeException('The assessment could not be saved.');
        }

        return $this->owned($existing, $userId);
    }

    private function owned(SkillsStrengthsAttempt $record, int $userId): SkillsStrengthsAttempt
    {
        if ((int) $record->user_id !== $userId) {
            abort(403);
        }

        return $record;
    }
}
