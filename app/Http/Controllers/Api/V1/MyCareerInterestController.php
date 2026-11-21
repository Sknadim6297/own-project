<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CareerInterestAttempt;
use App\Models\Tool;
use App\Models\User;
use App\Services\CareerInterestRecommender;
use App\Services\CareerInterestRecorder;
use App\Services\CareerInterestScorer;
use App\Support\CareerInterest\CareerInterestCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Career Interest Test for the mobile app.
 * Reuses CareerInterestCatalog / Scorer / Recommender / Recorder — no new schema.
 */
class MyCareerInterestController extends Controller
{
    public function __construct(
        private CareerInterestScorer $scorer,
        private CareerInterestRecommender $recommender,
        private CareerInterestRecorder $recorder,
    ) {
    }

    /** Guest-safe catalogue + openness (website guest-try). */
    public function options(): JsonResponse
    {
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $categories = collect(CareerInterestCatalog::categories())->map(fn (array $cat) => [
            'key' => $cat['key'],
            'label' => $cat['label'],
            'kicker' => $cat['kicker'],
            'description' => $cat['description'],
            'step' => $cat['step'],
        ])->values();

        $questions = collect(CareerInterestCatalog::questions())->map(fn (array $q) => [
            'id' => $q['id'],
            'category' => $q['category'],
            'text' => $q['text'],
        ])->values();

        $scale = collect(CareerInterestCatalog::scaleOptions())->values()->map(fn (array $opt) => [
            'value' => $opt['value'],
            'label' => $opt['label'],
            'emoji' => $opt['emoji'],
        ]);

        return response()->json([
            'status' => 'success',
            'open' => true,
            'assessment_version' => CareerInterestCatalog::ASSESSMENT_VERSION,
            'scoring_version' => CareerInterestCatalog::SCORING_VERSION,
            'question_count' => CareerInterestCatalog::questionCount(),
            'duration_label' => '~5 min',
            'category_count' => count(CareerInterestCatalog::CATEGORY_ORDER),
            'categories' => $categories,
            'category_order' => CareerInterestCatalog::CATEGORY_ORDER,
            'questions' => $questions,
            'scale' => $scale,
            'about' => [
                'age_groups' => $this->mapChoices(CareerInterestCatalog::AGE_GROUPS),
                'education' => $this->mapChoices(CareerInterestCatalog::EDUCATION),
                'situations' => $this->mapChoices(CareerInterestCatalog::SITUATIONS),
                'geographies' => $this->mapChoices(CareerInterestCatalog::GEOGRAPHIES),
            ],
            'disclaimer' => 'Self-rating of interests across six areas—not aptitude or guaranteed career fit.',
        ]);
    }

