<?php

namespace App\Services;

use App\Models\WeightGoal;
use App\Models\WeightMeasurement;
use App\Support\WeightJourney\WeightJourneyCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WeightJourneyService
{
    public function timezone(): string
    {
        return WeightJourneyCatalog::TIMEZONE;
    }

    public function kgToLb(string $kg): string
    {
        return bcdiv(bcmul($kg, WeightJourneyCatalog::LB_PER_KG, 8), '1', 2);
    }

    public function lbToKg(string $lb): string
    {
        return bcdiv($lb, WeightJourneyCatalog::LB_PER_KG, 3);
    }

    public function normalizeWeightKg(mixed $value, string $unit): string
    {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException('Weight is required.');
        }
        if (is_string($value)) {
            $value = trim($value);
        }
        if (! is_numeric($value)) {
            throw new InvalidArgumentException('Weight must be numeric.');
        }
        $raw = (string) $value;
        if (! is_finite((float) $raw) || (float) $raw <= 0) {
            throw new InvalidArgumentException('Weight must be a finite positive number.');
        }
        if (! in_array($unit, WeightJourneyCatalog::DISPLAY_UNITS, true)) {
            throw new InvalidArgumentException('Weight unit is invalid.');
        }

        $kg = $unit === 'lb' ? $this->lbToKg($raw) : bcadd($raw, '0', 3);
        if (bccomp($kg, WeightJourneyCatalog::WEIGHT_KG_MIN, 3) < 0
            || bccomp($kg, WeightJourneyCatalog::WEIGHT_KG_MAX, 3) > 0) {
            throw new InvalidArgumentException('Weight must be between 20 kg and 400 kg (or equivalent lb).');
        }

        return $kg;
    }

    public function formatWeight(string $kg, string $unit): string
    {
        if ($unit === 'lb') {
            return $this->kgToLb($kg).' lb';
        }

        return rtrim(rtrim(number_format((float) $kg, 3, '.', ''), '0'), '.').' kg';
    }

    public function activeGoal(int $userId): ?WeightGoal
    {
        return WeightGoal::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();
    }

    public function goalHistory(int $userId): Collection
    {
        return WeightGoal::query()
            ->where('user_id', $userId)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get();
    }

    public function createOrReplaceGoal(int $userId, array $data): WeightGoal
    {
        $direction = $data['direction'];
        if (! isset(WeightJourneyCatalog::DIRECTIONS[$direction])) {
            throw new InvalidArgumentException('Goal direction is invalid.');
        }

        $unit = $data['display_unit'] ?? 'kg';
        $startKg = $this->normalizeWeightKg($data['starting_weight'], $unit);
        $targetKg = null;
        if ($direction !== 'track' && ($data['target_weight'] ?? null) !== null && $data['target_weight'] !== '') {
            $targetKg = $this->normalizeWeightKg($data['target_weight'], $unit);
        }
        if (in_array($direction, ['lose', 'gain'], true) && $targetKg === null) {
            throw new InvalidArgumentException('A target weight is required for lose or gain goals.');
        }
        if ($direction === 'maintain') {
            $targetKg = $startKg;
        }

        $targetDate = null;
        if (! empty($data['target_date'])) {
            $targetDate = CarbonImmutable::createFromFormat('Y-m-d', $data['target_date'], $this->timezone())->toDateString();
        }

        return DB::transaction(function () use ($userId, $direction, $startKg, $targetKg, $targetDate, $unit) {
            $now = now();
            WeightGoal::query()
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->update([
                    'status' => 'archived',
                    'effective_to' => $now,
                    'updated_at' => $now,
                ]);

            $goal = new WeightGoal([
                'direction' => $direction,
                'starting_weight_kg' => $startKg,
                'target_weight_kg' => $targetKg,
                'target_date' => $targetDate,
                'display_unit' => $unit,
                'status' => 'active',
                'effective_from' => $now,
                'effective_to' => null,
            ]);
            $goal->user_id = $userId;
            $goal->save();

            return $goal;
        });
    }

    public function archiveActiveGoal(int $userId): void
    {
        WeightGoal::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->update([
                'status' => 'archived',
                'effective_to' => now(),
                'updated_at' => now(),
            ]);
    }

    public function addMeasurement(
        int $userId,
        string $weightKg,
        string $idempotencyKey,
        ?CarbonImmutable $recordedAt = null,
        ?string $note = null,
        string $displayUnit = 'kg'
    ): WeightMeasurement {
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 64) {
            throw new InvalidArgumentException('Idempotency key is invalid.');
        }
        if ($note !== null && mb_strlen($note) > WeightJourneyCatalog::NOTE_MAX) {
            throw new InvalidArgumentException('Note is too long.');
        }

        $recordedAt = ($recordedAt ?? CarbonImmutable::now($this->timezone()))->timezone('UTC');
        if ($recordedAt->gt(CarbonImmutable::now('UTC')->addMinutes(5))) {
            throw new InvalidArgumentException('Future-dated measurements are not allowed.');
        }

        return DB::transaction(function () use ($userId, $weightKg, $idempotencyKey, $recordedAt, $note, $displayUnit) {
            $existing = WeightMeasurement::query()
                ->where('user_id', $userId)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                return $existing;
            }

            $row = new WeightMeasurement([
                'weight_kg' => $weightKg,
                'recorded_at' => $recordedAt,
                'note' => $note,
                'display_unit' => $displayUnit,
                'idempotency_key' => $idempotencyKey,
            ]);
            $row->user_id = $userId;
            $row->save();

            return $row;
        });
    }

    public function updateMeasurement(
        WeightMeasurement $measurement,
        string $weightKg,
        ?CarbonImmutable $recordedAt = null,
        ?string $note = null,
        ?string $displayUnit = null
    ): WeightMeasurement {
        if ($note !== null && mb_strlen($note) > WeightJourneyCatalog::NOTE_MAX) {
            throw new InvalidArgumentException('Note is too long.');
        }
        if ($recordedAt) {
            if ($recordedAt->timezone('UTC')->gt(CarbonImmutable::now('UTC')->addMinutes(5))) {
                throw new InvalidArgumentException('Future-dated measurements are not allowed.');
            }
            $measurement->recorded_at = $recordedAt->timezone('UTC');
        }
        $measurement->weight_kg = $weightKg;
        $measurement->note = $note;
        if ($displayUnit) {
            $measurement->display_unit = $displayUnit;
        }
        $measurement->save();

        return $measurement;
    }

    public function deleteMeasurement(WeightMeasurement $measurement): void
    {
        $measurement->delete();
    }

    public function latest(int $userId): ?WeightMeasurement
    {
        return WeightMeasurement::query()
            ->where('user_id', $userId)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first();
    }

    public function earliest(int $userId): ?WeightMeasurement
    {
        return WeightMeasurement::query()
            ->where('user_id', $userId)
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->first();
    }

    public function paginateMeasurements(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return WeightMeasurement::query()
            ->where('user_id', $userId)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{points: array<int, array{date: string, weight_kg: string, label: string}>, period: string, days: int}
     */
    public function chart(int $userId, string $period, string $displayUnit = 'kg'): array
    {
        if (! isset(WeightJourneyCatalog::PERIODS[$period])) {
            $period = 'month';
        }
        $days = WeightJourneyCatalog::PERIODS[$period];
        $end = CarbonImmutable::now($this->timezone())->endOfDay();
        $start = $end->subDays($days - 1)->startOfDay();

        $rows = WeightMeasurement::query()
            ->where('user_id', $userId)
            ->where('recorded_at', '>=', $start->timezone('UTC'))
            ->where('recorded_at', '<=', $end->timezone('UTC'))
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();

        $byDay = [];
        foreach ($rows as $row) {
            $localDate = CarbonImmutable::parse($row->recorded_at)->timezone($this->timezone())->toDateString();
            // latest_per_local_day aggregation
            $byDay[$localDate] = $row;
        }

        $points = [];
        foreach ($byDay as $date => $row) {
            $points[] = [
                'date' => $date,
                'weight_kg' => (string) $row->weight_kg,
                'label' => $this->formatWeight((string) $row->weight_kg, $displayUnit),
            ];
        }

        return [
            'points' => array_values($points),
            'period' => $period,
            'days' => $days,
            'aggregation' => WeightJourneyCatalog::CHART_AGGREGATION,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ];
    }

    public function formatNetChange(?string $netKg, string $unit): ?string
    {
        if ($netKg === null) {
            return null;
        }
        $sign = bccomp($netKg, '0', 3) > 0 ? '+' : (bccomp($netKg, '0', 3) < 0 ? '−' : '');
        $abs = bccomp($netKg, '0', 3) < 0 ? bcmul($netKg, '-1', 3) : $netKg;

        return $sign.$this->formatWeight($abs, $unit);
    }

    public function journeySummary(int $userId): array
    {
        $goal = $this->activeGoal($userId);
        $unit = $goal?->display_unit ?? 'kg';
        $start = $this->earliest($userId);
        $latest = $this->latest($userId);
        $startKg = $start ? (string) $start->weight_kg : ($goal ? (string) $goal->starting_weight_kg : null);
        $latestKg = $latest ? (string) $latest->weight_kg : null;
        $net = ($startKg !== null && $latestKg !== null) ? bcsub($latestKg, $startKg, 3) : null;

        $progress = null;
        if ($goal && $latestKg !== null && $goal->target_weight_kg !== null
            && in_array($goal->direction, ['lose', 'gain'], true)) {
            $span = bcsub((string) $goal->target_weight_kg, (string) $goal->starting_weight_kg, 3);
            if (bccomp($span, '0', 3) !== 0) {
                $done = bcsub($latestKg, (string) $goal->starting_weight_kg, 3);
                $ratio = (float) bcdiv($done, $span, 6);
                $progress = [
                    'percent' => (int) round(max(0, min(100, $ratio * 100))),
                    'meaningful' => true,
                ];
            } else {
                $progress = ['percent' => null, 'meaningful' => false, 'reason' => 'start_equals_target'];
            }
        } elseif ($goal && in_array($goal->direction, ['maintain', 'track'], true)) {
            $progress = ['percent' => null, 'meaningful' => false, 'reason' => $goal->direction];
        }

        $targetKg = ($goal && $goal->target_weight_kg !== null) ? (string) $goal->target_weight_kg : null;
        $distance = ($latestKg !== null && $targetKg !== null) ? bcsub($latestKg, $targetKg, 3) : null;
        $goalStartKg = $goal ? (string) $goal->starting_weight_kg : null;

        return [
            'goal' => $goal,
            'unit' => $unit,
            'starting' => $startKg,
            'latest' => $latestKg,
            'net_kg' => $net,
            'target' => $targetKg,
            'distance_kg' => $distance,
            'goal_starting' => $goalStartKg,
            'starting_label' => $startKg ? $this->formatWeight($startKg, $unit) : null,
            'latest_label' => $latestKg ? $this->formatWeight($latestKg, $unit) : null,
            'net_label' => $this->formatNetChange($net, $unit),
            'target_label' => $targetKg ? $this->formatWeight($targetKg, $unit) : null,
            'distance_label' => $this->formatNetChange($distance, $unit),
            'goal_starting_label' => $goalStartKg ? $this->formatWeight($goalStartKg, $unit) : null,
            'progress' => $progress,
            'history' => $this->goalHistory($userId),
        ];
    }
}
