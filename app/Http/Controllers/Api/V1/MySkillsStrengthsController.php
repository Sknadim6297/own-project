<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SkillsStrengthsAttempt;
use App\Models\Tool;
use App\Models\User;
use App\Services\SkillsStrengthsRecorder;
use App\Services\SkillsStrengthsScorer;
use App\Support\SkillsStrengths\SkillsStrengthsCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Skills & Strengths Assessment for the mobile app.
 * Reuses SkillsStrengthsCatalog / Scorer / Recorder — no new schema.
 */
class MySkillsStrengthsController extends Controller
{
    public function __construct(
        private SkillsStrengthsScorer $scorer,
        private SkillsStrengthsRecorder $recorder,
    ) {
    }

    public function options(): JsonResponse
    {
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $categories = collect(SkillsStrengthsCatalog::categories())->map(fn (array $cat) => [
            'key' => $cat['key'],
            'label' => $cat['label'],
            'short' => $cat['short'],
            'step' => $cat['step'],
            'range_label' => $cat['range_label'],
        ])->values();

        $questions = collect(SkillsStrengthsCatalog::questions())->map(fn (array $q) => [
            'id' => $q['id'],
            'category' => $q['category'],
            'number' => $q['number'] ?? null,
            'text' => $q['text'],
        ])->values();

        $scale = collect(SkillsStrengthsCatalog::scaleOptions())->values()->map(fn (array $opt) => [
            'value' => $opt['value'],
            'label' => $opt['label'],
            'emoji' => $opt['emoji'],
        ]);

        return response()->json([
            'status' => 'success',
            'open' => true,
            'assessment_version' => SkillsStrengthsCatalog::ASSESSMENT_VERSION,
            'scoring_version' => SkillsStrengthsCatalog::SCORING_VERSION,
            'question_count' => count(SkillsStrengthsCatalog::questions()),
            'duration_label' => '~7 min',
            'category_count' => count(SkillsStrengthsCatalog::CATEGORY_ORDER),
            'categories' => $categories,
            'category_order' => SkillsStrengthsCatalog::CATEGORY_ORDER,
            'questions' => $questions,
            'scale' => $scale,
            'disclaimer' => 'Self-ratings of skills across eight areas—not hiring or competence claims.',
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $answers = $this->validatedAnswers($request);

        try {
            $scored = $this->scorer->score($answers);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['answers' => $exception->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'saved' => false,
            'result' => $this->presentScored($scored),
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $data = $request->validate([
            'completion_key' => ['required', 'uuid'],
            'answers' => ['required', 'array'],
        ]);
        $answers = $this->validatedAnswers($request);

        try {
            $record = $this->recorder->storeForUser(
                (int) $user->id,
                strtolower($data['completion_key']),
                $answers,
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
        $rows = SkillsStrengthsAttempt::query()
            ->where('user_id', (int) $user->id)
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->paginate(8, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'attempts' => collect($rows->items())->map(fn (SkillsStrengthsAttempt $row) => [
                'id' => (int) $row->id,
                'completed_at' => $this->stamp($row->completed_at),
                'strength_labels' => $this->labelsFromRows($row->strengths ?? []),
                'summary' => $row->summary,
            ])->values(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

    public function show(Request $request, SkillsStrengthsAttempt $attempt): JsonResponse
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

    public function destroy(Request $request, SkillsStrengthsAttempt $attempt): JsonResponse
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

    private function validatedAnswers(Request $request): array
    {
        $questionIds = SkillsStrengthsCatalog::questionIds();
        $rules = ['answers' => ['required', 'array']];
        foreach ($questionIds as $id) {
            $rules['answers.'.$id] = ['required', 'integer', Rule::in(SkillsStrengthsCatalog::scaleValues())];
        }
        $data = $request->validate($rules);

        return collect($questionIds)->mapWithKeys(
            fn (string $id) => [$id => (int) $data['answers'][$id]]
        )->all();
    }

    private function presentScored(array $scored): array
    {
        return [
            'assessment_version' => $scored['assessment_version'],
            'scoring_version' => $scored['scoring_version'],
            'percentages' => $scored['percentages'],
            'totals' => $scored['totals'],
            'ranked' => $scored['ranked'],
            'strengths' => $scored['strengths'],
            'develop' => $scored['develop'],
            'summary' => $scored['summary'],
            'meaning' => $scored['meaning'],
        ];
    }

    private function presentAttempt(SkillsStrengthsAttempt $row): array
    {
        $percentages = is_array($row->category_percentages) ? $row->category_percentages : [];
        $ranked = [];
        foreach (SkillsStrengthsCatalog::CATEGORY_ORDER as $index => $category) {
            $meta = SkillsStrengthsCatalog::categories()[$category];
            $ranked[] = [
                'key' => $category,
                'label' => $meta['label'],
                'short' => $meta['short'],
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

        return [
            'id' => (int) $row->id,
            'completion_key' => $row->completion_key,
            'completed_at' => $this->stamp($row->completed_at),
            'assessment_version' => $row->assessment_version,
            'scoring_version' => $row->scoring_version,
            'percentages' => $percentages,
            'totals' => $row->category_totals,
            'ranked' => $ranked,
            'strengths' => is_array($row->strengths) ? $row->strengths : [],
            'develop' => is_array($row->develop) ? $row->develop : [],
            'summary' => $row->summary,
            'meaning' => $row->meaning,
        ];
    }

    private function labelsFromRows(array $rows): array
    {
        return collect($rows)->map(fn ($row) => is_array($row) ? ($row['label'] ?? $row['key'] ?? null) : null)
            ->filter()
            ->values()
            ->all();
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

    private function authorizeAttempt(SkillsStrengthsAttempt $attempt, User $user): void
    {
        if ((int) $attempt->user_id !== (int) $user->id) {
            abort(403);
        }
    }

    private function isOpen(): bool
    {
        try {
            $tool = Tool::query()->where('slug', 'skills-strengths-assessment')->first();
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
