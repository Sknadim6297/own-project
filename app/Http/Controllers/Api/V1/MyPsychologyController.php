<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DigitalJournal;
use App\Services\GratitudeJournal;
use App\Services\GratitudeRecorder;
use App\Services\JournalRecorder;
use App\Services\MoodCheckin;
use App\Services\MoodCheckinRecorder;
use App\Support\Psychology\MySpacePresenter;
use App\Support\Psychology\PsychologyCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Mood check-in, digital journal and gratitude journal from the app.
 * Saves go through the same recorders as the website, so validation, the
 * completion-key idempotency and Yukti reward hooks are identical.
 * The app sends a fresh UUID per entry; retrying with it never duplicates
 * the entry or the reward, and another customer's key is rejected (403).
 */
class MyPsychologyController extends Controller
{
    public function options(Request $request, MoodCheckin $moods, DigitalJournal $journal, GratitudeJournal $gratitude): JsonResponse
    {
        $this->customer($request);

        return response()->json([
            'status' => 'success',
            'mood' => [
                'open' => $moods->isOpen(),
                'moods' => collect($moods->options())->map(fn (array $option) => [
                    'key' => $option['key'],
                    'label' => $option['label'],
                    'caption' => $option['caption'],
                    'tone' => $option['tone'],
                ])->values(),
                'emotion_groups' => collect($moods->emotionGroups())->map(fn (array $items, string $tone) => [
                    'tone' => $tone,
                    'emotions' => collect($items)->map(fn (array $item) => [
                        'key' => $item['key'],
                        'label' => $item['label'],
                    ])->values(),
                ])->values(),
                'min_emotions' => 1,
                'max_emotions' => PsychologyCatalog::MAX_EMOTIONS,
                'reflection_max' => PsychologyCatalog::REFLECTION_MAX,
            ],
            'journal' => [
                'open' => $journal->isOpen(),
                'prompts' => collect($journal->prompts())->map(fn (array $prompt) => [
                    'key' => $prompt['key'],
                    'label' => $prompt['label'],
                    'question' => $prompt['question'],
                ])->values(),
                'title_max' => PsychologyCatalog::JOURNAL_TITLE_MAX,
                'body_max' => PsychologyCatalog::JOURNAL_BODY_MAX,
            ],
            'gratitude' => [
                'open' => $gratitude->isOpen(),
                'cards' => collect($gratitude->cards())->map(fn (array $card) => [
                    'key' => $card['key'],
                    'label' => $card['label'],
                    'hint' => $card['hint'],
                    'question' => $card['question'],
                ])->values(),
                'min_answered' => PsychologyCatalog::GRATITUDE_MIN_ANSWERED,
                'answer_max' => PsychologyCatalog::GRATITUDE_ANSWER_MAX,
            ],
        ]);
    }

    public function storeMood(Request $request, MoodCheckin $moods, MoodCheckinRecorder $recorder, MySpacePresenter $present): JsonResponse
    {
        $user = $this->customer($request);
        if (! $moods->isOpen()) {
            return $this->closed();
        }

        $data = $request->validate([
            'completion_key' => ['required', 'uuid'],
            'mood' => ['required', 'string', Rule::in($moods->keys())],
            'emotions' => ['required', 'array', 'min:1', 'max:'.PsychologyCatalog::MAX_EMOTIONS],
            'emotions.*' => ['string', 'distinct', Rule::in($moods->emotionKeys())],
            'reflection' => ['nullable', 'string', 'max:'.PsychologyCatalog::REFLECTION_MAX],
        ]);
        $text = str_replace("\0", '', (string) ($data['reflection'] ?? ''));
        $reflection = trim($text) === '' ? null : $text;

        return $this->saved(
            fn () => $recorder->record(
                (int) $user->id,
                strtolower($data['completion_key']),
                $data['mood'],
                array_values($data['emotions']),
                $reflection,
            ),
            fn ($record) => $present->mood($record),
        );
    }

    public function storeJournal(Request $request, DigitalJournal $journal, JournalRecorder $recorder, MySpacePresenter $present): JsonResponse
    {
        $user = $this->customer($request);
        if (! $journal->isOpen()) {
            return $this->closed();
        }

        $prompts = array_column($journal->prompts(), 'key') ?: PsychologyCatalog::promptKeys();
        $data = $request->validate([
            'completion_key' => ['required', 'uuid'],
            'prompt_key' => ['required', 'string', Rule::in($prompts)],
            'title' => ['nullable', 'string', 'max:'.PsychologyCatalog::JOURNAL_TITLE_MAX],
            'body' => ['required', 'string', 'max:'.PsychologyCatalog::JOURNAL_BODY_MAX],
        ]);

        return $this->saved(
            fn () => $recorder->record(
                (int) $user->id,
                strtolower($data['completion_key']),
                $data['prompt_key'],
                $data['title'] ?? null,
                $data['body'],
            ),
            fn ($record) => $present->journal($record),
        );
    }

    public function storeGratitude(Request $request, GratitudeJournal $gratitude, GratitudeRecorder $recorder, MySpacePresenter $present): JsonResponse
    {
        $user = $this->customer($request);
        if (! $gratitude->isOpen()) {
            return $this->closed();
        }

        $cards = $gratitude->cardKeys() ?: PsychologyCatalog::cardKeys();
        $data = $request->validate([
            'completion_key' => ['required', 'uuid'],
            'answers' => ['required', 'array:'.implode(',', $cards)],
            'answers.*' => ['nullable', 'string', 'max:'.PsychologyCatalog::GRATITUDE_ANSWER_MAX],
        ]);
        $answers = array_filter($data['answers'], fn ($value) => is_string($value));

        return $this->saved(
            fn () => $recorder->record((int) $user->id, strtolower($data['completion_key']), $answers),
            fn ($record) => $present->gratitude($record),
        );
    }

    /**
     * @param  callable(): Model  $save
     * @param  callable(Model): array  $present
     */
    private function saved(callable $save, callable $present): JsonResponse
    {
        try {
            $record = $save();
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['entry' => $exception->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'created' => $record->wasRecentlyCreated,
            'entry' => $present($record),
        ], $record->wasRecentlyCreated ? 201 : 200);
    }

    private function closed(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'This tool is not available right now.',
        ], 403);
    }

    private function customer(Request $request): User
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
