<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Professional;
use App\Models\Rate;
use App\Services\ExploreCatalog;
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
        $featured = $request->boolean('featured');

        $query = Professional::query()
            ->with(['profile', 'professionalServices.service'])
            ->withAvg(['reviews as average_rating' => fn ($builder) => $builder->where('is_approved', 1)], 'rating')
            ->withCount(['reviews as review_count' => fn ($builder) => $builder->where('is_approved', 1)])
            ->withMin('rates as min_rate', 'final_rate')
            ->where('status', 'accepted')
            ->where('active', true)
            ->whereHas('profile');

        if ($featured) {
            $query->whereHas('professionalServices')->latest()->limit(min(8, $limit));
        } else {
            $query->latest()->limit($limit);
        }

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

        $experts = $query->get()
            ->map(fn (Professional $expert) => $this->present($expert))
            ->filter()
            ->values();

        return response()->json([
            'status' => 'success',
            'experts' => $experts,
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
            $payload['session_rates'] = $expert->rates
                ->map(fn (Rate $rate) => [
                    'id' => $rate->id,
                    'session_type' => $rate->session_type,
                    'num_sessions' => $rate->num_sessions,
                    'rate_per_session' => (float) $rate->rate_per_session,
                    'final_rate' => (float) $rate->final_rate,
                    'duration' => $rate->duration,
                ])
                ->values()
                ->all();
        }

        return $payload;
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
