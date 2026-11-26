<?php

namespace App\Support\Pfm;

use App\Services\Pfm\PfmCalculator;
use App\Services\Pfm\PfmMath;

/**
 * Task 51 — Presentational invested/value projection series for Savings & Investment results.
 * Re-expresses the existing SI model (monthly compounding, end-of-interval contributions).
 * Does not change engine outputs; final points snap to authoritative invested/corpus.
 */
final class SavingsInvestmentProjection
{
    /**
     * @param  array<string, mixed>  $inputs
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public static function build(array $inputs, array $result, int $maxChartPoints = 48): array
    {
        $has = static fn (array $bag, string $key): bool => array_key_exists($key, $bag) && $bag[$key] !== null && $bag[$key] !== '';

        $years = $has($inputs, 'years') ? (float) $inputs['years'] : null;
        $initial = $has($inputs, 'initial_amount') ? (float) $inputs['initial_amount'] : 0.0;
        $contribution = 0.0;
        if ($has($inputs, 'contribution_amount')) {
            $contribution = (float) $inputs['contribution_amount'];
        } elseif ($has($inputs, 'monthly_contribution')) {
            $contribution = (float) $inputs['monthly_contribution'];
        }
        $returnPercent = $has($inputs, 'expected_return_percent') ? (float) $inputs['expected_return_percent'] : null;

        $frequency = 'monthly';
        if ($has($inputs, 'investment_frequency')) {
            $candidate = strtolower(trim((string) $inputs['investment_frequency']));
            if (in_array($candidate, ['monthly', 'quarterly', 'half_yearly', 'yearly'], true)) {
                $frequency = $candidate;
            }
        } elseif ($has($result, 'investment_frequency')) {
            $candidate = strtolower(trim((string) $result['investment_frequency']));
            if (in_array($candidate, ['monthly', 'quarterly', 'half_yearly', 'yearly'], true)) {
                $frequency = $candidate;
            }
        }

        $engineInvested = $has($result, 'invested') ? (float) $result['invested'] : null;
        $engineCorpus = $has($result, 'corpus') ? (float) $result['corpus'] : null;
        $engineGains = $has($result, 'gains') ? (float) $result['gains'] : null;
        $months = $has($result, 'months')
            ? (int) $result['months']
            : ($years !== null ? (int) round($years * 12) : 0);

        if ($years === null || $returnPercent === null || $months < 1 || $engineInvested === null || $engineCorpus === null) {
            return [
                'available' => false,
                'reason' => 'Growth projection is unavailable because this calculation snapshot is missing duration, return, or maturity values.',
                'points' => [],
                'chart_points' => [],
                'table' => [],
                'year_rows' => [],
            ];
        }

        $interval = PfmCalculator::contributionIntervalMonths($frequency);
        if ($contribution <= 0.005) {
            $interval = max(1, $interval);
            // Lump sum: no recurring adds, but still grow initial.
        }

        $series = self::monthSeries($initial, max(0.0, $contribution), $returnPercent / 100, $months, $interval);

        // Snap finals to authoritative engine outputs.
        $last = count($series) - 1;
        $series[$last]['invested'] = PfmMath::roundMoney($engineInvested);
        $series[$last]['value'] = PfmMath::roundMoney($engineCorpus);
        $series[$last]['gains'] = PfmMath::roundMoney(
            $engineGains !== null ? $engineGains : ($engineCorpus - $engineInvested)
        );

        $chartIndexes = self::sampleIndexes(count($series), $maxChartPoints);
        $chartPoints = [];
        foreach ($chartIndexes as $idx) {
            $chartPoints[] = self::decoratePoint($series[$idx], $years, $months);
        }

        $yearRows = self::yearRows($series, $years, $months);

        $plot = self::plotGeometry($chartPoints);

        return [
            'available' => true,
            'reason' => null,
            'months' => $months,
            'years' => $years,
            'frequency' => $frequency,
            'interval_months' => $interval,
            'points' => $series,
            'chart_points' => $chartPoints,
            'year_rows' => $yearRows,
            'table' => array_map(static fn (array $row): array => [
                'period' => $row['label'],
                'invested' => $row['invested_full'],
                'value' => $row['value_full'],
                'gains' => $row['gains_full'],
            ], $yearRows),
            'polyline_invested' => $plot['polyline_invested'],
            'polyline_value' => $plot['polyline_value'],
            'plot_points' => $plot['plot_points'],
            'y_ticks' => $plot['y_ticks'],
            'grid_y' => $plot['grid_y'],
            'axis' => $plot['axis'],
            'show_x_indexes' => $plot['show_x_indexes'],
            'final_invested' => PfmMath::roundMoney($engineInvested),
            'final_value' => PfmMath::roundMoney($engineCorpus),
            'aria_summary' => sprintf(
                'Line chart comparing cumulative invested capital ending at %s with estimated investment value ending at %s over %s.',
                InflationChart::money($engineInvested),
                InflationChart::money($engineCorpus),
                InflationChart::periodLabel($years) === 'Today'
                    ? rtrim(rtrim(number_format($years, 2, '.', ''), '0'), '.').' years'
                    : rtrim(rtrim(number_format($years, 2, '.', ''), '0'), '.').' years'
            ),
        ];
    }

    /**
     * @return list<array{month: int, invested: float, value: float, gains: float}>
     */
    public static function monthSeries(
        float $initial,
        float $contribution,
        float $annualReturn,
        int $months,
        int $intervalMonths
    ): array {
        $r = $annualReturn / 12;
        $interval = max(1, $intervalMonths);
        $invested = $initial;
        $value = $initial;
        $series = [[
            'month' => 0,
            'invested' => PfmMath::roundMoney($invested),
            'value' => PfmMath::roundMoney($value),
            'gains' => PfmMath::roundMoney($value - $invested),
        ]];

        for ($m = 1; $m <= $months; $m++) {
            if (abs($r) >= 1e-12) {
                $value *= (1 + $r);
            }
            if ($contribution > 0.005 && ($m % $interval) === 0) {
                $value += $contribution;
                $invested += $contribution;
            }
            $series[] = [
                'month' => $m,
                'invested' => PfmMath::roundMoney($invested),
                'value' => PfmMath::roundMoney($value),
                'gains' => PfmMath::roundMoney($value - $invested),
            ];
        }

        return $series;
    }

