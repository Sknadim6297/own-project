<?php

namespace App\Support\Psychology;

class PsychologyCatalog
{
    public const PRIMARY_MOOD_SELECTION = 1;

    public const MAX_EMOTIONS = 6;

    public const REFLECTION_MAX = 1000;

    public const GRATITUDE_CARDS = 3;

    public const GRATITUDE_MIN_ANSWERED = 1;

    public const GRATITUDE_ANSWER_MAX = 1000;

    public const JOURNAL_TITLE_MAX = 120;

    public const JOURNAL_BODY_MAX = 5000;

    public const NOTES = [
        'mood' => [
            'Exactly one primary mood is stored for each check-in.',
            'A member can select at most six emotions across Positive, Neutral, and Difficult.',
            'Reflection is optional and limited to 1,000 characters.',
            'These labels are not a diagnosis or a medical conclusion.',
        ],
        'journal' => [
            'The title is optional.',
            'Journal thoughts are required.',
            'A prompt can suggest a title, and the member can edit that title.',
            'Entries stay private to the member who wrote them.',
            'The member can edit or delete their own entry.',
        ],
        'gratitude' => [
            'The experience uses three cards.',
            'Each card is optional.',
            'Next is used when a card has an answer.',
            'Skip is used when a card is blank.',
            'Finish & Save stores the entry.',
            'At least one answered card is required to save.',
            'Responses stay private to the member.',
        ],
    ];

    public static function tabFor(string $slug): ?string
    {
        return [
            'daily-mood-check-in' => 'mood',
            'digital-journal' => 'journal',
            'gratitude-journal' => 'gratitude',
        ][$slug] ?? null;
    }

    public static function definitions(): array
    {
        return [
            'daily-mood-check-in' => array_merge(self::moods(), self::emotions()),
            'digital-journal' => self::prompts(),
            'gratitude-journal' => self::cards(),
        ];
    }

    public static function moodKeys(): array
    {
        return array_column(self::moods(), 'item_key');
    }

    public static function emotionKeys(): array
    {
        return array_column(self::emotions(), 'item_key');
    }

    public static function promptKeys(): array
    {
        return array_column(self::prompts(), 'item_key');
    }

    public static function cardKeys(): array
    {
        return array_column(self::cards(), 'item_key');
    }

    public static function moods(): array
    {
        return [
            self::item('primary_mood', 'very-happy', 'Very happy', 1),
            self::item('primary_mood', 'good', 'Good', 2),
            self::item('primary_mood', 'neutral', 'Neutral', 3),
            self::item('primary_mood', 'sad', 'Sad', 4),
            self::item('primary_mood', 'stressed', 'Stressed', 5),
        ];
    }

    public static function emotions(): array
    {
        $groups = [
            'positive' => ['Happy', 'Calm', 'Excited', 'Loved', 'Grateful'],
            'neutral' => ['Neutral', 'Thoughtful', 'Quiet', 'Tired', 'Unsure'],
            'difficult' => ['Worried', 'Sad', 'Angry', 'Lonely', 'Anxious'],
        ];
        $items = [];
        foreach ($groups as $bucket => $labels) {
            foreach ($labels as $index => $label) {
                $items[] = self::item('emotion', strtolower($label), $label, $index + 1, $bucket);
            }
        }

        return $items;
    }

    public static function prompts(): array
    {
        return [
            self::item('journal_prompt', 'my-thoughts', 'My thoughts', 1, null, null, 'What is on your mind right now?'),
            self::item('journal_prompt', 'personal-growth', 'Personal growth', 2, null, null, 'How are you growing or changing lately?'),
            self::item('journal_prompt', 'work-career', 'Work & career', 3, null, null, 'What feels important about work or your career today?'),
            self::item('journal_prompt', 'relationships', 'Relationships', 4, null, null, 'What stands out in your relationships right now?'),
            self::item('journal_prompt', 'difficult-moments', 'Difficult moments', 5, null, null, 'What has felt hard, and what do you need?'),
            self::item('journal_prompt', 'my-aspirations', 'My aspirations', 6, null, null, 'What are you hoping for, near or far?'),
        ];
    }

    public static function cards(): array
    {
        return [
            self::item(
                'gratitude_card',
                'something-good-today',
                'Something good today',
                1,
                null,
                'A little moment that made you smile.',
                'What was one good thing about your day?'
            ),
            self::item(
                'gratitude_card',
                'someone-i-appreciate',
                'Someone I appreciate',
                2,
                null,
                'This can be someone you know, or someone you noticed.',
                'Who is someone you appreciate?'
            ),
            self::item(
                'gratitude_card',
                'something-about-myself',
                'Something about myself',
                3,
                null,
                'Notice one quality, effort, or choice of your own.',
                'What is something you appreciate about yourself?'
            ),
        ];
    }

    private static function item(
        string $group,
        string $key,
        string $label,
        int $order,
        ?string $bucket = null,
        ?string $hint = null,
        ?string $detail = null
    ): array {
        return [
            'group_key' => $group,
            'item_key' => $key,
            'label' => $label,
            'bucket' => $bucket,
            'hint' => $hint,
            'detail' => $detail,
            'display_order' => $order,
            'is_system' => true,
            'is_active' => true,
        ];
    }
}
