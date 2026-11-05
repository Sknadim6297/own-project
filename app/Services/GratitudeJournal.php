<?php

namespace App\Services;

use App\Models\PsychologyGratitudeEntry;
use App\Models\Tool;
use App\Models\ToolConfigItem;
use App\Support\Psychology\PsychologyCatalog;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

class GratitudeJournal
{
    public const TIMEZONE = 'Asia/Kolkata';

    public const HISTORY_PAGE_SIZE = 8;

    public function isOpen(): bool
    {
        $tool = $this->tool();

        if (!$tool) {
            return true;
        }

        return !in_array($tool->status, ['inactive', 'unavailable'], true);
    }

    /**
     * @return array<int, array{key: string, label: string, hint: ?string, question: ?string, order: int}>
     */
    public function cards(): array
    {
        $configured = $this->configuredCards();
        $items = [];

        foreach (PsychologyCatalog::cards() as $card) {
            $key = $card['item_key'];
            $row = $configured->get($key);
            if ($row && !$row->is_active) {
                continue;
            }

            $items[] = [
                'key' => $key,
                'label' => filled($row?->label) ? $row->label : $card['label'],
                'hint' => filled($row?->hint) ? $row->hint : ($card['hint'] ?? null),
                'question' => filled($row?->detail) ? $row->detail : ($card['detail'] ?? null),
                'order' => $row?->display_order ?? $card['display_order'],
            ];
        }

        usort($items, fn (array $a, array $b) => $a['order'] <=> $b['order']);

        return array_slice($items, 0, PsychologyCatalog::GRATITUDE_CARDS);
    }

    /**
     * @return array<int, string>
     */
    public function cardKeys(): array
    {
        return array_column($this->cards(), 'key');
    }

    public function todayLabel(): string
    {
        return now(self::TIMEZONE)->format('l, j F Y');
    }

    /**
     * @return array{when: string, datetime: string, cards: array<int, array{key: string, label: string, answer: ?string, skipped: bool}>}
     */
    public function presentSaved(PsychologyGratitudeEntry $entry): array
    {
        $at = $entry->written_at->timezone(self::TIMEZONE);
        $answers = [
            'something-good-today' => $entry->something_good_today,
            'someone-i-appreciate' => $entry->someone_i_appreciate,
            'something-about-myself' => $entry->something_about_myself,
        ];

        $cards = [];
        foreach ($this->cards() as $card) {
            $raw = $answers[$card['key']] ?? null;
            $answer = is_string($raw) && trim($raw) !== '' ? $raw : null;
            $cards[] = [
                'key' => $card['key'],
                'label' => $card['label'],
                'answer' => $answer,
                'skipped' => $answer === null,
            ];
        }

        if ($cards === []) {
            foreach (PsychologyCatalog::cards() as $card) {
                $raw = $answers[$card['item_key']] ?? null;
                $answer = is_string($raw) && trim($raw) !== '' ? $raw : null;
                $cards[] = [
                    'key' => $card['item_key'],
                    'label' => $card['label'],
                    'answer' => $answer,
                    'skipped' => $answer === null,
                ];
            }
        }

        return [
            'when' => $at->format('l, j F Y'),
            'datetime' => $at->toIso8601String(),
            'cards' => $cards,
        ];
    }

    public function totalEntries(int $userId): int
    {
        return PsychologyGratitudeEntry::query()->where('user_id', $userId)->count();
    }

    public function daysInMonth(int $userId, string $month): int
    {
        return count($this->entryDatesInMonth($userId, $month));
    }

    /**
     * @return array<int, string> Y-m-d dates in TIMEZONE
     */
    public function entryDatesInMonth(int $userId, string $month): array
    {
        [$start, $end] = $this->monthBounds($month);

        return PsychologyGratitudeEntry::query()
            ->where('user_id', $userId)
            ->where('written_at', '>=', $start)
            ->where('written_at', '<', $end)
            ->orderBy('written_at')
            ->get(['written_at'])
            ->map(fn (PsychologyGratitudeEntry $entry) => $entry->written_at->timezone(self::TIMEZONE)->format('Y-m-d'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     month: string,
     *     label: string,
     *     previous: string,
     *     next: string,
     *     weeks: array<int, array<int, array{day: ?int, date: ?string, has_entry: bool, is_today: bool}>>
     * }
     */
    public function calendar(int $userId, string $month): array
    {
        $cursor = Carbon::createFromFormat('Y-m', $month, self::TIMEZONE)->startOfMonth();
        $marked = array_fill_keys($this->entryDatesInMonth($userId, $month), true);
        $today = now(self::TIMEZONE)->toDateString();
        // Monday-first: dayOfWeekIso 1=Mon … 7=Sun
        $startPad = ((int) $cursor->dayOfWeekIso) - 1;
        $daysInMonth = $cursor->daysInMonth;
        $cells = [];

        for ($i = 0; $i < $startPad; $i++) {
            $cells[] = ['day' => null, 'date' => null, 'has_entry' => false, 'is_today' => false];
        }

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $cursor->copy()->day($day)->toDateString();
            $cells[] = [
                'day' => $day,
                'date' => $date,
                'has_entry' => isset($marked[$date]),
                'is_today' => $date === $today,
            ];
        }

        while (count($cells) % 7 !== 0) {
            $cells[] = ['day' => null, 'date' => null, 'has_entry' => false, 'is_today' => false];
        }

        return [
            'month' => $month,
            'label' => $cursor->format('F Y'),
            'previous' => $cursor->copy()->subMonth()->format('Y-m'),
            'next' => $cursor->copy()->addMonth()->format('Y-m'),
            'weeks' => array_chunk($cells, 7),
        ];
    }

    public function paginateHistory(int $userId, int $page): LengthAwarePaginator
    {
        return PsychologyGratitudeEntry::query()
            ->where('user_id', $userId)
            ->orderByDesc('written_at')
            ->orderByDesc('id')
            ->paginate(self::HISTORY_PAGE_SIZE, ['*'], 'page', $page);
    }

    /**
     * @return array<string, mixed>
     */
    public function presentHistoryCard(PsychologyGratitudeEntry $entry): array
    {
        $at = $entry->written_at->timezone(self::TIMEZONE);
        $summary = $this->presentSaved($entry);
        $answered = array_values(array_filter(
            $summary['cards'],
            fn (array $card) => !$card['skipped']
        ));

        return [
            'id' => $entry->id,
            'when' => $at->format('j M Y'),
            'datetime' => $at->toIso8601String(),
            'categories' => array_column($answered, 'label'),
            'answered_count' => count($answered),
        ];
    }

    private function monthBounds(string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month, self::TIMEZONE)->startOfMonth();
        $end = $start->copy()->addMonth();

        return [$start->clone()->timezone(config('app.timezone')), $end->clone()->timezone(config('app.timezone'))];
    }

    private function configuredCards()
    {
        try {
            $tool = $this->tool();
            if (!$tool || !Schema::hasTable('tool_config_items')) {
                return collect();
            }

            return ToolConfigItem::query()
                ->where('tool_id', $tool->id)
                ->where('group_key', 'gratitude_card')
                ->get()
                ->keyBy('item_key');
        } catch (QueryException) {
            return collect();
        }
    }

    private function tool(): ?Tool
    {
        try {
            if (!Schema::hasTable('tools')) {
                return null;
            }

            return Tool::query()->where('slug', 'gratitude-journal')->first();
        } catch (QueryException) {
            return null;
        }
    }
}