    /**
     * @param  list<array{month: int, invested: float, value: float, gains: float}>  $series
     * @return list<array<string, mixed>>
     */
    private static function yearRows(array $series, float $years, int $months): array
    {
        $rows = [];
        $rows[] = self::decoratePoint($series[0], $years, $months);

        $wholeYears = (int) floor($years + 1e-9);
        for ($y = 1; $y <= $wholeYears; $y++) {
            $month = min($months, $y * 12);
            $rows[] = self::decoratePoint($series[$month], $years, $months);
        }

        if ($months > 0 && ($months % 12) !== 0) {
            $rows[] = self::decoratePoint($series[$months], $years, $months);
        } elseif ($months > 0 && abs($years - $wholeYears) > 1e-9) {
            $rows[] = self::decoratePoint($series[$months], $years, $months);
        }

        // Deduplicate by month.
        $unique = [];
        foreach ($rows as $row) {
            $unique[(int) $row['month']] = $row;
        }
        ksort($unique);

        return array_values($unique);
    }

    /**
     * @param  array{month: int, invested: float, value: float, gains: float}  $point
     * @return array<string, mixed>
     */
    private static function decoratePoint(array $point, float $years, int $months): array
    {
        $month = (int) $point['month'];
        $elapsedYears = $month / 12;
        if ($month === 0) {
            $label = 'Start';
        } elseif ($month === $months) {
            $label = abs($years - round($years)) < 1e-9
                ? 'Year '.(int) round($years)
                : 'After '.rtrim(rtrim(number_format($years, 2, '.', ''), '0'), '.').' yr';
        } elseif ($month % 12 === 0) {
            $label = 'Year '.($month / 12);
        } else {
            $label = 'Month '.$month;
        }

        return [
            'month' => $month,
            'years' => $elapsedYears,
            'label' => $label,
            'invested' => $point['invested'],
            'value' => $point['value'],
            'gains' => $point['gains'],
            'invested_full' => InflationChart::money($point['invested']),
            'value_full' => InflationChart::money($point['value']),
            'gains_full' => InflationChart::money($point['gains']),
            'invested_short' => InflationChart::compactMoney($point['invested']),
            'value_short' => InflationChart::compactMoney($point['value']),
            'is_start' => $month === 0,
            'is_end' => $month === $months,
        ];
    }

