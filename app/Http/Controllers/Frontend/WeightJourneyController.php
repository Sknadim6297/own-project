<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Tool;
use App\Models\WeightMeasurement;
use App\Services\WeightJourneyService;
use App\Support\WeightJourney\WeightJourneyCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class WeightJourneyController extends Controller
{
    public function __construct(private WeightJourneyService $weights)
    {
    }

    public function welcome()
    {
        if (! $this->isOpen()) {
            return view('frontend.sections.weight-journey.closed');
        }
        if (Auth::guard('user')->check()) {
            return redirect()->route('tools.weight-journey');
        }

        return view('frontend.sections.weight-journey.welcome', ['open' => true]);
    }

    public function journey()
    {
        if (! $this->isOpen()) {
            return view('frontend.sections.weight-journey.closed');
        }
        if (! Auth::guard('user')->check()) {
            return view('frontend.sections.weight-journey.welcome', ['open' => true]);
        }

        $userId = (int) Auth::guard('user')->id();
        $summary = $this->weights->journeySummary($userId);
        $recent = WeightMeasurement::query()
            ->where('user_id', $userId)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('frontend.sections.weight-journey.journey', [
            'summary' => $summary,
            'recent' => $recent,
            'timezone' => WeightJourneyCatalog::TIMEZONE,
            'status' => session('status'),
        ]);
    }

    public function goalForm()
    {
        $this->requireOpenAuth();
        $goal = $this->weights->activeGoal((int) Auth::guard('user')->id());

        return view('frontend.sections.weight-journey.goal', [
            'goal' => $goal,
            'directions' => WeightJourneyCatalog::DIRECTIONS,
            'history' => $this->weights->goalHistory((int) Auth::guard('user')->id()),
        ]);
    }

    public function saveGoal(Request $request)
    {
        $this->requireOpenAuth();
        $data = $request->validate([
            'direction' => ['required', Rule::in(array_keys(WeightJourneyCatalog::DIRECTIONS))],
            'starting_weight' => ['required'],
            'target_weight' => ['nullable'],
            'target_date' => ['nullable', 'date'],
            'display_unit' => ['required', Rule::in(WeightJourneyCatalog::DISPLAY_UNITS)],
        ]);

        try {
            $this->weights->createOrReplaceGoal((int) Auth::guard('user')->id(), $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['starting_weight' => $exception->getMessage()]);
        }

        return redirect()->route('tools.weight-journey')->with('status', 'Weight goal saved.');
    }

    public function archiveGoal()
    {
        $this->requireOpenAuth();
        $this->weights->archiveActiveGoal((int) Auth::guard('user')->id());

        return redirect()->route('tools.weight-journey.goal')->with('status', 'Active goal archived. You can set a new one anytime.');
    }

    public function logForm()
    {
        $this->requireOpenAuth();
        $userId = (int) Auth::guard('user')->id();
        $goal = $this->weights->activeGoal($userId);
        $unit = $goal?->display_unit ?? 'kg';
        $recent = WeightMeasurement::query()
            ->where('user_id', $userId)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('frontend.sections.weight-journey.log', [
            'unit' => $unit,
            'recent' => $recent,
            'timezone' => WeightJourneyCatalog::TIMEZONE,
        ]);
    }

    public function storeMeasurement(Request $request)
    {
        $this->requireOpenAuth();
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
            $this->weights->addMeasurement(
                (int) Auth::guard('user')->id(),
                $kg,
                $data['idempotency_key'],
                $at,
                $data['note'] ?? null,
                $data['unit']
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['weight' => $exception->getMessage()]);
        }

        return redirect()->route('tools.weight-journey.log')->with('status', 'Weight logged.');
    }

    public function updateMeasurement(Request $request, WeightMeasurement $measurement)
    {
        $this->requireOpenAuth();
        $this->authorizeMeasurement($measurement);
        $data = $request->validate([
            'weight' => ['required'],
            'unit' => ['required', Rule::in(WeightJourneyCatalog::DISPLAY_UNITS)],
            'recorded_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:'.WeightJourneyCatalog::NOTE_MAX],
        ]);

        try {
            $kg = $this->weights->normalizeWeightKg($data['weight'], $data['unit']);
            $at = ! empty($data['recorded_at'])
                ? CarbonImmutable::parse($data['recorded_at'], WeightJourneyCatalog::TIMEZONE)
                : null;
            $this->weights->updateMeasurement($measurement, $kg, $at, $data['note'] ?? null, $data['unit']);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['weight' => $exception->getMessage()]);
        }

        return redirect()->route('tools.weight-journey.log')->with('status', 'Measurement updated.');
    }

    public function destroyMeasurement(WeightMeasurement $measurement)
    {
        $this->requireOpenAuth();
        $this->authorizeMeasurement($measurement);
        $this->weights->deleteMeasurement($measurement);

        return redirect()->route('tools.weight-journey.log')->with('status', 'Measurement deleted.');
    }

    public function progress(Request $request)
    {
        $this->requireOpenAuth();
        $userId = (int) Auth::guard('user')->id();
        $period = (string) $request->query('period', 'month');
        if (! isset(WeightJourneyCatalog::PERIODS[$period])) {
            $period = 'month';
        }
        $goal = $this->weights->activeGoal($userId);
        $unit = $goal?->display_unit ?? 'kg';
        $chart = $this->weights->chart($userId, $period, $unit);
        $history = $this->weights->paginateMeasurements($userId, 12);

        return view('frontend.sections.weight-journey.progress', [
            'chart' => $chart,
            'period' => $period,
            'periods' => WeightJourneyCatalog::PERIODS,
            'unit' => $unit,
            'history' => $history,
            'timezone' => WeightJourneyCatalog::TIMEZONE,
            'aggregation' => WeightJourneyCatalog::CHART_AGGREGATION,
        ]);
    }

    private function requireOpenAuth(): void
    {
        if (! $this->isOpen()) {
            abort(403);
        }
        if (! Auth::guard('user')->check()) {
            abort(403);
        }
    }

    private function authorizeMeasurement(WeightMeasurement $measurement): void
    {
        if ((int) $measurement->user_id !== (int) Auth::guard('user')->id()) {
            abort(403);
        }
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
}
