<?php

namespace App\Support\Pfm;

/**
 * Task 44 — Presentational view-model for Retirement Calculator conclusions.
 * Uses existing calculator inputs/outputs only. Does not recalculate corpus or investments.
 */
final class RetirementPresentation
{
    /**
     * @param  array<string, mixed>  $inputs
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public static function build(array $inputs, array $result): array
    {
        $has = static fn (array $bag, string $key): bool => array_key_exists($key, $bag) && $bag[$key] !== null && $bag[$key] !== '';

        $currentAge = $has($inputs, 'current_age') ? (int) $inputs['current_age'] : null;
        $retirementAge = $has($inputs, 'retirement_age') ? (int) $inputs['retirement_age'] : null;
        $lifeExpectancy = $has($inputs, 'life_expectancy') ? (int) $inputs['life_expectancy'] : null;
        $expenses = $has($inputs, 'current_monthly_expenses') ? (float) $inputs['current_monthly_expenses'] : null;
        $assets = $has($inputs, 'current_assets') ? (float) $inputs['current_assets'] : null;
        $inflation = $has($inputs, 'inflation_percent') ? (float) $inputs['inflation_percent'] : null;
        $preReturn = $has($inputs, 'pre_retirement_return_percent') ? (float) $inputs['pre_retirement_return_percent'] : null;
        $postReturn = $has($inputs, 'post_retirement_return_percent') ? (float) $inputs['post_retirement_return_percent'] : null;

        $yearsToRetire = $has($result, 'years_to_retire')
            ? (int) $result['years_to_retire']
            : (($currentAge !== null && $retirementAge !== null) ? max(0, $retirementAge - $currentAge) : null);

        $futureMonthly = $has($result, 'future_monthly_expense') ? (float) $result['future_monthly_expense'] : null;
        $corpus = $has($result, 'required_corpus') ? (float) $result['required_corpus'] : null;
        $fvAssets = $has($result, 'future_value_assets') ? (float) $result['future_value_assets'] : null;
        $gap = $has($result, 'funding_gap') ? (float) $result['funding_gap'] : null;
        $monthly = $has($result, 'monthly_investment') ? (float) $result['monthly_investment'] : null;
        $readiness = $has($result, 'readiness_score') ? (int) $result['readiness_score'] : null;

        $retirementYears = ($retirementAge !== null && $lifeExpectancy !== null)
            ? max(0, $lifeExpectancy - $retirementAge)
            : null;

        $noSavings = $assets !== null && $assets <= 0.005;
        $hasSavings = $assets !== null && $assets > 0.005;
        $zeroMonthly = $monthly !== null && $monthly <= 0.005;
        $shortHorizon = $yearsToRetire !== null && $yearsToRetire > 0 && $yearsToRetire <= 10;
        $longHorizon = $yearsToRetire !== null && $yearsToRetire > 10;

        $scenario = 'standard';
        if ($zeroMonthly) {
            $scenario = 'zero_monthly';
        } elseif ($noSavings) {
            $scenario = 'no_savings';
        } elseif ($shortHorizon) {
            $scenario = 'short_horizon';
        } elseif ($longHorizon && $hasSavings) {
            $scenario = 'long_with_savings';
        } elseif ($longHorizon) {
            $scenario = 'long_horizon';
        } elseif ($hasSavings) {
            $scenario = 'with_savings';
        }

        $sufficiency = self::sufficiencyView($inputs, $result, $monthly, $corpus);

        $paragraphs = self::conclusionParagraphs(
            $currentAge,
            $retirementAge,
            $yearsToRetire,
            $retirementYears,
            $expenses,
            $futureMonthly,
            $assets,
            $corpus,
            $fvAssets,
            $gap,
            $monthly,
            $readiness,
            $inflation,
            $preReturn,
            $postReturn,
            $noSavings,
            $zeroMonthly,
            $shortHorizon,
            $longHorizon
        );
        foreach ($sufficiency['conclusion_additions'] as $extraPara) {
            $paragraphs[] = $extraPara;
        }

        $nextSteps = [
            'Review your retirement expense estimates periodically as lifestyle costs change.',
            'Update savings balances and return or inflation assumptions when your circumstances change.',
            'Recalculate after significant changes in income, expenses, or planned retirement age.',
            'Consider discussing retirement planning with a qualified financial professional.',
        ];
        if (($sufficiency['status'] ?? '') === 'insufficient') {
            array_unshift(
                $nextSteps,
                'Review your planned monthly contribution against the estimated requirement.',
                'Review estimated retirement expenses, planned retirement age, and existing savings before changing assumptions.',
                'Avoid relying on higher assumed investment returns alone to close a contribution gap.'
            );
        }

        return [
            'current_age' => $currentAge,
            'retirement_age' => $retirementAge,
            'life_expectancy' => $lifeExpectancy,
            'years_to_retire' => $yearsToRetire,
            'retirement_years' => $retirementYears,
            'current_monthly_expenses' => $expenses,
            'current_assets' => $assets,
            'inflation_percent' => $inflation,
            'pre_retirement_return_percent' => $preReturn,
            'post_retirement_return_percent' => $postReturn,
            'future_monthly_expense' => $futureMonthly,
            'required_corpus' => $corpus,
            'future_value_assets' => $fvAssets,
            'funding_gap' => $gap,
            'monthly_investment' => $monthly,
            'readiness_score' => $readiness,
            'no_savings' => $noSavings,
            'has_savings' => $hasSavings,
            'zero_monthly' => $zeroMonthly,
            'short_horizon' => $shortHorizon,
            'long_horizon' => $longHorizon,
            'scenario' => $scenario,
            'money' => [
                'expenses' => self::moneyOrUnavailable($expenses, $expenses !== null),
                'assets' => self::moneyOrUnavailable($assets, $assets !== null),
                'future_monthly' => self::moneyOrUnavailable($futureMonthly, $futureMonthly !== null),
                'corpus' => self::moneyOrUnavailable($corpus, $corpus !== null),
                'fv_assets' => self::moneyOrUnavailable($fvAssets, $fvAssets !== null),
                'gap' => self::moneyOrUnavailable($gap, $gap !== null),
                'monthly' => self::moneyOrUnavailable($monthly, $monthly !== null),
            ],
            'rate' => [
                'inflation' => self::rateOrUnavailable($inflation),
                'pre' => self::rateOrUnavailable($preReturn),
                'post' => self::rateOrUnavailable($postReturn),
            ],
            'paragraphs' => $paragraphs,
            'sufficiency' => $sufficiency,
            'terms' => [
                [
                    'term' => 'Retirement corpus',
                    'text' => 'The estimated amount projected to be needed at retirement to support the planned retirement expenses under your entered assumptions.',
                ],
                [
                    'term' => 'Years until retirement',
                    'text' => 'The accumulation period available between your current age and planned retirement age.',
                ],
                [
                    'term' => 'Retirement expenses',
                    'text' => 'Current monthly expenses are inflated to an estimated monthly need at retirement. That future expense drives the corpus estimate.',
                ],
                [
                    'term' => 'Existing retirement savings',
                    'text' => 'Current retirement assets are grown to retirement at the pre-retirement return assumption and reduce the remaining funding gap.',
                ],
                [
                    'term' => 'Monthly investment required',
                    'text' => 'An estimated additional monthly contribution needed to close the funding gap under the entered return and timeline assumptions. It is not a guarantee of retirement success.',
                ],
                [
                    'term' => 'Planned monthly investment',
                    'text' => 'The amount you intend to invest each month. This is compared with the estimated requirement; it does not replace the calculated required amount.',
                ],
                [
                    'term' => 'Inflation and investment returns',
                    'text' => 'Inflation raises estimated future expenses. Pre- and post-retirement returns are assumed annual rates used by this model; they are illustrative, not promised outcomes.',
                ],
            ],
            'next_steps' => $nextSteps,
            'disclaimer' => 'These retirement projections are estimates based on your entered financial information and assumptions. This assessment compares your planned monthly investment with the estimated contribution required under your entered retirement assumptions. Actual investment returns, inflation, expenses, taxes, fees, healthcare needs, and retirement spending may vary. This tool does not include taxes, fees, or healthcare costs unless you have already reflected them in the amounts you entered. An estimated contribution target is not a guarantee of retirement security. These figures are educational estimates only and are not financial advice.',
            'sparse' => $corpus === null && $monthly === null,
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private static function sufficiencyView(array $inputs, array $result, ?float $requiredMonthly, ?float $requiredCorpus): array
    {
        $has = static fn (array $bag, string $key): bool => array_key_exists($key, $bag) && $bag[$key] !== null && $bag[$key] !== '';

        $provided = (bool) ($result['planned_monthly_provided'] ?? false);
        if (! $provided && $has($inputs, 'planned_monthly_investment')) {
            $provided = true;
        }
        // Distinguish omitted key / null from explicit zero in snapshots.
        if (! $provided && array_key_exists('planned_monthly_investment', $result) && $result['planned_monthly_investment'] !== null) {
            $provided = true;
        }

        $planned = null;
        if ($provided) {
            $planned = (float) ($result['planned_monthly_investment'] ?? $inputs['planned_monthly_investment'] ?? 0);
        }

        $status = (string) ($result['investment_status'] ?? ($provided ? 'meets' : 'unavailable'));
        if (! $provided) {
            $status = 'unavailable';
        }

        $monthlyShortfall = $provided && $has($result, 'monthly_shortfall') ? (float) $result['monthly_shortfall'] : ($provided && $requiredMonthly !== null && $planned !== null ? max($requiredMonthly - $planned, 0) : null);
        $monthlySurplus = $provided && $has($result, 'monthly_surplus') ? (float) $result['monthly_surplus'] : ($provided && $requiredMonthly !== null && $planned !== null ? max($planned - $requiredMonthly, 0) : null);
        $coverage = $provided && array_key_exists('contribution_coverage_percent', $result)
            ? ($result['contribution_coverage_percent'] !== null ? (float) $result['contribution_coverage_percent'] : null)
            : null;
        $projected = $provided && $has($result, 'projected_corpus') ? (float) $result['projected_corpus'] : null;
        $corpusShortfall = $provided && $has($result, 'corpus_shortfall') ? (float) $result['corpus_shortfall'] : null;
        $corpusSurplus = $provided && $has($result, 'corpus_surplus') ? (float) $result['corpus_surplus'] : null;

        $barPercent = 0.0;
        $coverageLabel = 'Unavailable';
        if ($status === 'zero_required') {
            $barPercent = 100.0;
            $coverageLabel = 'Not applicable (no additional contribution estimated)';
        } elseif ($coverage !== null) {
            $barPercent = max(0.0, min(100.0, $coverage));
            $coverageLabel = number_format($coverage, 2).'%';
        } elseif ($provided && $requiredMonthly !== null && $requiredMonthly <= 0.005) {
            $barPercent = 100.0;
            $coverageLabel = 'Not applicable (no additional contribution estimated)';
            $status = 'zero_required';
        }

        $m = fn (?float $v) => self::formatMoney($v);
        $conclusionAdditions = [];
        $paragraphs = [];

        if ($status === 'unavailable') {
            // Assessment section handles the unavailable message; avoid repeating in summary.
        } elseif ($status === 'insufficient' && $requiredMonthly !== null && $planned !== null && $monthlyShortfall !== null) {
            $paragraphs[] = sprintf(
                'Based on your entered assumptions, your estimated monthly investment requirement is %s. You currently plan to invest %s, leaving an estimated monthly contribution gap of %s.',
                $m($requiredMonthly),
                $m($planned),
                $m($monthlyShortfall)
            );
            if ($corpusShortfall !== null && $corpusShortfall > 0.005 && $projected !== null && $requiredCorpus !== null) {
                $paragraphs[] = sprintf(
                    'At retirement, your projected corpus of %s is below the estimated requirement of %s by about %s under these assumptions.',
                    $m($projected),
                    $m($requiredCorpus),
                    $m($corpusShortfall)
                );
            }
            $conclusionAdditions[] = sprintf(
                'Your planned monthly investment is below the estimated amount required for your retirement target. Increasing your monthly contribution by approximately %s could help close the projected funding gap under the current assumptions.',
                $m($monthlyShortfall)
            );
        } elseif ($status === 'meets' && $requiredMonthly !== null && $planned !== null) {
            $paragraphs[] = sprintf(
                'Your planned monthly investment of %s matches the estimated monthly contribution requirement of %s under the entered assumptions. This alignment is an estimate, not a guarantee of retirement sufficiency.',
                $m($planned),
                $m($requiredMonthly)
            );
            $conclusionAdditions[] = 'Your planned monthly investment meets the estimated contribution requirement under the current assumptions.';
        } elseif ($status === 'above' && $requiredMonthly !== null && $planned !== null && $monthlySurplus !== null) {
            $paragraphs[] = sprintf(
                'Your planned monthly investment of %s is above the estimated requirement of %s by about %s each month under the entered assumptions.',
                $m($planned),
                $m($requiredMonthly),
                $m($monthlySurplus)
            );
            if ($corpusSurplus !== null && $corpusSurplus > 0.005 && $projected !== null) {
                $paragraphs[] = sprintf(
                    'Your projected retirement corpus of %s is about %s above the estimated requirement under these assumptions. Extra contributions do not eliminate every retirement risk.',
                    $m($projected),
                    $m($corpusSurplus)
                );
            }
            $conclusionAdditions[] = sprintf(
                'Your planned monthly investment exceeds the estimated requirement by about %s per month under the current assumptions.',
                $m($monthlySurplus)
            );
        } elseif ($status === 'zero_required' && $requiredMonthly !== null) {
            $paragraphs[] = sprintf(
                'The calculation estimates a required monthly investment of %s under the entered assumptions, so no additional monthly contribution is estimated to be necessary. This does not guarantee retirement outcomes if returns, inflation, or spending differ.',
                $m($requiredMonthly)
            );
            if ($provided && $planned !== null && $planned > 0.005) {
                $paragraphs[] = sprintf(
                    'You still entered a planned monthly investment of %s, which may increase projected savings beyond the estimated requirement in this model.',
                    $m($planned)
                );
            } elseif ($provided && $planned !== null && $planned <= 0.005) {
                $paragraphs[] = 'You entered a planned monthly investment of ₹0.00. Existing savings and assumptions still drive the projected outcome in this estimate.';
            }
            $conclusionAdditions[] = 'No additional monthly contribution is estimated to be required under the current assumptions.';
        } elseif ($provided && $planned !== null && $planned <= 0.005 && $status === 'insufficient') {
            $paragraphs[] = 'You entered a planned monthly investment of ₹0.00, so the contribution assessment treats planned investing as zero for this comparison.';
        }

        return [
            'provided' => $provided,
            'status' => $status,
            'planned' => $planned,
            'monthly_shortfall' => $monthlyShortfall,
            'monthly_surplus' => $monthlySurplus,
            'coverage_percent' => $coverage,
            'coverage_label' => $coverageLabel,
            'bar_percent' => $barPercent,
            'projected_corpus' => $projected,
            'corpus_shortfall' => $corpusShortfall,
            'corpus_surplus' => $corpusSurplus,
            'money' => [
                'planned' => $provided ? self::formatMoney($planned) : 'Unavailable',
                'monthly_shortfall' => self::moneyOrUnavailable($monthlyShortfall, $monthlyShortfall !== null),
                'monthly_surplus' => self::moneyOrUnavailable($monthlySurplus, $monthlySurplus !== null),
                'projected_corpus' => self::moneyOrUnavailable($projected, $projected !== null),
                'corpus_shortfall' => self::moneyOrUnavailable($corpusShortfall, $corpusShortfall !== null),
                'corpus_surplus' => self::moneyOrUnavailable($corpusSurplus, $corpusSurplus !== null),
            ],
            'paragraphs' => $paragraphs,
            'conclusion_additions' => $conclusionAdditions,
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

        $trimmed = rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.');

        return ($trimmed === '' ? '0' : $trimmed).'%';
    }

    /**
     * @return list<string>
     */
    private static function conclusionParagraphs(
        ?int $currentAge,
        ?int $retirementAge,
        ?int $yearsToRetire,
        ?int $retirementYears,
        ?float $expenses,
        ?float $futureMonthly,
        ?float $assets,
        ?float $corpus,
        ?float $fvAssets,
        ?float $gap,
        ?float $monthly,
        ?int $readiness,
        ?float $inflation,
        ?float $preReturn,
        ?float $postReturn,
        bool $noSavings,
        bool $zeroMonthly,
        bool $shortHorizon,
        bool $longHorizon
    ): array {
        $m = fn (?float $v) => self::formatMoney($v);
        $paras = [];

        if ($yearsToRetire !== null && $retirementAge !== null) {
            $ageClause = $currentAge !== null
                ? sprintf(' (currently age %d)', $currentAge)
                : '';
            if ($shortHorizon) {
                $paras[] = sprintf(
                    'You have %d %s remaining until your planned retirement at age %d%s. That is a relatively limited accumulation period under these inputs, so small changes in savings or assumptions can affect the plan noticeably.',
                    $yearsToRetire,
                    $yearsToRetire === 1 ? 'year' : 'years',
                    $retirementAge,
                    $ageClause
                );
            } elseif ($longHorizon) {
                $paras[] = sprintf(
                    'You have %d years remaining until your planned retirement at age %d%s. That longer accumulation period is the window used by this estimate to grow savings and close any funding gap.',
                    $yearsToRetire,
                    $retirementAge,
                    $ageClause
                );
            } else {
                $paras[] = sprintf(
                    'You have %d %s remaining until your planned retirement at age %d%s.',
                    $yearsToRetire,
                    $yearsToRetire === 1 ? 'year' : 'years',
                    $retirementAge,
                    $ageClause
                );
            }
        }

        if ($corpus !== null) {
            $expenseClause = '';
            if ($expenses !== null && $futureMonthly !== null) {
                $expenseClause = sprintf(
                    ' Based on current monthly expenses of %s, estimated monthly expenses at retirement are about %s under the inflation assumption used.',
                    $m($expenses),
                    $m($futureMonthly)
                );
            } elseif ($futureMonthly !== null) {
                $expenseClause = sprintf(
                    ' Estimated monthly expenses at retirement are about %s under the inflation assumption used.',
                    $m($futureMonthly)
                );
            }
            $paras[] = sprintf(
                'Based on your entered expenses and financial assumptions, your estimated retirement corpus requirement is %s.%s',
                $m($corpus),
                $expenseClause
            );
        }

        if ($retirementYears !== null && $retirementYears > 0) {
            $paras[] = sprintf(
                'The plan assumes about %d %s of retirement spending support (from retirement age through the entered life expectancy).',
                $retirementYears,
                $retirementYears === 1 ? 'year' : 'years'
            );
        }

        if ($noSavings) {
            $paras[] = 'This calculation starts without an existing retirement savings balance, so projected asset growth before retirement comes only from future contributions under the entered assumptions.';
        } elseif ($assets !== null && $fvAssets !== null) {
            $growthClause = $preReturn !== null
                ? sprintf(' at the assumed pre-retirement return of %s a year', self::rateOrUnavailable($preReturn))
                : '';
            $paras[] = sprintf(
                'Your existing retirement savings of %s are projected to grow to about %s by retirement%s. That projected balance reduces the remaining funding need.',
                $m($assets),
                $m($fvAssets),
                $growthClause
            );
        }

        if ($zeroMonthly && $monthly !== null) {
            $paras[] = sprintf(
                'Under the entered assumptions, the estimated additional monthly investment required is %s. That means projected existing savings cover the estimated corpus in this model — it does not guarantee retirement outcomes if returns, inflation, or spending differ.',
                $m($monthly)
            );
        } elseif ($monthly !== null && $gap !== null) {
            $paras[] = sprintf(
                'Your estimated funding gap is %s. Based on this calculation, an estimated monthly investment of %s is required to work towards your retirement target under the entered timeline and return assumptions.',
                $m($gap),
                $m($monthly)
            );
        } elseif ($monthly !== null) {
            $paras[] = sprintf(
                'Based on the calculation, an estimated monthly investment of %s is required to work towards your retirement target under the entered assumptions.',
                $m($monthly)
            );
        }

        if ($inflation !== null || $preReturn !== null || $postReturn !== null) {
            $bits = [];
            if ($inflation !== null) {
                $bits[] = 'inflation '.self::rateOrUnavailable($inflation).' a year';
            }
            if ($preReturn !== null) {
                $bits[] = 'pre-retirement return '.self::rateOrUnavailable($preReturn).' a year';
            }
            if ($postReturn !== null) {
                $bits[] = 'post-retirement return '.self::rateOrUnavailable($postReturn).' a year';
            }
            $paras[] = 'Key assumptions used: '.implode('; ', $bits).'. These rates are illustrative estimates, not promised investment results.';
        }

        if ($readiness !== null) {
            $paras[] = sprintf(
                'The readiness score of %d%% compares projected assets at retirement with the estimated corpus under these assumptions only.',
                $readiness
            );
        }

        if ($paras === []) {
            $paras[] = 'A detailed retirement summary is limited because some saved values are unavailable for this historical record.';
        }

        return $paras;
    }
}
