<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Tool;
use App\Models\WaterIntakeEntry;
use App\Services\WaterIntakeService;
use App\Support\WaterIntake\WaterIntakeCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class WaterIntakeController extends Controller
{
    public function __construct(private WaterIntakeService $water)
    {
    }

    public function dashboard(Request $request)
    {
        if (! $this->isOpen()) {
            return view('frontend.sections.water-intake.closed');
        }
        if (! Auth::guard('user')->check()) {
            return view('frontend.sections.water-intake.welcome', ['open' => true]);
        }

        $userId = (int) Auth::guard('user')->id();
        $date = $this->resolveDate($request->query('date'));
        $dash = $this->water->dashboard($userId, $date);

        return view('frontend.sections.water-intake.dashboard', [
            'dash' => $dash,
            'entries' => $this->water->dayEntries($userId, $date),
            'timezone' => WaterIntakeCatalog::TIMEZONE,
            'isToday' => $date === $this->water->today()->toDateString(),
            'status' => session('status'),
        ]);
    }

    public function store(Request $request)
    {
        $this->requireOpenAuth();
        $userId = (int) Auth::guard('user')->id();

        $data = $request->validate([
            'amount_ml' => ['required'],
            'idempotency_key' => ['required', 'string', 'max:64'],
            'consumed_at' => ['nullable', 'date'],
        ]);

        try {
            $amount = $this->water->normalizeAmountMl($data['amount_ml']);
            $consumedAt = null;
            if (! empty($data['consumed_at'])) {
                $consumedAt = CarbonImmutable::parse($data['consumed_at'], WaterIntakeCatalog::TIMEZONE);
            }
            $this->water->addEntry($userId, $amount, $data['idempotency_key'], $consumedAt);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount_ml' => $exception->getMessage()]);
        }

        return redirect()->route('tools.water-intake', ['date' => $this->water->today()->toDateString()])
            ->with('status', 'Water entry added.');
    }

    public function today(Request $request)
    {
        $this->requireOpenAuth();
        $userId = (int) Auth::guard('user')->id();
        $date = $this->resolveDate($request->query('date'));
        $dash = $this->water->dashboard($userId, $date);
        $entries = $this->water->dayEntries($userId, $date);

        return view('frontend.sections.water-intake.today', [
            'dash' => $dash,
            'entries' => $entries,
            'timezone' => WaterIntakeCatalog::TIMEZONE,
            'prevDate' => CarbonImmutable::createFromFormat('Y-m-d', $date, WaterIntakeCatalog::TIMEZONE)->subDay()->toDateString(),
            'nextDate' => CarbonImmutable::createFromFormat('Y-m-d', $date, WaterIntakeCatalog::TIMEZONE)->addDay()->toDateString(),
            'isToday' => $date === $this->water->today()->toDateString(),
        ]);
    }

    public function update(Request $request, WaterIntakeEntry $entry)
    {
        $this->requireOpenAuth();
        $this->authorizeEntry($entry);

        $data = $request->validate([
            'amount_ml' => ['required'],
            'consumed_at' => ['nullable', 'date'],
        ]);

        try {
            $amount = $this->water->normalizeAmountMl($data['amount_ml']);
            $consumedAt = ! empty($data['consumed_at'])
                ? CarbonImmutable::parse($data['consumed_at'], WaterIntakeCatalog::TIMEZONE)
                : null;
            $this->water->updateEntry($entry, $amount, $consumedAt);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['amount_ml' => $exception->getMessage()]);
        }

        $localDate = CarbonImmutable::parse($entry->fresh()->consumed_at)->timezone(WaterIntakeCatalog::TIMEZONE)->toDateString();

        return redirect()->route('tools.water-intake.today', ['date' => $localDate])
            ->with('status', 'Entry updated.');
    }

    public function destroy(WaterIntakeEntry $entry)
    {
        $this->requireOpenAuth();
        $this->authorizeEntry($entry);
        $localDate = CarbonImmutable::parse($entry->consumed_at)->timezone(WaterIntakeCatalog::TIMEZONE)->toDateString();
        $this->water->deleteEntry($entry);

        return redirect()->route('tools.water-intake.today', ['date' => $localDate])
            ->with('status', 'Entry deleted.');
    }

    public function week(Request $request)
    {
        $this->requireOpenAuth();
        $userId = (int) Auth::guard('user')->id();
        $end = $this->resolveDate($request->query('end'));
        $days = $this->water->weekSummary($userId, $end);
        $daysWithData = array_values(array_filter($days, fn ($d) => $d['has_entries']));
        $avg = $daysWithData === []
            ? null
            : (int) round(array_sum(array_column($daysWithData, 'total_ml')) / count($daysWithData));
        $settings = $this->water->settingsFor($userId);

        return view('frontend.sections.water-intake.week', [
            'days' => $days,
            'end' => $end,
            'avg_ml' => $avg,
            'avg_label' => $avg === null ? null : $this->water->formatAmount($avg, $settings->display_unit),
            'display_unit' => $settings->display_unit,
            'prevEnd' => CarbonImmutable::createFromFormat('Y-m-d', $end, WaterIntakeCatalog::TIMEZONE)->subDays(7)->toDateString(),
            'nextEnd' => CarbonImmutable::createFromFormat('Y-m-d', $end, WaterIntakeCatalog::TIMEZONE)->addDays(7)->toDateString(),
            'today' => $this->water->today()->toDateString(),
            'max' => max(1, ...array_column($days, 'total_ml'), ...array_column($days, 'goal_ml')),
        ]);
    }

    public function editGoal()
    {
        $this->requireOpenAuth();
        $settings = $this->water->settingsFor((int) Auth::guard('user')->id());

        return view('frontend.sections.water-intake.goal', [
            'settings' => $settings,
            'min' => WaterIntakeCatalog::GOAL_MIN_ML,
            'max' => WaterIntakeCatalog::GOAL_MAX_ML,
        ]);
    }

    public function updateGoal(Request $request)
    {
        $this->requireOpenAuth();
        $data = $request->validate([
            'daily_goal_ml' => ['required', 'integer', 'min:'.WaterIntakeCatalog::GOAL_MIN_ML, 'max:'.WaterIntakeCatalog::GOAL_MAX_ML],
            'display_unit' => ['required', Rule::in(WaterIntakeCatalog::DISPLAY_UNITS)],
        ]);

        try {
            $this->water->updateGoal(
                (int) Auth::guard('user')->id(),
                (int) $data['daily_goal_ml'],
                $data['display_unit']
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['daily_goal_ml' => $exception->getMessage()]);
        }

        return redirect()->route('tools.water-intake')->with('status', 'Daily goal updated.');
    }

    private function resolveDate(?string $date): string
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

    private function requireOpenAuth(): void
    {
        if (! $this->isOpen()) {
            abort(403);
        }
        if (! Auth::guard('user')->check()) {
            abort(403);
        }
    }

    private function authorizeEntry(WaterIntakeEntry $entry): void
    {
        if ((int) $entry->user_id !== (int) Auth::guard('user')->id()) {
            abort(403);
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
}
