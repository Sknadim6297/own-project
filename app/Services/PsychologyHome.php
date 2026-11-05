<?php

namespace App\Services;

use App\Models\Service;
use App\Models\Tool;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PsychologyHome
{
    public function __construct(
        private readonly ExploreCatalog $catalog,
    ) {
    }

    public function present(): array
    {
        $cards = $this->cards();
        $discovery = $this->psychologistDiscovery();

        return [
            'cards' => $cards,
            'mood' => $cards[0],
            'psychologistsUrl' => $discovery['url'],
            'psychologistDiscovery' => $discovery,
        ];
    }

    /**
     * Public Experts listing URL for Psychology → Connect with a Psychologist.
     */
    public function psychologistsUrl(): string
    {
        return $this->psychologistDiscovery()['url'];
    }

    /**
     * Resolve Experts filters from live Service + Explore category mappings.
     *
     * @return array{
     *     url: string,
     *     category: ?string,
     *     service: ?string,
     *     resolved_via: string,
     *     mapping_missing: bool,
     *     message: ?string
     * }
     */
    public function psychologistDiscovery(): array
    {
        $category = $this->catalog->psychologyCategoryKey();
        $service = $this->safePsychologistService();

        if ($category === null && $service === null) {
            Log::warning('Psychology discovery mapping missing: no Mental Health category and no psychologist service.');

            return [
                'url' => route('professionals'),
                'category' => null,
                'service' => null,
                'resolved_via' => 'unfiltered_fallback',
                'mapping_missing' => true,
                'message' => 'Psychologist category mapping is missing. Showing the Experts listing without a Psychology filter.',
            ];
        }

        $query = [];
        $resolvedVia = 'category';

        if ($category !== null) {
            $query['category'] = $category;
        }

        if ($service !== null) {
            $query['service'] = $service->slug;
            $resolvedVia = $category !== null ? 'service_and_category' : 'service';
        } elseif ($category !== null) {
            Log::notice('Psychology discovery using category only; no psychologist Service slug found in services table.', [
                'category' => $category,
            ]);
        }

        return [
            'url' => route('professionals', $query),
            'category' => $category,
            'service' => $service?->slug,
            'resolved_via' => $resolvedVia,
            'mapping_missing' => $service === null && $category !== null,
            'message' => $service === null && $category !== null
                ? 'No psychologist Service record found; Experts will open with the Mental Health category selected.'
                : null,
        ];
    }

    private function safePsychologistService(): ?Service
    {
        try {
            if (! Schema::hasTable('services')) {
                return null;
            }

            return $this->catalog->resolvePsychologistService();
        } catch (QueryException) {
            return null;
        }
    }

    private function cards(): array
    {
        $definitions = [
            'daily-mood-check-in' => [
                'name' => 'Daily Mood Check-in',
                'description' => 'Understand and track your emotions.',
                'action' => 'Start check-in',
                'tone' => 'sage',
                'icon' => 'mood',
            ],
            'digital-journal' => [
                'name' => 'Digital Journal',
                'description' => 'Express your thoughts in your own words.',
                'action' => 'Open journal',
                'tone' => 'yellow',
                'icon' => 'journal',
            ],
            'gratitude-journal' => [
                'name' => 'Gratitude Journal',
                'description' => 'Notice and appreciate the good in your day.',
                'action' => 'Open gratitude',
                'tone' => 'blush',
                'icon' => 'gratitude',
            ],
        ];

        $records = $this->registryTools(array_keys($definitions));

        $cards = [];
        foreach ($definitions as $slug => $copy) {
            $tool = $records->get($slug);
            $launch = $tool?->canLaunch() === true;
            $closed = $tool && in_array($tool->status, ['inactive', 'unavailable'], true);
            $href = null;
            $action = 'Coming soon';
            $state = 'coming_soon';

            if ($closed) {
                $action = 'Unavailable';
                $state = 'unavailable';
            } elseif ($launch) {
                $href = route($tool->route_name);
                $action = $copy['action'];
                $state = 'available';
            } elseif ($slug === 'daily-mood-check-in') {
                $href = route('tools.psychology.mood');
                $action = $copy['action'];
                $state = 'available';
            }

            $cards[] = [
                'slug' => $slug,
                'name' => $copy['name'],
                'description' => $copy['description'],
                'action' => $action,
                'href' => $href,
                'tone' => $copy['tone'],
                'icon' => $copy['icon'],
                'state' => $state,
            ];
        }

        return $cards;
    }

    private function registryTools(array $slugs)
    {
        try {
            if (! Schema::hasTable('tools')) {
                return collect();
            }

            return Tool::query()->whereIn('slug', $slugs)->get()->keyBy('slug');
        } catch (QueryException) {
            return collect();
        }
    }
}
