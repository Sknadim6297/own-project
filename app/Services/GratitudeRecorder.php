<?php

namespace App\Services;

use App\Models\PsychologyGratitudeEntry;
use App\Support\Psychology\PsychologyCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class GratitudeRecorder
{
    public function store(GratitudeDraft $draft): PsychologyGratitudeEntry
    {
        $userId = Auth::guard('user')->id();
        $key = $draft->completionKey();
        $answers = $draft->answers();

        if (!$userId || !is_string($key)) {
            throw new InvalidArgumentException('The gratitude draft is incomplete.');
        }

        $normalized = $this->normalize($answers);
        $this->assertAtLeastOne($normalized);

        try {
            return $this->persist((int) $userId, $key, $normalized);
        } catch (UniqueConstraintViolationException) {
            return $this->existingForUser($key, (int) $userId);
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                return $this->existingForUser($key, (int) $userId);
            }

            throw $exception;
        }
    }

    /**
     * @param  array<string, string>  $answers
     */
    public function update(PsychologyGratitudeEntry $entry, array $answers): PsychologyGratitudeEntry
    {
        $userId = Auth::guard('user')->id();
        if (!$userId) {
            throw new InvalidArgumentException('Authentication is required.');
        }

        $this->owned($entry, (int) $userId);
        $normalized = $this->normalize($answers);
        $this->assertAtLeastOne($normalized);

        $entry->something_good_today = $normalized['something_good_today'] ?? null;
        $entry->someone_i_appreciate = $normalized['someone_i_appreciate'] ?? null;
        $entry->something_about_myself = $normalized['something_about_myself'] ?? null;
        $entry->save();

        return $entry->refresh();
    }

    public function delete(PsychologyGratitudeEntry $entry): void
    {
        $userId = Auth::guard('user')->id();
        if (!$userId) {
            throw new InvalidArgumentException('Authentication is required.');
        }

        $this->owned($entry, (int) $userId);
        $entry->delete();
    }

    /**
     * @param  array{something_good_today: ?string, someone_i_appreciate: ?string, something_about_myself: ?string}  $normalized
     */
    private function assertAtLeastOne(array $normalized): void
    {
        $answered = array_filter(
            $normalized,
            fn (?string $value) => is_string($value) && $value !== ''
        );
        if (count($answered) < PsychologyCatalog::GRATITUDE_MIN_ANSWERED) {
            throw new InvalidArgumentException('At least one gratitude answer is required.');
        }
    }

    /**
     * @param  array<string, string>  $answers
     * @return array{something_good_today: ?string, someone_i_appreciate: ?string, something_about_myself: ?string}
     */
    private function normalize(array $answers): array
    {
        $allowed = app(GratitudeJournal::class)->cardKeys();
        if ($allowed === []) {
            $allowed = PsychologyCatalog::cardKeys();
        }

        $columns = [
            'something-good-today' => 'something_good_today',
            'someone-i-appreciate' => 'someone_i_appreciate',
            'something-about-myself' => 'something_about_myself',
        ];

        $normalized = [
            'something_good_today' => null,
            'someone_i_appreciate' => null,
            'something_about_myself' => null,
        ];

        foreach ($answers as $key => $value) {
            if (!in_array($key, $allowed, true) || !isset($columns[$key])) {
                continue;
            }

            $text = trim(str_replace("\0", '', $value));
            if ($text === '' || mb_strlen($text) > PsychologyCatalog::GRATITUDE_ANSWER_MAX) {
                continue;
            }

            $normalized[$columns[$key]] = $text;
        }

        return $normalized;
    }

    /**
     * @param  array{something_good_today: ?string, someone_i_appreciate: ?string, something_about_myself: ?string}  $answers
     */
    private function persist(int $userId, string $key, array $answers): PsychologyGratitudeEntry
    {
        return DB::transaction(function () use ($userId, $key, $answers) {
            $existing = PsychologyGratitudeEntry::query()->where('completion_key', $key)->lockForUpdate()->first();
            if ($existing) {
                return $this->owned($existing, $userId);
            }

            $record = new PsychologyGratitudeEntry([
                'completion_key' => $key,
                'something_good_today' => $answers['something_good_today'] ?? null,
                'someone_i_appreciate' => $answers['someone_i_appreciate'] ?? null,
                'something_about_myself' => $answers['something_about_myself'] ?? null,
                'written_at' => now(),
            ]);
            $record->user_id = $userId;
            $record->save();

            return $record;
        });
    }

    private function existingForUser(string $key, int $userId): PsychologyGratitudeEntry
    {
        $existing = PsychologyGratitudeEntry::query()->where('completion_key', $key)->first();
        if (!$existing) {
            throw new RuntimeException('The gratitude entry could not be saved.');
        }

        return $this->owned($existing, $userId);
    }

    private function owned(PsychologyGratitudeEntry $record, int $userId): PsychologyGratitudeEntry
    {
        if ((int) $record->user_id !== $userId) {
            abort(403);
        }

        return $record;
    }
}
