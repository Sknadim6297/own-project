<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tool;
use App\Models\User;
use App\Models\WaterIntakeEntry;
use App\Services\WaterIntakeService;
use App\Support\WaterIntake\WaterIntakeCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Account-backed Water Intake Tracker for the mobile app.
 * Reuses WaterIntakeService and the same tables as the website — no new schema.
 */
class MyWaterIntakeController extends Controller
{
    public function __construct(private WaterIntakeService $water)
    {
    }

    public function dashboard(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $date = $this->resolveDate($request->query('date'));
        $dash = $this->water->dashboard((int) $user->id, $date);
        $entries = $this->water->dayEntries((int) $user->id, $date)->map(fn (WaterIntakeEntry $entry) => $this->presentEntry($entry));

        return response()->json([
            'status' => 'success',
            'open' => true,
            'date' => $dash['date'],
            'total_ml' => $dash['total_ml'],
            'goal_ml' => $dash['goal_ml'],
            'remaining_ml' => $dash['remaining_ml'],
            'percent' => $dash['percent'],
            'display_unit' => $dash['display_unit'],
            'total_label' => $dash['total_label'],
            'goal_label' => $dash['goal_label'],
            'remaining_label' => $dash['remaining_label'],
            'quick_adds' => collect($dash['quick_adds'])->map(fn (array $item) => [
                'ml' => $item['ml'],
                'label' => $item['label'],
            ])->values(),
            'goal_min_ml' => WaterIntakeCatalog::GOAL_MIN_ML,
            'goal_max_ml' => WaterIntakeCatalog::GOAL_MAX_ML,
            'entry_min_ml' => WaterIntakeCatalog::ENTRY_MIN_ML,
            'entry_max_ml' => WaterIntakeCatalog::ENTRY_MAX_ML,
            'timezone' => WaterIntakeCatalog::TIMEZONE,
            'is_today' => $date === $this->water->today()->toDateString(),
            'entries' => $entries,
        ]);
    }

    public function week(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $end = $this->resolveDate($request->query('end'));
        $days = $this->water->weekSummary((int) $user->id, $end);
        $withEntries = collect($days)->where('has_entries', true);
        $averageMl = $withEntries->count() > 0
            ? (int) round($withEntries->avg('total_ml'))
            : null;

        return response()->json([
            'status' => 'success',
            'open' => true,
            'end' => $end,
            'timezone' => WaterIntakeCatalog::TIMEZONE,
            'average_ml' => $averageMl,
            'days' => $days,
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $data = $request->validate([
            'daily_goal_ml' => ['required', 'integer', 'min:'.WaterIntakeCatalog::GOAL_MIN_ML, 'max:'.WaterIntakeCatalog::GOAL_MAX_ML],
            'display_unit' => ['required', Rule::in(WaterIntakeCatalog::DISPLAY_UNITS)],
        ]);

        try {
            $settings = $this->water->updateGoal((int) $user->id, (int) $data['daily_goal_ml'], $data['display_unit']);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['daily_goal_ml' => $exception->getMessage()]);
        }

        $dash = $this->water->dashboard((int) $user->id);

        return response()->json([
            'status' => 'success',
            'settings' => [
                'daily_goal_ml' => (int) $settings->daily_goal_ml,
                'display_unit' => $settings->display_unit,
            ],
            'dashboard' => [
                'date' => $dash['date'],
                'total_ml' => $dash['total_ml'],
                'goal_ml' => $dash['goal_ml'],
                'remaining_ml' => $dash['remaining_ml'],
                'percent' => $dash['percent'],
                'display_unit' => $dash['display_unit'],
                'total_label' => $dash['total_label'],
                'goal_label' => $dash['goal_label'],
                'remaining_label' => $dash['remaining_label'],
            ],
        ]);
    }

    public function storeEntry(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $data = $request->validate([
            'amount_ml' => ['required'],
            'idempotency_key' => ['required', 'string', 'max:64'],
            'consumed_at' => ['nullable', 'date'],
        ]);

        try {
            $amount = $this->water->normalizeAmountMl($data['amount_ml']);
            $consumedAt = ! empty($data['consumed_at'])
                ? CarbonImmutable::parse($data['consumed_at'], WaterIntakeCatalog::TIMEZONE)
                : null;
            $entry = $this->water->addEntry((int) $user->id, $amount, $data['idempotency_key'], $consumedAt);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['amount_ml' => $exception->getMessage()]);
        }

        $dash = $this->water->dashboard((int) $user->id);

        return response()->json([
            'status' => 'success',
            'created' => $entry->wasRecentlyCreated,
            'entry' => $this->presentEntry($entry),
            'dashboard' => [
                'date' => $dash['date'],
                'total_ml' => $dash['total_ml'],
                'goal_ml' => $dash['goal_ml'],
                'remaining_ml' => $dash['remaining_ml'],
                'percent' => $dash['percent'],
                'display_unit' => $dash['display_unit'],
                'total_label' => $dash['total_label'],
                'goal_label' => $dash['goal_label'],
                'remaining_label' => $dash['remaining_label'],
            ],
        ], $entry->wasRecentlyCreated ? 201 : 200);
    }

    public function destroyEntry(Request $request, int $entry): JsonResponse
    {
        $user = $this->customer($request);
        if (! $this->isOpen()) {
            return $this->closed();
        }

        $record = WaterIntakeEntry::query()->findOrFail($entry);
        if ((int) $record->user_id !== (int) $user->id) {
            abort(403);
        }

        $this->water->deleteEntry($record);
        $dash = $this->water->dashboard((int) $user->id);

        return response()->json([
            'status' => 'success',
            'deleted' => true,
            'dashboard' => [
                'date' => $dash['date'],
                'total_ml' => $dash['total_ml'],
                'goal_ml' => $dash['goal_ml'],
                'remaining_ml' => $dash['remaining_ml'],
                'percent' => $dash['percent'],
                'display_unit' => $dash['display_unit'],
                'total_label' => $dash['total_label'],
                'goal_label' => $dash['goal_label'],
                'remaining_label' => $dash['remaining_label'],
            ],
        ]);
    }

    private function presentEntry(WaterIntakeEntry $entry): array
    {
        return [
            'id' => (int) $entry->id,
            'amount_ml' => (int) $entry->amount_ml,
            'consumed_at' => CarbonImmutable::parse($entry->consumed_at)
                ->timezone(WaterIntakeCatalog::TIMEZONE)
                ->toIso8601String(),
            'idempotency_key' => $entry->idempotency_key,
        ];
    }

    private function resolveDate(mixed $date): string
    {
        if (! is_string($date) || $date === '') {
            return $this->water->today()->toDateString();
        }
        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $date, WaterIntakeCatalog::TIMEZONE)->toDateString();
        } catch (\Throwable) {
            return $this->water->today()->toDateString();
        }
    }

    private function isOpen(): bool
    {
        try {
            $tool = Tool::query()->where('slug', 'water-intake-tracker')->first();
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
