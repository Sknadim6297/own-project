<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tool;
use App\Models\User;
use App\Models\WeightGoal;
use App\Models\WeightMeasurement;
use App\Services\WeightJourneyService;
use App\Support\WeightJourney\WeightJourneyCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Account-backed Weight Journey for the mobile app.
 * Reuses WeightJourneyService and existing weight_* tables — no new schema.
 */
class MyWeightJourneyController extends Controller
{
    public function __construct(private WeightJourneyService $weights)
    {
    }

    public function summary(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $userId = (int) $user->id;
        $summary = $this->weights->journeySummary($userId);
        $unit = $summary['unit'];
        $recent = WeightMeasurement::query()
            ->where('user_id', $userId)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (WeightMeasurement $row) => $this->presentMeasurement($row, $unit));

        $latestChange = null;
        if ($recent->count() >= 2) {
            $delta = bcsub((string) $recent[0]['weight_kg'], (string) $recent[1]['weight_kg'], 3);
            $latestChange = [
                'kg' => $delta,
                'label' => $this->weights->formatNetChange($delta, $unit),
            ];
        }

        return response()->json([
            'status' => 'success',
            'open' => true,
            'timezone' => WeightJourneyCatalog::TIMEZONE,
            'unit' => $unit,
            'weight_min_kg' => WeightJourneyCatalog::WEIGHT_KG_MIN,
            'weight_max_kg' => WeightJourneyCatalog::WEIGHT_KG_MAX,
            'note_max' => WeightJourneyCatalog::NOTE_MAX,
            'directions' => collect(WeightJourneyCatalog::DIRECTIONS)->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
            ])->values(),
            'periods' => array_keys(WeightJourneyCatalog::PERIODS),
            'display_units' => WeightJourneyCatalog::DISPLAY_UNITS,
            'goal' => $summary['goal'] ? $this->presentGoal($summary['goal']) : null,
            'starting' => $summary['starting'],
            'latest' => $summary['latest'],
            'target' => $summary['target'],
            'net_kg' => $summary['net_kg'],
            'distance_kg' => $summary['distance_kg'],
            'goal_starting' => $summary['goal_starting'],
            'starting_label' => $summary['starting_label'],
            'latest_label' => $summary['latest_label'],
            'net_label' => $summary['net_label'],
            'target_label' => $summary['target_label'],
            'distance_label' => $summary['distance_label'],
            'goal_starting_label' => $summary['goal_starting_label'],
            'progress' => $summary['progress'],
            'latest_change' => $latestChange,
            'latest_recorded_at' => $recent->first()['recorded_at'] ?? null,
            'recent' => $recent->values(),
            'chart' => $this->weights->chart($userId, 'month', $unit),
        ]);
    }

    public function chart(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $period = is_string($request->query('period')) ? $request->query('period') : 'month';
        if (! isset(WeightJourneyCatalog::PERIODS[$period])) {
            $period = 'month';
        }
        $goal = $this->weights->activeGoal((int) $user->id);
        $unit = $goal?->display_unit ?? 'kg';

        return response()->json([
            'status' => 'success',
            'open' => true,
            'chart' => $this->weights->chart((int) $user->id, $period, $unit),
        ]);
    }

    public function measurements(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $goal = $this->weights->activeGoal((int) $user->id);
        $unit = $goal?->display_unit ?? 'kg';
        $page = $this->weights->paginateMeasurements((int) $user->id, 15);

        return response()->json([
            'status' => 'success',
            'open' => true,
            'unit' => $unit,
            'measurements' => collect($page->items())->map(
                fn (WeightMeasurement $row) => $this->presentMeasurement($row, $unit)
            )->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function storeMeasurement(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $data = $request->validate([
            'weight' => ['required'],
            'unit' => ['required', Rule::in(WeightJourneyCatalog::DISPLAY_UNITS)],
            'recorded_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:'.WeightJourneyCatalog::NOTE_MAX],
            'idempotency_key' => ['required', 'string', 'max:64'],
        ]);

        try {
            $kg = $this->weights->normalizeWeightKg($data['weight'], $data['unit']);
            $at = ! empty($data['recorded_at'])
                ? CarbonImmutable::parse($data['recorded_at'], WeightJourneyCatalog::TIMEZONE)
                : null;
            $row = $this->weights->addMeasurement(
                (int) $user->id,
                $kg,
                $data['idempotency_key'],
                $at,
                $data['note'] ?? null,
                $data['unit']
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['weight' => $exception->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'created' => $row->wasRecentlyCreated,
            'measurement' => $this->presentMeasurement($row, $data['unit']),
            'summary' => $this->compactSummary((int) $user->id),
        ], $row->wasRecentlyCreated ? 201 : 200);
    }

    public function destroyMeasurement(Request $request, int $measurement): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $row = WeightMeasurement::query()->findOrFail($measurement);
        if ((int) $row->user_id !== (int) $user->id) {
            abort(403);
        }

        $this->weights->deleteMeasurement($row);

        return response()->json([
            'status' => 'success',
            'deleted' => true,
            'summary' => $this->compactSummary((int) $user->id),
        ]);
    }

    public function saveGoal(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $data = $request->validate([
            'direction' => ['required', Rule::in(array_keys(WeightJourneyCatalog::DIRECTIONS))],
            'starting_weight' => ['required'],
            'target_weight' => ['nullable'],
            'target_date' => ['nullable', 'date'],
            'display_unit' => ['required', Rule::in(WeightJourneyCatalog::DISPLAY_UNITS)],
        ]);

        try {
            $goal = $this->weights->createOrReplaceGoal((int) $user->id, $data);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['starting_weight' => $exception->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'goal' => $this->presentGoal($goal),
            'summary' => $this->compactSummary((int) $user->id),
        ]);
    }

    public function archiveGoal(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $this->weights->archiveActiveGoal((int) $user->id);

        return response()->json([
            'status' => 'success',
            'archived' => true,
            'summary' => $this->compactSummary((int) $user->id),
        ]);
    }

    private function compactSummary(int $userId): array
    {
        $summary = $this->weights->journeySummary($userId);

        return [
            'unit' => $summary['unit'],
            'starting' => $summary['starting'],
            'latest' => $summary['latest'],
            'target' => $summary['target'],
            'net_kg' => $summary['net_kg'],
            'distance_kg' => $summary['distance_kg'],
            'starting_label' => $summary['starting_label'],
            'latest_label' => $summary['latest_label'],
            'net_label' => $summary['net_label'],
            'target_label' => $summary['target_label'],
            'distance_label' => $summary['distance_label'],
            'progress' => $summary['progress'],
            'goal' => $summary['goal'] ? $this->presentGoal($summary['goal']) : null,
        ];
    }

    private function presentGoal(WeightGoal $goal): array
    {
        $unit = $goal->display_unit ?: 'kg';

        return [
            'id' => (int) $goal->id,
            'direction' => $goal->direction,
            'direction_label' => WeightJourneyCatalog::DIRECTIONS[$goal->direction] ?? $goal->direction,
            'starting_weight_kg' => (string) $goal->starting_weight_kg,
            'target_weight_kg' => $goal->target_weight_kg !== null ? (string) $goal->target_weight_kg : null,
            'starting_label' => $this->weights->formatWeight((string) $goal->starting_weight_kg, $unit),
            'target_label' => $goal->target_weight_kg !== null
                ? $this->weights->formatWeight((string) $goal->target_weight_kg, $unit)
                : null,
            'target_date' => $goal->target_date
                ? CarbonImmutable::parse($goal->target_date)->toDateString()
                : null,
            'display_unit' => $unit,
            'status' => $goal->status,
        ];
    }

    private function presentMeasurement(WeightMeasurement $row, string $unit): array
    {
        return [
            'id' => (int) $row->id,
            'weight_kg' => (string) $row->weight_kg,
            'label' => $this->weights->formatWeight((string) $row->weight_kg, $unit),
            'recorded_at' => CarbonImmutable::parse($row->recorded_at)
                ->timezone(WeightJourneyCatalog::TIMEZONE)
                ->toIso8601String(),
            'note' => $row->note,
            'display_unit' => $row->display_unit,
            'idempotency_key' => $row->idempotency_key,
        ];
    }

    private function isOpen(): bool
    {
        try {
            $tool = Tool::query()->where('slug', 'weight-journey')->first();
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
