<?php

namespace App\Support\ParentingSpirituality;

class PsCatalog
{
    public const CONTENT_VERSION = 'ps-content-v3';

    public const TOOLS = [
        'parenting-stress-check' => [
            'name' => 'Parenting Stress Check',
            'slug' => 'parenting-stress-check',
            'route' => 'tools.ps.stress-check',
            'category' => 'parenting',
            'category_label' => 'Parenting',
            'description' => 'A short self-check for overwhelm, exhaustion, guilt and support gaps.',
            'icon' => 'ri-heart-pulse-line',
            'display_order' => 1,
            'module_key' => 'parenting',
        ],
        'child-routine-planner' => [
            'name' => 'Child Routine Planner',
            'slug' => 'child-routine-planner',
            'route' => 'tools.ps.routine-planner',
            'category' => 'parenting',
            'category_label' => 'Parenting',
            'description' => 'Build a practical daily routine around fixed commitments and preferences.',
            'icon' => 'ri-calendar-schedule-line',
            'display_order' => 2,
            'module_key' => 'parenting',
        ],
        'parent-child-conversation-guide' => [
            'name' => 'Parent–Child Conversation Guide',
            'slug' => 'parent-child-conversation-guide',
            'route' => 'tools.ps.conversation-guide',
            'category' => 'parenting',
            'category_label' => 'Parenting',
            'description' => 'Get calm, age-aware guidance for difficult parent–child conversations.',
            'icon' => 'ri-chat-heart-line',
            'display_order' => 3,
            'module_key' => 'parenting',
        ],
        'daily-mindfulness-calm' => [
            'name' => 'Daily Mindfulness & Calm',
            'slug' => 'daily-mindfulness-calm',
            'route' => 'tools.ps.mindfulness',
            'category' => 'spirituality',
            'category_label' => 'Spirituality',
            'description' => 'Short secular grounding practices with a 3, 5 or 10-minute timer.',
            'icon' => 'ri-leaf-line',
            'display_order' => 1,
            'module_key' => 'spirituality',
        ],
        'purpose-values-finder' => [
            'name' => 'Purpose & Values Finder',
            'slug' => 'purpose-values-finder',
            'route' => 'tools.ps.values-finder',
            'category' => 'spirituality',
            'category_label' => 'Spirituality',
            'description' => 'Identify core personal values and translate them into practical actions.',
            'icon' => 'ri-compass-3-line',
            'display_order' => 2,
            'module_key' => 'spirituality',
        ],
    ];

    public const DISCLAIMER = 'This tool is for self-reflection and general guidance. It is not a diagnosis or a substitute for professional care.';

    /**
     * @return list<string>
     */
    public static function toolKeys(): array
    {
        return array_keys(self::TOOLS);
    }

    public static function tool(string $key): ?array
    {
        return self::TOOLS[$key] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function toolsForCategory(string $category): array
    {
        return array_filter(self::TOOLS, fn (array $tool) => ($tool['category'] ?? '') === $category);
    }
}