    /**
     * @return list<int>
     */
    private static function sampleIndexes(int $count, int $maxPoints): array
    {
        if ($count <= 0) {
            return [];
        }
        if ($count <= $maxPoints) {
            return range(0, $count - 1);
        }

        $step = (int) ceil(($count - 1) / max(1, $maxPoints - 1));
        $indexes = [];
        for ($i = 0; $i < $count; $i += $step) {
            $indexes[] = $i;
        }
        if (end($indexes) !== $count - 1) {
            $indexes[] = $count - 1;
        }

        return array_values(array_unique($indexes));
    }

    /**
     * @param  list<array<string, mixed>>  $points
     * @return array<string, mixed>
     */
    private static function plotGeometry(array $points): array
    {
        $axis = [
            'left' => 58,
            'right' => 460,
            'top' => 18,
            'bottom' => 168,
        ];

        if ($points === []) {
            return [
                'polyline_invested' => '',
                'polyline_value' => '',
                'plot_points' => [],
                'y_ticks' => [],
                'grid_y' => [],
                'axis' => $axis,
                'show_x_indexes' => [],
            ];
        }

        $values = [];
        foreach ($points as $p) {
            $values[] = (float) $p['invested'];
            $values[] = (float) $p['value'];
        }
        $minValue = min(0, min($values));
        $maxValue = max($values);
        if ($maxValue <= $minValue) {
            $maxValue = $minValue + 1;
        }

        $count = count($points);
        $spanX = $axis['right'] - $axis['left'];
        $spanY = $axis['bottom'] - $axis['top'];

        $plotPoints = [];
        $investedPairs = [];
        $valuePairs = [];
        foreach ($points as $i => $p) {
            $cx = $count === 1
                ? ($axis['left'] + $spanX / 2)
                : $axis['left'] + ($spanX * ($i / ($count - 1)));
            $cyInvested = $axis['bottom'] - (($p['invested'] - $minValue) / ($maxValue - $minValue)) * $spanY;
            $cyValue = $axis['bottom'] - (($p['value'] - $minValue) / ($maxValue - $minValue)) * $spanY;
            $plotPoints[] = array_merge($p, [
                'cx' => round($cx, 2),
                'cy_invested' => round($cyInvested, 2),
                'cy_value' => round($cyValue, 2),
            ]);
            $investedPairs[] = round($cx, 2).','.round($cyInvested, 2);
            $valuePairs[] = round($cx, 2).','.round($cyValue, 2);
        }

        $tickCount = 4;
        $yTicks = [];
        $gridY = [];
        for ($t = 0; $t <= $tickCount; $t++) {
            $ratio = $t / $tickCount;
            $value = $minValue + ($maxValue - $minValue) * (1 - $ratio);
            $y = $axis['top'] + $spanY * $ratio;
            $yTicks[] = [
                'y' => round($y, 2),
                'label' => InflationChart::compactMoney($value),
            ];
            $gridY[] = round($y, 2);
        }

        $showX = [];
        if ($count <= 8) {
            $showX = range(0, $count - 1);
        } else {
            $showX = [0, $count - 1];
            $step = (int) ceil(($count - 1) / 6);
            for ($i = $step; $i < $count - 1; $i += $step) {
                $showX[] = $i;
            }
            $showX = array_values(array_unique($showX));
        }

        return [
            'polyline_invested' => implode(' ', $investedPairs),
            'polyline_value' => implode(' ', $valuePairs),
            'plot_points' => $plotPoints,
            'y_ticks' => $yTicks,
            'grid_y' => $gridY,
            'axis' => $axis,
            'show_x_indexes' => $showX,
        ];
    }
}
