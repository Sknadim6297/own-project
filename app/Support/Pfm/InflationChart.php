<?php

namespace App\Support\Pfm;

use App\Services\Pfm\PfmMath;

/**
 * Task 46 — Presentational year-wise future-cost series for Inflation Calculator.
 * Uses the same annual compound model as PfmCalculator::inflation. Does not change engine outputs.
 */
final class InflationChart
{
    /**
     * @param  array<string, mixed>  $inputs
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public static function build(array $inputs, array $result): array
    {
        $has = static fn (array $bag, string $key): bool => array_key_exists($key, $bag) && $bag[$key] !== null && $bag[$key] !== '';

        if (! $has($inputs, 'current_amount') || ! $has($inputs, 'inflation_percent') || ! $has($inputs, 'years')) {
            return [
                'available' => false,
                'reason' => 'Chart values are unavailable because the saved calculation is missing the original amount, inflation rate, or duration.',
                'points' => [],
                'table' => [],
                'y_ticks' => [],
                'explanation' => null,
            ];
        }

        $amount = (float) $inputs['current_amount'];
        $ratePercent = (float) $inputs['inflation_percent'];
        $years = (float) $inputs['years'];
        $rate = $ratePercent / 100;

        if (! is_finite($amount) || ! is_finite($ratePercent) || ! is_finite($years) || $amount < 0 || $years < 0) {
            return [
                'available' => false,
                'reason' => 'Chart values are unavailable for this calculation snapshot.',
                'points' => [],
                'table' => [],
                'y_ticks' => [],
                'explanation' => null,
            ];
        }

        $engineFuture = $has($result, 'future_cost')
            ? (float) $result['future_cost']
            : PfmMath::roundMoney(PfmMath::compound($amount, $rate, $years));
        $engineIncrease = $has($result, 'increase')
            ? (float) $result['increase']
            : PfmMath::roundMoney($engineFuture - $amount);

        $yearMarks = self::yearMarks($years);
        $points = [];
        foreach ($yearMarks as $year) {
            $value = abs($year - $years) < 1e-9
                ? $engineFuture
                : PfmMath::roundMoney(PfmMath::compound($amount, $rate, $year));
            $points[] = [
                'year' => $year,
                'label' => self::periodLabel($year),
                'value' => $value,
                'value_full' => self::money($value),
                'value_short' => self::compactMoney($value),
                'is_start' => abs($year) < 1e-9,
                'is_end' => abs($year - $years) < 1e-9,
            ];
        }

        // Ensure final point matches engine KPI even if marks rounding differed.
        if ($points !== []) {
            $last = count($points) - 1;
            $points[$last]['value'] = $engineFuture;
            $points[$last]['value_full'] = self::money($engineFuture);
            $points[$last]['value_short'] = self::compactMoney($engineFuture);
            $points[$last]['is_end'] = true;
        }

        $values = array_column($points, 'value');
        $minValue = min($values);
        $maxValue = max($values);
        if ($maxValue <= $minValue) {
            $maxValue = $minValue + 1;
        }

        $plot = self::plotGeometry($points, $minValue, $maxValue);
        $showValueIndexes = self::valueLabelIndexes($points);
        $showXIndexes = self::xLabelIndexes($points);

        $explanation = sprintf(
            'At an annual inflation rate of %s%%, an item costing %s today is estimated to cost %s after %s. Estimated increase: %s. Inflation may not remain constant in reality.',
            self::formatRate($ratePercent),
            self::money($amount),
            self::money($engineFuture),
            self::yearsPhrase($years),
            self::money($engineIncrease)
        );

        return [
            'available' => true,
            'reason' => null,
            'current_amount' => $amount,
            'inflation_percent' => $ratePercent,
            'years' => $years,
            'future_cost' => $engineFuture,
            'increase' => $engineIncrease,
            'points' => $points,
            'table' => array_map(static fn (array $p): array => [
                'period' => $p['label'],
                'amount' => $p['value_full'],
                'value' => $p['value'],
            ], $points),
            'y_ticks' => $plot['y_ticks'],
            'polyline' => $plot['polyline'],
            'plot_points' => $plot['plot_points'],
            'show_value_indexes' => $showValueIndexes,
            'show_x_indexes' => $showXIndexes,
            'grid_y' => $plot['grid_y'],
            'axis' => $plot['axis'],
            'explanation' => $explanation,
            'aria_summary' => sprintf(
                'Line chart of estimated future cost from %s today to %s after %s at %s percent annual inflation.',
                self::money($amount),
                self::money($engineFuture),
                self::yearsPhrase($years),
                self::formatRate($ratePercent)
            ),
        ];
    }

    /**
     * @return list<float>
     */
    public static function yearMarks(float $years): array
    {
        if ($years <= 0) {
            return [0.0];
        }

        $marks = [0.0];
        $whole = (int) floor($years + 1e-9);

        if ($years <= 12) {
            for ($y = 1; $y <= $whole; $y++) {
                $marks[] = (float) $y;
            }
        } else {
            $target = 8;
            $step = max(1, (int) ceil($whole / $target));
            for ($y = $step; $y < $whole; $y += $step) {
                $marks[] = (float) $y;
            }
            if ($whole > 0) {
                $marks[] = (float) $whole;
            }
        }

        if (abs($years - $whole) > 1e-9) {
            $marks[] = $years;
        } elseif ($whole > 0 && abs(end($marks) - $years) > 1e-9) {
            $marks[] = $years;
        }

        $unique = [];
        foreach ($marks as $mark) {
            $key = number_format($mark, 4, '.', '');
            $unique[$key] = $mark;
        }

        $list = array_values($unique);
        sort($list, SORT_NUMERIC);

        return $list;
    }

