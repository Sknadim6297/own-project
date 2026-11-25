@php
    $money = fn ($value) => '₹'.number_format((float) $value, 2);
@endphp

@if ($toolKey === 'monthly-money-planner')
    @php $freeCash = (float) ($result['free_cash'] ?? 0); @endphp
    <div class="yk-pfm__metrics">
        <div class="yk-pfm__metric yk-pfm__metric--primary {{ $freeCash < 0 ? 'yk-pfm__metric--bad' : '' }}"><span>Free cash</span><strong>{{ $freeCash < 0 ? '−'.$money(abs($freeCash)) : $money($freeCash) }}</strong></div>
        <div class="yk-pfm__metric"><span>Savings rate</span><strong>{{ number_format((float) $result['savings_rate_percent'], 2) }}%</strong></div>
        <div class="yk-pfm__metric yk-pfm__metric--{{ $result['health_tone'] }}"><span>Health</span><strong>{{ $result['health_label'] }}</strong></div>
        <div class="yk-pfm__metric"><span>Total outflow</span><strong>{{ $money($result['total_outflow']) }}</strong></div>
    </div>
@elseif ($toolKey === 'net-worth-calculator')
    <div class="yk-pfm__metrics">
        <div class="yk-pfm__metric yk-pfm__metric--primary"><span>Net worth</span><strong>{{ $money($result['net_worth']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Total assets</span><strong>{{ $money($result['total_assets']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Total liabilities</span><strong>{{ $money($result['total_liabilities']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Debt / asset</span><strong>{{ $result['debt_asset_ratio'] === null ? 'N/A' : number_format(((float) $result['debt_asset_ratio']) * 100, 2).'%' }}</strong></div>
    </div>
@elseif ($toolKey === 'emergency-fund-calculator')
    @include('frontend.sections.pfm.partials.emergency-summary', [
        'result' => $result,
        'inputs' => $inputs ?? [],
    ])
@elseif ($toolKey === 'financial-goal-calculator')
    <div class="yk-pfm__metrics">
        <div class="yk-pfm__metric yk-pfm__metric--primary"><span>Monthly investment</span><strong>{{ $money($result['monthly_investment']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Required corpus</span><strong>{{ $money($result['required_corpus']) }}</strong></div>
        <div class="yk-pfm__metric"><span>FV of savings</span><strong>{{ $money($result['future_value_existing']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Funding gap</span><strong>{{ $money($result['funding_gap']) }}</strong></div>
    </div>
@elseif ($toolKey === 'debt-emi-calculator')
    @php
        $purchaseAmount = (float) ($result['purchase_amount'] ?? $inputs['principal'] ?? 0);
        $downPayment = (float) ($result['down_payment'] ?? $inputs['down_payment'] ?? 0);
        $financed = (float) ($result['financed_principal'] ?? max(0, $purchaseAmount - $downPayment));
        $noLoan = (bool) ($result['no_loan'] ?? ($financed <= 0));
        $loanRepayment = (float) ($result['loan_repayment_total'] ?? $result['standard_total'] ?? 0);
        $totalOutflow = (float) ($result['total_purchase_outflow'] ?? ($downPayment + $loanRepayment));
    @endphp
    <div class="yk-pfm__metrics">
        <div class="yk-pfm__metric yk-pfm__metric--primary"><span>EMI</span><strong>{{ $money($result['emi']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Total purchase amount</span><strong>{{ $money($purchaseAmount) }}</strong></div>
        <div class="yk-pfm__metric"><span>Down payment</span><strong>{{ $money($downPayment) }}</strong></div>
        <div class="yk-pfm__metric"><span>Actual loan amount</span><strong>{{ $money($financed) }}</strong></div>
        @if ($noLoan)
            <div class="yk-pfm__metric"><span>Loan status</span><strong>No borrowing required</strong></div>
            <div class="yk-pfm__metric"><span>Total paid upfront</span><strong>{{ $money($downPayment) }}</strong></div>
        @else
            <div class="yk-pfm__metric"><span>Standard interest</span><strong>{{ $money($result['standard_interest']) }}</strong></div>
            <div class="yk-pfm__metric"><span>Total loan repayment</span><strong>{{ $money($loanRepayment) }}</strong></div>
            <div class="yk-pfm__metric"><span>Purchase + loan outflow</span><strong>{{ $money($totalOutflow) }}</strong></div>
            <div class="yk-pfm__metric"><span>Interest saved</span><strong>{{ $money($result['interest_saved']) }}</strong></div>
            <div class="yk-pfm__metric"><span>Months saved</span><strong>{{ (int) $result['months_saved'] }}</strong></div>
        @endif
    </div>
@elseif ($toolKey === 'retirement-calculator')
    <div class="yk-pfm__metrics">
        <div class="yk-pfm__metric yk-pfm__metric--primary"><span>Required corpus</span><strong>{{ $money($result['required_corpus'] ?? 0) }}</strong></div>
        <div class="yk-pfm__metric"><span>Monthly investment</span><strong>{{ $money($result['monthly_investment'] ?? 0) }}</strong></div>
        <div class="yk-pfm__metric"><span>Funding gap</span><strong>{{ $money($result['funding_gap'] ?? 0) }}</strong></div>
        <div class="yk-pfm__metric"><span>Readiness</span><strong>{{ (int) ($result['readiness_score'] ?? 0) }}%</strong></div>
        @if (array_key_exists('years_to_retire', $result))
            <div class="yk-pfm__metric"><span>Years until retirement</span><strong>{{ (int) $result['years_to_retire'] }}</strong></div>
        @endif
        @if (array_key_exists('future_monthly_expense', $result))
            <div class="yk-pfm__metric"><span>Est. expenses at retirement</span><strong>{{ $money($result['future_monthly_expense']) }}</strong></div>
        @endif
        @if (array_key_exists('future_value_assets', $result))
            <div class="yk-pfm__metric"><span>Projected assets at retirement</span><strong>{{ $money($result['future_value_assets']) }}</strong></div>
        @endif
    </div>
@elseif ($toolKey === 'inflation-calculator')
    @php
        $infHas = static fn (string $key): bool => array_key_exists($key, $result) && $result[$key] !== null && $result[$key] !== '';
        $infMoney = static fn (string $key): string => $infHas($key) ? $money($result[$key]) : 'Unavailable';
    @endphp
    <div class="yk-pfm__metrics">
        <div class="yk-pfm__metric yk-pfm__metric--primary"><span>Future cost</span><strong>{{ $infMoney('future_cost') }}</strong></div>
        <div class="yk-pfm__metric"><span>Increase</span><strong>{{ $infMoney('increase') }}</strong></div>
        <div class="yk-pfm__metric"><span>Purchasing power</span><strong>{{ $infMoney('purchasing_power') }}</strong></div>
    </div>
@elseif ($toolKey === 'savings-investment-calculator')
    @php
        $inputs = is_array($inputs ?? null) ? $inputs : [];
        $siHas = static fn (string $key): bool => array_key_exists($key, $result) && $result[$key] !== null && $result[$key] !== '';
        $siMoney = static fn (string $key): string => $siHas($key) ? $money($result[$key]) : 'Unavailable';
        $siYears = array_key_exists('years', $inputs) && $inputs['years'] !== null && $inputs['years'] !== ''
            ? rtrim(rtrim(number_format((float) $inputs['years'], 2, '.', ''), '0'), '.').' years'
            : null;
    @endphp
    <div class="yk-pfm__metrics">
        <div class="yk-pfm__metric yk-pfm__metric--primary">
            <span>Estimated maturity amount</span>
            <strong>{{ $siMoney('corpus') }}</strong>
            @if ($siYears)
                <em class="yk-pfm__metric-note">Over {{ $siYears }}</em>
            @endif
        </div>
        <div class="yk-pfm__metric"><span>Total invested amount</span><strong>{{ $siMoney('invested') }}</strong></div>
        <div class="yk-pfm__metric"><span>Estimated investment returns</span><strong>{{ $siMoney('gains') }}</strong></div>
        <div class="yk-pfm__metric"><span>Grown value of monthly contributions</span><strong>{{ $siMoney('fv_contributions') }}</strong></div>
    </div>
@endif
