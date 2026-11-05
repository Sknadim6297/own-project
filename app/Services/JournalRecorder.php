<?php

namespace App\Services;

use App\Models\PsychologyJournalEntry;
use App\Support\Psychology\PsychologyCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class JournalRecorder
{
    public function store(JournalDraft $draft): PsychologyJournalEntry
    {
        $userId = Auth::guard('user')->id();
        $promptKey = $draft->promptKey();
        $title = $draft->title();
        $body = $draft->body();
        $key = $draft->completionKey();

        if (!$userId || !is_string($body) || trim($body) === '' || !is_string($key)) {
            throw new InvalidArgumentException('The journal draft is incomplete.');
        }

        $body = $this->cleanBody($body);
        if ($body === '' || mb_strlen($body) > PsychologyCatalog::JOURNAL_BODY_MAX) {
            throw new InvalidArgumentException('The journal thoughts are invalid.');
        }

        $allowedPrompts = array_column(app(DigitalJournal::class)->prompts(), 'key');
        if ($allowedPrompts === []) {
            $allowedPrompts = PsychologyCatalog::promptKeys();
        }
        if (!is_string($promptKey) || $promptKey === '' || !in_array($promptKey, $allowedPrompts, true)) {
            throw new InvalidArgumentException('A journal prompt is required.');
        }

        $title = $this->cleanTitle($title);
        if ($title !== null && mb_strlen($title) > PsychologyCatalog::JOURNAL_TITLE_MAX) {
            throw new InvalidArgumentException('The journal title is too long.');
        }

        try {
            return $this->persist((int) $userId, $key, $promptKey, $title, $body);
        } catch (UniqueConstraintViolationException) {
            return $this->existingForUser($key, (int) $userId);
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'unique')) {
                return $this->existingForUser($key, (int) $userId);
            }

            throw $exception;
        }
    }

    public function update(PsychologyJournalEntry $entry, string $promptKey, ?string $title, string $body): PsychologyJournalEntry
    {
        $userId = Auth::guard('user')->id();
        if (!$userId) {
            throw new InvalidArgumentException('Authentication is required.');
        }

        $this->owned($entry, (int) $userId);

        $body = $this->cleanBody($body);
        if ($body === '' || mb_strlen($body) > PsychologyCatalog::JOURNAL_BODY_MAX) {
            throw new InvalidArgumentException('The journal thoughts are invalid.');
        }

        $allowedPrompts = array_column(app(DigitalJournal::class)->prompts(), 'key');
        if ($allowedPrompts === []) {
            $allowedPrompts = PsychologyCatalog::promptKeys();
        }
        if ($promptKey === '' || !in_array($promptKey, $allowedPrompts, true)) {
            throw new InvalidArgumentException('A journal prompt is required.');
        }

        $title = $this->cleanTitle($title);
        if ($title !== null && mb_strlen($title) > PsychologyCatalog::JOURNAL_TITLE_MAX) {
            throw new InvalidArgumentException('The journal title is too long.');
        }

        $entry->prompt_key = $promptKey;
        $entry->title = $title;
        $entry->body = $body;
        $entry->save();

        return $entry->refresh();
    }

    public function delete(PsychologyJournalEntry $entry): void
    {
        $userId = Auth::guard('user')->id();
        if (!$userId) {
            throw new InvalidArgumentException('Authentication is required.');
        }

        $this->owned($entry, (int) $userId);
        $entry->delete();
    }

    private function persist(int $userId, string $key, string $promptKey, ?string $title, string $body): PsychologyJournalEntry
    {
        return DB::transaction(function () use ($userId, $key, $promptKey, $title, $body) {
            $existing = PsychologyJournalEntry::query()->where('completion_key', $key)->lockForUpdate()->first();
            if ($existing) {
                return $this->owned($existing, $userId);
            }

            $record = new PsychologyJournalEntry([
                'completion_key' => $key,
                'prompt_key' => $promptKey,
                'title' => $title,
                'body' => $body,
                'written_at' => now(),
            ]);
            $record->user_id = $userId;
            $record->save();

            return $record;
        });
    }

    private function existingForUser(string $key, int $userId): PsychologyJournalEntry
    {
        $existing = PsychologyJournalEntry::query()->where('completion_key', $key)->first();
        if (!$existing) {
            throw new RuntimeException('The journal entry could not be saved.');
        }

        return $this->owned($existing, $userId);
    }

    private function owned(PsychologyJournalEntry $record, int $userId): PsychologyJournalEntry
    {
        if ((int) $record->user_id !== $userId) {
            abort(403);
        }

        return $record;
    }

    private function cleanTitle(?string $title): ?string
    {
        if (!is_string($title)) {
            return null;
        }

        $text = trim(str_replace("\0", '', $title));

        return $text === '' ? null : $text;
    }

    private function cleanBody(string $body): string
    {
        return trim(str_replace("\0", '', $body));
    }
}
