<?php

namespace App\Services\Pfm;

class PfmMath
{
    public static function roundMoney(float $value): float
    {
        return round($value, 2);
    }

    public static function roundRate(float $value): float
    {
        return round($value, 6);
    }

    public static function compound(float $present, float $annualRate, float $years): float
    {
        return $present * ((1 + $annualRate) ** $years);
    }

    /**
     * Monthly payment required to accumulate a future value with end-of-month deposits.
     */
    public static function pmtForFutureValue(float $futureValue, float $monthlyRate, int $months): float
    {
        if ($months <= 0) {
            return 0.0;
        }

        if ($futureValue <= 0) {
            return 0.0;
        }

        if (abs($monthlyRate) < 1e-12) {
            return $futureValue / $months;
        }

        $factor = ((1 + $monthlyRate) ** $months) - 1;

        if (abs($factor) < 1e-12) {
            return $futureValue / $months;
        }

        return $futureValue * $monthlyRate / $factor;
    }

    /**
     * Future value of an initial amount plus end-of-month contributions.
     *
     * @return array{fv_initial: float, fv_contributions: float, corpus: float, invested: float, gains: float}
     */
    public static function futureValuePlan(float $initial, float $monthly, float $annualReturn, float $years): array
    {
        $months = (int) round($years * 12);
        $r = $annualReturn / 12;
        $fvInitial = $initial * ((1 + $r) ** $months);

        if (abs($r) < 1e-12) {
            $fvContributions = $monthly * $months;
        } else {
            $fvContributions = $monthly * ((((1 + $r) ** $months) - 1) / $r);
        }

        $corpus = $fvInitial + $fvContributions;
        $invested = $initial + ($monthly * $months);

        return [
            'fv_initial' => self::roundMoney($fvInitial),
            'fv_contributions' => self::roundMoney($fvContributions),
            'corpus' => self::roundMoney($corpus),
            'invested' => self::roundMoney($invested),
            'gains' => self::roundMoney($corpus - $invested),
            'months' => $months,
        ];
    }

    /**
     * Standard EMI and amortisation comparison with optional extra payment.
     *
     * @return array<string, mixed>
     */
    public static function emiPlan(float $principal, float $annualRate, float $years, float $extraPayment = 0.0): array
    {
        $n = (int) round($years * 12);
        $r = $annualRate / 12 / 100;

        if ($n <= 0 || $principal <= 0) {
            return [
                'emi' => 0.0,
                'standard_interest' => 0.0,
                'standard_months' => 0,
                'accelerated_interest' => 0.0,
                'accelerated_months' => 0,
                'interest_saved' => 0.0,
                'months_saved' => 0,
            ];
        }

        if (abs($r) < 1e-12) {
            $emi = $principal / $n;
        } else {
            $emi = $principal * $r * ((1 + $r) ** $n) / (((1 + $r) ** $n) - 1);
        }

        $standardInterest = ($emi * $n) - $principal;
        $accelerated = self::amortize($principal, $r, $emi + max(0, $extraPayment), $n);

        return [
            'emi' => self::roundMoney($emi),
            'standard_interest' => self::roundMoney($standardInterest),
            'standard_total' => self::roundMoney($emi * $n),
            'standard_months' => $n,
            'accelerated_interest' => self::roundMoney($accelerated['interest']),
            'accelerated_months' => $accelerated['months'],
            'accelerated_total' => self::roundMoney($accelerated['total_paid']),
            'interest_saved' => self::roundMoney(max(0, $standardInterest - $accelerated['interest'])),
            'months_saved' => max(0, $n - $accelerated['months']),
            'monthly_rate' => self::roundRate($r),
        ];
    }

    /**
     * @return array{months: int, interest: float, total_paid: float}
     */
    private static function amortize(float $principal, float $monthlyRate, float $payment, int $maxMonths): array
    {
        $balance = $principal;
        $interest = 0.0;
        $totalPaid = 0.0;
        $months = 0;

        if ($payment <= 0) {
            return ['months' => $maxMonths, 'interest' => ($monthlyRate * $principal * $maxMonths), 'total_paid' => 0.0];
        }

        while ($balance > 0.01 && $months < $maxMonths + 600) {
            $months++;
            $interestPortion = abs($monthlyRate) < 1e-12 ? 0.0 : $balance * $monthlyRate;
            $interest += $interestPortion;
            $due = $balance + $interestPortion;
            $pay = min($payment, $due);
            $balance = $due - $pay;
            $totalPaid += $pay;

            if ($months > 1200) {
                break;
            }
        }

        return [
            'months' => $months,
            'interest' => $interest,
            'total_paid' => $totalPaid,
        ];
    }

    /**
     * Present value of a level monthly annuity.
     */
    public static function presentValueAnnuity(float $monthlyPayment, float $monthlyRate, int $months): float
    {
        if ($months <= 0 || $monthlyPayment <= 0) {
            return 0.0;
        }

        if (abs($monthlyRate) < 1e-12) {
            return $monthlyPayment * $months;
        }

        return $monthlyPayment * (1 - ((1 + $monthlyRate) ** (-$months))) / $monthlyRate;
    }
}
