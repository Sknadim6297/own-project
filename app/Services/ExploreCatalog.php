<?php

namespace App\Services;

use App\Models\AllEvent;
use App\Models\Availability;
use App\Models\BlogPost;
use App\Models\Professional;
use App\Models\Review;
use App\Models\Service;
use App\Models\SubService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExploreCatalog
{
    /** @var array<string, list<int>>|null */
    private ?array $serviceCategoryMapCache = null;

    public const PER_PAGE = 8;

    /** Explore category that represents Psychology / Psychologist discovery. */
    public const PSYCHOLOGY_CATEGORY = 'mental-health';

    public const PREFERRED_PSYCHOLOGIST_SERVICE_SLUG = 'best-online-psychologists-india';

    /**
     * Exact experience values stored on profiles (registration / profile forms).
     * Do not invent alternate numeric ranges that the database does not use.
     */
    public const EXPERIENCE_BUCKETS = [
        '0-2' => '0–2 years',
        '2-4' => '2–4 years',
        '4-6' => '4–6 years',
        '6-8' => '6–8 years',
        '8-10' => '8–10 years',
        '10+' => '10+ years',
    ];

    /**
     * Explore UI categories and keyword needles.
     *
     * Expert filtering uses linked Service rows as the source of truth (see
     * whereExpertCategory). Needles must stay non-overlapping across categories
     * so categoryKeyForText() can classify a service/specialization uniquely.
     *
     * Do not use bare "counsel" for mental-health: it false-positives Career /
     * Business Counsellor services into Mental Health.
     */
    public const CATEGORIES = [
        'mental-health' => ['label' => 'Mental Health', 'tone' => 'mint', 'icon' => 'lotus', 'needles' => ['mental', 'psycholog', 'psychiatr', 'mindset', 'therap']],
        'astrology-tarot' => ['label' => 'Astrology & Tarot', 'tone' => 'lavender', 'icon' => 'moon', 'needles' => ['astro', 'tarot', 'vastu', 'horoscope']],
        'relationships' => ['label' => 'Relationships', 'tone' => 'blush', 'icon' => 'heart', 'needles' => ['relation', 'couple', 'marriage', 'family']],
        'career-business' => ['label' => 'Career & Business', 'tone' => 'lilac', 'icon' => 'chart', 'needles' => ['career', 'business', 'job', 'coach']],
        // "estate" maps Estate and Will Planners via name/slug without the ambiguous bare "will".
        'money-finance' => ['label' => 'Money & Finance', 'tone' => 'cream', 'icon' => 'coins', 'needles' => ['finance', 'money', 'tax', 'account', 'invest', 'estate']],
        'health-wellness' => ['label' => 'Health & Wellness', 'tone' => 'mint', 'icon' => 'leaf', 'needles' => ['health', 'wellness', 'yoga', 'healing']],
        'parenting' => ['label' => 'Parenting', 'tone' => 'blush', 'icon' => 'family', 'needles' => ['parent', 'child', 'teen']],
        // "influencer" maps Influencers; keep "image" for Image Consultant.
        'lifestyle' => ['label' => 'Lifestyle', 'tone' => 'sage', 'icon' => 'cup', 'needles' => ['lifestyle', 'style', 'image', 'fashion', 'influencer']],
        'spirituality' => ['label' => 'Spirituality', 'tone' => 'lavender', 'icon' => 'spark', 'needles' => ['spirit', 'meditat', 'energy']],
        'creativity' => ['label' => 'Creativity', 'tone' => 'blue', 'icon' => 'bulb', 'needles' => ['creativ', 'art', 'design', 'writer']],
        // "trainer" maps Physical Trainers without colliding with career coaching.
        'nutrition-fitness' => ['label' => 'Nutrition & Fitness', 'tone' => 'mint', 'icon' => 'fitness', 'needles' => ['nutrition', 'diet', 'fitness', 'workout', 'trainer']],
    ];

    public function browse(Request $request): array
    {
        $filters = $this->filters($request);
        $items = $this->paginate($filters);
        $cards = $this->cards($items);
        $items->setCollection($cards);

        $ratingAvailable = Review::query()->where('is_approved', 1)->exists();
        $availabilityAvailable = Availability::query()->exists();

        return [
            'items' => $items,
            'filters' => $filters,
            'categories' => self::CATEGORIES,
            'experienceBuckets' => self::EXPERIENCE_BUCKETS,
            'activeTypes' => $this->activeTypes($filters),
            'tab' => $this->tab($filters),
            'counts' => [
                'types' => $this->typeCounts($filters),
                'categories' => $this->categoryCounts($filters),
                'price' => $this->priceCounts($filters),
                'rating' => $ratingAvailable ? $this->ratingCounts($filters) : [],
                'availability' => $availabilityAvailable ? $this->availabilityCounts($filters) : [],
            ],
            'ratingAvailable' => $ratingAvailable,
            'availabilityAvailable' => $availabilityAvailable,
            'toolsAvailable' => false,
            'featured' => $this->featured(),
        ];
    }

    private function filters(Request $request): array
    {
        $q = trim(strip_tags((string) $request->query('q', '')));
        $q = Str::limit($q, 100, '');

        $type = (string) $request->query('type', 'all');
        if (!in_array($type, ['all', 'experts', 'tools', 'programmes', 'articles'], true)) {
            $type = 'all';
        }
        if (! $this->programmesEnabled() && $type === 'programmes') {
            $type = 'all';
        }

        $category = (string) $request->query('category', '');
        if (!array_key_exists($category, self::CATEGORIES)) {
            $category = '';
        }

        $service = (string) $request->query('service', '');
        if (!preg_match('/^[a-z0-9-]{1,160}$/', $service)) {
            $service = '';
        }
        $serviceModel = $service !== '' ? Service::query()->where('slug', $service)->first() : null;
        if ($service !== '' && !$serviceModel) {
            $service = '';
        }
        if ($category === '' && $serviceModel) {
            $inferred = $this->categoryKeyForText($serviceModel->name.' '.$serviceModel->slug);
            if ($inferred) {
                $category = $inferred;
            }
        }

        $subService = (string) $request->query('sub_service', '');
        if (!preg_match('/^[a-z0-9-]{1,160}$/', $subService)) {
            $subService = '';
        }

        $experience = (string) $request->query('experience', '');
        if (! array_key_exists($experience, self::EXPERIENCE_BUCKETS)) {
            $experience = '';
        }

        $sort = (string) $request->query('sort', 'relevant');
        if (!in_array($sort, ['relevant', 'newest', 'rating', 'price_asc', 'price_desc'], true)) {
            $sort = 'relevant';
        }

        $view = $request->query('view') === 'list' ? 'list' : 'grid';

        $allowedTypes = $this->programmesEnabled()
            ? ['experts', 'programmes', 'articles']
            : ['experts', 'articles'];
        $types = array_values(array_intersect((array) $request->query('types', []), $allowedTypes));
        $price = array_values(array_intersect((array) $request->query('price', []), ['free', 'paid']));
        $rating = array_values(array_intersect((array) $request->query('rating', []), ['4.5', '4', '3.5']));
        $availability = array_values(array_intersect((array) $request->query('availability', []), ['now', 'anytime']));

        return [
            'q' => $q,
            'type' => $type,
            'types' => $types,
            'category' => $category,
            'service' => $service,
            'sub_service' => $subService,
            'experience' => $experience,
            'price' => $price,
            'rating' => $rating,
            'availability' => $availability,
            'sort' => $sort,
            'view' => $view,
        ];
    }

    private function paginate(array $filters): LengthAwarePaginator
    {
        if ($filters['type'] === 'tools' || $this->selectedKinds($filters) === []) {
            return new Paginator([], 0, self::PER_PAGE, 1, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
        }

        $union = null;
        foreach ($this->selectedKinds($filters) as $kind) {
            $ranked = match ($kind) {
                'experts' => $this->expertRankQuery($filters),
                'programmes' => $this->programmeRankQuery($filters),
                default => $this->articleRankQuery($filters),
            };
            $union = $union ? $union->unionAll($ranked) : $ranked;
        }

        $sorted = DB::query()->fromSub($union, 'explore_items');
        match ($filters['sort']) {
            'newest' => $sorted->orderByDesc('sort_at'),
            'rating' => $sorted->orderByDesc('rating')->orderByDesc('sort_at'),
            'price_asc' => $sorted->orderByRaw('price IS NULL')->orderBy('price')->orderByDesc('sort_at'),
            'price_desc' => $sorted->orderByRaw('price IS NULL')->orderByDesc('price')->orderByDesc('sort_at'),
            default => $sorted->orderByDesc('relevance')->orderByDesc('sort_at'),
        };

        return $sorted->paginate(self::PER_PAGE)->withQueryString();
    }

    private function cards(LengthAwarePaginator $items)
    {
        $rows = collect($items->items());
        $expertIds = $rows->where('kind', 'expert')->pluck('item_id')->all();
        $programmeIds = $rows->where('kind', 'programme')->pluck('item_id')->all();
        $articleIds = $rows->where('kind', 'article')->pluck('item_id')->all();

        $experts = Professional::query()
            ->with(['profile', 'professionalServices.service'])
            ->withAvg(['reviews as average_rating' => fn ($query) => $query->where('is_approved', 1)], 'rating')
            ->withCount(['reviews as review_count' => fn ($query) => $query->where('is_approved', 1)])
            ->withMin('rates as min_rate', 'final_rate')
            ->whereIn('id', $expertIds)
            ->get()
            ->keyBy('id');

        $programmes = AllEvent::query()
            ->with('eventDetails')
            ->whereIn('id', $programmeIds)
            ->get()
            ->keyBy('id');

        $articles = BlogPost::query()
            ->with('blog')
            ->whereIn('id', $articleIds)
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($experts, $programmes, $articles) {
            return match ($row->kind) {
                'expert' => $this->expertCard($experts->get($row->item_id)),
                'programme' => $this->programmeCard($programmes->get($row->item_id)),
                default => $this->articleCard($articles->get($row->item_id)),
            };
        })->filter()->values();
    }

    private function expertCard(?Professional $expert): ?array
    {
        if (!$expert) {
            return null;
        }

        $price = is_numeric($expert->profile?->starting_price)
            ? (float) $expert->profile->starting_price
            : (is_numeric($expert->min_rate) ? (float) $expert->min_rate : null);
        $reviewCount = (int) $expert->review_count;
        $chips = $this->chipSet($expert->professionalServices
            ->map(fn ($service) => $service->service?->name ?: $service->service_name));
        [$priceLabel, $priceSuffix] = $this->priceParts($price, true);
        $experienceKey = is_string($expert->profile?->experience) ? $expert->profile->experience : null;
        $qualification = $this->plain($expert->profile?->education)
            ?: $this->plain($expert->profile?->education2);

        return [
            'kind' => 'expert',
            'kicker' => 'Expert',
            'title' => $expert->name,
            'subtitle' => $this->plain($expert->profile?->specialization),
            'excerpt' => null,
            'image' => $this->professionalPhoto($expert->profile?->photo),
            'initials' => $this->initials($expert->name),
            'price_label' => $priceLabel,
            'price_suffix' => $priceSuffix,
            'rating' => $reviewCount > 0 ? number_format((float) $expert->average_rating, 1) : null,
            'review_count' => $reviewCount,
            'experience' => $experienceKey && isset(self::EXPERIENCE_BUCKETS[$experienceKey])
                ? self::EXPERIENCE_BUCKETS[$experienceKey]
                : $this->plain($experienceKey),
            'qualification' => $qualification,
            'chips' => $chips['chips'],
            'chip_extra' => $chips['extra'],
            'meta' => null,
            'url' => route('professionals.details', ['id' => $expert->id, 'professional_name' => Str::slug($expert->name)]),
            'cta' => 'View Profile',
            'book_cta' => 'Book Consultation',
            'verified' => false,
        ];
    }

    private function programmeCard(?AllEvent $programme): ?array
    {
        if (!$programme) {
            return null;
        }

        $detail = $programme->eventDetails->first();
        $fees = is_numeric($programme->starting_fees) ? (float) $programme->starting_fees : null;
        $chips = $this->chipSet([$programme->mini_heading]);
        [$priceLabel, $priceSuffix] = $fees === null ? [null, null] : $this->priceParts($fees, false);

        return [
            'kind' => 'programme',
            'kicker' => 'Programme',
            'title' => $programme->heading,
            'subtitle' => null,
            'excerpt' => $this->plain($programme->short_description),
            'image' => $this->publicImage($detail?->banner_image ?: $programme->card_image),
            'initials' => $this->initials($programme->heading),
            'price_label' => $priceLabel,
            'price_suffix' => $priceSuffix,
            'rating' => null,
            'review_count' => 0,
            'chips' => $chips['chips'],
            'chip_extra' => $chips['extra'],
            'meta' => null,
            'url' => url('/allevent/'.$programme->id),
            'cta' => 'View Programme',
            'verified' => false,
        ];
    }

    private function articleCard(?BlogPost $article): ?array
    {
        if (!$article || !$article->blog) {
            return null;
        }

        $words = str_word_count(trim(strip_tags((string) $article->content)));
        $minutes = $words > 0 ? max(1, (int) ceil($words / 200)) : null;
        $slug = Str::slug($article->blog->title ?? '');

        return [
            'kind' => 'article',
            'kicker' => 'Article',
            'title' => $article->blog->title,
            'subtitle' => null,
            'excerpt' => $this->plain($article->blog->description_short),
            'image' => $this->publicImage($article->image ?: $article->blog->image),
            'initials' => $this->initials($article->blog->title),
            'price_label' => null,
            'price_suffix' => null,
            'rating' => null,
            'review_count' => 0,
            'chips' => array_values(array_filter([$article->category])),
            'chip_extra' => 0,
            'meta' => $minutes ? $minutes.' min read' : null,
            'url' => route('blog.show', $slug !== '' ? $slug : $article->id),
            'cta' => 'Read Article',
            'verified' => false,
        ];
    }

    private function expertIdQuery(array $filters): Builder
    {
        $query = Professional::query()
            ->where('status', 'accepted')
            ->where('active', true);

        if ($filters['q'] !== '') {
            $like = $this->like($filters['q']);
            $query->where(function (Builder $inner) use ($like) {
                $inner->where('name', 'like', $like)
                    ->orWhereHas('profile', fn (Builder $profile) => $profile->where('specialization', 'like', $like))
                    ->orWhereHas('professionalServices', function (Builder $service) use ($like) {
                        $service->where('service_name', 'like', $like)
                            ->orWhere('tags', 'like', $like)
                            ->orWhereHas('service', fn (Builder $parent) => $parent->where('name', 'like', $like));
                    });
            });
        }

        if ($filters['category'] !== '') {
            $this->whereExpertCategory($query, $filters['category']);
        }

        if ($filters['service'] !== '') {
            $slug = $filters['service'];
            $query->whereHas('professionalServices.service', fn (Builder $service) => $service->where('slug', $slug));
        }

        if (($filters['experience'] ?? '') !== '') {
            $bucket = $filters['experience'];
            $query->whereHas('profile', fn (Builder $profile) => $profile->where('experience', $bucket));
        }

        if ($filters['sub_service'] !== '') {
            $name = str_replace('-', ' ', $filters['sub_service']);
            $subService = SubService::query()->where('name', 'like', $this->like($name))->first();
            if ($subService) {
                $subServiceId = $subService->id;
                $subServiceName = strtolower($subService->name);
                $query->where(function (Builder $inner) use ($subServiceId, $subServiceName) {
                    $inner->whereHas('professionalServices.subServices', fn (Builder $sub) => $sub->where('sub_services.id', $subServiceId))
                        ->orWhereHas('professionalServices', fn (Builder $service) => $service->where('tags', 'like', $this->like($subServiceName)));
                });
            }
        }

        $prices = $filters['price'];
        if (count($prices) === 1 && $prices[0] === 'free') {
            $query->where(function (Builder $inner) {
                $inner->whereHas('profile', function (Builder $profile) {
                    $profile->whereNull('starting_price')->orWhere('starting_price', '<=', 0);
                })->whereDoesntHave('rates', fn (Builder $rate) => $rate->where('final_rate', '>', 0));
            });
        }
        if (count($prices) === 1 && $prices[0] === 'paid') {
            $query->where(function (Builder $inner) {
                $inner->whereHas('profile', fn (Builder $profile) => $profile->where('starting_price', '>', 0))
                    ->orWhereHas('rates', fn (Builder $rate) => $rate->where('final_rate', '>', 0));
            });
        }

        if ($filters['rating'] !== []) {
            $minimum = min(array_map('floatval', $filters['rating']));
            $query->whereIn('professionals.id', function ($sub) use ($minimum) {
                $sub->from('reviews')
                    ->select('professional_id')
                    ->where('is_approved', 1)
                    ->groupBy('professional_id')
                    ->havingRaw('AVG(rating) >= ?', [$minimum]);
            });
        }

        if ($filters['availability'] !== []) {
            $weekday = strtolower(now()->format('D'));
            $query->where(function (Builder $inner) use ($filters, $weekday) {
                if (in_array('now', $filters['availability'], true)) {
                    $inner->orWhereHas('availabilities.availabilitySlots', fn (Builder $slot) => $slot->where('weekday', $weekday));
                }
                if (in_array('anytime', $filters['availability'], true)) {
                    $inner->orWhereHas('availabilities');
                }
            });
        }

        return $query;
    }

    private function programmeIdQuery(array $filters): Builder
    {
        $query = AllEvent::query()->where('status', 'approved');

        if ($filters['rating'] !== [] || $filters['availability'] !== []) {
            return $query->whereRaw('1 = 0');
        }

        if ($filters['q'] !== '') {
            $like = $this->like($filters['q']);
            $query->where(function (Builder $inner) use ($like) {
                $inner->where('heading', 'like', $like)
                    ->orWhere('short_description', 'like', $like)
                    ->orWhere('mini_heading', 'like', $like);
            });
        }

        if ($filters['category'] !== '') {
            $this->whereTextMatches($query, self::CATEGORIES[$filters['category']]['needles'], 'programme');
        }

        $prices = $filters['price'];
        if (count($prices) === 1 && $prices[0] === 'free') {
            $query->where(function (Builder $inner) {
                $inner->whereNull('starting_fees')->orWhere('starting_fees', '<=', 0);
            });
        }
        if (count($prices) === 1 && $prices[0] === 'paid') {
            $query->where('starting_fees', '>', 0);
        }

        return $query;
    }

    private function articleIdQuery(array $filters): Builder
    {
        $query = BlogPost::query()->whereHas('blog', fn (Builder $blog) => $blog->where('status', 'active'));

        if ($filters['rating'] !== [] || $filters['availability'] !== [] || $filters['price'] === ['paid']) {
            return $query->whereRaw('1 = 0');
        }

        if ($filters['q'] !== '') {
            $like = $this->like($filters['q']);
            $query->where(function (Builder $inner) use ($like) {
                $inner->where('category', 'like', $like)
                    ->orWhereHas('blog', function (Builder $blog) use ($like) {
                        $blog->where('title', 'like', $like)->orWhere('description_short', 'like', $like);
                    });
            });
        }

        if ($filters['category'] !== '') {
            $this->whereTextMatches($query, self::CATEGORIES[$filters['category']]['needles'], 'article');
        }

        return $query;
    }

    /**
     * Restrict experts to a single Explore category.
     *
     * Source of truth is the linked Service catalogue: a professional matches when
     * they offer a service whose name/slug classifies to this category. Experts
     * without any classifiable service fall back to specialization needles, but
     * only when they do not already have a service mapped to a different category.
     */
    private function whereExpertCategory(Builder $query, string $categoryKey): void
    {
        if (! array_key_exists($categoryKey, self::CATEGORIES)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $serviceIds = $this->serviceIdsMappedToCategory($categoryKey);
        $otherMappedServiceIds = $this->serviceIdsMappedToOtherCategories($categoryKey);
        $needles = self::CATEGORIES[$categoryKey]['needles'];

        $query->where(function (Builder $outer) use ($serviceIds, $otherMappedServiceIds, $needles) {
            if ($serviceIds !== []) {
                $outer->whereHas(
                    'professionalServices',
                    fn (Builder $service) => $service->whereIn('service_id', $serviceIds)
                );
            }

            $outer->orWhere(function (Builder $fallback) use ($otherMappedServiceIds, $needles) {
                if ($otherMappedServiceIds !== []) {
                    $fallback->whereDoesntHave(
                        'professionalServices',
                        fn (Builder $service) => $service->whereIn('service_id', $otherMappedServiceIds)
                    );
                }

                $fallback->where(function (Builder $text) use ($needles) {
                    foreach ($needles as $needle) {
                        $like = $this->like($needle);
                        $text->orWhereHas(
                            'profile',
                            fn (Builder $profile) => $profile->where('specialization', 'like', $like)
                        )->orWhereHas('professionalServices', function (Builder $service) use ($like) {
                            $service->where('service_name', 'like', $like)
                                ->orWhere('tags', 'like', $like);
                        });
                    }
                });
            });
        });
    }

    /**
     * @return list<int>
     */
    private function serviceIdsMappedToCategory(string $categoryKey): array
    {
        return $this->serviceCategoryMap()[$categoryKey] ?? [];
    }

    /**
     * @return list<int>
     */
    private function serviceIdsMappedToOtherCategories(string $categoryKey): array
    {
        $ids = [];
        foreach ($this->serviceCategoryMap() as $mappedCategory => $serviceIds) {
            if ($mappedCategory === $categoryKey) {
                continue;
            }
            foreach ($serviceIds as $serviceId) {
                $ids[] = $serviceId;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Map each live Service id to at most one Explore category key.
     *
     * @return array<string, list<int>>
     */
    private function serviceCategoryMap(): array
    {
        if ($this->serviceCategoryMapCache !== null) {
            return $this->serviceCategoryMapCache;
        }

        $map = array_fill_keys(array_keys(self::CATEGORIES), []);

        foreach (Service::query()->get(['id', 'name', 'slug']) as $service) {
            $key = $this->categoryKeyForText(trim(($service->name ?? '').' '.($service->slug ?? '')));
            if ($key === null) {
                continue;
            }
            $map[$key][] = (int) $service->id;
        }

        return $this->serviceCategoryMapCache = $map;
    }

    private function whereTextMatches(Builder $query, array $needles, string $kind): void
    {
        $query->where(function (Builder $inner) use ($needles, $kind) {
            foreach ($needles as $needle) {
                $like = $this->like($needle);
                if ($kind === 'programme') {
                    $inner->orWhere('heading', 'like', $like)
                        ->orWhere('mini_heading', 'like', $like)
                        ->orWhere('short_description', 'like', $like);
                } else {
                    $inner->orWhere('category', 'like', $like)
                        ->orWhereHas('blog', function (Builder $blog) use ($like) {
                            $blog->where('title', 'like', $like)->orWhere('description_short', 'like', $like);
                        });
                }
            }
        });
    }

    private function expertRankQuery(array $filters)
    {
        $ids = $this->expertIdQuery($filters)->select('professionals.id')->reorder();
        $profiles = DB::table('profiles')
            ->select('professional_id', DB::raw('MIN(starting_price) as starting_price'))
            ->groupBy('professional_id');
        $ratings = DB::table('reviews')
            ->select('professional_id', DB::raw('AVG(rating) as rating_avg'))
            ->where('is_approved', 1)
            ->groupBy('professional_id');

        $query = DB::table('professionals')
            ->whereIn('professionals.id', $ids)
            ->leftJoinSub($profiles, 'profile_price', 'profile_price.professional_id', '=', 'professionals.id')
            ->leftJoinSub($ratings, 'rating_stats', 'rating_stats.professional_id', '=', 'professionals.id');

        $relevance = '1';
        $bindings = [];
        if ($filters['q'] !== '') {
            $relevance = 'CASE WHEN professionals.name LIKE ? THEN 3 WHEN professionals.name LIKE ? THEN 2 ELSE 1 END';
            $bindings = [$this->likePrefix($filters['q']), $this->like($filters['q'])];
        }

        return $query->selectRaw(
            "'expert' as kind, professionals.id as item_id, professionals.created_at as sort_at, profile_price.starting_price as price, COALESCE(rating_stats.rating_avg, 0) as rating, {$relevance} as relevance",
            $bindings
        );
    }

    private function programmeRankQuery(array $filters)
    {
        $ids = $this->programmeIdQuery($filters)->select('all_events.id')->reorder();
        $query = DB::table('all_events')->whereIn('all_events.id', $ids);
        $relevance = '1';
        $bindings = [];
        if ($filters['q'] !== '') {
            $relevance = 'CASE WHEN all_events.heading LIKE ? THEN 3 WHEN all_events.heading LIKE ? THEN 2 ELSE 1 END';
            $bindings = [$this->likePrefix($filters['q']), $this->like($filters['q'])];
        }

        return $query->selectRaw(
            "'programme' as kind, all_events.id as item_id, all_events.created_at as sort_at, all_events.starting_fees as price, 0 as rating, {$relevance} as relevance",
            $bindings
        );
    }

    private function articleRankQuery(array $filters)
    {
        $ids = $this->articleIdQuery($filters)->select('blog_posts.id')->reorder();
        $query = DB::table('blog_posts')
            ->join('blogs', 'blogs.id', '=', 'blog_posts.blog_id')
            ->whereIn('blog_posts.id', $ids);
        $relevance = '1';
        $bindings = [];
        if ($filters['q'] !== '') {
            $relevance = 'CASE WHEN blogs.title LIKE ? THEN 3 WHEN blogs.title LIKE ? THEN 2 ELSE 1 END';
            $bindings = [$this->likePrefix($filters['q']), $this->like($filters['q'])];
        }

        return $query->selectRaw(
            "'article' as kind, blog_posts.id as item_id, COALESCE(blog_posts.published_at, blog_posts.created_at) as sort_at, NULL as price, 0 as rating, {$relevance} as relevance",
            $bindings
        );
    }

    private function typeCounts(array $filters): array
    {
        $filters['type'] = 'all';
        $filters['types'] = [];

        return [
            'experts' => $this->expertIdQuery($filters)->count(),
            'programmes' => $this->programmesEnabled() ? $this->programmeIdQuery($filters)->count() : 0,
            'articles' => $this->articleIdQuery($filters)->count(),
            'tools' => 0,
        ];
    }

    private function categoryCounts(array $filters): array
    {
        $filters['category'] = '';
        $counts = array_fill_keys(array_keys(self::CATEGORIES), 0);

        if ($this->includes('experts', $filters)) {
            $rows = DB::table('professionals')
                ->whereIn('professionals.id', $this->expertIdQuery($filters)->select('professionals.id')->reorder())
                ->leftJoin('profiles', 'profiles.professional_id', '=', 'professionals.id')
                ->leftJoin('professional_services', 'professional_services.professional_id', '=', 'professionals.id')
                ->leftJoin('services', 'services.id', '=', 'professional_services.service_id')
                ->get([
                    'professionals.id',
                    'profiles.specialization',
                    'professional_services.service_id',
                    'professional_services.service_name',
                    'professional_services.tags',
                    'services.name as service_label',
                    'services.slug',
                ]);
            $grouped = [];
            foreach ($rows as $row) {
                $grouped[$row->id][] = $row;
            }
            foreach ($grouped as $expertRows) {
                // Prefer the linked service mapping used by whereExpertCategory.
                $key = $this->primaryCategoryForExpertRows($expertRows);
                if ($key !== null) {
                    $counts[$key]++;
                }
            }
        }

        if ($this->includes('programmes', $filters)) {
            foreach ($this->programmeIdQuery($filters)->get(['heading', 'mini_heading', 'short_description']) as $programme) {
                $this->incrementCategories($counts, strtolower(implode(' ', array_filter([
                    $programme->heading,
                    $programme->mini_heading,
                    $programme->short_description,
                ]))));
            }
        }

        if ($this->includes('articles', $filters)) {
            $articles = $this->articleIdQuery($filters)
                ->join('blogs', 'blogs.id', '=', 'blog_posts.blog_id')
                ->get(['blog_posts.category', 'blogs.title', 'blogs.description_short']);
            foreach ($articles as $article) {
                $this->incrementCategories($counts, strtolower(implode(' ', array_filter([
                    $article->category,
                    $article->title,
                    $article->description_short,
                ]))));
            }
        }

        return $counts;
    }

    private function priceCounts(array $filters): array
    {
        $filters['price'] = [];
        $free = 0;
        $paid = 0;

        if ($this->includes('experts', $filters)) {
            $freeFilters = $filters;
            $paidFilters = $filters;
            $freeFilters['price'] = ['free'];
            $paidFilters['price'] = ['paid'];
            $free += $this->expertIdQuery($freeFilters)->count();
            $paid += $this->expertIdQuery($paidFilters)->count();
        }
        if ($this->includes('programmes', $filters)) {
            $freeFilters = $filters;
            $paidFilters = $filters;
            $freeFilters['price'] = ['free'];
            $paidFilters['price'] = ['paid'];
            $free += $this->programmeIdQuery($freeFilters)->count();
            $paid += $this->programmeIdQuery($paidFilters)->count();
        }
        if ($this->includes('articles', $filters)) {
            $free += $this->articleIdQuery($filters)->count();
        }

        return ['free' => $free, 'paid' => $paid];
    }

    private function ratingCounts(array $filters): array
    {
        $filters['rating'] = [];
        if (!$this->includes('experts', $filters)) {
            return ['4.5' => 0, '4' => 0, '3.5' => 0];
        }

        $ids = $this->expertIdQuery($filters)->select('professionals.id')->reorder();
        $ratings = DB::table('reviews')
            ->select('professional_id', DB::raw('AVG(rating) as rating_avg'))
            ->where('is_approved', 1)
            ->groupBy('professional_id');
        $row = DB::query()
            ->fromSub($ids, 'experts')
            ->joinSub($ratings, 'rating_stats', 'rating_stats.professional_id', '=', 'experts.id')
            ->selectRaw('SUM(CASE WHEN rating_avg >= 4.5 THEN 1 ELSE 0 END) as r45, SUM(CASE WHEN rating_avg >= 4 THEN 1 ELSE 0 END) as r40, SUM(CASE WHEN rating_avg >= 3.5 THEN 1 ELSE 0 END) as r35')
            ->first();

        return [
            '4.5' => (int) ($row->r45 ?? 0),
            '4' => (int) ($row->r40 ?? 0),
            '3.5' => (int) ($row->r35 ?? 0),
        ];
    }

    private function availabilityCounts(array $filters): array
    {
        $filters['availability'] = [];
        if (!$this->includes('experts', $filters)) {
            return ['now' => 0, 'anytime' => 0];
        }

        $now = $filters;
        $anytime = $filters;
        $now['availability'] = ['now'];
        $anytime['availability'] = ['anytime'];

        return [
            'now' => $this->expertIdQuery($now)->count(),
            'anytime' => $this->expertIdQuery($anytime)->count(),
        ];
    }

    private function incrementCategories(array &$counts, string $haystack): void
    {
        foreach (self::CATEGORIES as $key => $category) {
            foreach ($category['needles'] as $needle) {
                if (str_contains($haystack, $needle)) {
                    $counts[$key]++;
                    break;
                }
            }
        }
    }

    /**
     * Classify free text into at most one Explore category key.
     */
    public function categoryKeyForText(string $text): ?string
    {
        $haystack = strtolower($text);
        foreach (self::CATEGORIES as $key => $category) {
            foreach ($category['needles'] as $needle) {
                if (str_contains($haystack, $needle)) {
                    return $key;
                }
            }
        }

        return null;
    }

    /**
     * Resolve the Psychology/Mental Health category key used on Experts.
     * Returns null only if the catalogue mapping was removed (misconfiguration).
     */
    public function psychologyCategoryKey(): ?string
    {
        return array_key_exists(self::PSYCHOLOGY_CATEGORY, self::CATEGORIES)
            ? self::PSYCHOLOGY_CATEGORY
            : null;
    }

    /**
     * Resolve a live Service for an Explore category using the shared service map.
     * Prefer preferred psychologist slug for mental-health when present.
     */
    public function resolveServiceForExploreCategory(string $categoryKey): ?Service
    {
        if ($categoryKey === self::PSYCHOLOGY_CATEGORY) {
            $preferred = $this->resolvePsychologistService();
            if ($preferred) {
                return $preferred;
            }
        }

        $ids = $this->serviceIdsMappedToCategory($categoryKey);
        if ($ids === []) {
            return null;
        }

        return Service::query()->whereIn('id', $ids)->orderBy('id')->first();
    }

    /**
     * Find a psychologist-related Service from the live catalogue (no hardcoded IDs).
     */
    public function resolvePsychologistService(): ?Service
    {
        $preferred = Service::query()
            ->where('slug', self::PREFERRED_PSYCHOLOGIST_SERVICE_SLUG)
            ->first();

        if ($preferred) {
            return $preferred;
        }

        $needles = self::CATEGORIES[self::PSYCHOLOGY_CATEGORY]['needles'] ?? ['psycholog', 'therap', 'mental', 'psychiatr'];

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
    }

    /**
     * @param  iterable<int, object>  $rows
     */
    private function primaryCategoryForExpertRows(iterable $rows): ?string
    {
        $map = $this->serviceCategoryMap();
        $specialization = null;
        $fallbackHaystack = [];

        foreach ($rows as $row) {
            $serviceId = isset($row->service_id) ? (int) $row->service_id : 0;
            if ($serviceId > 0) {
                foreach ($map as $key => $ids) {
                    if (in_array($serviceId, $ids, true)) {
                        return $key;
                    }
                }
            }

            $specialization = $specialization ?: ($row->specialization ?? null);
            $fallbackHaystack[] = $row->specialization ?? null;
            $fallbackHaystack[] = $row->service_name ?? null;
            $fallbackHaystack[] = $row->tags ?? null;
            $fallbackHaystack[] = $row->service_label ?? null;
            $fallbackHaystack[] = $row->slug ?? null;
        }

        return $this->categoryKeyForText(implode(' ', array_filter($fallbackHaystack)));
    }

    private function selectedKinds(array $filters): array
    {
        return array_values(array_filter(
            ['experts', 'programmes', 'articles'],
            fn (string $kind) => $this->includes($kind, $filters)
        ));
    }

    private function includes(string $kind, array $filters): bool
    {
        if ($kind === 'programmes' && ! $this->programmesEnabled()) {
            return false;
        }
        if ($filters['type'] === 'tools') {
            return false;
        }
        if (in_array($filters['type'], ['experts', 'programmes', 'articles'], true)) {
            return $filters['type'] === $kind;
        }
        if ($filters['types'] === []) {
            return true;
        }

        return in_array($kind, $filters['types'], true);
    }

    private function activeTypes(array $filters): array
    {
        if ($filters['type'] === 'tools') {
            return ['tools'];
        }
        if (in_array($filters['type'], ['experts', 'programmes', 'articles'], true)) {
            return [$filters['type']];
        }
        if ($filters['types'] === []) {
            return $this->programmesEnabled()
                ? ['experts', 'programmes', 'articles']
                : ['experts', 'articles'];
        }

        return $filters['types'];
    }

    private function programmesEnabled(): bool
    {
        return (bool) config('yuktione.programmes_enabled', true);
    }

    private function tab(array $filters): string
    {
        if ($filters['type'] === 'tools') {
            return 'tools';
        }
        $active = $this->activeTypes($filters);

        return count($active) === 1 ? $active[0] : 'all';
    }

    private function featured(): array
    {
        return [
            ['label' => 'Manage Anxiety', 'tone' => 'mint', 'icon' => 'leaf', 'query' => ['q' => 'Anxiety']],
            ['label' => 'Career Guidance', 'tone' => 'lilac', 'icon' => 'chart', 'query' => ['category' => 'career-business']],
            ['label' => 'Improve Relationships', 'tone' => 'blush', 'icon' => 'heart', 'query' => ['category' => 'relationships']],
            ['label' => 'Financial Wellness', 'tone' => 'cream', 'icon' => 'coins', 'query' => ['category' => 'money-finance']],
            ['label' => 'Better Sleep', 'tone' => 'blue', 'icon' => 'moon', 'query' => ['q' => 'Sleep']],
            ['label' => 'Spiritual Growth', 'tone' => 'lavender', 'icon' => 'spark', 'query' => ['category' => 'spirituality']],
        ];
    }

    private function like(string $value): string
    {
        return '%'.$this->escapeLike($value).'%';
    }

    private function likePrefix(string $value): string
    {
        return $this->escapeLike($value).'%';
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    private function professionalPhoto(?string $path): ?string
    {
        $blocked = [
            'professional_documents/McPqTOLmpLHhukE6ka0DJmmNWTU3qG6segI57ccy.jpg',
            'uploads/professionals/photo/media_68b52ad01436a.jpg',
        ];
        if (!filled($path) || in_array($path, $blocked, true)) {
            return null;
        }

        return $this->publicImage($path);
    }

    private function publicImage(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = $path[0] ?? null;
        }
        $path = ltrim((string) $path, '/');
        if ($path === '') {
            return null;
        }
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }
        if (Str::startsWith($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        // Avoid emitting broken /storage URLs when the upload is missing locally.
        if (!Storage::disk('public')->exists($path)) {
            return null;
        }

        return asset('storage/'.$path);
    }

    private function chipSet(iterable $labels): array
    {
        $chips = collect($labels)
            ->map(fn ($label) => $this->plain(is_string($label) ? $label : null))
            ->filter()
            ->unique()
            ->values();

        return [
            'chips' => $chips->take(2)->all(),
            'extra' => max(0, $chips->count() - 2),
        ];
    }

    private function priceParts(?float $amount, bool $perSession): array
    {
        if ($amount === null) {
            return ['Contact for pricing', null];
        }
        if ($amount <= 0) {
            return ['Free', null];
        }

        $converted = (float) convertCurrency($amount);
        $decimals = abs($converted - round($converted)) < 0.009 ? 0 : 2;

        return [
            getCurrencySymbol().number_format($converted, $decimals),
            $perSession ? '/session' : null,
        ];
    }

    private function plain(?string $value): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)) ?? '');

        return $text !== '' ? $text : null;
    }

    private function initials(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $letters = '';
        foreach (array_slice(array_filter($parts), 0, 2) as $part) {
            $letters .= Str::upper(Str::substr($part, 0, 1));
        }

        return $letters !== '' ? $letters : 'YK';
    }
}