    /** Score + recommend without saving (guest preview, same as website profile). */
    public function preview(Request $request): JsonResponse
    {
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $payload = $this->validatedAssessment($request);

        try {
            $scored = $this->scorer->score($payload['answers']);
            $recommendations = $this->recommender->recommend($scored, $payload['about']);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['answers' => $exception->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'saved' => false,
            'result' => $this->presentScored($scored, $recommendations, $payload['about']),
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $payload = $this->validatedAssessment($request, requireKey: true);

        try {
            $record = $this->recorder->storeForUser(
                (int) $user->id,
                strtolower($payload['completion_key']),
                $payload['about'],
                $payload['answers'],
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['entry' => $exception->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'created' => $record->wasRecentlyCreated,
            'attempt' => $this->presentAttempt($record),
        ], $record->wasRecentlyCreated ? 201 : 200);
    }

    public function history(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $page = max(1, (int) $request->query('page', 1));
        $rows = CareerInterestAttempt::query()
            ->where('user_id', (int) $user->id)
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->paginate(8, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'attempts' => collect($rows->items())->map(fn (CareerInterestAttempt $row) => [
                'id' => (int) $row->id,
                'completed_at' => $this->stamp($row->completed_at),
                'top_categories' => $row->top_categories,
                'top_labels' => $this->topLabels($row->top_categories ?? []),
                'interpretation' => $row->interpretation,
            ])->values(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

    public function show(Request $request, CareerInterestAttempt $attempt): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }
        $this->authorizeAttempt($attempt, $user);

        return response()->json([
            'status' => 'success',
            'attempt' => $this->presentAttempt($attempt),
        ]);
    }

    public function destroy(Request $request, CareerInterestAttempt $attempt): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }
        $this->authorizeAttempt($attempt, $user);
        $attempt->delete();

        return response()->json([
            'status' => 'success',
            'deleted' => true,
        ]);
    }

    private function validatedAssessment(Request $request, bool $requireKey = false): array
    {
        $questionIds = CareerInterestCatalog::questionIds();
        $rules = [
            'age_group' => ['required', 'string', Rule::in(array_keys(CareerInterestCatalog::AGE_GROUPS))],
            'education' => ['required', 'string', Rule::in(array_keys(CareerInterestCatalog::EDUCATION))],
            'situation' => ['required', 'string', Rule::in(array_keys(CareerInterestCatalog::SITUATIONS))],
            'geography' => ['required', 'string', Rule::in(array_keys(CareerInterestCatalog::GEOGRAPHIES))],
            'answers' => ['required', 'array'],
        ];
        foreach ($questionIds as $id) {
            $rules['answers.'.$id] = ['required', 'integer', Rule::in(CareerInterestCatalog::scaleValues())];
        }
        if ($requireKey) {
            $rules['completion_key'] = ['required', 'uuid'];
        }

        $data = $request->validate($rules);

        return [
            'completion_key' => $data['completion_key'] ?? null,
            'about' => [
                'age_group' => $data['age_group'],
                'education' => $data['education'],
                'situation' => $data['situation'],
                'geography' => $data['geography'],
            ],
            'answers' => collect($questionIds)->mapWithKeys(
                fn (string $id) => [$id => (int) $data['answers'][$id]]
            )->all(),
        ];
    }

    private function presentScored(array $scored, array $recommendations, array $about): array
    {
        return [
            'assessment_version' => $scored['assessment_version'],
            'scoring_version' => $scored['scoring_version'],
            'about' => [
                'age_group' => $about['age_group'],
                'education' => $about['education'],
                'situation' => $about['situation'],
                'geography' => $about['geography'],
                'age_group_label' => CareerInterestCatalog::AGE_GROUPS[$about['age_group']] ?? $about['age_group'],
                'education_label' => CareerInterestCatalog::EDUCATION[$about['education']] ?? $about['education'],
                'situation_label' => CareerInterestCatalog::SITUATIONS[$about['situation']] ?? $about['situation'],
                'geography_label' => CareerInterestCatalog::GEOGRAPHIES[$about['geography']] ?? $about['geography'],
            ],
            'percentages' => $scored['percentages'],
            'totals' => $scored['totals'],
            'ranked' => $scored['ranked'],
            'top_categories' => $scored['top'],
            'interpretation' => $scored['interpretation'],
            'recommendations' => [
                'catalogue_version' => $recommendations['catalogue_version'],
                'disclaimer' => $recommendations['disclaimer'],
                'items' => $recommendations['items'],
            ],
        ];
    }

    private function presentAttempt(CareerInterestAttempt $row): array
    {
        $about = [
            'age_group' => $row->age_group,
            'education' => $row->education,
            'situation' => $row->situation,
            'geography' => $row->geography,
        ];
        $ranked = [];
        $percentages = is_array($row->category_percentages) ? $row->category_percentages : [];
        foreach (CareerInterestCatalog::CATEGORY_ORDER as $index => $category) {
            $ranked[] = [
                'key' => $category,
                'label' => CareerInterestCatalog::categories()[$category]['label'],
                'description' => CareerInterestCatalog::categories()[$category]['description'],
                'percent' => (int) ($percentages[$category] ?? 0),
                'order' => $index,
            ];
        }
        usort($ranked, static function (array $a, array $b) {
            if ($a['percent'] === $b['percent']) {
                return $a['order'] <=> $b['order'];
            }

            return $b['percent'] <=> $a['percent'];
        });

        $recommendations = is_array($row->recommendations) ? $row->recommendations : [];

        return [
            'id' => (int) $row->id,
            'completion_key' => $row->completion_key,
            'completed_at' => $this->stamp($row->completed_at),
            'assessment_version' => $row->assessment_version,
            'scoring_version' => $row->scoring_version,
            'catalogue_version' => $row->catalogue_version,
            'about' => [
                ...$about,
                'age_group_label' => CareerInterestCatalog::AGE_GROUPS[$row->age_group] ?? $row->age_group,
                'education_label' => CareerInterestCatalog::EDUCATION[$row->education] ?? $row->education,
                'situation_label' => CareerInterestCatalog::SITUATIONS[$row->situation] ?? $row->situation,
                'geography_label' => CareerInterestCatalog::GEOGRAPHIES[$row->geography] ?? $row->geography,
            ],
            'percentages' => $percentages,
            'totals' => $row->category_totals,
            'ranked' => $ranked,
            'top_categories' => $row->top_categories,
            'interpretation' => $row->interpretation,
            'recommendations' => [
                'catalogue_version' => $recommendations['catalogue_version'] ?? null,
                'disclaimer' => $recommendations['disclaimer'] ?? null,
                'items' => $recommendations['items'] ?? [],
            ],
        ];
    }

    private function topLabels(array $keys): array
    {
        return collect($keys)->map(
            fn ($key) => CareerInterestCatalog::categories()[$key]['label'] ?? $key
        )->values()->all();
    }

    private function mapChoices(array $map): array
    {
        return collect($map)->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
        ])->values()->all();
    }

    private function stamp(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return CarbonImmutable::parse($value)
            ->timezone('Asia/Kolkata')
            ->toIso8601String();
    }

    private function authorizeAttempt(CareerInterestAttempt $attempt, User $user): void
    {
        if ((int) $attempt->user_id !== (int) $user->id) {
            abort(403);
        }
    }

    private function isOpen(): bool
    {
        try {
            $tool = Tool::query()->where('slug', 'career-interest-test')->first();
        } catch (QueryException) {
            return false;
        }

        return $tool ? $tool->canLaunch() : false;
    }

    private function closed(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'open' => false,
            'message' => 'This tool is not available right now.',
        ], 403);
    }

    private function customer(Request $request): User
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
