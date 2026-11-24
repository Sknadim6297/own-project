<?php

namespace App\Support\Psychology;

/**
 * Mood-specific follow-up copy and emotion options for Daily Mood Check-in.
 * Emotion keys are always a subset of PsychologyCatalog::emotionKeys() so
 * historical check-ins remain readable.
 */
class MoodFollowUpCatalog
{
    /**
     * @return array{
     *     emotion_heading: string,
     *     emotion_lead: string,
     *     emotion_keys: list<string>,
     *     reflection_question: string,
     *     reflection_prompts: list<string>,
     *     complete_tip: string
     * }
     */
    public static function forMood(string $moodKey): array
    {
        $sets = self::sets();
        if (! isset($sets[$moodKey])) {
            throw new \InvalidArgumentException('Unknown mood key.');
        }

        return $sets[$moodKey];
    }

    /**
     * @return list<string>
     */
    public static function emotionKeysForMood(string $moodKey): array
    {
        return self::forMood($moodKey)['emotion_keys'];
    }

    public static function hasMood(string $moodKey): bool
    {
        return array_key_exists($moodKey, self::sets());
    }

    /**
     * @return array<string, array{
     *     emotion_heading: string,
     *     emotion_lead: string,
     *     emotion_keys: list<string>,
     *     reflection_question: string,
     *     reflection_prompts: list<string>,
     *     complete_tip: string
     * }>
     */
    private static function sets(): array
    {
        return [
            'very-happy' => [
                'emotion_heading' => 'What is lifting your mood?',
                'emotion_lead' => 'Pick up to 6 feelings that match this positive moment.',
                'emotion_keys' => ['happy', 'excited', 'grateful', 'loved', 'calm'],
                'reflection_question' => 'What contributed to this positive feeling today?',
                'reflection_prompts' => [
                    'Something or someone made me smile',
                    'I accomplished something I care about',
                    'I want to remember this feeling',
                ],
                'complete_tip' => 'Notice what supported this brighter mood — small habits often help it last.',
            ],
            'good' => [
                'emotion_heading' => 'What feels good right now?',
                'emotion_lead' => 'Choose up to 6 emotions that describe this steady, positive feeling.',
                'emotion_keys' => ['happy', 'calm', 'grateful', 'loved', 'thoughtful'],
                'reflection_question' => 'What helped you feel good today?',
                'reflection_prompts' => [
                    'A kind moment with someone',
                    'Something went smoothly today',
                    'I took care of myself in a small way',
                ],
                'complete_tip' => 'A calm, positive day is worth noticing. Gentle routines can help you stay grounded.',
            ],
            'neutral' => [
                'emotion_heading' => 'How would you describe this in-between feeling?',
                'emotion_lead' => 'Choose up to 6 emotions that fit a quieter or mixed day.',
                'emotion_keys' => ['neutral', 'thoughtful', 'quiet', 'tired', 'unsure', 'calm'],
                'reflection_question' => 'What has your day been like so far?',
                'reflection_prompts' => [
                    'My mind is on a few different things',
                    'Nothing stands out strongly yet',
                    'Something small could make today feel more meaningful',
                ],
                'complete_tip' => 'Neutral days are valid too. A small intentional step can gently shape the rest of your day.',
            ],
            'sad' => [
                'emotion_heading' => 'What emotions are sitting with you?',
                'emotion_lead' => 'Choose up to 6 feelings that feel true — there is no wrong answer.',
                'emotion_keys' => ['sad', 'lonely', 'tired', 'worried', 'thoughtful', 'grateful'],
                'reflection_question' => 'What may have contributed to this low feeling?',
                'reflection_prompts' => [
                    'Something feels heavy today',
                    'I could use a little comfort or support',
                    'A small kind activity might help',
                ],
                'complete_tip' => 'Being honest about a low mood is a caring step. Rest, connection, or a gentle activity can help.',
            ],
            'stressed' => [
                'emotion_heading' => 'What is showing up with this stress?',
                'emotion_lead' => 'Choose up to 6 emotions that describe how the pressure feels right now.',
                'emotion_keys' => ['anxious', 'worried', 'tired', 'angry', 'unsure', 'calm'],
                'reflection_question' => 'What is contributing to the stress right now?',
                'reflection_prompts' => [
                    'Something feels hard to manage',
                    'I need a moment to feel calmer',
                    'One next step might help me settle',
                ],
                'complete_tip' => 'Stress is a signal, not a failure. A short pause or one manageable next step can ease the load.',
            ],
        ];
    }
}
