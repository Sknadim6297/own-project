<?php

namespace App\Support\Tarot;

final class TarotCatalog
{
    public const QUESTION_MAX = 400;

    public const QUESTION_MIN = 8;

    public const FAN_SIZE = 16;

    public const DRAFT_TTL_MINUTES = 45;

    public const SPREAD_ONE = 'one';

    public const SPREAD_THREE = 'three';

    /**
     * @return array<string, array{label: string, lens: string, examples: list<string>, tone: string}>
     */
    public static function categories(): array
    {
        return [
            'love-relationships' => [
                'label' => 'Love & Relationships',
                'lens' => 'relationship, emotional needs, boundaries, communication and connection',
                'examples' => [
                    'How can I communicate more clearly with my partner?',
                    'What may help me feel more secure in this relationship?',
                ],
                'tone' => 'blush',
            ],
            'career-work' => [
                'label' => 'Career & Work',
                'lens' => 'work direction, skills, confidence, collaboration and practical choices',
                'examples' => [
                    'Should I change my job or stay?',
                    'What may help me grow in my current role?',
                ],
                'tone' => 'lilac',
            ],
            'money-finances' => [
                'label' => 'Money & Finances',
                'lens' => 'resources, security, financial habits, opportunities and practical planning',
                'examples' => [
                    'What financial habit may be useful to review now?',
                    'How can I approach money decisions with more clarity?',
                ],
                'tone' => 'cream',
            ],
            'personal-growth' => [
                'label' => 'Personal Growth',
                'lens' => 'self-awareness, habits, confidence, boundaries and growth',
                'examples' => [
                    'What habit may support my growth right now?',
                    'Where might I need clearer boundaries?',
                ],
                'tone' => 'mint',
            ],
            'life-direction' => [
                'label' => 'Life Direction',
                'lens' => 'values, priorities, transitions and longer-term direction',
                'examples' => [
                    'What priority deserves more of my attention?',
                    'How can I navigate this transition with more steadiness?',
                ],
                'tone' => 'gold',
            ],
            'general-guidance' => [
                'label' => 'General Guidance',
                'lens' => 'the broader situation and what may be useful to notice',
                'examples' => [
                    'What may be useful to notice in my situation right now?',
                    'Where could a fresh perspective help me?',
                ],
                'tone' => 'sage',
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, subtitle: string, count: int, positions: list<array{key: string, label: string, prompt: string}>}>
     */
    public static function spreads(): array
    {
        return [
            self::SPREAD_ONE => [
                'label' => 'One Card',
                'subtitle' => 'Quick Clarity',
                'count' => 1,
                'positions' => [
                    [
                        'key' => 'quick_clarity',
                        'label' => 'Quick Clarity',
                        'prompt' => 'What do I need to understand right now?',
                    ],
                ],
            ],
            self::SPREAD_THREE => [
                'label' => 'Three Cards',
                'subtitle' => 'Deeper Insight',
                'count' => 3,
                'positions' => [
                    [
                        'key' => 'situation',
                        'label' => 'Situation',
                        'prompt' => 'What is happening around this question?',
                    ],
                    [
                        'key' => 'challenge',
                        'label' => 'Challenge',
                        'prompt' => 'What may be making this difficult or unclear?',
                    ],
                    [
                        'key' => 'guidance',
                        'label' => 'Guidance',
                        'prompt' => 'What perspective may help me move forward?',
                    ],
                ],
            ],
        ];
    }

    public static function categoryKeys(): array
    {
        return array_keys(self::categories());
    }

    public static function spreadKeys(): array
    {
        return array_keys(self::spreads());
    }

    public static function disclaimer(): string
    {
        return 'Tarot Reflection is for personal reflection and entertainment. Cards do not prove another person’s thoughts, guarantee future events, diagnose conditions, or give certain financial, medical, or legal outcomes.';
    }

    /**
     * Optional closing reflection prompt (not a separate CTA).
     */
    public static function reflectionPrompt(string $spreadType): string
    {
        if ($spreadType === self::SPREAD_ONE) {
            return TarotCatalog::spreads()[self::SPREAD_ONE]['positions'][0]['prompt'];
        }

        return 'Which Guidance insight feels most useful to carry into your next small step?';
    }

    public static function categoryLabel(string $key): string
    {
        return self::categories()[$key]['label'] ?? $key;
    }

    public static function spreadLabel(string $key): string
    {
        $spread = self::spreads()[$key] ?? null;
        if ($spread === null) {
            return $key;
        }

        return $spread['label'].' · '.$spread['subtitle'];
    }
}
