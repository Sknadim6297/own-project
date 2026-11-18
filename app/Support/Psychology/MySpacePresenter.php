<?php

namespace App\Support\Psychology;

use App\Models\PsychologyGratitudeEntry;
use App\Models\PsychologyJournalEntry;
use App\Models\PsychologyMoodCheckin;
use App\Services\DigitalJournal;
use App\Services\GratitudeJournal;
use App\Services\MoodCheckin;
use Illuminate\Support\Str;

/** JSON shape of psychology entries for /api/v1/my/*, shared by the read and write endpoints. */
class MySpacePresenter
{
    public const TIMEZONE = 'Asia/Kolkata';

    private const EXCERPT = 160;

    public function __construct(
        private readonly MoodCheckin $moods,
        private readonly DigitalJournal $journal,
        private readonly GratitudeJournal $gratitude,
    ) {
    }

    public function mood(PsychologyMoodCheckin $record): array
    {
        $summary = $this->moods->summary($record->mood_key, $record->emotion_keys ?? []);

        return [
            'id' => $record->id,
            'mood_key' => $record->mood_key,
            'mood_label' => $summary['mood'],
            'emotions' => $summary['emotions'],
            'reflection' => $record->reflection,
            'checked_in_at' => $record->checked_in_at->timezone(self::TIMEZONE)->toIso8601String(),
        ];
    }

    public function journal(PsychologyJournalEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'prompt_key' => $entry->prompt_key,
            'prompt_label' => $this->journal->promptLabel($entry->prompt_key),
            'title' => $entry->title,
            'excerpt' => Str::limit(trim((string) $entry->body), self::EXCERPT),
            'written_at' => $entry->written_at->timezone(self::TIMEZONE)->toIso8601String(),
        ];
    }

    public function gratitude(PsychologyGratitudeEntry $entry): array
    {
        $saved = $this->gratitude->presentSaved($entry);

        return [
            'id' => $entry->id,
            'answers' => collect($saved['cards'])
                ->reject(fn (array $card) => $card['skipped'])
                ->map(fn (array $card) => [
                    'key' => $card['key'],
                    'label' => $card['label'],
                    'excerpt' => Str::limit(trim((string) $card['answer']), self::EXCERPT),
                ])->values(),
            'written_at' => $saved['datetime'],
        ];
    }
}
