<?php

namespace App\Support\SkillsStrengths;

class SkillsStrengthsCatalog
{
    public const ASSESSMENT_VERSION = 'ssa-2026.11';

    /** Implementation convention: round(((sum_of_3 - 3) / 12) * 100). Not a validated ability measure. */
    public const SCORING_VERSION = 'skills-sum3shift12-pct-v1';

    public const MIN_CATEGORY_SCORE = 3;

    public const MAX_CATEGORY_SCORE = 15;

    public const CATEGORY_ORDER = [
        'communication',
        'analytical',
        'problem-solving',
        'digital',
        'leadership',
        'organisation',
        'creativity',
        'business',
    ];

    public const SCALE = [
        1 => 'Strongly Disagree',
        2 => 'Disagree',
        3 => 'Neutral',
        4 => 'Agree',
        5 => 'Strongly Agree',
    ];

    public static function categories(): array
    {
        return [
            'communication' => [
                'key' => 'communication',
                'label' => 'Communication',
                'short' => 'Communication',
                'number' => '01',
                'step' => 1,
                'range_label' => 'Questions 1–3 of 24',
            ],
            'analytical' => [
                'key' => 'analytical',
                'label' => 'Analytical Thinking',
                'short' => 'Analytical',
                'number' => '02',
                'step' => 2,
                'range_label' => 'Questions 4–6 of 24',
            ],
            'problem-solving' => [
                'key' => 'problem-solving',
                'label' => 'Problem Solving',
                'short' => 'Problem Solving',
                'number' => '03',
                'step' => 3,
                'range_label' => 'Questions 7–9 of 24',
            ],
            'digital' => [
                'key' => 'digital',
                'label' => 'Digital Skills',
                'short' => 'Digital',
                'number' => '04',
                'step' => 4,
                'range_label' => 'Questions 10–12 of 24',
            ],
            'leadership' => [
                'key' => 'leadership',
                'label' => 'Leadership',
                'short' => 'Leadership',
                'number' => '05',
                'step' => 5,
                'range_label' => 'Questions 13–15 of 24',
            ],
            'organisation' => [
                'key' => 'organisation',
                'label' => 'Organisation',
                'short' => 'Organisation',
                'number' => '06',
                'step' => 6,
                'range_label' => 'Questions 16–18 of 24',
            ],
            'creativity' => [
                'key' => 'creativity',
                'label' => 'Creativity',
                'short' => 'Creativity',
                'number' => '07',
                'step' => 7,
                'range_label' => 'Questions 19–21 of 24',
            ],
            'business' => [
                'key' => 'business',
                'label' => 'Business Skills',
                'short' => 'Business',
                'number' => '08',
                'step' => 8,
                'range_label' => 'Questions 22–24 of 24',
            ],
        ];
    }

    /**
     * Exact PDF wording. Stable IDs are keys.
     */
    public static function questions(): array
    {
        return [
            'c1' => [
                'id' => 'c1',
                'category' => 'communication',
                'number' => 1,
                'text' => 'I can clearly express my ideas and opinions.',
            ],
            'c2' => [
                'id' => 'c2',
                'category' => 'communication',
                'number' => 2,
                'text' => 'I am a good listener and understand others’ points of view.',
            ],
            'c3' => [
                'id' => 'c3',
                'category' => 'communication',
                'number' => 3,
                'text' => 'I can communicate well in group discussions or meetings.',
            ],
            'a1' => [
                'id' => 'a1',
                'category' => 'analytical',
                'number' => 4,
                'text' => 'I can understand information and identify important patterns.',
            ],
            'a2' => [
                'id' => 'a2',
                'category' => 'analytical',
                'number' => 5,
                'text' => 'I enjoy working with numbers, facts or data to reach conclusions.',
            ],
            'a3' => [
                'id' => 'a3',
                'category' => 'analytical',
                'number' => 6,
                'text' => 'I can break a complex issue into smaller parts and analyse it.',
            ],
            'p1' => [
                'id' => 'p1',
                'category' => 'problem-solving',
                'number' => 7,
                'text' => 'I can identify the main cause of a problem before acting.',
            ],
            'p2' => [
                'id' => 'p2',
                'category' => 'problem-solving',
                'number' => 8,
                'text' => 'I can think of more than one way to solve a challenge.',
            ],
            'p3' => [
                'id' => 'p3',
                'category' => 'problem-solving',
                'number' => 9,
                'text' => 'I stay focused and work through difficulties until I find a solution.',
            ],
            'd1' => [
                'id' => 'd1',
                'category' => 'digital',
                'number' => 10,
                'text' => 'I am comfortable using digital tools and applications for everyday tasks.',
            ],
            'd2' => [
                'id' => 'd2',
                'category' => 'digital',
                'number' => 11,
                'text' => 'I can learn to use new software or technology with confidence.',
            ],
            'd3' => [
                'id' => 'd3',
                'category' => 'digital',
                'number' => 12,
                'text' => 'I can find, assess and organise information using digital resources.',
            ],
            'l1' => [
                'id' => 'l1',
                'category' => 'leadership',
                'number' => 13,
                'text' => 'I am comfortable taking responsibility for a task or outcome.',
            ],
            'l2' => [
                'id' => 'l2',
                'category' => 'leadership',
                'number' => 14,
                'text' => 'I can motivate or guide others towards a shared goal.',
            ],
            'l3' => [
                'id' => 'l3',
                'category' => 'leadership',
                'number' => 15,
                'text' => 'I can make decisions when a group needs direction.',
            ],
            'o1' => [
                'id' => 'o1',
                'category' => 'organisation',
                'number' => 16,
                'text' => 'I plan my work and manage my time effectively.',
            ],
            'o2' => [
                'id' => 'o2',
                'category' => 'organisation',
                'number' => 17,
                'text' => 'I can prioritise tasks and meet deadlines.',
            ],
            'o3' => [
                'id' => 'o3',
                'category' => 'organisation',
                'number' => 18,
                'text' => 'I keep information, documents or tasks organised and up to date.',
            ],
            'cr1' => [
                'id' => 'cr1',
                'category' => 'creativity',
                'number' => 19,
                'text' => 'I often come up with new ideas or different approaches.',
            ],
            'cr2' => [
                'id' => 'cr2',
                'category' => 'creativity',
                'number' => 20,
                'text' => 'I enjoy finding imaginative ways to communicate or present ideas.',
            ],
            'cr3' => [
                'id' => 'cr3',
                'category' => 'creativity',
                'number' => 21,
                'text' => 'I can adapt my thinking when a familiar approach does not work.',
            ],
            'b1' => [
                'id' => 'b1',
                'category' => 'business',
                'number' => 22,
                'text' => 'I can recognise opportunities to improve a product, service or process.',
            ],
            'b2' => [
                'id' => 'b2',
                'category' => 'business',
                'number' => 23,
                'text' => 'I understand how costs, value and customer needs affect decisions.',
            ],
            'b3' => [
                'id' => 'b3',
                'category' => 'business',
                'number' => 24,
                'text' => 'I feel confident presenting an idea and explaining its benefits.',
            ],
        ];
    }

    public static function questionIds(): array
    {
        return array_keys(self::questions());
    }

    public static function questionIdsFor(string $category): array
    {
        return array_values(array_map(
            static fn (array $q) => $q['id'],
            array_filter(self::questions(), static fn (array $q) => $q['category'] === $category)
        ));
    }

    public static function scaleValues(): array
    {
        return array_map('intval', array_keys(self::SCALE));
    }

    public static function progressPercent(int $step): int
    {
        return (int) round(($step / count(self::CATEGORY_ORDER)) * 100);
    }
}
