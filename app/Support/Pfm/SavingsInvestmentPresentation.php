<?php

namespace App\Support\Pfm;

/**
 * Task 47 / 50 — Presentational view-model for Savings & Investment Calculator summaries.
 * Uses existing calculator inputs/outputs only. Does not recalculate maturity or gains.
 */
final class SavingsInvestmentPresentation
{
    private const FREQUENCIES = [
        'monthly' => [
            'label' => 'Monthly',
            'adverb' => 'every month',
            'period' => 'month',
            'per_year' => 12,
            'interval_months' => 1,
            'timing' => 'End of each month',
            'amount_label' => 'Monthly investment',
        ],
        'quarterly' => [
            'label' => 'Quarterly',
            'adverb' => 'every three months',
            'period' => 'quarter',
            'per_year' => 4,
            'interval_months' => 3,
            'timing' => 'End of each quarter (every 3 months)',
            'amount_label' => 'Quarterly investment',
        ],
        'half_yearly' => [
            'label' => 'Half-Yearly',
            'adverb' => 'every six months',
            'period' => 'half-year',
            'per_year' => 2,
            'interval_months' => 6,
            'timing' => 'End of each half-year (every 6 months)',
            'amount_label' => 'Half-yearly investment',
        ],
        'yearly' => [
            'label' => 'Yearly',
            'adverb' => 'every year',
            'period' => 'year',
            'per_year' => 1,
            'interval_months' => 12,
            'timing' => 'End of each year',
            'amount_label' => 'Yearly investment',
        ],
    ];

