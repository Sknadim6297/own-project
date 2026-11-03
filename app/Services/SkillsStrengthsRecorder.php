<?php

namespace App\Services;

use App\Models\SkillsStrengthsAttempt;
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

        $scored = $this->scorer->score($answers);

        try {
            return $this->persist((int) $userId, $key, $scored);
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                return $this->existingForUser($key, (int) $userId);
            }

            throw $exception;
        }
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
