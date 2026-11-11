<?php

namespace App\Services;

use App\Models\CareerInterestAttempt;
use App\Services\YuktiRewards\YuktiCustomerRewardHooks;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class CareerInterestRecorder
{
    public function __construct(
        private CareerInterestScorer $scorer,
        private CareerInterestRecommender $recommender,
    ) {
    }

    public function store(CareerInterestDraft $draft): CareerInterestAttempt
    {
        $userId = Auth::guard('user')->id();
        $key = $draft->completionKey();
        $about = $draft->about();
        $answers = $draft->answers();

        if (! $userId || ! is_string($key) || ! is_array($about) || ! $draft->isAssessmentComplete()) {
            throw new InvalidArgumentException('The career interest draft is incomplete.');
        }

        $scored = $this->scorer->score($answers);
        $recommendations = $this->recommender->recommend($scored, $about);

        try {
            $record = $this->persist((int) $userId, $key, $about, $scored, $recommendations);
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                $record = $this->existingForUser($key, (int) $userId);
            } else {
                throw $exception;
            }
        }

        $this->awardIfNew($record);

        return $record;
    }

    private function awardIfNew(CareerInterestAttempt $record): void
    {
        if (! $record->wasRecentlyCreated) {
            return;
        }

        app(YuktiCustomerRewardHooks::class)->onEligibleToolCompleted(
            (int) $record->user_id,
            'career-interest-test',
            (string) $record->completion_key,
            (int) $record->id,
        );
    }

    private function persist(int $userId, string $key, array $about, array $scored, array $recommendations): CareerInterestAttempt
    {
        return DB::transaction(function () use ($userId, $key, $about, $scored, $recommendations) {
            $existing = CareerInterestAttempt::query()->where('completion_key', $key)->lockForUpdate()->first();
            if ($existing) {
                return $this->owned($existing, $userId);
            }

            $record = new CareerInterestAttempt([
                'completion_key' => $key,
                'assessment_version' => $scored['assessment_version'],
                'scoring_version' => $scored['scoring_version'],
                'catalogue_version' => $recommendations['catalogue_version'],
                'age_group' => $about['age_group'],
                'education' => $about['education'],
                'situation' => $about['situation'],
                'geography' => $about['geography'],
                'answers' => $scored['answers'],
                'category_totals' => $scored['totals'],
                'category_percentages' => $scored['percentages'],
                'top_categories' => $scored['top'],
                'interpretation' => $scored['interpretation'],
                'recommendations' => $recommendations,
                'completed_at' => now(),
            ]);
            $record->user_id = $userId;
            $record->save();

            return $record;
        });
    }

    private function existingForUser(string $key, int $userId): CareerInterestAttempt
    {
        $existing = CareerInterestAttempt::query()->where('completion_key', $key)->first();
        if (! $existing) {
            throw new RuntimeException('The assessment could not be saved.');
        }

        return $this->owned($existing, $userId);
    }

    private function owned(CareerInterestAttempt $record, int $userId): CareerInterestAttempt
    {
        if ((int) $record->user_id !== $userId) {
            abort(403);
        }

        return $record;
    }
}