    public static function periodLabel(float $year): string
    {
        if (abs($year) < 1e-9) {
            return 'Today';
        }

        $rounded = round($year, 2);
        if (abs($rounded - (int) $rounded) < 1e-9) {
            return 'Year '.(int) $rounded;
        }

        return 'Year '.rtrim(rtrim(number_format($rounded, 2, '.', ''), '0'), '.');
    }

    public static function money(float $value): string
    {
        return '₹'.number_format($value, 2);
    }

    public static function compactMoney(float $value): string
    {
        $trim = static function (float $x, int $d): string {
            return rtrim(rtrim(number_format($x, $d, '.', ''), '0'), '.');
        };

        $abs = abs($value);
        if ($abs >= 1e7) {
            return '₹'.$trim($value / 1e7, 2).'Cr';
        }
        if ($abs >= 1e5) {
            return '₹'.$trim($value / 1e5, 2).'L';
        }
        if ($abs >= 1e3) {
            return '₹'.$trim($value / 1e3, 1).'K';
        }

        return '₹'.number_format($value, $abs >= 100 ? 0 : 2);
    }

    private static function formatRate(float $rate): string
    {
        return rtrim(rtrim(number_format($rate, 4, '.', ''), '0'), '.');
    }

    private static function yearsPhrase(float $years): string
    {
        $label = rtrim(rtrim(number_format($years, 2, '.', ''), '0'), '.');

        return $label.' '.($years == 1.0 ? 'year' : 'years');
    }

    /**
     * @param  list<array<string, mixed>>  $points
     * @return list<int>
     */
    private static function valueLabelIndexes(array $points): array
    {
        return self::sparseIndexes($points, 6, 5);
    }

    /**
     * @param  list<array<string, mixed>>  $points
     * @return list<int>
     */
    private static function xLabelIndexes(array $points): array
    {
        return self::sparseIndexes($points, 8, 6);
    }

    /**
     * @param  list<array<string, mixed>>  $points
     * @return list<int>
     */
    private static function sparseIndexes(array $points, int $fullThreshold, int $maxExtras): array
    {
        $count = count($points);
        if ($count === 0) {
            return [];
        }
        if ($count <= $fullThreshold) {
            return range(0, $count - 1);
        }

        $indexes = [0, $count - 1];
        $inner = max(1, $count - 2);
        $step = max(1, (int) ceil($inner / max(1, $maxExtras)));
        for ($i = $step; $i < $count - 1; $i += $step) {
            $indexes[] = $i;
        }

        $indexes = array_values(array_unique($indexes));
        sort($indexes);

        return $indexes;
    }

    /**
     * @param  list<array<string, mixed>>  $points
     * @return array<string, mixed>
     */
    private static function plotGeometry(array $points, float $minValue, float $maxValue): array
    {
        $left = 58.0;
        $right = 460.0;
        $top = 28.0;
        $bottom = 168.0;
        $width = $right - $left;
        $height = $bottom - $top;
        $count = count($points);
        $span = max(1e-9, $maxValue - $minValue);

        $plotPoints = [];
        $coords = [];
        foreach ($points as $i => $point) {
            $x = $count === 1
                ? ($left + $right) / 2
                : $left + ($width * ($i / max(1, $count - 1)));
            $y = $bottom - (((float) $point['value'] - $minValue) / $span) * $height;
            $plotPoints[] = [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'cx' => round($x, 2),
                'cy' => round($y, 2),
                'label_y' => round(max(14, $y - 12), 2),
                'year' => $point['year'],
                'period' => $point['label'],
                'value' => $point['value'],
                'value_full' => $point['value_full'],
                'value_short' => $point['value_short'],
                'is_start' => $point['is_start'],
                'is_end' => $point['is_end'],
            ];
            $coords[] = round($x, 2).','.round($y, 2);
        }

        $tickCount = 4;
        $yTicks = [];
        $gridY = [];
        for ($i = 0; $i <= $tickCount; $i++) {
            $value = $minValue + ($span * ($i / $tickCount));
            $y = $bottom - (($value - $minValue) / $span) * $height;
            $yTicks[] = [
                'value' => PfmMath::roundMoney($value),
                'label' => self::compactMoney($value),
                'y' => round($y, 2),
            ];
            $gridY[] = round($y, 2);
        }

        return [
            'polyline' => implode(' ', $coords),
            'plot_points' => $plotPoints,
            'y_ticks' => $yTicks,
            'grid_y' => $gridY,
            'axis' => [
                'left' => $left,
                'right' => $right,
                'top' => $top,
                'bottom' => $bottom,
            ],
        ];
    }
}
