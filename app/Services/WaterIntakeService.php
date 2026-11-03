<?php

namespace App\Services;

use App\Models\WaterIntakeEntry;
use App\Models\WaterIntakeSetting;
use App\Support\WaterIntake\WaterIntakeCatalog;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WaterIntakeService
{
    public function timezone(): string
    {
        return WaterIntakeCatalog::TIMEZONE;
    }

    public function today(CarbonImmutable|Carbon|null $now = null): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone())->startOfDay();
    }

    public function dayBounds(string $localDate): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m-d', $localDate, $this->timezone())->startOfDay();
        $end = $start->addDay();

        return [$start->utc(), $end->utc()];
    }

    public function settingsFor(int $userId): WaterIntakeSetting
    {
        $settings = WaterIntakeSetting::query()->where('user_id', $userId)->first();
        if ($settings) {
            return $settings;
        }

        $settings = new WaterIntakeSetting([
            'daily_goal_ml' => WaterIntakeCatalog::DEFAULT_GOAL_ML,
            'display_unit' => 'ml',
        ]);
        $settings->user_id = $userId;
        $settings->save();

        WaterIntakeSetting::query()->whereKey($settings->id)->update([
            'goal_changed_at' => now(),
        ]);

        return $settings->fresh();
    }

    public function goalForDate(int $userId, string $localDate): int
    {
        $settings = $this->settingsFor($userId);
        $dayStart = CarbonImmutable::createFromFormat('Y-m-d', $localDate, $this->timezone())->startOfDay();
        $changedAt = $settings->goal_changed_at
            ? CarbonImmutable::parse($settings->goal_changed_at)->timezone($this->timezone())
            : null;

        // Current goal applies from the local day it was changed onward.
        // Days before the change use the previous goal when recorded; otherwise current.
        if ($changedAt && $dayStart->lt($changedAt->startOfDay()) && $settings->previous_goal_ml) {
            return (int) $settings->previous_goal_ml;
        }

        return (int) $settings->daily_goal_ml;
    }

    public function updateGoal(int $userId, int $goalMl, string $displayUnit = 'ml'): WaterIntakeSetting
    {
        if ($goalMl < WaterIntakeCatalog::GOAL_MIN_ML || $goalMl > WaterIntakeCatalog::GOAL_MAX_ML) {
            throw new InvalidArgumentException('Daily goal must be between 250 ml and 10,000 ml.');
        }
        if (! in_array($displayUnit, WaterIntakeCatalog::DISPLAY_UNITS, true)) {
            throw new InvalidArgumentException('Display unit is invalid.');
        }

        $settings = $this->settingsFor($userId);
        if ((int) $settings->daily_goal_ml !== $goalMl) {
            $settings->previous_goal_ml = (int) $settings->daily_goal_ml;
            $settings->daily_goal_ml = $goalMl;
            $settings->goal_changed_at = now();
        }
        $settings->display_unit = $displayUnit;
        $settings->save();

        return $settings;
    }

    public function normalizeAmountMl(mixed $value): int
    {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException('Amount is required.');
        }
        if (is_string($value)) {
            $value = trim($value);
        }
        if (! is_numeric($value)) {
            throw new InvalidArgumentException('Amount must be numeric.');
        }
        $float = (float) $value;
        if (! is_finite($float)) {
            throw new InvalidArgumentException('Amount must be a finite number.');
        }
        if ((string) (int) $float !== (string) (int) round($float) && abs($float - (int) $float) > 0.00001) {
            // Allow whole numbers only for stored ml.
        }
        if (floor($float) != $float) {
            throw new InvalidArgumentException('Amount must be a whole number of millilitres.');
        }
        $ml = (int) $float;
        if ($ml < WaterIntakeCatalog::ENTRY_MIN_ML || $ml > WaterIntakeCatalog::ENTRY_MAX_ML) {
            throw new InvalidArgumentException('Amount must be between 1 ml and 5,000 ml.');
        }

        return $ml;
    }

    public function addEntry(int $userId, int $amountMl, string $idempotencyKey, ?CarbonImmutable $consumedAt = null): WaterIntakeEntry
    {
        if ($amountMl < WaterIntakeCatalog::ENTRY_MIN_ML || $amountMl > WaterIntakeCatalog::ENTRY_MAX_ML) {
            throw new InvalidArgumentException('Amount must be between 1 ml and 5,000 ml.');
        }
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 64) {
            throw new InvalidArgumentException('Idempotency key is invalid.');
        }

        $consumedAt = ($consumedAt ?? CarbonImmutable::now($this->timezone()))->timezone('UTC');

        return DB::transaction(function () use ($userId, $amountMl, $idempotencyKey, $consumedAt) {
            $existing = WaterIntakeEntry::query()
                ->where('user_id', $userId)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                return $existing;
            }

            $entry = new WaterIntakeEntry([
                'amount_ml' => $amountMl,
                'consumed_at' => $consumedAt,
                'idempotency_key' => $idempotencyKey,
            ]);
            $entry->user_id = $userId;
            $entry->save();

            return $entry;
        });
    }

    public function updateEntry(WaterIntakeEntry $entry, int $amountMl, ?CarbonImmutable $consumedAt = null): WaterIntakeEntry
    {
        if ($amountMl < WaterIntakeCatalog::ENTRY_MIN_ML || $amountMl > WaterIntakeCatalog::ENTRY_MAX_ML) {
            throw new InvalidArgumentException('Amount must be between 1 ml and 5,000 ml.');
        }
        $entry->amount_ml = $amountMl;
        if ($consumedAt) {
            $entry->consumed_at = $consumedAt->timezone('UTC');
        }
        $entry->save();

        return $entry;
    }

    public function deleteEntry(WaterIntakeEntry $entry): void
    {
        $entry->delete();
    }

    public function dayTotalMl(int $userId, string $localDate): int
    {
        [$start, $end] = $this->dayBounds($localDate);

        return (int) WaterIntakeEntry::query()
            ->where('user_id', $userId)
            ->where('consumed_at', '>=', $start)
            ->where('consumed_at', '<', $end)
            ->sum('amount_ml');
    }

    public function dayEntries(int $userId, string $localDate): Collection
    {
        [$start, $end] = $this->dayBounds($localDate);

        return WaterIntakeEntry::query()
            ->where('user_id', $userId)
            ->where('consumed_at', '>=', $start)
            ->where('consumed_at', '<', $end)
            ->orderBy('consumed_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<int, array{date: string, label: string, total_ml: int, goal_ml: int, has_entries: bool, is_today: bool}>
     */
    public function weekSummary(int $userId, string $endLocalDate): array
    {
        $end = CarbonImmutable::createFromFormat('Y-m-d', $endLocalDate, $this->timezone())->startOfDay();
        $start = $end->subDays(6);
        [$rangeStart] = $this->dayBounds($start->toDateString());
        [, $rangeEnd] = $this->dayBounds($end->toDateString());

        $entries = WaterIntakeEntry::query()
            ->where('user_id', $userId)
            ->where('consumed_at', '>=', $rangeStart)
            ->where('consumed_at', '<', $rangeEnd)
            ->get(['amount_ml', 'consumed_at']);

        $rows = $entries->groupBy(function (WaterIntakeEntry $entry) {
            return CarbonImmutable::parse($entry->consumed_at)->timezone($this->timezone())->toDateString();
        })->map(fn (Collection $group) => (int) $group->sum('amount_ml'));

        $today = $this->today()->toDateString();
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $start->addDays($i)->toDateString();
            $hasEntries = $rows->has($date);
            $total = $hasEntries ? (int) $rows[$date] : 0;
            $days[] = [
                'date' => $date,
                'label' => CarbonImmutable::createFromFormat('Y-m-d', $date, $this->timezone())->format('D'),
                'total_ml' => $total,
                'goal_ml' => $this->goalForDate($userId, $date),
                'has_entries' => $hasEntries,
                'is_today' => $date === $today,
            ];
        }

        return $days;
    }

    public function formatAmount(int $ml, string $unit): string
    {
        if ($unit === 'L') {
            $litres = $ml / 1000;

            return rtrim(rtrim(number_format($litres, 3, '.', ''), '0'), '.').' L';
        }

        return number_format($ml).' ml';
    }

    public function dashboard(int $userId, ?string $localDate = null): array
    {
        $date = $localDate ?: $this->today()->toDateString();
        $settings = $this->settingsFor($userId);
        $goal = $this->goalForDate($userId, $date);
        $total = $this->dayTotalMl($userId, $date);
        $remaining = max(0, $goal - $total);
        $percent = $goal > 0 ? (int) round(($total / $goal) * 100) : 0;

        return [
            'date' => $date,
            'total_ml' => $total,
            'goal_ml' => $goal,
            'remaining_ml' => $remaining,
            'percent' => $percent,
            'display_unit' => $settings->display_unit,
            'settings' => $settings,
            'quick_adds' => WaterIntakeCatalog::QUICK_ADD_ML,
            'total_label' => $this->formatAmount($total, $settings->display_unit),
            'goal_label' => $this->formatAmount($goal, $settings->display_unit),
            'remaining_label' => $this->formatAmount($remaining, $settings->display_unit),
        ];
    }
}
