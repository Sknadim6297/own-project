<?php

namespace App\Support\Pfm;

/**
 * Month-by-month projection behind the Financial Goal results chart.
 *
 * It only re-expresses the numbers the calculator already produced: the savings line grows
 * existing savings at the assumed return and adds the required monthly investment (end-of-month),
 * and the cost line grows today's cost at the assumed inflation. The last point is snapped to the
 * calculator's own results so the chart always ends exactly where the figures on the page end.
 */
class GoalProjection
{
    /**
     * @param  array<string, mixed>  $inputs  Normalised calculator inputs.
     * @param  array<string, mixed>  $result  Calculator result (required_corpus, future_value_existing, funding_gap, monthly_investment).
     * @return array{months: int, points: list<array{month: int, savings: float, cost: float}>}|null
     */
    public static function build(array $inputs, array $result, int $maxPoints = 48): ?array
    {
        $years = (float) ($inputs['years'] ?? 0);
        $months = (int) round($years * 12);
        if ($months < 1) {
            return null;
        }

        $cost = (float) ($inputs['current_cost'] ?? 0);
        $existing = (float) ($inputs['existing_savings'] ?? 0);
        $inflation = (float) ($inputs['inflation_percent'] ?? 0) / 100;
        $annualReturn = (float) ($inputs['expected_return_percent'] ?? 0) / 100;
        $monthly = (float) ($result['monthly_investment'] ?? 0);

        $corpus = (float) ($result['required_corpus'] ?? 0);
        $fvExisting = (float) ($result['future_value_existing'] ?? 0);
        $gap = (float) ($result['funding_gap'] ?? 0);

        $monthlyRate = $annualReturn / 12;
        $step = max(1, (int) ceil($months / max(2, $maxPoints)));

        $indices = range(0, $months, $step);
        if (end($indices) !== $months) {
            $indices[] = $months;
        }

        $points = [];
        foreach ($indices as $month) {
            if ($month === $months) {
                $savings = $fvExisting + ($gap > 0 ? $gap : 0.0);
                $costNow = $corpus;
            } else {
                $factor = (1 + $annualReturn) ** ($month / 12);
                $annuity = abs($monthlyRate) < 1e-12
                    ? $month
                    : (((1 + $monthlyRate) ** $month) - 1) / $monthlyRate;
                $savings = ($existing * $factor) + ($monthly * $annuity);
                $costNow = $cost * ((1 + $inflation) ** ($month / 12));
            }

            $points[] = [
                'month' => $month,
                'savings' => round($savings, 2),
                'cost' => round($costNow, 2),
            ];
        }

        return ['months' => $months, 'points' => $points];
    }
}
