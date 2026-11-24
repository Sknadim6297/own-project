<?php

namespace App\Services;

use App\Models\Service;
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
     * Tarot Reflection expert CTA → Astrology & Tarot professional field.
     *
     * Reading themes (love, career, …) are intentionally not used as Explore
     * destinations: the client requires a Tarot field/service link, not theme
     * remapping into Relationships/Career/etc. The optional $tarotCategoryKey
     * argument is retained for call-site compatibility and is not appended to
     * the URL (privacy: no reading content in query strings).
     *
     * When a live Service whose name/slug contains "tarot" exists, the CTA
     * filters to that service and uses a Tarot-specific label. Otherwise the
     * truthful Astrology & Tarot category listing is used (no invented services).
     *
     * @return array{url: string, category: ?string, service: ?string, resolved_via: string, label: string}
     */
    public function forTarotCategory(?string $tarotCategoryKey = null): array
    {
        unset($tarotCategoryKey); // retained for signature compatibility only

        $categoryKey = 'astrology-tarot';
        if (! array_key_exists($categoryKey, ExploreCatalog::CATEGORIES)) {
            return [
                'url' => route('professionals'),
                'category' => null,
                'service' => null,
                'resolved_via' => 'unfiltered_fallback',
                'label' => 'Browse Experts',
            ];
        }

        $tarotService = $this->resolveTarotService();
        if ($tarotService !== null) {
            return [
                'url' => route('professionals', [
                    'category' => $categoryKey,
                    'service' => $tarotService->slug,
                ]),
                'category' => $categoryKey,
                'service' => $tarotService->slug,
                'resolved_via' => 'tarot_service_and_category',
                'label' => 'Talk to a Tarot Expert',
            ];
        }

        // No Tarot-named service in the catalogue — do not pick a Vastu/Astrology
        // sibling and label it as Tarot. Category filter remains truthful.
        return [
            'url' => route('professionals', ['category' => $categoryKey]),
            'category' => $categoryKey,
            'service' => null,
            'resolved_via' => 'astrology_tarot_category',
            'label' => 'Talk to an Astrology & Tarot Expert',
        ];
    }

    /**
     * Prefer a live catalogue Service that is clearly Tarot (name/slug), not
     * merely the first Astrology & Tarot mapped row (which may be Vastu/etc.).
     */
    private function resolveTarotService(): ?Service
    {
        try {
            if (! Schema::hasTable('services')) {
                return null;
            }

            return app(ExploreCatalog::class)->resolveTarotService();
        } catch (QueryException) {
            return null;
        }
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

            return app(ExploreCatalog::class)->resolveServiceForExploreCategory($categoryKey);
        } catch (QueryException) {
            return null;
        }
    }
}
