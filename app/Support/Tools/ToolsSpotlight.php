<?php

namespace App\Support\Tools;

use App\Models\Tool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Curated home / discovery tiles for the public Tools strip.
 */
class ToolsSpotlight
{
    /**
     * Featured entry points shown in the "Try Our Tools" strip.
     *
     * @return list<array{label: string, icon: string, url: string, slug: ?string}>
     */
    public static function cards(int $limit = 7): array
    {
        $entries = [
            [
                'label' => 'Daily Mood Check',
                'icon' => 'mood',
                'slug' => 'daily-mood-check-in',
                'route' => 'tools.psychology.mood',
            ],
            [
                'label' => 'Guided Journal',
                'icon' => 'journal',
                'slug' => 'digital-journal',
                'route' => 'tools.psychology.journal',
            ],
            [
                'label' => 'Goal Planner',
                'icon' => 'goal',
                'slug' => 'financial-goal-calculator',
                'route' => 'tools.pfm.financial-goal',
            ],
            [
                'label' => 'BMI Calculator',
                'icon' => 'bmi',
                'slug' => 'bmi-bmr-calculator',
                'route' => 'tools.bmi-bmr',
            ],
            [
                'label' => 'Finance Calculators',
                'icon' => 'finance',
                'slug' => null,
                'route' => 'tools.pfm',
            ],
            [
                'label' => 'Parenting Toolkit',
                'icon' => 'parenting',
                'slug' => null,
                'route' => 'tools.ps',
            ],
            [
                'label' => 'Mindfulness Practices',
                'icon' => 'lotus',
                'slug' => 'daily-mindfulness-calm',
                'route' => 'tools.ps.mindfulness',
            ],
        ];

        $launchableSlugs = self::launchableSlugs();

        $cards = [];
        foreach ($entries as $entry) {
            if (! Route::has($entry['route'])) {
                continue;
            }

            if ($entry['slug'] !== null && ! $launchableSlugs->contains($entry['slug'])) {
                continue;
            }

            try {
                $url = route($entry['route']);
            } catch (\Throwable) {
                continue;
            }

            $cards[] = [
                'label' => $entry['label'],
                'icon' => $entry['icon'],
                'url' => $url,
                'slug' => $entry['slug'],
            ];

            if (count($cards) >= $limit) {
                break;
            }
        }

        return $cards;
    }

    /**
     * @return Collection<int, string>
     */
    private static function launchableSlugs(): Collection
    {
        try {
            if (! Schema::hasTable('tools')) {
                return collect();
            }

            return Tool::query()
                ->publiclyAvailable()
                ->orderBy('display_order')
                ->orderBy('id')
                ->get()
                ->filter(fn (Tool $tool) => $tool->canLaunch())
                ->pluck('slug')
                ->values();
        } catch (\Throwable) {
            return collect();
        }
    }
}
