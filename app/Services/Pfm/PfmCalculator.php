<?php

namespace App\Services\Pfm;

use App\Support\Pfm\PfmCatalog;
use InvalidArgumentException;

class PfmCalculator
{
    /** Largest single amount accepted (₹10,000 crore); keeps results finite and readable. */
    private const MAX_AMOUNT = 100_000_000_000;

    /**
     * @param  array<string, mixed>  $input
     * @return array{normalized: array<string, mixed>, result: array<string, mixed>}
     */
    public function calculate(string $toolKey, array $input): array
    {
        return match ($toolKey) {
            'monthly-money-planner' => $this->moneyPlanner($input),
            'net-worth-calculator' => $this->netWorth($input),
            'emergency-fund-calculator' => $this->emergencyFund($input),
            'financial-goal-calculator' => $this->financialGoal($input),
            'debt-emi-calculator' => $this->debtEmi($input),
            'retirement-calculator' => $this->retirement($input),
            'inflation-calculator' => $this->inflation($input),
            'savings-investment-calculator' => $this->savingsInvestment($input),
            default => throw new InvalidArgumentException('Unknown PFM tool.'),
        };
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{normalized: array<string, mixed>, result: array<string, mixed>}
     */
    private function moneyPlanner(array $input): array
    {
        $income = $this->money($input['monthly_income'] ?? null, 'Monthly income');
        $essential = $this->money($input['essential_expenses'] ?? null, 'Essential expenses', true);
        $lifestyle = $this->money($input['lifestyle_expenses'] ?? null, 'Lifestyle expenses', true);
        $emis = $this->money($input['emis'] ?? null, 'EMIs', true);
        $other = $this->money($input['other_recurring'] ?? null, 'Other recurring', true);
        $investments = $this->money($input['investments'] ?? null, 'Investments', true);

        $totalExpenses = $essential + $lifestyle + $emis + $other;
        $totalOutflow = $totalExpenses + $investments;
        $freeCash = $income - $totalOutflow;
        $savingsRate = $income > 0
            ? ($investments + max($freeCash, 0.0)) / $income
            : 0.0;

        $health = $this->healthStatus($savingsRate);

        $normalized = [
            'monthly_income' => $income,
            'essential_expenses' => $essential,
            'lifestyle_expenses' => $lifestyle,
            'emis' => $emis,
            'other_recurring' => $other,
            'investments' => $investments,
        ];

        $result = [
            'tool_key' => 'monthly-money-planner',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'total_expenses' => PfmMath::roundMoney($totalExpenses),
            'total_outflow' => PfmMath::roundMoney($totalOutflow),
            'free_cash' => PfmMath::roundMoney($freeCash),
            'savings_rate' => PfmMath::roundRate($savingsRate),
            'savings_rate_percent' => round($savingsRate * 100, 2),
            'health_status' => $health['status'],
            'health_label' => $health['label'],
            'health_tone' => $health['tone'],
            'summary' => sprintf(
                'Free cash %s₹%s · Savings rate %s%% · %s',
                $freeCash < 0 ? '−' : '',
                number_format(abs(PfmMath::roundMoney($freeCash)), 2),
                number_format(round($savingsRate * 100, 2), 2),
                $health['label']
            ),
        ];

        return compact('normalized', 'result');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{normalized: array<string, mixed>, result: array<string, mixed>}
     */
    private function netWorth(array $input): array
    {
        $assets = [
            'cash' => $this->money($input['cash'] ?? null, 'Cash', true),
            'bank' => $this->money($input['bank'] ?? null, 'Bank', true),
            'fd' => $this->money($input['fd'] ?? null, 'FD', true),
            'mf' => $this->money($input['mf'] ?? null, 'Mutual funds', true),
            'stocks' => $this->money($input['stocks'] ?? null, 'Stocks', true),
            'gold' => $this->money($input['gold'] ?? null, 'Gold', true),
            'property' => $this->money($input['property'] ?? null, 'Property', true),
            'epf_nps' => $this->money($input['epf_nps'] ?? null, 'EPF/NPS', true),
        ];
        $liabilities = [
            'loans' => $this->money($input['loans'] ?? null, 'Loans', true),
            'credit_cards' => $this->money($input['credit_cards'] ?? null, 'Credit cards', true),
            'other_debt' => $this->money($input['other_debt'] ?? null, 'Other debt', true),
        ];

        $totalAssets = array_sum($assets);
        $totalLiabilities = array_sum($liabilities);
        $netWorth = $totalAssets - $totalLiabilities;

        $normalized = array_merge($assets, $liabilities);

        $result = [
            'tool_key' => 'net-worth-calculator',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'total_assets' => PfmMath::roundMoney($totalAssets),
            'total_liabilities' => PfmMath::roundMoney($totalLiabilities),
            'net_worth' => PfmMath::roundMoney($netWorth),
            'debt_asset_ratio' => $totalAssets > 0 ? PfmMath::roundRate($totalLiabilities / $totalAssets) : null,
            'summary' => sprintf(
                'Net worth ₹%s (assets ₹%s − liabilities ₹%s)',
                number_format(PfmMath::roundMoney($netWorth), 2),
                number_format(PfmMath::roundMoney($totalAssets), 2),
                number_format(PfmMath::roundMoney($totalLiabilities), 2)
            ),
        ];

        return compact('normalized', 'result');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{normalized: array<string, mixed>, result: array<string, mixed>}
     */
    private function emergencyFund(array $input): array
    {
        $monthlyExpenses = $this->money($input['monthly_expenses'] ?? null, 'Monthly expenses');
        $months = (int) ($input['coverage_months'] ?? 0);
        if ($months < 1 || $months > 36) {
            throw new InvalidArgumentException('Coverage months must be between 1 and 36.');
        }
        $current = $this->money($input['current_savings'] ?? null, 'Current savings', true);
        $capacity = $this->money($input['monthly_saving_capacity'] ?? null, 'Monthly saving capacity', true);

        $target = $monthlyExpenses * $months;
        $shortfall = max($target - $current, 0.0);
        $coverage = $monthlyExpenses > 0 ? $current / $monthlyExpenses : 0.0;
        $monthsToTarget = $capacity > 0 ? ($shortfall / $capacity) : null;

        $normalized = [
            'monthly_expenses' => $monthlyExpenses,
            'coverage_months' => $months,
            'current_savings' => $current,
            'monthly_saving_capacity' => $capacity,
        ];

        $result = [
            'tool_key' => 'emergency-fund-calculator',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'target_amount' => PfmMath::roundMoney($target),
            'shortfall' => PfmMath::roundMoney($shortfall),
            'coverage_months_actual' => PfmMath::roundRate($coverage),
            'months_to_target' => $monthsToTarget === null ? null : PfmMath::roundRate($monthsToTarget),
            'summary' => sprintf(
                'Target ₹%s · Shortfall ₹%s · Coverage %.1f months',
                number_format(PfmMath::roundMoney($target), 2),
                number_format(PfmMath::roundMoney($shortfall), 2),
                $coverage
            ),
        ];

        return compact('normalized', 'result');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{normalized: array<string, mixed>, result: array<string, mixed>}
     */
    private function financialGoal(array $input): array
    {
        $goalType = (string) ($input['goal_type'] ?? 'custom');
        if (! array_key_exists($goalType, PfmCatalog::GOAL_TYPES)) {
            $goalType = 'custom';
        }
        $goalName = trim((string) ($input['goal_name'] ?? ''));
        if ($goalName === '') {
            $goalName = PfmCatalog::GOAL_TYPES[$goalType];
        }

        $currentCost = $this->money($input['current_cost'] ?? null, 'Current cost');
        $years = $this->positiveFloat($input['years'] ?? null, 'Years');
        $inflation = $this->ratePercent($input['inflation_percent'] ?? null, 'Inflation');
        $return = $this->ratePercent($input['expected_return_percent'] ?? null, 'Expected return');
        $existing = $this->money($input['existing_savings'] ?? null, 'Existing savings', true);

        $requiredCorpus = PfmMath::compound($currentCost, $inflation / 100, $years);
        $fvExisting = PfmMath::compound($existing, $return / 100, $years);
        $gap = max($requiredCorpus - $fvExisting, 0.0);
        $months = (int) round($years * 12);
        $monthlyRate = ($return / 100) / 12;
        $monthlyInvestment = PfmMath::pmtForFutureValue($gap, $monthlyRate, $months);

        $normalized = [
            'goal_type' => $goalType,
            'goal_name' => $goalName,
            'current_cost' => $currentCost,
            'years' => $years,
            'inflation_percent' => $inflation,
            'expected_return_percent' => $return,
            'existing_savings' => $existing,
        ];

        $result = [
            'tool_key' => 'financial-goal-calculator',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'required_corpus' => PfmMath::roundMoney($requiredCorpus),
            'future_value_existing' => PfmMath::roundMoney($fvExisting),
            'funding_gap' => PfmMath::roundMoney($gap),
            'monthly_investment' => PfmMath::roundMoney($monthlyInvestment),
            'summary' => sprintf(
                '%s · Required ₹%s · Monthly investment ₹%s',
                $goalName,
                number_format(PfmMath::roundMoney($requiredCorpus), 2),
                number_format(PfmMath::roundMoney($monthlyInvestment), 2)
            ),
        ];

        return compact('normalized', 'result');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{normalized: array<string, mixed>, result: array<string, mixed>}
     */
    private function debtEmi(array $input): array
    {
        // Task 42 — `principal` remains the purchase/total amount key for API + history.
        // Financed loan = principal − down_payment (server-side only; never trust client financed values).
        $purchaseAmount = $this->money($input['principal'] ?? null, 'Principal');
        $downPayment = $this->money($input['down_payment'] ?? null, 'Down payment', true);
        if ($downPayment > $purchaseAmount) {
            throw new InvalidArgumentException('Down payment cannot exceed the total purchase amount.');
        }

        $financedPrincipal = PfmMath::roundMoney($purchaseAmount - $downPayment);
        $rate = $this->ratePercent($input['annual_rate_percent'] ?? null, 'Interest rate');
        $tenure = $this->positiveFloat($input['tenure_years'] ?? null, 'Tenure');
        $extra = $this->money($input['extra_payment'] ?? null, 'Extra payment', true);

        $noLoan = $financedPrincipal <= 0.0;
        if ($noLoan) {
            // Full down payment: skip amortize; do not invent interest/months saved.
            $plan = [
                'emi' => 0.0,
                'standard_interest' => 0.0,
                'standard_total' => 0.0,
                'standard_months' => 0,
                'accelerated_interest' => 0.0,
                'accelerated_months' => 0,
                'accelerated_total' => 0.0,
                'interest_saved' => 0.0,
                'months_saved' => 0,
            ];
            $summary = sprintf(
                'No loan required · Purchase ₹%s paid entirely as down payment',
                number_format($purchaseAmount, 2)
            );
        } else {
            $plan = PfmMath::emiPlan($financedPrincipal, $rate, $tenure, $extra);
            $summary = sprintf(
                'EMI ₹%s · Interest saved ₹%s · Months saved %d',
                number_format($plan['emi'], 2),
                number_format($plan['interest_saved'], 2),
                $plan['months_saved']
            );
        }

        $loanRepayment = (float) ($plan['standard_total'] ?? (($plan['emi'] ?? 0) * ($plan['standard_months'] ?? 0)));
        $totalOutflow = PfmMath::roundMoney($downPayment + $loanRepayment);

        $normalized = [
            'principal' => $purchaseAmount,
            'down_payment' => $downPayment,
            'financed_principal' => $financedPrincipal,
            'annual_rate_percent' => $rate,
            'tenure_years' => $tenure,
            'extra_payment' => $extra,
        ];

        $result = array_merge([
            'tool_key' => 'debt-emi-calculator',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'summary' => $summary,
            'purchase_amount' => $purchaseAmount,
            'down_payment' => $downPayment,
            'financed_principal' => $financedPrincipal,
            'no_loan' => $noLoan,
            'loan_repayment_total' => PfmMath::roundMoney($loanRepayment),
            'total_purchase_outflow' => $totalOutflow,
        ], $plan);

        return compact('normalized', 'result');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{normalized: array<string, mixed>, result: array<string, mixed>}
     */
    private function retirement(array $input): array
    {
        $currentAge = (int) ($input['current_age'] ?? 0);
        $retirementAge = (int) ($input['retirement_age'] ?? 0);
        $lifeExpectancy = (int) ($input['life_expectancy'] ?? 0);
        if ($currentAge < 18 || $currentAge > 80) {
            throw new InvalidArgumentException('Current age must be between 18 and 80.');
        }
        if ($retirementAge <= $currentAge || $retirementAge > 90) {
            throw new InvalidArgumentException('Retirement age must be greater than current age.');
        }
        if ($lifeExpectancy <= $retirementAge || $lifeExpectancy > 110) {
            throw new InvalidArgumentException('Life expectancy must be greater than retirement age.');
        }

        $expenses = $this->money($input['current_monthly_expenses'] ?? null, 'Current monthly expenses');
        $inflation = $this->ratePercent($input['inflation_percent'] ?? null, 'Inflation');
        $preReturn = $this->ratePercent($input['pre_retirement_return_percent'] ?? null, 'Pre-retirement return');
        $postReturn = $this->ratePercent($input['post_retirement_return_percent'] ?? null, 'Post-retirement return');
        $assets = $this->money($input['current_assets'] ?? null, 'Current assets', true);
        // Task 45 — optional planned contribution; omitted/blank stays null (not zero).
        $plannedMonthly = $this->optionalMoney($input['planned_monthly_investment'] ?? null, 'Planned monthly investment');

        $yearsToRetire = $retirementAge - $currentAge;
        $retireMonths = ($lifeExpectancy - $retirementAge) * 12;
        $futureMonthly = PfmMath::compound($expenses, $inflation / 100, $yearsToRetire);
        $postMonthlyRate = ($postReturn / 100) / 12;
        $corpus = PfmMath::presentValueAnnuity($futureMonthly, $postMonthlyRate, $retireMonths);
        $fvAssets = PfmMath::compound($assets, $preReturn / 100, $yearsToRetire);
        $gap = max($corpus - $fvAssets, 0.0);
        $monthlyInvestment = PfmMath::pmtForFutureValue($gap, ($preReturn / 100) / 12, $yearsToRetire * 12);
        $readiness = $corpus > 0 ? min(100, round(($fvAssets / $corpus) * 100)) : 100;

        $requiredMonthly = PfmMath::roundMoney($monthlyInvestment);
        $requiredCorpus = PfmMath::roundMoney($corpus);
        $roundedFvAssets = PfmMath::roundMoney($fvAssets);

        // Additive sufficiency outputs — same contribution timing as pmtForFutureValue (end-of-month).
        $sufficiency = $this->retirementSufficiency(
            $requiredMonthly,
            $plannedMonthly,
            $roundedFvAssets,
            $requiredCorpus,
            $preReturn,
            $yearsToRetire
        );

        $normalized = [
            'current_age' => $currentAge,
            'retirement_age' => $retirementAge,
            'life_expectancy' => $lifeExpectancy,
            'current_monthly_expenses' => $expenses,
            'inflation_percent' => $inflation,
            'pre_retirement_return_percent' => $preReturn,
            'post_retirement_return_percent' => $postReturn,
            'current_assets' => $assets,
            'planned_monthly_investment' => $plannedMonthly,
        ];

        $result = array_merge([
            'tool_key' => 'retirement-calculator',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'years_to_retire' => $yearsToRetire,
            'future_monthly_expense' => PfmMath::roundMoney($futureMonthly),
            'required_corpus' => $requiredCorpus,
            'future_value_assets' => $roundedFvAssets,
            'funding_gap' => PfmMath::roundMoney($gap),
            'monthly_investment' => $requiredMonthly,
            'readiness_score' => (int) $readiness,
            'summary' => sprintf(
                'Corpus ₹%s · Gap ₹%s · Monthly ₹%s · Readiness %d%%',
                number_format($requiredCorpus, 2),
                number_format(PfmMath::roundMoney($gap), 2),
                number_format($requiredMonthly, 2),
                $readiness
            ),
        ], $sufficiency);

        return compact('normalized', 'result');
    }

    /**
     * Task 45 — compare planned vs required monthly investment; project corpus from planned contributions.
     * Existing assets use the engine’s annual compound FV; contributions use end-of-month FV (same as PMT).
     *
     * @return array<string, mixed>
     */
    private function retirementSufficiency(
        float $requiredMonthly,
        ?float $plannedMonthly,
        float $fvAssets,
        float $requiredCorpus,
        float $preReturnPercent,
        int $yearsToRetire
    ): array {
        $provided = $plannedMonthly !== null;

        $base = [
            'planned_monthly_provided' => $provided,
            'planned_monthly_investment' => $plannedMonthly,
            'monthly_shortfall' => null,
            'monthly_surplus' => null,
            'contribution_coverage_percent' => null,
            'projected_corpus' => null,
            'corpus_shortfall' => null,
            'corpus_surplus' => null,
            'investment_status' => 'unavailable',
        ];

        if (! $provided) {
            return $base;
        }

        $planned = PfmMath::roundMoney($plannedMonthly);
        // Contribution FV only (initial=0) so asset growth stays on the annual-compound path used for required PMT.
        $contribPlan = PfmMath::futureValuePlan(0.0, $planned, $preReturnPercent / 100, (float) $yearsToRetire);
        $projectedCorpus = PfmMath::roundMoney($fvAssets + (float) $contribPlan['fv_contributions']);

        $monthlyShortfall = PfmMath::roundMoney(max($requiredMonthly - $planned, 0.0));
        $monthlySurplus = PfmMath::roundMoney(max($planned - $requiredMonthly, 0.0));
        $corpusShortfall = PfmMath::roundMoney(max($requiredCorpus - $projectedCorpus, 0.0));
        $corpusSurplus = PfmMath::roundMoney(max($projectedCorpus - $requiredCorpus, 0.0));

        $coverage = null;
        if ($requiredMonthly > 0.005) {
            $coverage = round(($planned / $requiredMonthly) * 100, 2);
        }

        if ($requiredMonthly <= 0.005) {
            $status = 'zero_required';
        } elseif ($monthlyShortfall > 0.005) {
            $status = 'insufficient';
        } elseif ($monthlySurplus > 0.005) {
            $status = 'above';
        } else {
            $status = 'meets';
        }

        return [
            'planned_monthly_provided' => true,
            'planned_monthly_investment' => $planned,
            'monthly_shortfall' => $monthlyShortfall,
            'monthly_surplus' => $monthlySurplus,
            'contribution_coverage_percent' => $coverage,
            'projected_corpus' => $projectedCorpus,
            'corpus_shortfall' => $corpusShortfall,
            'corpus_surplus' => $corpusSurplus,
            'investment_status' => $status,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{normalized: array<string, mixed>, result: array<string, mixed>}
     */
    private function inflation(array $input): array
    {
        $amount = $this->money($input['current_amount'] ?? null, 'Current amount', true);
        $rate = $this->ratePercent($input['inflation_percent'] ?? null, 'Inflation');
        $years = $this->nonNegativeYears($input['years'] ?? null, 'Years');

        $future = PfmMath::compound($amount, $rate / 100, $years);
        $divisor = (1 + ($rate / 100)) ** $years;
        $purchasingPower = $divisor > 0 ? $amount / $divisor : $amount;
        $increase = $future - $amount;

        $normalized = [
            'current_amount' => $amount,
            'inflation_percent' => $rate,
            'years' => $years,
        ];

        $result = [
            'tool_key' => 'inflation-calculator',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'future_cost' => PfmMath::roundMoney($future),
            'purchasing_power' => PfmMath::roundMoney($purchasingPower),
            'increase' => PfmMath::roundMoney($increase),
            'summary' => sprintf(
                'Future cost ₹%s (increase ₹%s over %s years)',
                number_format(PfmMath::roundMoney($future), 2),
                number_format(PfmMath::roundMoney($increase), 2),
                rtrim(rtrim(number_format($years, 2), '0'), '.')
            ),
        ];

        return compact('normalized', 'result');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{normalized: array<string, mixed>, result: array<string, mixed>}
     */
    private function savingsInvestment(array $input): array
    {
        $initial = $this->money($input['initial_amount'] ?? null, 'Initial amount', true);
        $frequency = $this->investmentFrequency($input['investment_frequency'] ?? null);
        $contribution = $this->resolveContributionAmount($input);
        $return = $this->ratePercent($input['expected_return_percent'] ?? null, 'Expected return');
        $years = $this->positiveFloat($input['years'] ?? null, 'Years');

        if ($initial <= 0 && $contribution <= 0) {
            throw new InvalidArgumentException('Enter an initial amount or a contribution amount.');
        }

        $intervalMonths = self::contributionIntervalMonths($frequency);

        // Exact legacy monthly path for baseline compatibility (interval = 1).
        $plan = $intervalMonths === 1
            ? PfmMath::futureValuePlan($initial, $contribution, $return / 100, $years)
            : PfmMath::futureValuePlanPeriodic($initial, $contribution, $return / 100, $years, $intervalMonths);

        // Lump-sum / zero recurring: no scheduled contributions regardless of frequency.
        if ($contribution <= 0.005) {
            $plan['contribution_count'] = 0;
            $plan['fv_contributions'] = 0.0;
        }

        $normalized = [
            'initial_amount' => $initial,
            'contribution_amount' => $contribution,
            // Legacy alias: per-period contribution amount (not always "monthly" when frequency differs).
            'monthly_contribution' => $contribution,
            'investment_frequency' => $frequency,
            'expected_return_percent' => $return,
            'years' => $years,
        ];

        $result = array_merge([
            'tool_key' => 'savings-investment-calculator',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'investment_frequency' => $frequency,
            'contribution_amount' => $contribution,
            'summary' => sprintf(
                'Corpus ₹%s · Invested ₹%s · Gains ₹%s',
                number_format($plan['corpus'], 2),
                number_format($plan['invested'], 2),
                number_format($plan['gains'], 2)
            ),
        ], $plan);

        return compact('normalized', 'result');
    }

    /**
     * @return 'monthly'|'quarterly'|'half_yearly'|'yearly'
     */
    private function investmentFrequency(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'monthly';
        }

        $frequency = strtolower(trim((string) $value));
        $allowed = ['monthly', 'quarterly', 'half_yearly', 'yearly'];
        if (! in_array($frequency, $allowed, true)) {
            throw new InvalidArgumentException('Investment frequency must be monthly, quarterly, half_yearly, or yearly.');
        }

        return $frequency;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function resolveContributionAmount(array $input): float
    {
        $hasContribution = array_key_exists('contribution_amount', $input)
            && $input['contribution_amount'] !== null
            && $input['contribution_amount'] !== '';
        $hasMonthly = array_key_exists('monthly_contribution', $input)
            && $input['monthly_contribution'] !== null
            && $input['monthly_contribution'] !== '';

        if ($hasContribution && $hasMonthly) {
            $a = $this->money($input['contribution_amount'], 'Contribution amount', true);
            $b = $this->money($input['monthly_contribution'], 'Monthly contribution', true);
            if (abs($a - $b) > 0.005) {
                throw new InvalidArgumentException('contribution_amount and monthly_contribution must match when both are provided.');
            }

            return $a;
        }

        if ($hasContribution) {
            return $this->money($input['contribution_amount'], 'Contribution amount', true);
        }

        return $this->money($input['monthly_contribution'] ?? null, 'Monthly contribution', true);
    }

    /**
     * @param  'monthly'|'quarterly'|'half_yearly'|'yearly'  $frequency
     */
    public static function contributionIntervalMonths(string $frequency): int
    {
        return match ($frequency) {
            'monthly' => 1,
            'quarterly' => 3,
            'half_yearly' => 6,
            'yearly' => 12,
            default => 1,
        };
    }

    /**
     * @return array{status: string, label: string, tone: string}
     */
    private function healthStatus(float $savingsRate): array
    {
        if ($savingsRate >= PfmCatalog::HEALTH_THRESHOLDS['healthy']) {
            return ['status' => 'healthy', 'label' => 'Healthy', 'tone' => 'good'];
        }
        if ($savingsRate >= PfmCatalog::HEALTH_THRESHOLDS['fair']) {
            return ['status' => 'fair', 'label' => 'Fair', 'tone' => 'warn'];
        }

        return ['status' => 'needs_attention', 'label' => 'Needs attention', 'tone' => 'bad'];
    }

    private function money(mixed $value, string $label, bool $allowZero = false): float
    {
        if ($value === null || $value === '') {
            if ($allowZero) {
                return 0.0;
            }
            throw new InvalidArgumentException($label.' is required.');
        }
        if (! is_numeric($value)) {
            throw new InvalidArgumentException($label.' must be a number.');
        }
        $n = (float) $value;
        if (! is_finite($n) || $n > self::MAX_AMOUNT) {
            throw new InvalidArgumentException($label.' is too large. Enter an amount up to ₹'.number_format(self::MAX_AMOUNT).'.');
        }
        if ($n < 0) {
            throw new InvalidArgumentException($label.' cannot be negative.');
        }
        if (! $allowZero && $n <= 0) {
            throw new InvalidArgumentException($label.' must be greater than zero.');
        }

        return PfmMath::roundMoney($n);
    }

    /**
     * Optional monetary input: omitted/blank → null; explicit 0 → 0.0; negatives rejected.
     */
    private function optionalMoney(mixed $value, string $label): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            throw new InvalidArgumentException($label.' must be a number.');
        }
        $n = (float) $value;
        if (! is_finite($n) || $n > self::MAX_AMOUNT) {
            throw new InvalidArgumentException($label.' is too large. Enter an amount up to ₹'.number_format(self::MAX_AMOUNT).'.');
        }
        if ($n < 0) {
            throw new InvalidArgumentException($label.' cannot be negative.');
        }

        return PfmMath::roundMoney($n);
    }

    private function positiveFloat(mixed $value, string $label): float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            throw new InvalidArgumentException($label.' is required.');
        }
        $n = (float) $value;
        if ($n <= 0 || $n > 100) {
            throw new InvalidArgumentException($label.' must be between 0 and 100.');
        }

        return round($n, 2);
    }

    private function nonNegativeYears(mixed $value, string $label): float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            throw new InvalidArgumentException($label.' is required.');
        }
        $n = (float) $value;
        if ($n < 0 || $n > 100) {
            throw new InvalidArgumentException($label.' must be between 0 and 100.');
        }

        return round($n, 2);
    }

    private function ratePercent(mixed $value, string $label): float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            throw new InvalidArgumentException($label.' is required.');
        }
        $n = (float) $value;
        if ($n < 0 || $n > 50) {
            throw new InvalidArgumentException($label.' must be between 0 and 50.');
        }

        return round($n, 4);
    }
}
