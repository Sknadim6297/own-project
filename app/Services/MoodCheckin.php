<?php

namespace App\Services;

use App\Models\Tool;
use App\Models\ToolConfigItem;
use App\Support\Psychology\MoodFollowUpCatalog;
use App\Support\Psychology\PsychologyCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

class MoodCheckin
{
    public const CAPTIONS = [
        'very-happy' => 'Feeling great',
        'good' => 'Feeling positive',
        'neutral' => 'Neither good nor bad',
        'sad' => 'Feeling low',
        'stressed' => 'Feeling overwhelmed',
    ];

    public function isOpen(): bool
    {
        $tool = $this->tool();

        if (!$tool) {
            return true;
        }

        return !in_array($tool->status, ['inactive', 'unavailable'], true);
    }

    public function keys(): array
    {
        return PsychologyCatalog::moodKeys();
    }

    public function emotionKeys(): array
    {
        return PsychologyCatalog::emotionKeys();
    }

    /**
     * @return list<string>
     */
    public function emotionKeysForMood(string $moodKey): array
    {
        if (! MoodFollowUpCatalog::hasMood($moodKey)) {
            return [];
        }

        $allowed = MoodFollowUpCatalog::emotionKeysForMood($moodKey);
        $catalog = $this->emotionKeys();

        return array_values(array_filter(
            $allowed,
            fn (string $key) => in_array($key, $catalog, true)
        ));
    }

    /**
     * @return array{emotion_heading: string, emotion_lead: string, reflection_question: string, reflection_prompts: list<string>, complete_tip: string}
     */
    public function followUpForMood(string $moodKey): array
    {
        $set = MoodFollowUpCatalog::forMood($moodKey);

        return [
            'emotion_heading' => $set['emotion_heading'],
            'emotion_lead' => $set['emotion_lead'],
            'reflection_question' => $set['reflection_question'],
            'reflection_prompts' => $set['reflection_prompts'],
            'complete_tip' => $set['complete_tip'],
        ];
    }

    public function emotionGroups(?string $moodKey = null): array
    {
        $configured = $this->configuredEmotions();
        $groups = [
            'positive' => [],
            'neutral' => [],
            'difficult' => [],
        ];
        $allowed = $moodKey !== null ? $this->emotionKeysForMood($moodKey) : null;

        foreach (PsychologyCatalog::emotions() as $emotion) {
            $key = $emotion['item_key'];
            $bucket = $emotion['bucket'];
            if (!isset($groups[$bucket])) {
                continue;
            }
            if (is_array($allowed) && ! in_array($key, $allowed, true)) {
                continue;
            }

            $row = $configured->get($key);
            $groups[$bucket][] = [
                'key' => $key,
                'label' => filled($row?->label) ? $row->label : $emotion['label'],
                'bucket' => $bucket,
                'order' => $row?->display_order ?? $emotion['display_order'],
            ];
        }

        foreach ($groups as $bucket => $items) {
            usort($items, fn (array $a, array $b) => $a['order'] <=> $b['order']);
            $groups[$bucket] = $items;
        }

        return $groups;
    }

    /**
     * Keep only emotion keys that belong to the selected mood's follow-up set.
     *
     * @param  list<string>  $emotionKeys
     * @return list<string>
     */
    public function filterEmotionsForMood(string $moodKey, array $emotionKeys): array
    {
        $allowed = $this->emotionKeysForMood($moodKey);

        return array_values(array_filter(
            array_unique($emotionKeys),
            fn ($key) => is_string($key) && in_array($key, $allowed, true)
        ));
    }

    public function summary(string $moodKey, array $emotionKeys): array
    {
        $moods = collect($this->options())->keyBy('key');
        // Full catalogue so historical entries stay readable after mood-specific filtering.
        $emotions = collect($this->emotionGroups())->flatten(1)->keyBy('key');
        $chips = [];
        foreach ($emotionKeys as $key) {
            if (!is_string($key) || !$emotions->has($key)) {
                continue;
            }
            $chips[] = [
                'key' => $key,
                'label' => $emotions[$key]['label'],
                'tone' => $emotions[$key]['bucket'] ?? $this->emotionTone($key),
            ];
        }

        $tip = null;
        if (MoodFollowUpCatalog::hasMood($moodKey)) {
            $tip = MoodFollowUpCatalog::forMood($moodKey)['complete_tip'];
        }

        return [
            'mood' => $moods->has($moodKey) ? $moods[$moodKey]['label'] : null,
            'emotions' => $chips,
            'tip' => $tip,
        ];
    }

    private function emotionTone(string $key): string
    {
        foreach (PsychologyCatalog::emotions() as $emotion) {
            if ($emotion['item_key'] === $key) {
                return $emotion['bucket'] ?? 'neutral';
            }
        }

        return 'neutral';
    }

    public function options(): array
    {
        $configured = $this->configured();
        $options = [];

        foreach (PsychologyCatalog::moods() as $mood) {
            $key = $mood['item_key'];
            $row = $configured->get($key);
            $options[] = [
                'key' => $key,
                'label' => filled($row?->label) ? $row->label : $mood['label'],
                'caption' => self::CAPTIONS[$key],
                'tone' => in_array($key, ['sad', 'stressed'], true) ? 'difficult' : ($key === 'neutral' ? 'neutral' : 'positive'),
                'order' => $row?->display_order ?? $mood['display_order'],
            ];
        }

        usort($options, fn (array $a, array $b) => $a['order'] <=> $b['order']);

        return $options;
    }

    private function tool(): ?Tool
    {
        try {
            if (!Schema::hasTable('tools')) {
                return null;
            }

            return Tool::query()->where('slug', 'daily-mood-check-in')->first();
        } catch (QueryException) {
            return null;
        }
    }

    private function configured()
    {
        try {
            $tool = $this->tool();
            if (!$tool || !Schema::hasTable('tool_config_items')) {
                return collect();
            }

            return $this->configRows('primary_mood', $this->keys());
        } catch (QueryException) {
            return collect();
        }
    }

    private function configuredEmotions()
    {
        try {
            $tool = $this->tool();
            if (!$tool || !Schema::hasTable('tool_config_items')) {
                return collect();
            }

            return $this->configRows('emotion', $this->emotionKeys());
        } catch (QueryException) {
            return collect();
        }
    }

    private function configRows(string $group, array $keys)
    {
        $tool = $this->tool();

        return ToolConfigItem::query()
            ->where('tool_id', $tool->id)
            ->where('group_key', $group)
            ->where('is_active', true)
            ->whereIn('item_key', $keys)
            ->get()
            ->keyBy('item_key');
    }
}
