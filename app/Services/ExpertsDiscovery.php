<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

/**
 * Resolve Experts listing URLs from verified Explore categories / live Service rows.
 * Does not invent service IDs. Falls back to category-only or unfiltered Experts.
 */
class ExpertsDiscovery
{
    public function __construct(
        private readonly PsychologyHome $psychologyHome,
    ) {
    }

    /**
     * @return array{url: string, category: ?string, service: ?string, resolved_via: string, label: string}
     */
    public function psychologists(): array
    {
        $discovery = $this->psychologyHome->psychologistDiscovery();

        return [
            'url' => $discovery['url'],
            'category' => $discovery['category'],
            'service' => $discovery['service'],
            'resolved_via' => $discovery['resolved_via'],
            'label' => 'Connect with a Psychologist',
        ];
    }

    /**
     * Career & Business experts (category + optional live service match).
     *
     * @return array{url: string, category: ?string, service: ?string, resolved_via: string, label: string}
     */
    public function career(): array
    {
        return $this->resolveCategory(
            'career-business',
            'Connect with a Career Expert',
            'Browse Career Experts'
        );
    }

    /**
     * Nutrition & Fitness experts (diet / nutrition needles via live services when present).
     *
     * @return array{url: string, category: ?string, service: ?string, resolved_via: string, label: string}
     */
    public function nutrition(): array
    {
        return $this->resolveCategory(
            'nutrition-fitness',
            'Connect with a Nutrition Expert',
            'Browse Nutrition Experts'
        );
    }

    /**
     * Map a Tarot reading category to a real Explore experts filter.
     * Uses only verified ExploreCatalog keys; falls back to astrology-tarot.
     *
     * @return array{url: string, category: ?string, service: ?string, resolved_via: string, label: string}
     */
    public function forTarotCategory(?string $tarotCategoryKey): array
    {
        $map = [
            'love-relationships' => ['explore' => 'relationships', 'specialist' => 'Talk to a Relationships Expert', 'browse' => 'Browse Relationships Experts'],
            'career-work' => ['explore' => 'career-business', 'specialist' => 'Talk to a Career Expert', 'browse' => 'Browse Career Experts'],
            'money-finances' => ['explore' => 'money-finance', 'specialist' => 'Talk to a Money & Finance Expert', 'browse' => 'Browse Money & Finance Experts'],
            'personal-growth' => ['explore' => 'mental-health', 'specialist' => 'Talk to a Mental Health Expert', 'browse' => 'Browse Mental Health Experts'],
            'life-direction' => ['explore' => 'spirituality', 'specialist' => 'Talk to a Spirituality Expert', 'browse' => 'Browse Spirituality Experts'],
            'general-guidance' => ['explore' => 'astrology-tarot', 'specialist' => 'Talk to an Astrology & Tarot Expert', 'browse' => 'Browse Astrology & Tarot Experts'],
        ];

        $fallback = [
            'explore' => 'astrology-tarot',
            'specialist' => 'Talk to an Astrology & Tarot Expert',
            'browse' => 'Browse Astrology & Tarot Experts',
        ];

        $target = $map[$tarotCategoryKey] ?? $fallback;
        if (! array_key_exists($target['explore'], ExploreCatalog::CATEGORIES)) {
            $target = $fallback;
        }

        return $this->resolveCategory(
            $target['explore'],
            $target['specialist'],
            $target['browse']
        );
    }

    /**
     * @return array{url: string, category: ?string, service: ?string, resolved_via: string, label: string}
     */
    private function resolveCategory(string $categoryKey, string $specialistLabel, string $categoryLabel): array
    {
        if (! array_key_exists($categoryKey, ExploreCatalog::CATEGORIES)) {
            return [
                'url' => route('professionals'),
                'category' => null,
                'service' => null,
                'resolved_via' => 'unfiltered_fallback',
                'label' => 'Browse Experts',
            ];
        }

        $service = $this->resolveServiceForCategory($categoryKey);
        $query = ['category' => $categoryKey];
        $resolvedVia = 'category';

        if ($service !== null) {
            $query['service'] = $service->slug;
            $resolvedVia = 'service_and_category';
        }

        return [
            'url' => route('professionals', $query),
            'category' => $categoryKey,
            'service' => $service?->slug,
            'resolved_via' => $resolvedVia,
            'label' => $service !== null ? $specialistLabel : $categoryLabel,
        ];
    }

    private function resolveServiceForCategory(string $categoryKey): ?Service
    {
        try {
            if (! Schema::hasTable('services')) {
                return null;
            }

            $needles = ExploreCatalog::CATEGORIES[$categoryKey]['needles'] ?? [];
            if ($needles === []) {
                return null;
            }

            return Service::query()
                ->where(function (Builder $query) use ($needles) {
                    foreach ($needles as $needle) {
                        $like = '%'.$needle.'%';
                        $query->orWhere('slug', 'like', $like)
                            ->orWhere('name', 'like', $like);
                    }
                })
                ->orderBy('id')
                ->first();
        } catch (QueryException) {
            return null;
        }
    }
}