    /**
     * @param  array<string, mixed>  $inputs
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public static function build(array $inputs, array $result): array
    {
        $has = static fn (array $bag, string $key): bool => array_key_exists($key, $bag) && $bag[$key] !== null && $bag[$key] !== '';

        $initial = $has($inputs, 'initial_amount') ? (float) $inputs['initial_amount'] : null;
        $contribution = null;
        if ($has($inputs, 'contribution_amount')) {
            $contribution = (float) $inputs['contribution_amount'];
        } elseif ($has($inputs, 'monthly_contribution')) {
            $contribution = (float) $inputs['monthly_contribution'];
        } elseif ($has($result, 'contribution_amount')) {
            $contribution = (float) $result['contribution_amount'];
        }

        $years = $has($inputs, 'years') ? (float) $inputs['years'] : null;
        $returnPercent = $has($inputs, 'expected_return_percent') ? (float) $inputs['expected_return_percent'] : null;

        $frequencyKey = 'monthly';
        if ($has($inputs, 'investment_frequency')) {
            $candidate = strtolower(trim((string) $inputs['investment_frequency']));
            if (isset(self::FREQUENCIES[$candidate])) {
                $frequencyKey = $candidate;
            }
        } elseif ($has($result, 'investment_frequency')) {
            $candidate = strtolower(trim((string) $result['investment_frequency']));
            if (isset(self::FREQUENCIES[$candidate])) {
                $frequencyKey = $candidate;
            }
        }
        $frequencyMeta = self::FREQUENCIES[$frequencyKey];

        $corpus = $has($result, 'corpus') ? (float) $result['corpus'] : null;
        $invested = $has($result, 'invested') ? (float) $result['invested'] : null;
        $gains = $has($result, 'gains') ? (float) $result['gains'] : null;
        $fvInitial = $has($result, 'fv_initial') ? (float) $result['fv_initial'] : null;
        $fvContributions = $has($result, 'fv_contributions') ? (float) $result['fv_contributions'] : null;
        $months = $has($result, 'months')
            ? (int) $result['months']
            : ($years !== null ? (int) round($years * 12) : null);

        $contributionCount = $has($result, 'contribution_count')
            ? (int) $result['contribution_count']
            : null;
        if ($contributionCount === null && $months !== null) {
            $contributionCount = intdiv($months, $frequencyMeta['interval_months']);
        }

        $hasInitial = $initial !== null && $initial > 0.005;
        $hasContribution = $contribution !== null && $contribution > 0.005;
        $zeroReturn = $returnPercent !== null && abs($returnPercent) <= 0.00005;
        $positiveReturn = $returnPercent !== null && $returnPercent > 0.00005;
        $zeroGains = $gains !== null && abs($gains) <= 0.005;
        $negativeGains = $gains !== null && $gains < -0.005;

        $scenario = 'sparse';
        if ($hasInitial && $hasContribution) {
            $scenario = 'combined';
        } elseif ($hasInitial && ! $hasContribution) {
            $scenario = 'lump_sum';
        } elseif (! $hasInitial && $hasContribution) {
            $scenario = 'recurring';
        } elseif ($initial !== null || $contribution !== null || $corpus !== null) {
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
            $contribution,
            $years,
            $returnPercent,
            $invested,
            $gains,
            $corpus,
            $months,
            $contributionCount,
            $fvInitial,
            $fvContributions,
            $zeroReturn,
            $positiveReturn,
            $zeroGains,
            $negativeGains,
            $hasInitial,
            $hasContribution,
            $frequencyKey,
            $frequencyMeta
        );

        $recurringTotal = null;
        if ($hasContribution && $contributionCount !== null) {
            $recurringTotal = round($contribution * $contributionCount, 2);
        } elseif ($invested !== null && $initial !== null) {
            $recurringTotal = round(max(0, $invested - max(0, $initial)), 2);
        }

        $totalGainPercent = null;
        $totalGainPercentLabel = 'Unavailable';
        if ($invested !== null && $invested > 0.005 && $gains !== null) {
            $totalGainPercent = round(($gains / $invested) * 100, 2);
            $totalGainPercentLabel = rtrim(rtrim(number_format($totalGainPercent, 2, '.', ''), '0'), '.').'%';
        }

        $investmentTypeLabel = match ($scenario) {
            'lump_sum' => 'Lump Sum',
            'recurring' => 'Recurring Investment',
            'combined' => 'Combined Investment',
            default => 'Investment plan',
        };

        $donut = self::donutGeometry($invested, $gains, $corpus, $showMixChart, $negativeGains);
        $projection = SavingsInvestmentProjection::build($inputs, $result);

        return [
            'scenario' => $scenarioKey,
            'base_scenario' => $scenario,
            'has_initial' => $hasInitial,
            'has_monthly' => $hasContribution,
            'has_contribution' => $hasContribution,
            'zero_return' => $zeroReturn,
            'positive_return' => $positiveReturn,
            'zero_gains' => $zeroGains,
            'negative_gains' => $negativeGains,
            'initial' => $initial,
            'monthly' => $contribution,
            'contribution_amount' => $contribution,
            'recurring_total' => $recurringTotal,
            'years' => $years,
            'return_percent' => $returnPercent,
            'investment_frequency' => $frequencyKey,
            'investment_type_label' => $investmentTypeLabel,
            'corpus' => $corpus,
            'invested' => $invested,
            'gains' => $gains,
            'fv_initial' => $fvInitial,
            'fv_contributions' => $fvContributions,
            'months' => $months,
            'contribution_count' => $contributionCount,
            'contributions_per_year' => $frequencyMeta['per_year'],
            'contribution_interval_months' => $frequencyMeta['interval_months'],
            'total_gain_percent' => $totalGainPercent,
            'total_gain_percent_label' => $totalGainPercentLabel,
            'money' => [
                'initial' => self::moneyOrUnavailable($initial, $initial !== null),
                'monthly' => self::moneyOrUnavailable($contribution, $contribution !== null),
                'contribution' => self::moneyOrUnavailable($contribution, $contribution !== null),
                'recurring_total' => self::moneyOrUnavailable($recurringTotal, $recurringTotal !== null),
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
            'frequency_label' => $frequencyMeta['label'],
            'frequency_amount_label' => $frequencyMeta['amount_label'],
            'compounding_label' => 'Monthly compounding',
            'contribution_timing' => $frequencyMeta['timing'],
            'donut' => $donut,
            'projection' => $projection,
            'paragraphs' => $paragraphs,
            'terms' => [
                [
                    'term' => 'Invested amount',
                    'text' => 'The total money contributed under this plan: the initial investment plus all scheduled recurring contributions over the selected period.',
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
                    'text' => 'How often recurring contributions are assumed. Contributions are modelled at the end of each selected period, while investment growth compounds monthly.',
                ],
            ],
            'next_steps' => [
                'Review your planned initial and recurring investment contributions.',
                'Review your investment duration and contribution frequency as your goals change.',
                'Revisit expected return assumptions periodically; avoid relying on unrealistic rates.',
                'Recalculate when your financial circumstances change.',
                'Consider discussing investment planning with a qualified financial professional.',
            ],
            'disclaimer' => 'These investment projections are estimates based on the entered contribution amounts, investment duration, contribution frequency, and expected return assumptions. Actual investment returns may vary, and the estimated maturity amount is not guaranteed. This model does not include taxes, fees, inflation adjustments, or product-specific charges unless you already reflected them in the amounts you entered. These figures are educational estimates only and are not financial advice.',
            'show_mix_chart' => $showMixChart,
            'invested_percent' => $investedPct,
            'gains_percent' => $gainsPct,
            'sparse' => $corpus === null && $invested === null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function donutGeometry(
        ?float $invested,
        ?float $gains,
        ?float $corpus,
        bool $showMixChart,
        bool $negativeGains
    ): array {
        if (! $showMixChart || $corpus === null || $corpus <= 0.005 || $invested === null || $gains === null) {
            return [
                'available' => false,
                'reason' => $negativeGains
                    ? 'A contribution-versus-return chart is not shown because estimated returns are negative under the entered assumptions.'
                    : 'Investment mix chart is unavailable for this calculation snapshot.',
            ];
        }

        $investedShare = min(100, max(0, ($invested / $corpus) * 100));
        $gainsShare = min(100, max(0, (max(0.0, $gains) / $corpus) * 100));
        $radius = 54;
        $circumference = 2 * M_PI * $radius;
        $investedLength = ($investedShare / 100) * $circumference;
        $gainsLength = ($gainsShare / 100) * $circumference;

        return [
            'available' => true,
            'reason' => null,
            'radius' => $radius,
            'circumference' => round($circumference, 3),
            'invested_dash' => round($investedLength, 3).' '.round($circumference, 3),
            'gains_dash' => round($gainsLength, 3).' '.round($circumference, 3),
            'gains_offset' => round(-$investedLength, 3),
            'invested_percent' => round($investedShare, 1),
            'gains_percent' => round($gainsShare, 1),
            'aria' => sprintf(
                'Donut chart of estimated maturity %s: invested capital %s (%s percent), estimated gains %s (%s percent).',
                self::formatMoney($corpus),
                self::formatMoney($invested),
                number_format($investedShare, 1),
                self::formatMoney(max(0.0, $gains)),
                number_format($gainsShare, 1)
            ),
        ];
    }

    public static function formatMoney(?float $value): string
    {
        if ($value === null) {
            return 'Unavailable';
        }

        return '₹'.number_format($value, 2);
    }

    /**
     * @return array{label: string, adverb: string, period: string, per_year: int, interval_months: int, timing: string, amount_label: string}
     */
    public static function frequencyMeta(string $frequency): array
    {
        return self::FREQUENCIES[$frequency] ?? self::FREQUENCIES['monthly'];
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
     * @param  array{label: string, adverb: string, period: string, per_year: int, interval_months: int, timing: string, amount_label: string}  $frequencyMeta
     * @return list<string>
     */
    private static function conclusionParagraphs(
        string $scenario,
        ?float $initial,
        ?float $contribution,
        ?float $years,
        ?float $returnPercent,
        ?float $invested,
        ?float $gains,
        ?float $corpus,
        ?int $months,
        ?int $contributionCount,
        ?float $fvInitial,
        ?float $fvContributions,
        bool $zeroReturn,
        bool $positiveReturn,
        bool $zeroGains,
        bool $negativeGains,
        bool $hasInitial,
        bool $hasContribution,
        string $frequencyKey,
        array $frequencyMeta
    ): array {
        $m = fn (?float $v) => self::formatMoney($v);
        $paragraphs = [];
        $adverb = $frequencyMeta['adverb'];

        if ($scenario === 'sparse' && $corpus === null && $invested === null) {
            return [
                'A detailed investment conclusion is unavailable because this saved calculation is missing key contribution or maturity values. The original stored summary is shown where available.',
            ];
        }

        if ($scenario === 'lump_sum' && $hasInitial && $years !== null) {
            $paragraphs[] = sprintf(
                'Based on your investment plan, you intend to invest a lump sum of %s for %s with no recurring contributions in this estimate. Your total invested amount is estimated to be %s.',
                $m($initial),
                self::yearsPhrase($years),
                $invested !== null ? $m($invested) : $m($initial)
            );
        } elseif ($scenario === 'recurring' && $hasContribution && $years !== null) {
            $countBit = $contributionCount !== null
                ? ' ('.$contributionCount.' '.($contributionCount === 1 ? 'contribution' : 'contributions').')'
                : '';
            $paragraphs[] = sprintf(
                'Based on your investment plan, you intend to contribute %s %s for %s%s. Your total invested amount is estimated to be %s.',
                $m($contribution),
                $adverb,
                self::yearsPhrase($years),
                $countBit,
                $invested !== null ? $m($invested) : 'Unavailable'
            );
        } elseif ($scenario === 'combined' && $hasInitial && $hasContribution && $years !== null) {
            $paragraphs[] = sprintf(
                'Based on your investment plan, you intend to invest an initial amount of %s plus %s %s for %s. Your total invested amount is estimated to be %s.',
                $m($initial),
                $m($contribution),
                $adverb,
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

        if ($invested !== null && $corpus !== null && $gains !== null && $years !== null) {
            $paragraphs[] = sprintf(
                'With a total investment of %s over %s, your estimated maturity amount is %s, including estimated gains of %s.',
                $m($invested),
                self::yearsPhrase($years),
                $m($corpus),
                $m($gains)
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

        if ($hasInitial && $hasContribution && $fvInitial !== null && $fvContributions !== null) {
            $paragraphs[] = sprintf(
                'The maturity estimate combines the grown value of your initial investment (%s) with the grown value of recurring contributions (%s). The total invested amount counts the initial sum once plus each scheduled contribution.',
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
            if ($hasContribution) {
                $bits[] = $frequencyMeta['timing'].' contribution timing';
            } else {
                $bits[] = 'lump-sum investment with no recurring contributions';
            }
            $bits[] = 'monthly compounding of the annual rate (independent of contribution frequency)';
            $paragraphs[] = 'Key assumptions used: '.implode('; ', $bits).'. These rates and timings are illustrative estimates, not promised investment results.';
        }

        return $paragraphs;
    }
}
