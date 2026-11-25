<?php

namespace App\Support\Pfm;

/**
 * Task 43 — Presentational view-model for Debt & EMI results.
 * Derives labels and copy only from existing calculator inputs/outputs.
 * Does not recalculate EMI, interest, or amortization.
 */
final class DebtEmiPresentation
{
    /**
     * @param  array<string, mixed>  $inputs
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public static function build(array $inputs, array $result): array
    {
        $hasKey = static fn (array $bag, string $key): bool => array_key_exists($key, $bag) && $bag[$key] !== null && $bag[$key] !== '';

        $purchaseKnown = $hasKey($result, 'purchase_amount') || $hasKey($inputs, 'principal');
        $purchase = $purchaseKnown
            ? (float) ($result['purchase_amount'] ?? $inputs['principal'] ?? 0)
            : null;

        $hasDownPaymentField = $hasKey($result, 'down_payment') || $hasKey($inputs, 'down_payment');
        $downPayment = $hasDownPaymentField
            ? (float) ($result['down_payment'] ?? $inputs['down_payment'] ?? 0)
            : ($purchaseKnown ? 0.0 : null);

        // Legacy snapshots without down_payment: principal was the financed loan.
        if ($hasKey($result, 'financed_principal')) {
            $financed = (float) $result['financed_principal'];
            $financedKnown = true;
        } elseif ($hasKey($inputs, 'financed_principal')) {
            $financed = (float) $inputs['financed_principal'];
            $financedKnown = true;
        } elseif ($purchase !== null && $downPayment !== null && $hasDownPaymentField) {
            $financed = max(0.0, $purchase - $downPayment);
            $financedKnown = true;
        } elseif ($purchase !== null && ! $hasDownPaymentField) {
            $financed = $purchase;
            $financedKnown = true;
        } else {
            $financed = null;
            $financedKnown = false;
        }

        $noLoan = (bool) ($result['no_loan'] ?? ($financedKnown && $financed <= 0));
        $rate = $hasKey($inputs, 'annual_rate_percent')
            ? (float) $inputs['annual_rate_percent']
            : null;
        $tenureYears = $hasKey($inputs, 'tenure_years')
            ? (float) $inputs['tenure_years']
            : null;
        $extra = $hasKey($inputs, 'extra_payment')
            ? (float) $inputs['extra_payment']
            : 0.0;

        $emi = $hasKey($result, 'emi') ? (float) $result['emi'] : null;
        $standardInterest = $hasKey($result, 'standard_interest') ? (float) $result['standard_interest'] : null;
        $standardTotal = $hasKey($result, 'standard_total')
            ? (float) $result['standard_total']
            : ($hasKey($result, 'loan_repayment_total') ? (float) $result['loan_repayment_total'] : null);
        $standardMonths = $hasKey($result, 'standard_months') ? (int) $result['standard_months'] : null;

        $acceleratedInterest = $hasKey($result, 'accelerated_interest') ? (float) $result['accelerated_interest'] : null;
        $acceleratedTotal = $hasKey($result, 'accelerated_total') ? (float) $result['accelerated_total'] : null;
        $acceleratedMonths = $hasKey($result, 'accelerated_months') ? (int) $result['accelerated_months'] : null;
        $interestSaved = $hasKey($result, 'interest_saved') ? (float) $result['interest_saved'] : null;
        $monthsSaved = $hasKey($result, 'months_saved') ? (int) $result['months_saved'] : null;

        $loanRepayment = $hasKey($result, 'loan_repayment_total')
            ? (float) $result['loan_repayment_total']
            : $standardTotal;

        $outflowKnown = $hasKey($result, 'total_purchase_outflow')
            || ($downPayment !== null && $loanRepayment !== null);
        $totalOutflow = $hasKey($result, 'total_purchase_outflow')
            ? (float) $result['total_purchase_outflow']
            : (($downPayment !== null && $loanRepayment !== null)
                ? round($downPayment + $loanRepayment, 2)
                : null);

        $hasExtra = $extra > 0.005 && ! $noLoan;
        $extraBenefit = $hasExtra
            && (($interestSaved !== null && $interestSaved > 0.005) || ($monthsSaved !== null && $monthsSaved > 0));
        $extraNoBenefit = $hasExtra && ! $extraBenefit;
        $zeroInterest = ! $noLoan && $rate !== null && $rate <= 0.00001;
        $partialDown = ! $noLoan && $downPayment !== null && $downPayment > 0.005;
        $scenario = $noLoan
            ? 'full_down_payment'
            : ($zeroInterest
                ? 'zero_interest'
                : ($hasExtra
                    ? ($extraBenefit ? 'extra_repayment' : 'extra_no_benefit')
                    : ($partialDown ? 'partial_down_payment' : 'no_down_payment')));

        $combinedMonthly = ($emi !== null && $hasExtra) ? round($emi + $extra, 2) : null;

        $chartPrincipal = $financedKnown ? max(0.0, (float) $financed) : null;
        $chartInterest = $standardInterest !== null ? max(0.0, $standardInterest) : null;
        $chartTotal = ($chartPrincipal !== null && $chartInterest !== null)
            ? max(0.0, $chartPrincipal + $chartInterest)
            : null;
        $principalPct = ($chartTotal !== null && $chartTotal > 0)
            ? round(($chartPrincipal / $chartTotal) * 100, 1)
            : null;
        $interestPct = ($chartTotal !== null && $chartTotal > 0)
            ? round(($chartInterest / $chartTotal) * 100, 1)
            : null;

        return [
            'purchase_amount' => $purchase,
            'down_payment' => $downPayment,
            'financed_principal' => $financed,
            'purchase_known' => $purchaseKnown,
            'down_payment_known' => $downPayment !== null,
            'financed_known' => $financedKnown,
            'no_loan' => $noLoan,
            'annual_rate_percent' => $rate,
            'tenure_years' => $tenureYears,
            'extra_payment' => $extra,
            'has_extra' => $hasExtra,
            'extra_benefit' => $extraBenefit,
            'extra_no_benefit' => $extraNoBenefit,
            'zero_interest' => $zeroInterest,
            'partial_down' => $partialDown,
            'scenario' => $scenario,
            'emi' => $emi,
            'combined_monthly' => $combinedMonthly,
            'standard_interest' => $standardInterest,
            'standard_total' => $standardTotal,
            'standard_months' => $standardMonths,
            'accelerated_interest' => $acceleratedInterest,
            'accelerated_total' => $acceleratedTotal,
            'accelerated_months' => $acceleratedMonths,
            'interest_saved' => $interestSaved,
            'months_saved' => $monthsSaved,
            'loan_repayment_total' => $loanRepayment,
            'total_purchase_outflow' => $totalOutflow,
            'outflow_known' => $outflowKnown && $totalOutflow !== null,
            'principal_percent' => $principalPct,
            'interest_percent' => $interestPct,
            'show_chart' => ! $noLoan && $chartTotal !== null && $chartTotal > 0,
            'tenure_label' => $tenureYears !== null ? self::yearsLabel($tenureYears) : null,
            'standard_duration_label' => $standardMonths !== null ? self::monthsLabel($standardMonths) : null,
            'accelerated_duration_label' => $acceleratedMonths !== null ? self::monthsLabel($acceleratedMonths) : null,
            'months_saved_label' => $monthsSaved !== null ? self::monthsLabel($monthsSaved) : null,
            'money' => [
                'purchase' => self::moneyOrUnavailable($purchase, $purchaseKnown),
                'down_payment' => self::moneyOrUnavailable($downPayment, $downPayment !== null),
                'financed' => self::moneyOrUnavailable($financed, $financedKnown),
                'emi' => self::moneyOrUnavailable($emi, $emi !== null),
                'extra' => self::formatMoney($extra),
                'combined_monthly' => self::moneyOrUnavailable($combinedMonthly, $combinedMonthly !== null),
                'standard_interest' => self::moneyOrUnavailable($standardInterest, $standardInterest !== null),
                'standard_total' => self::moneyOrUnavailable($standardTotal, $standardTotal !== null),
                'accelerated_interest' => self::moneyOrUnavailable($acceleratedInterest, $acceleratedInterest !== null),
                'accelerated_total' => self::moneyOrUnavailable($acceleratedTotal, $acceleratedTotal !== null),
                'interest_saved' => self::moneyOrUnavailable($interestSaved, $interestSaved !== null),
                'loan_repayment' => self::moneyOrUnavailable($loanRepayment, $loanRepayment !== null),
                'total_outflow' => self::moneyOrUnavailable($totalOutflow, $outflowKnown && $totalOutflow !== null),
            ],
            'explanation' => self::explanationParagraphs(
                $scenario,
                $purchase,
                $downPayment,
                $financed,
                $rate,
                $tenureYears,
                $emi,
                $standardInterest,
                $loanRepayment,
                $totalOutflow,
                $extra,
                $interestSaved,
                $monthsSaved,
                $standardMonths,
                $acceleratedMonths
            ),
            'terms' => [
                ['term' => 'EMI', 'text' => 'Equated Monthly Instalment — the fixed amount you pay each month under a standard repayment plan.'],
                ['term' => 'Principal', 'text' => 'The amount you actually borrow after any down payment (the financed loan amount).'],
                ['term' => 'Down payment', 'text' => 'An optional upfront payment that reduces how much you need to borrow.'],
                ['term' => 'Annual interest rate', 'text' => 'The yearly interest rate used to estimate monthly EMI. Actual lender rates may differ.'],
                ['term' => 'Total interest', 'text' => 'The estimated interest cost over the loan if you follow the repayment schedule shown.'],
                ['term' => 'Total loan repayment', 'text' => 'Financed principal plus loan interest — what you repay to the lender, excluding the down payment.'],
                ['term' => 'Extra monthly repayment', 'text' => 'An optional amount paid in addition to EMI that may shorten the loan and reduce interest.'],
            ],
            'disclaimer' => 'These results are estimates based on the entered loan amount, interest rate, tenure, and repayment assumptions. Actual lender charges, interest rate changes, prepayment rules, and repayment schedules may affect the final cost. This is not a lender quotation or financial advice.',
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

    public static function yearsLabel(float $years): string
    {
        $trimmed = rtrim(rtrim(number_format($years, 2, '.', ''), '0'), '.');
        if ($trimmed === '') {
            $trimmed = '0';
        }

        return $trimmed.' '.(((float) $trimmed) == 1.0 ? 'year' : 'years');
    }

    public static function monthsLabel(int $months): string
    {
        if ($months <= 0) {
            return '0 months';
        }

        $years = intdiv($months, 12);
        $rem = $months % 12;
        if ($years > 0 && $rem === 0) {
            return $years.' '.($years === 1 ? 'year' : 'years');
        }
        if ($years > 0) {
            return $years.' '.($years === 1 ? 'year' : 'years').' '.$rem.' '.($rem === 1 ? 'month' : 'months');
        }

        return $months.' '.($months === 1 ? 'month' : 'months');
    }

    private static function rateLabel(float $rate): string
    {
        $trimmed = rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.');

        return ($trimmed === '' ? '0' : $trimmed).'%';
    }

    /**
     * @return list<string>
     */
    private static function explanationParagraphs(
        string $scenario,
        ?float $purchase,
        ?float $downPayment,
        ?float $financed,
        ?float $rate,
        ?float $tenureYears,
        ?float $emi,
        ?float $standardInterest,
        ?float $loanRepayment,
        ?float $totalOutflow,
        float $extra,
        ?float $interestSaved,
        ?int $monthsSaved,
        ?int $standardMonths,
        ?int $acceleratedMonths
    ): array {
        $m = fn (?float $v) => self::formatMoney($v);
        $paras = [];

        if ($scenario === 'full_down_payment') {
            $paras[] = 'No loan is required.';
            $paras[] = 'Your down payment covers the entire purchase amount. Therefore, no borrowing, monthly EMI, or loan interest is required.'
                .($purchase !== null ? ' Purchase amount '.$m($purchase).' is paid entirely upfront.' : '');

            return $paras;
        }

        if (in_array($scenario, ['partial_down_payment', 'extra_repayment', 'extra_no_benefit', 'zero_interest'], true)) {
            if ($downPayment !== null && $downPayment > 0.005 && $financed !== null && $emi !== null && $rate !== null && $tenureYears !== null) {
                $paras[] = sprintf(
                    'After paying %s upfront, your financed loan amount is %s. At an annual interest rate of %s, your estimated monthly EMI is %s over %s.',
                    $m($downPayment),
                    $m($financed),
                    self::rateLabel($rate),
                    $m($emi),
                    self::yearsLabel($tenureYears)
                );
            } elseif ($scenario === 'zero_interest' && $financed !== null && $emi !== null) {
                $paras[] = sprintf(
                    'With zero interest under the entered assumptions, your financed loan amount of %s is repaid through estimated monthly payments of %s%s.',
                    $m($financed),
                    $m($emi),
                    $tenureYears !== null ? ' over '.self::yearsLabel($tenureYears) : ''
                );
            }
        }

        if ($scenario === 'no_down_payment') {
            $paras[] = sprintf(
                'You are borrowing %s%s%s. Your estimated monthly EMI is %s%s.',
                $financed !== null ? $m($financed) : 'the entered loan amount',
                $rate !== null ? ' at '.self::rateLabel($rate).' annual interest' : '',
                $tenureYears !== null ? ' over '.self::yearsLabel($tenureYears) : '',
                $emi !== null ? $m($emi) : 'calculated from your inputs',
                $standardMonths !== null ? ', with a planned repayment period of '.self::monthsLabel($standardMonths) : ''
            );
        }

        if ($scenario === 'zero_interest') {
            $paras[] = 'Your monthly payments repay the borrowed principal without additional interest under the entered assumptions.';
        }

        if ($standardInterest !== null && $loanRepayment !== null && $scenario !== 'zero_interest') {
            $paras[] = sprintf(
                'Under the standard plan, estimated total interest is %s and total loan repayment (principal plus interest) is %s.',
                $m($standardInterest),
                $m($loanRepayment)
            );
        } elseif ($scenario === 'zero_interest' && $loanRepayment !== null) {
            $paras[] = sprintf(
                'Total loan repayment equals the financed principal of %s, with no interest component under these assumptions.',
                $m($loanRepayment)
            );
        }

        if ($totalOutflow !== null && $downPayment !== null && $downPayment > 0.005) {
            $paras[] = sprintf(
                'Including the down payment, total purchase-related outflow is about %s (down payment plus loan repayment).',
                $m($totalOutflow)
            );
        }

        if ($scenario === 'extra_repayment' && $extra > 0) {
            $savedInterest = $interestSaved !== null && $interestSaved > 0.005
                ? $m($interestSaved)
                : null;
            $savedMonths = $monthsSaved !== null && $monthsSaved > 0
                ? self::monthsLabel($monthsSaved)
                : null;
            if ($savedMonths !== null && $savedInterest !== null) {
                $paras[] = sprintf(
                    'By paying an additional %s each month, you could repay your loan %s earlier and save approximately %s in interest, depending on your lender’s prepayment rules.',
                    $m($extra),
                    $savedMonths,
                    $savedInterest
                );
            } elseif ($savedMonths !== null) {
                $paras[] = sprintf(
                    'By paying an additional %s each month, you could repay your loan about %s earlier, depending on your lender’s prepayment rules.',
                    $m($extra),
                    $savedMonths
                );
            } elseif ($savedInterest !== null) {
                $paras[] = sprintf(
                    'By paying an additional %s each month, you could save approximately %s in interest, depending on your lender’s prepayment rules.',
                    $m($extra),
                    $savedInterest
                );
            }
            if ($acceleratedMonths !== null && $standardMonths !== null) {
                $paras[] = sprintf(
                    'Accelerated repayment shortens the schedule from %s to %s under these assumptions.',
                    self::monthsLabel($standardMonths),
                    self::monthsLabel($acceleratedMonths)
                );
            }
        }

        if ($scenario === 'extra_no_benefit' && $extra > 0) {
            $paras[] = sprintf(
                'The additional monthly repayment of %s does not produce measurable interest or tenure savings under the current calculation assumptions.',
                $m($extra)
            );
        }

        if ($paras === [] && $emi !== null) {
            $paras[] = 'Your estimated monthly EMI is '.$m($emi).' based on the saved inputs for this calculation.';
        }
        if ($paras === []) {
            $paras[] = 'Detailed explanation is limited because some saved values are unavailable for this historical record.';
        }

        return $paras;
    }
}
