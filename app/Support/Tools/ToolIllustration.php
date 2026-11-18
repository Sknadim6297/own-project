<?php

namespace App\Support\Tools;

/**
 * Presentation-only: maps a tool (slug / icon key) to its 3D illustration file.
 * Shared by the /tools catalogue cards and the home "Try Our Tools" strip so both show the same art.
 */
class ToolIllustration
{
    public const DIRECTORY = 'frontend/assets/img/tools-illustrations/';

    /**
     * @return string|null Filename inside DIRECTORY, or null when no illustration exists for the tool.
     */
    public static function for(string $slug, string $icon): ?string
    {
        return match (true) {
            $slug === 'daily-mood-check-in', $icon === 'mood' => 'daily-mood-3d.webp',
            $slug === 'career-interest-test', $icon === 'career' => 'career-interest-3d.webp',
            $slug === 'bmi-bmr-calculator', $icon === 'bmi' => 'bmi-bmr-3d.webp',
            $slug === 'tarot-reflection', $icon === 'tarot' => 'tarot-reflection-3d.webp',
            $slug === 'monthly-money-planner' => 'monthly-money-3d.webp',
            $slug === 'parenting-stress-check' => 'parenting-stress-3d.webp',
            $slug === 'daily-mindfulness-calm' => 'mindfulness-calm-3d.webp',
            $slug === 'digital-journal', $icon === 'journal' => 'digital-journal-3d.webp',
            $icon === 'finance', $icon === 'coins', $icon === 'goal' => 'monthly-money-3d.webp',
            $icon === 'parenting', $icon === 'family' => 'parenting-stress-3d.webp',
            $icon === 'lotus', $icon === 'gratitude' => 'mindfulness-calm-3d.webp',
            $icon === 'skills' => 'career-interest-3d.webp',
            $icon === 'weight', $icon === 'nutrition', $icon === 'water' => 'bmi-bmr-3d.webp',
            default => null,
        };
    }
}
