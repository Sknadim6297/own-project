<?php

namespace App\Services;

use App\Models\PsychologyMoodCheckin;
use App\Services\YuktiRewards\YuktiCustomerRewardHooks;
use App\Support\Psychology\PsychologyCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class MoodCheckinRecorder
{
    public function store(MoodCheckinDraft $draft, ?string $reflection): PsychologyMoodCheckin
    {
        $userId = Auth::guard('user')->id();
        $mood = $draft->mood();
        $emotions = array_values(array_unique($draft->emotions()));
        $key = $draft->completionKey();

        if (!$userId || !is_string($mood) || !is_string($key) || !$draft->hasEmotions()) {
            throw new InvalidArgumentException('The check-in draft is incomplete.');
        }
        if (!in_array($mood, PsychologyCatalog::moodKeys(), true)) {
            throw new InvalidArgumentException('The primary mood is not allowed.');
        }
        if (count($emotions) > PsychologyCatalog::MAX_EMOTIONS) {
            throw new InvalidArgumentException('Too many emotions were selected.');
        }
        if (array_diff($emotions, PsychologyCatalog::emotionKeys()) !== []) {
            throw new InvalidArgumentException('An emotion is not allowed.');
        }
        if ($reflection !== null && mb_strlen($reflection) > PsychologyCatalog::REFLECTION_MAX) {
            throw new InvalidArgumentException('The reflection is too long.');
        }

        try {
            $record = $this->persist((int) $userId, $key, $mood, $emotions, $reflection);
        } catch (UniqueConstraintViolationException) {
            $record = $this->existingForUser($key, (int) $userId);
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

    private function awardIfNew(PsychologyMoodCheckin $record): void
    {
        if (! $record->wasRecentlyCreated) {
            return;
        }

        app(YuktiCustomerRewardHooks::class)->onEligibleToolCompleted(
            (int) $record->user_id,
            'daily-mood-check-in',
            (string) $record->completion_key,
            (int) $record->id,
        );
    }

    private function persist(int $userId, string $key, string $mood, array $emotions, ?string $reflection): PsychologyMoodCheckin
    {
        return DB::transaction(function () use ($userId, $key, $mood, $emotions, $reflection) {
            $existing = PsychologyMoodCheckin::query()->where('completion_key', $key)->lockForUpdate()->first();
            if ($existing) {
                return $this->owned($existing, $userId);
            }

            $record = new PsychologyMoodCheckin([
                'completion_key' => $key,
                'mood_key' => $mood,
                'emotion_keys' => $emotions,
                'reflection' => $reflection,
                'checked_in_at' => now(),
            ]);
            $record->user_id = $userId;
            $record->save();

            return $record;
        });
    }

    private function existingForUser(string $key, int $userId): PsychologyMoodCheckin
    {
        $existing = PsychologyMoodCheckin::query()->where('completion_key', $key)->first();
        if (!$existing) {
            throw new RuntimeException('The check-in could not be saved.');
        }

        return $this->owned($existing, $userId);
    }

    private function owned(PsychologyMoodCheckin $record, int $userId): PsychologyMoodCheckin
    {
        if ((int) $record->user_id !== $userId) {
            abort(403);
        }

        return $record;
    }
}
