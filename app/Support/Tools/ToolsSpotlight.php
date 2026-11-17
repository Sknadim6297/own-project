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
     * @return list<array{label: string, icon: string, url: string, slug: ?string, blurb: string}>
     */
    public static function cards(int $limit = 12): array
    {
        $entries = [
            [
                'label' => 'Daily Mood Check',
                'blurb' => 'Track mood & feel balanced',
                'icon' => 'mood',
                'slug' => 'daily-mood-check-in',
                'route' => 'tools.psychology.mood',
            ],
            [
                'label' => 'Guided Journal',
                'blurb' => 'Write, reflect, grow',
                'icon' => 'journal',
                'slug' => 'digital-journal',
                'route' => 'tools.psychology.journal',
            ],
            [
                'label' => 'Goal Planner',
                'blurb' => 'Plan goals with clarity',
                'icon' => 'goal',
                'slug' => 'financial-goal-calculator',
                'route' => 'tools.pfm.financial-goal',
            ],
            [
                'label' => 'BMI Calculator',
                'blurb' => 'Body metrics & fitness range',
                'icon' => 'bmi',
                'slug' => 'bmi-bmr-calculator',
                'route' => 'tools.bmi-bmr',
            ],
            [
                'label' => 'Finance Calculators',
                'blurb' => 'Money tools in one place',
                'icon' => 'finance',
                'slug' => null,
                'route' => 'tools.pfm',
            ],
            [
                'label' => 'Parenting Toolkit',
                'blurb' => 'Support for family life',
                'icon' => 'parenting',
                'slug' => null,
                'route' => 'tools.ps',
            ],
            [
                'label' => 'Mindfulness Practices',
                'blurb' => 'Calm mind, stay centered',
                'icon' => 'lotus',
                'slug' => 'daily-mindfulness-calm',
                'route' => 'tools.ps.mindfulness',
            ],
            [
                'label' => 'Career Interest Test',
                'blurb' => 'Find your ideal path',
                'icon' => 'career',
                'slug' => 'career-interest-test',
                'route' => 'tools.career-interest',
            ],
            [
                'label' => 'Monthly Money Planner',
                'blurb' => 'Plan & track finances',
                'icon' => 'finance',
                'slug' => 'monthly-money-planner',
                'route' => 'tools.pfm.money-planner',
            ],
            [
                'label' => 'Parenting Stress Check',
                'blurb' => 'Assess stress & get insight',
                'icon' => 'parenting',
                'slug' => 'parenting-stress-check',
                'route' => 'tools.ps.stress-check',
            ],
            [
                'label' => 'Water Intake Tracker',
                'blurb' => 'Hit your daily water goal',
                'icon' => 'water',
                'slug' => 'water-intake-tracker',
                'route' => 'tools.water-intake',
            ],
            [
                'label' => 'Skills & Strengths',
                'blurb' => 'Map your transferable skills',
                'icon' => 'skills',
                'slug' => 'skills-strengths-assessment',
                'route' => 'tools.skills-strengths',
            ],
        ];

        $launchableSlugs = self::launchableSlugs();
        $cards = [];
        $seen = [];

        foreach ($entries as $entry) {
            if (count($cards) >= $limit) {
                break;
            }

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

            $key = $entry['slug'] ?? $entry['route'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $cards[] = [
                'label' => $entry['label'],
                'blurb' => $entry['blurb'],
                'icon' => $entry['icon'],
                'url' => $url,
                'slug' => $entry['slug'],
            ];
        }

        if (count($cards) < $limit) {
            foreach (self::publishedExtras($limit - count($cards), $seen) as $extra) {
                $cards[] = $extra;
            }
        }

        return $cards;
    }

    /**
     * @param  array<string, bool>  $seen
     * @return list<array{label: string, icon: string, url: string, slug: ?string, blurb: string}>
     */
    private static function publishedExtras(int $need, array &$seen): array
    {
        if ($need <= 0) {
            return [];
        }

        try {
            if (! Schema::hasTable('tools')) {
                return [];
            }

            $extras = [];
            $tools = Tool::query()
                ->publiclyAvailable()
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();

            foreach ($tools as $tool) {
                if (count($extras) >= $need) {
                    break;
                }

                if (! $tool->canLaunch()) {
                    continue;
                }

                $slug = (string) $tool->slug;
                $routeName = (string) $tool->route_name;
                if ($slug !== '' && isset($seen[$slug])) {
                    continue;
                }
                if ($routeName === '' || ! Route::has($routeName)) {
                    continue;
                }

                try {
                    $url = route($routeName);
                } catch (\Throwable) {
                    continue;
                }

                $key = $slug !== '' ? $slug : $routeName;
                $seen[$key] = true;

                $extras[] = [
                    'label' => (string) $tool->name,
                    'blurb' => 'Open this practical tool',
                    'icon' => 'leaf',
                    'url' => $url,
                    'slug' => $slug !== '' ? $slug : null,
                ];
            }

            return $extras;
        } catch (\Throwable) {
            return [];
        }
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
