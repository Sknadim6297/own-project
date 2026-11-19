<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Professional;
use App\Models\ProfessionalOtherInformation;
use App\Models\Rate;
use App\Services\ExploreCatalog;
use App\Services\Mobile\ExpertBookingCatalog;
use App\Support\Explore\CategoryImages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Customer-facing experts — same accepted/active professionals as the website explore/home.
 * Bank / document fields are never exposed.
 */
class ExpertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim(strip_tags((string) $request->query('q', '')));
        $q = Str::limit($q, 100, '');
        $specialty = trim(strip_tags((string) $request->query('specialty', '')));
        $category = trim((string) $request->query('category', ''));
        $limit = min(50, max(1, (int) $request->query('limit', 24)));
        $page = max(1, (int) $request->query('page', 1));
        $featured = $request->boolean('featured');

        $query = Professional::query()
            ->with(['profile', 'professionalServices.service'])
            ->withAvg(['reviews as average_rating' => fn ($builder) => $builder->where('is_approved', 1)], 'rating')
            ->withCount(['reviews as review_count' => fn ($builder) => $builder->where('is_approved', 1)])
            ->withMin('rates as min_rate', 'final_rate')
            ->where('status', 'accepted')
            ->where('active', true)
            ->whereHas('profile');

        if ($q !== '') {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(function ($builder) use ($like) {
                $builder->where('name', 'like', $like)
                    ->orWhereHas('profile', function ($profile) use ($like) {
                        $profile->where('specialization', 'like', $like)
                            ->orWhere('bio', 'like', $like)
                            ->orWhere('education', 'like', $like);
                    });
            });
        }

        if ($specialty !== '') {
            $like = '%'.addcslashes($specialty, '%_\\').'%';
            $query->where(function ($builder) use ($like) {
                $builder->whereHas('profile', fn ($profile) => $profile->where('specialization', 'like', $like))
                    ->orWhereHas('professionalServices.service', fn ($service) => $service->where('name', 'like', $like));
            });
        }

        if ($category !== '') {
            app(ExploreCatalog::class)->constrainExpertsToCategory($query, $category);
        }

        $meta = null;
        if ($featured) {
            $query->whereHas('professionalServices')->latest()->orderByDesc('id')->limit(min(8, $limit));
        } else {
            // Total of the filtered set so clients can show a real count and page through it.
            $total = (clone $query)->count();
            $query->latest()->orderByDesc('id')->forPage($page, $limit);
            $meta = [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $limit)),
            ];
        }

        $experts = $query->get()
            ->map(fn (Professional $expert) => $this->present($expert))
            ->filter()
            ->values();

        return response()->json([
            'status' => 'success',
            'experts' => $experts,
            'meta' => $meta,
            'experience_buckets' => ExploreCatalog::EXPERIENCE_BUCKETS,
            'categories' => collect(ExploreCatalog::CATEGORIES)
                ->map(fn (array $category, string $slug) => [
                    'slug' => $slug,
                    'label' => $category['label'],
                    'image_url' => CategoryImages::url($slug),
                ])
                ->values(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $expert = Professional::query()
            ->with(['profile', 'professionalServices.service', 'professionalServices.subServices', 'rates'])
            ->withAvg(['reviews as average_rating' => fn ($builder) => $builder->where('is_approved', 1)], 'rating')
            ->withCount(['reviews as review_count' => fn ($builder) => $builder->where('is_approved', 1)])
            ->withMin('rates as min_rate', 'final_rate')
            ->where('status', 'accepted')
            ->where('active', true)
            ->find($id);

        if (! $expert || ! $expert->profile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Expert not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'expert' => $this->present($expert, true),
        ]);
    }

    /**
     * Website booking calendar for one expert: bookable dates, each date's time slots and
     * whether a slot is already taken (booking_timedates, cancelled excluded).
     */
    public function availability(int $id, ExpertBookingCatalog $catalog): JsonResponse
    {
        $expert = Professional::query()
            ->where('status', 'accepted')
            ->where('active', true)
            ->whereHas('profile')
            ->find($id);

        if (! $expert) {
            return response()->json([
                'status' => 'error',
                'message' => 'Expert not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'timezone' => ExpertBookingCatalog::SLOT_TIMEZONE,
        ] + $catalog->schedule($expert->id));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function present(Professional $expert, bool $detailed = false): ?array
    {
        $profile = $expert->profile;
        if (! $profile) {
            return null;
        }

        $experienceKey = is_string($profile->experience) ? $profile->experience : null;
        $experienceLabel = $experienceKey && isset(ExploreCatalog::EXPERIENCE_BUCKETS[$experienceKey])
            ? ExploreCatalog::EXPERIENCE_BUCKETS[$experienceKey]
            : $this->plain($experienceKey);

        $price = is_numeric($profile->starting_price)
            ? (float) $profile->starting_price
            : (is_numeric($expert->min_rate) ? (float) $expert->min_rate : null);

        $services = $expert->professionalServices
            ->map(fn ($row) => $row->service?->name ?: $row->service_name)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $payload = [
            'id' => $expert->id,
            'name' => $expert->name,
            'specialization' => $this->plain($profile->specialization),
            'experience' => $experienceLabel,
            'experience_key' => $experienceKey,
            'education' => $this->plain($profile->education) ?: $this->plain($profile->education2),
            'bio' => $detailed ? $this->plain($profile->bio) : null,
            'starting_price' => $price,
            'currency' => 'INR',
            'rating' => ((int) $expert->review_count) > 0
                ? round((float) $expert->average_rating, 1)
                : null,
            'review_count' => (int) $expert->review_count,
            'photo_url' => $this->photoUrl($profile->photo),
            'services' => $services,
            'verified' => $expert->status === 'accepted' && (bool) $expert->active,
            'status' => $expert->status,
            'active' => (bool) $expert->active,
        ];

        if ($detailed) {
            // Website profile shows (and books) the service-level plans only.
            $payload['session_rates'] = $expert->rates
                ->whereNull('sub_service_id')
                ->sortBy('final_rate')
                ->map(fn (Rate $rate) => [
                    'id' => $rate->id,
                    'session_type' => $rate->session_type,
                    'num_sessions' => (int) $rate->num_sessions,
                    'rate_per_session' => (float) $rate->rate_per_session,
                    'final_rate' => (float) $rate->final_rate,
                    'duration' => $rate->duration,
                    'features' => is_array($rate->features) ? array_values(array_filter($rate->features, 'is_string')) : [],
                ])
                ->values()
                ->all();
            $payload += $this->profileDetails($expert);
        }

        return $payload;
    }

    /**
     * The rest of the website profile (expert-profile.blade.php): chips, quote, qualifications,
     * service description, approved reviews and the lowest per-session price.
     *
     * @return array<string, mixed>
     */
    private function profileDetails(Professional $expert): array
    {
        $profile = $expert->profile;
        $service = $expert->professionalServices->first();
        $other = ProfessionalOtherInformation::where('professional_id', $expert->id)->first();

        $chips = collect();
        if ($specialization = $this->plain($profile->specialization)) {
            $chips = $chips->merge(preg_split('/\s*,\s*/', $specialization));
        }
        if ($service && filled($service->tags)) {
            $chips = $chips->merge(preg_split('/\s*,\s*/', (string) $service->tags));
        }
        if ($service) {
            $chips = $chips->merge($service->subServices->pluck('name'));
        }
        if ($other && is_array($other->specializations)) {
            $chips = $chips->merge($other->specializations);
        }

        $qualifications = collect();
        $education = $other?->education;
        if (is_string($education)) {
            $education = json_decode($education, true);
        }
        if (is_array($education) && isset($education['college_name']) && is_array($education['college_name'])) {
            foreach ($education['college_name'] as $index => $college) {
                $degree = trim((string) ($education['degree'][$index] ?? ''));
                $college = trim((string) $college);
                if ($degree !== '' || $college !== '') {
                    $qualifications->push(['title' => $degree !== '' ? $degree : $college, 'place' => $degree !== '' ? $college : '']);
                }
            }
        }
        foreach (['education', 'education2'] as $field) {
            $value = trim(strip_tags((string) ($profile->{$field} ?? '')));
            if ($value !== '' && ! str_starts_with($value, '{') && ! str_starts_with($value, '[')) {
                $qualifications->push(['title' => $value, 'place' => '']);
            }
        }

        $quote = $this->plain($profile->comments);
        if ($quote !== null && strlen($quote) > 180) {
            $quote = null;
        }

        $reviews = $expert->reviews()
            ->where('is_approved', 1)
            ->latest()
            ->limit(30)
            ->get(['id', 'rating', 'review_text', 'created_at'])
            ->map(fn ($review) => [
                'id' => $review->id,
                'rating' => (int) $review->rating,
                'text' => $this->plain($review->review_text),
                'created_at' => optional($review->created_at)->toDateString(),
            ])
            ->values()
            ->all();

        $lowest = $expert->rates->whereNull('sub_service_id')
            ->filter(fn (Rate $rate) => is_numeric($rate->rate_per_session))
            ->min('rate_per_session');

        return [
            'quote' => $quote,
            'chips' => $chips->map(fn ($chip) => trim(strip_tags((string) $chip)))->filter()->unique()->values()->all(),
            'qualifications' => $qualifications->unique('title')->values()->all(),
            'service_description' => $service ? $this->plain($service->description) : null,
            'lowest_session_price' => is_numeric($lowest) ? (float) $lowest : null,
            'reviews' => $reviews,
        ];
    }

    private function plain(?string $value): ?string
    {
        $clean = trim(str_replace("\0", '', strip_tags((string) $value)));

        return $clean === '' ? null : $clean;
    }

    private function photoUrl(?string $path): ?string
    {
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
        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return asset('storage/'.$path);
    }
}
