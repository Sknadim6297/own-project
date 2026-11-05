<?php

namespace App\Services;

use App\Models\PsychologyJournalEntry;
use App\Models\Tool;
use App\Models\ToolConfigItem;
use App\Support\Psychology\PsychologyCatalog;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

class DigitalJournal
{
    public const HISTORY_PAGE_SIZE = 8;

    public const HISTORY_TIMEZONE = 'Asia/Kolkata';

    public const HISTORY_PREVIEW = 140;

    public function isOpen(): bool
    {
        $tool = $this->tool();

        if (!$tool) {
            return true;
        }

        return !in_array($tool->status, ['inactive', 'unavailable'], true);
    }

    public function promptKeys(): array
    {
        return PsychologyCatalog::promptKeys();
    }

    /**
     * @return array<int, array{key: string, label: string, question: ?string, order: int}>
     */
    public function prompts(): array
    {
        $configured = $this->configuredPrompts();
        $items = [];

        foreach (PsychologyCatalog::prompts() as $prompt) {
            $key = $prompt['item_key'];
            $row = $configured->get($key);
            if ($row && !$row->is_active) {
                continue;
            }

            $items[] = [
                'key' => $key,
                'label' => filled($row?->label) ? $row->label : $prompt['label'],
                'question' => filled($row?->detail) ? $row->detail : ($prompt['detail'] ?? null),
                'order' => $row?->display_order ?? $prompt['display_order'],
            ];
        }

        foreach ($configured as $row) {
            if ($row->is_system || !$row->is_active) {
                continue;
            }
            if (collect($items)->contains(fn (array $item) => $item['key'] === $row->item_key)) {
                continue;
            }
            $items[] = [
                'key' => $row->item_key,
                'label' => $row->label,
                'question' => filled($row->detail) ? $row->detail : null,
                'order' => $row->display_order,
            ];
        }

        usort($items, fn (array $a, array $b) => $a['order'] <=> $b['order']);

        return $items;
    }

    public function promptLabel(?string $key): ?string
    {
        return $this->promptField($key, 'label');
    }

    public function promptQuestion(?string $key): ?string
    {
        return $this->promptField($key, 'question');
    }

    private function promptField(?string $key, string $field): ?string
    {
        if (!is_string($key) || $key === '') {
            return null;
        }

        foreach ($this->prompts() as $prompt) {
            if ($prompt['key'] === $key) {
                $value = $prompt[$field] ?? null;

                return is_string($value) && $value !== '' ? $value : null;
            }
        }

        return null;
    }

    public function historyQuery(int $userId, ?string $search, ?string $month, ?string $promptKey): Builder
    {
        $query = PsychologyJournalEntry::query()
            ->where('user_id', $userId)
            ->orderByDesc('written_at')
            ->orderByDesc('id');

        if (is_string($search) && trim($search) !== '') {
            $needle = trim($search);
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $needle).'%';
            $promptKeys = collect($this->prompts())
                ->filter(fn (array $prompt) => str_contains(mb_strtolower($prompt['label']), mb_strtolower($needle)))
                ->pluck('key')
                ->all();

            $query->where(function (Builder $inner) use ($term, $promptKeys) {
                $inner->where('title', 'like', $term)
                    ->orWhere('body', 'like', $term)
                    ->orWhere('prompt_key', 'like', $term);
                if ($promptKeys !== []) {
                    $inner->orWhereIn('prompt_key', $promptKeys);
                }
            });
        }

        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $start = Carbon::createFromFormat('Y-m-d', $month.'-01', config('app.timezone'))->startOfDay();
            $end = $start->copy()->addMonth();
            $query->where('written_at', '>=', $start)->where('written_at', '<', $end);
        }

        if (is_string($promptKey) && $promptKey !== '') {
            $query->where('prompt_key', $promptKey);
        }

        return $query;
    }

    public function paginateHistory(int $userId, ?string $search, ?string $month, ?string $promptKey, int $page): LengthAwarePaginator
    {
        return $this->historyQuery($userId, $search, $month, $promptKey)
            ->paginate(self::HISTORY_PAGE_SIZE, ['*'], 'page', $page);
    }

    /**
     * @return array<int, string>
     */
    public function availableMonths(int $userId): array
    {
        return PsychologyJournalEntry::query()
            ->where('user_id', $userId)
            ->orderByDesc('written_at')
            ->get(['written_at'])
            ->map(fn (PsychologyJournalEntry $entry) => $entry->written_at->timezone(self::HISTORY_TIMEZONE)->format('Y-m'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  iterable<PsychologyJournalEntry>  $entries
     * @return array<string, array{label: string, items: array<int, array<string, mixed>>}>
     */
    public function groupByMonth(iterable $entries): array
    {
        $groups = [];
        foreach ($entries as $entry) {
            $at = $entry->written_at->timezone(self::HISTORY_TIMEZONE);
            $key = $at->format('Y-m');
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'label' => $at->format('F Y'),
                    'items' => [],
                ];
            }
            $groups[$key]['items'][] = $this->presentCard($entry);
        }

        return $groups;
    }

    /**
     * @return array<string, mixed>
     */
    public function presentCard(PsychologyJournalEntry $entry): array
    {
        $at = $entry->written_at->timezone(self::HISTORY_TIMEZONE);
        $prompt = $this->promptLabel($entry->prompt_key) ?? 'Journal entry';
        $title = filled($entry->title) ? $entry->title : $prompt;
        $preview = trim(preg_replace('/\s+/u', ' ', (string) $entry->body) ?? '');

        return [
            'id' => $entry->id,
            'title' => $title,
            'prompt' => $prompt,
            'preview' => mb_strlen($preview) > self::HISTORY_PREVIEW
                ? mb_substr($preview, 0, self::HISTORY_PREVIEW - 1).'…'
                : $preview,
            'when' => $at->format('j M Y'),
            'datetime' => $at->toIso8601String(),
        ];
    }

    private function configuredPrompts()
    {
        try {
            $tool = $this->tool();
            if (!$tool || !Schema::hasTable('tool_config_items')) {
                return collect();
            }

            return ToolConfigItem::query()
                ->where('tool_id', $tool->id)
                ->where('group_key', 'journal_prompt')
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

            return Tool::query()->where('slug', 'digital-journal')->first();
        } catch (QueryException) {
            return null;
        }
    }
}
