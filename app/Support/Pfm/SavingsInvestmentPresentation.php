<?php

namespace App\Support\Pfm;

/**
 * Task 47 — Presentational view-model for Savings & Investment Calculator summaries.
 * Uses existing calculator inputs/outputs only. Does not recalculate maturity or gains.
 */
final class SavingsInvestmentPresentation
{
    /**
     * @param  array<string, mixed>  $inputs
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public static function build(array $inputs, array $result): array
    {
        $has = static fn (array $bag, string $key): bool => array_key_exists($key, $bag) && $bag[$key] !== null && $bag[$key] !== '';

        $initial = $has($inputs, 'initial_amount') ? (float) $inputs['initial_amount'] : null;
        $monthly = $has($inputs, 'monthly_contribution') ? (float) $inputs['monthly_contribution'] : null;
        $years = $has($inputs, 'years') ? (float) $inputs['years'] : null;
        $returnPercent = $has($inputs, 'expected_return_percent') ? (float) $inputs['expected_return_percent'] : null;

        $corpus = $has($result, 'corpus') ? (float) $result['corpus'] : null;
        $invested = $has($result, 'invested') ? (float) $result['invested'] : null;
        $gains = $has($result, 'gains') ? (float) $result['gains'] : null;
        $fvInitial = $has($result, 'fv_initial') ? (float) $result['fv_initial'] : null;
        $fvContributions = $has($result, 'fv_contributions') ? (float) $result['fv_contributions'] : null;
        $months = $has($result, 'months')
            ? (int) $result['months']
            : ($years !== null ? (int) round($years * 12) : null);

        $hasInitial = $initial !== null && $initial > 0.005;
        $hasMonthly = $monthly !== null && $monthly > 0.005;
        $zeroReturn = $returnPercent !== null && abs($returnPercent) <= 0.00005;
        $positiveReturn = $returnPercent !== null && $returnPercent > 0.00005;
        $zeroGains = $gains !== null && abs($gains) <= 0.005;
        $negativeGains = $gains !== null && $gains < -0.005;

        $scenario = 'sparse';
        if ($hasInitial && $hasMonthly) {
            $scenario = 'combined';
        } elseif ($hasInitial && ! $hasMonthly) {
            $scenario = 'lump_sum';
        } elseif (! $hasInitial && $hasMonthly) {
            $scenario = 'recurring';
        } elseif ($initial !== null || $monthly !== null || $corpus !== null) {
            $scenario = 'standard';
        }

        if ($scenario !== 'sparse' && $zeroReturn) {
            $scenarioKey = $scenario.'_zero_return';
        } else {
            $scenarioKey = $scenario;
        }

        $investedPct = null;
        $gainsPct = null;
        $showMixChart = false;
        if ($corpus !== null && $corpus > 0.005 && $invested !== null && $gains !== null && ! $negativeGains) {
            $showMixChart = true;
            $investedPct = round(min(100, max(0, ($invested / $corpus) * 100)), 1);
            $gainsPct = round(min(100, max(0, (max(0, $gains) / $corpus) * 100)), 1);
        }

        $paragraphs = self::conclusionParagraphs(
            $scenario,
            $initial,
            $monthly,
            $years,
            $returnPercent,
            $invested,
            $gains,
            $corpus,
            $months,
            $fvInitial,
            $fvContributions,
            $zeroReturn,
            $positiveReturn,
            $zeroGains,
            $negativeGains,
            $hasInitial,
            $hasMonthly
        );

        return [
            'scenario' => $scenarioKey,
            'base_scenario' => $scenario,
            'has_initial' => $hasInitial,
            'has_monthly' => $hasMonthly,
            'zero_return' => $zeroReturn,
            'positive_return' => $positiveReturn,
            'zero_gains' => $zeroGains,
            'negative_gains' => $negativeGains,
            'initial' => $initial,
            'monthly' => $monthly,
            'years' => $years,
            'return_percent' => $returnPercent,
            'corpus' => $corpus,
            'invested' => $invested,
            'gains' => $gains,
            'fv_initial' => $fvInitial,
            'fv_contributions' => $fvContributions,
            'months' => $months,
            'money' => [
                'initial' => self::moneyOrUnavailable($initial, $initial !== null),
                'monthly' => self::moneyOrUnavailable($monthly, $monthly !== null),
                'invested' => self::moneyOrUnavailable($invested, $invested !== null),
                'gains' => self::moneyOrUnavailable($gains, $gains !== null),
                'corpus' => self::moneyOrUnavailable($corpus, $corpus !== null),
                'fv_initial' => self::moneyOrUnavailable($fvInitial, $fvInitial !== null),
                'fv_contributions' => self::moneyOrUnavailable($fvContributions, $fvContributions !== null),
            ],
            'rate' => [
                'return' => self::rateOrUnavailable($returnPercent),
            ],
            'years_label' => $years !== null ? self::yearsPhrase($years) : 'Unavailable',
            'frequency_label' => 'Monthly',
            'compounding_label' => 'Monthly (end of month)',
            'contribution_timing' => 'End of each month',
            'paragraphs' => $paragraphs,
            'terms' => [
                [
                    'term' => 'Invested amount',
                    'text' => 'The total money contributed under this plan: the initial investment plus all monthly contributions over the selected period.',
                ],
                [
                    'term' => 'Estimated returns',
                    'text' => 'The projected change in investment value beyond the contributed amount under the entered expected return assumption. This is an estimate, not a guaranteed profit.',
                ],
                [
                    'term' => 'Estimated maturity amount',
                    'text' => 'The estimated investment value at the end of the selected investment period under the entered assumptions.',
                ],
                [
                    'term' => 'Expected annual return',
                    'text' => 'The assumed annual growth rate used by this model. It is converted to a monthly rate for compounding and is illustrative only.',
                ],
                [
                    'term' => 'Investment frequency',
                    'text' => 'How often recurring contributions are assumed. This calculator models recurring contributions as monthly deposits.',
                ],
            ],
            'next_steps' => [
                'Review your planned initial and monthly investment contributions.',
                'Review your investment duration as your goals change.',
                'Revisit expected return assumptions periodically; avoid relying on unrealistic rates.',
                'Recalculate when your financial circumstances change.',
                'Consider discussing investment planning with a qualified financial professional.',
            ],
            'disclaimer' => 'These investment projections are estimates based on the entered contribution amounts, investment duration, and expected return assumptions. Actual investment returns may vary, and the estimated maturity amount is not guaranteed. This model does not include taxes, fees, inflation adjustments, or product-specific charges unless you already reflected them in the amounts you entered. These figures are educational estimates only and are not financial advice.',
            'show_mix_chart' => $showMixChart,
            'invested_percent' => $investedPct,
            'gains_percent' => $gainsPct,
            'sparse' => $corpus === null && $invested === null,
        ];
    }

    public static function formatMoney(?float $value): string
    {
        if ($value === null) {
            return 'Unavailable';
        }

        return '₹'.number_format($value, 2);
    }

    private static function moneyOrUnavailable(?float $value, bool $known): string
    {
        if (! $known || $value === null) {
            return 'Unavailable';
        }

        return self::formatMoney($value);
    }

    private static function rateOrUnavailable(?float $rate): string
    {
        if ($rate === null) {
            return 'Unavailable';
        }

        return rtrim(rtrim(number_format($rate, 4, '.', ''), '0'), '.').'%';
    }

    private static function yearsPhrase(float $years): string
    {
        $label = rtrim(rtrim(number_format($years, 2, '.', ''), '0'), '.');

        return $label.' '.((abs($years - 1.0) < 1e-9) ? 'year' : 'years');
    }

    /**
     * @return list<string>
     */
    private static function conclusionParagraphs(
        string $scenario,
        ?float $initial,
        ?float $monthly,
        ?float $years,
        ?float $returnPercent,
        ?float $invested,
        ?float $gains,
        ?float $corpus,
        ?int $months,
        ?float $fvInitial,
        ?float $fvContributions,
        bool $zeroReturn,
        bool $positiveReturn,
        bool $zeroGains,
        bool $negativeGains,
        bool $hasInitial,
        bool $hasMonthly
    ): array {
        $m = fn (?float $v) => self::formatMoney($v);
        $paragraphs = [];

        if ($scenario === 'sparse' && $corpus === null && $invested === null) {
            return [
                'A detailed investment conclusion is unavailable because this saved calculation is missing key contribution or maturity values. The original stored summary is shown where available.',
            ];
        }

        if ($scenario === 'lump_sum' && $hasInitial && $years !== null) {
            $paragraphs[] = sprintf(
                'Based on your investment plan, you intend to invest a lump sum of %s for %s with no recurring monthly contributions in this estimate. Your total invested amount is estimated to be %s.',
                $m($initial),
                self::yearsPhrase($years),
                $invested !== null ? $m($invested) : $m($initial)
            );
        } elseif ($scenario === 'recurring' && $hasMonthly && $years !== null) {
            $paragraphs[] = sprintf(
                'Based on your investment plan, you intend to invest %s every month for %s%s. Your total invested amount is estimated to be %s.',
                $m($monthly),
                self::yearsPhrase($years),
                $months !== null ? ' ('.$months.' monthly contributions)' : '',
                $invested !== null ? $m($invested) : 'Unavailable'
            );
        } elseif ($scenario === 'combined' && $hasInitial && $hasMonthly && $years !== null) {
            $paragraphs[] = sprintf(
                'Based on your investment plan, you intend to invest an initial amount of %s plus %s every month for %s. Your total invested amount is estimated to be %s.',
                $m($initial),
                $m($monthly),
                self::yearsPhrase($years),
                $invested !== null ? $m($invested) : 'Unavailable'
            );
        } elseif ($invested !== null && $years !== null) {
            $paragraphs[] = sprintf(
                'Based on your investment plan, your total invested amount is estimated to be %s over %s.',
                $m($invested),
                self::yearsPhrase($years)
            );
        }

        if ($zeroReturn && $corpus !== null) {
            $paragraphs[] = sprintf(
                'With an entered expected annual return of 0%%, the projected maturity amount of %s consists of your invested contributions without additional investment growth under these assumptions.',
                $m($corpus)
            );
        } elseif ($negativeGains && $corpus !== null && $gains !== null && $returnPercent !== null) {
            $paragraphs[] = sprintf(
                'Assuming the entered annual return rate of %s, your investment is projected at approximately %s by the end of the selected period, reflecting an estimated change of %s relative to contributions. A negative change is not a gain.',
                self::rateOrUnavailable($returnPercent),
                $m($corpus),
                $m($gains)
            );
        } elseif ($positiveReturn && $corpus !== null && $gains !== null && $returnPercent !== null) {
            $paragraphs[] = sprintf(
                'Assuming the entered annual return rate of %s, your investment could grow to approximately %s by the end of the selected period, including estimated returns of %s.',
                self::rateOrUnavailable($returnPercent),
                $m($corpus),
                $m($gains)
            );
        } elseif ($corpus !== null) {
            $paragraphs[] = sprintf(
                'Your estimated maturity amount under the entered assumptions is %s.',
                $m($corpus)
            );
        }

        if ($hasInitial && $hasMonthly && $fvInitial !== null && $fvContributions !== null) {
            $paragraphs[] = sprintf(
                'The maturity estimate combines the grown value of your initial investment (%s) with the grown value of monthly contributions (%s). The total invested amount counts the initial sum once plus each monthly contribution.',
                $m($fvInitial),
                $m($fvContributions)
            );
        }

        if ($zeroGains && ! $zeroReturn && $corpus !== null) {
            $paragraphs[] = 'Under the entered assumptions, estimated returns are approximately zero for this projection.';
        }

        if ($returnPercent !== null || $years !== null) {
            $bits = [];
            if ($returnPercent !== null) {
                $bits[] = 'expected annual return '.self::rateOrUnavailable($returnPercent);
            }
            $bits[] = 'monthly end-of-month contribution timing';
            $bits[] = 'monthly compounding of the annual rate';
            $paragraphs[] = 'Key assumptions used: '.implode('; ', $bits).'. These rates and timings are illustrative estimates, not promised investment results.';
        }

        return $paragraphs;
    }
}
