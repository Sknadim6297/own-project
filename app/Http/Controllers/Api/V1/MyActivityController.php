<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PsychologyGratitudeEntry;
use App\Models\PsychologyJournalEntry;
use App\Models\PsychologyMoodCheckin;
use App\Models\User;
use App\Services\MoodCheckin;
use App\Support\Psychology\MySpacePresenter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Read-only psychology activity (mood check-ins, digital journal, gratitude journal).
 * Entries are only created through the shared recorders (website flow or
 * MyPsychologyController), which also handle Yukti rewards; this controller never writes.
 *
 * Calendar days follow the website history pages (Asia/Kolkata); stored timestamps are UTC.
 */
class MyActivityController extends Controller
{
    private const TIMEZONE = MySpacePresenter::TIMEZONE;

    private const DEFAULT_DAYS = 30;

    private const MAX_DAYS = 366;

    public function summary(Request $request, MoodCheckin $moods): JsonResponse
    {
        $user = $this->customer($request);
        [$from, $to] = $this->range($request);

        $checkins = $this->within(PsychologyMoodCheckin::query()->where('user_id', $user->id), 'checked_in_at', $from, $to)
            ->orderBy('checked_in_at')
            ->get(['mood_key', 'checked_in_at']);

        $options = collect($moods->options());
        $distribution = $options->map(fn (array $option) => [
            'key' => $option['key'],
            'label' => $option['label'],
            'tone' => $option['tone'],
            'count' => $checkins->where('mood_key', $option['key'])->count(),
        ])->values();

        $byDay = $checkins->groupBy(fn ($row) => $row->checked_in_at->timezone(self::TIMEZONE)->toDateString());
        $latest = PsychologyMoodCheckin::query()->where('user_id', $user->id)->orderByDesc('checked_in_at')->orderByDesc('id')->first();

        return response()->json([
            'status' => 'success',
            'range' => $this->rangePayload($from, $to),
            'mood' => [
                'count' => $checkins->count(),
                'active_days' => $byDay->count(),
                'distribution' => $distribution,
                'daily' => $byDay->map(fn ($rows, $day) => [
                    'date' => $day,
                    'count' => $rows->count(),
                    'mood_key' => $rows->last()->mood_key,
                ])->values(),
                'latest' => $latest ? [
                    'key' => $latest->mood_key,
                    'label' => $moods->summary($latest->mood_key, [])['mood'],
                    'checked_in_at' => $latest->checked_in_at->timezone(self::TIMEZONE)->toIso8601String(),
                ] : null,
            ],
            'journal' => [
                'count' => $this->within(PsychologyJournalEntry::query()->where('user_id', $user->id), 'written_at', $from, $to)->count(),
            ],
            'gratitude' => [
                'count' => $this->within(PsychologyGratitudeEntry::query()->where('user_id', $user->id), 'written_at', $from, $to)->count(),
            ],
            'all_time' => [
                'mood' => PsychologyMoodCheckin::query()->where('user_id', $user->id)->count(),
                'journal' => PsychologyJournalEntry::query()->where('user_id', $user->id)->count(),
                'gratitude' => PsychologyGratitudeEntry::query()->where('user_id', $user->id)->count(),
            ],
        ]);
    }

    public function moods(Request $request, MySpacePresenter $present): JsonResponse
    {
        $user = $this->customer($request);
        [$from, $to] = $this->range($request);

        $page = $this->within(PsychologyMoodCheckin::query()->where('user_id', $user->id), 'checked_in_at', $from, $to)
            ->orderByDesc('checked_in_at')
            ->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return response()->json([
            'status' => 'success',
            'range' => $this->rangePayload($from, $to),
            'entries' => $page->getCollection()->map(fn (PsychologyMoodCheckin $record) => $present->mood($record))->values(),
            'meta' => $this->meta($page),
        ]);
    }

    public function journal(Request $request, MySpacePresenter $present): JsonResponse
    {
        $user = $this->customer($request);
        [$from, $to] = $this->range($request);

        $page = $this->within(PsychologyJournalEntry::query()->where('user_id', $user->id), 'written_at', $from, $to)
            ->orderByDesc('written_at')
            ->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return response()->json([
            'status' => 'success',
            'range' => $this->rangePayload($from, $to),
            'entries' => $page->getCollection()->map(fn (PsychologyJournalEntry $entry) => $present->journal($entry))->values(),
            'meta' => $this->meta($page),
        ]);
    }

    public function gratitude(Request $request, MySpacePresenter $present): JsonResponse
    {
        $user = $this->customer($request);
        [$from, $to] = $this->range($request);

        $page = $this->within(PsychologyGratitudeEntry::query()->where('user_id', $user->id), 'written_at', $from, $to)
            ->orderByDesc('written_at')
            ->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return response()->json([
            'status' => 'success',
            'range' => $this->rangePayload($from, $to),
            'entries' => $page->getCollection()->map(fn (PsychologyGratitudeEntry $entry) => $present->gratitude($entry))->values(),
            'meta' => $this->meta($page),
        ]);
    }

    private function customer(Request $request): User
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable} Inclusive local calendar days.
     */
    private function range(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $to = isset($data['to'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $data['to'], self::TIMEZONE)->startOfDay()
            : CarbonImmutable::now(self::TIMEZONE)->startOfDay();
        $from = isset($data['from'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $data['from'], self::TIMEZONE)->startOfDay()
            : $to->subDays(self::DEFAULT_DAYS - 1);

        if ($from->greaterThan($to)) {
            throw ValidationException::withMessages(['from' => 'The start date must be on or before the end date.']);
        }
        if ($from->diffInDays($to) + 1 > self::MAX_DAYS) {
            throw ValidationException::withMessages(['from' => 'The date range can cover at most '.self::MAX_DAYS.' days.']);
        }

        return [$from, $to];
    }

    private function within(Builder $query, string $column, CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return $query
            ->where($column, '>=', $from->utc()->toDateTimeString())
            ->where($column, '<', $to->addDay()->utc()->toDateTimeString());
    }

    private function rangePayload(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'timezone' => self::TIMEZONE,
        ];
    }

    private function perPage(Request $request): int
    {
        $data = $request->validate(['per_page' => ['nullable', 'integer', 'min:1', 'max:50']]);

        return (int) ($data['per_page'] ?? 20);
    }

    private function meta($page): array
    {
        return [
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
        ];
    }
}
