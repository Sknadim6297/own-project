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
    <div class="yk-pfm__metrics">
        <div class="yk-pfm__metric yk-pfm__metric--primary"><span>Target fund</span><strong>{{ $money($result['target_amount']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Shortfall</span><strong>{{ $money($result['shortfall']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Coverage</span><strong>{{ number_format((float) $result['coverage_months_actual'], 1) }} months</strong></div>
        <div class="yk-pfm__metric"><span>Months to target</span><strong>{{ $result['months_to_target'] === null ? '—' : number_format((float) $result['months_to_target'], 1) }}</strong></div>
    </div>
@elseif ($toolKey === 'financial-goal-calculator')
    <div class="yk-pfm__metrics">
        <div class="yk-pfm__metric yk-pfm__metric--primary"><span>Monthly investment</span><strong>{{ $money($result['monthly_investment']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Required corpus</span><strong>{{ $money($result['required_corpus']) }}</strong></div>
        <div class="yk-pfm__metric"><span>FV of savings</span><strong>{{ $money($result['future_value_existing']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Funding gap</span><strong>{{ $money($result['funding_gap']) }}</strong></div>
    </div>
@elseif ($toolKey === 'debt-emi-calculator')
    <div class="yk-pfm__metrics">
        <div class="yk-pfm__metric yk-pfm__metric--primary"><span>EMI</span><strong>{{ $money($result['emi']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Standard interest</span><strong>{{ $money($result['standard_interest']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Interest saved</span><strong>{{ $money($result['interest_saved']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Months saved</span><strong>{{ (int) $result['months_saved'] }}</strong></div>
    </div>
@elseif ($toolKey === 'retirement-calculator')
    <div class="yk-pfm__metrics">
        <div class="yk-pfm__metric yk-pfm__metric--primary"><span>Required corpus</span><strong>{{ $money($result['required_corpus']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Monthly investment</span><strong>{{ $money($result['monthly_investment']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Funding gap</span><strong>{{ $money($result['funding_gap']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Readiness</span><strong>{{ (int) $result['readiness_score'] }}%</strong></div>
    </div>
@elseif ($toolKey === 'inflation-calculator')
    <div class="yk-pfm__metrics">
        <div class="yk-pfm__metric yk-pfm__metric--primary"><span>Future cost</span><strong>{{ $money($result['future_cost']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Increase</span><strong>{{ $money($result['increase']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Purchasing power</span><strong>{{ $money($result['purchasing_power']) }}</strong></div>
    </div>
@elseif ($toolKey === 'savings-investment-calculator')
    <div class="yk-pfm__metrics">
        <div class="yk-pfm__metric yk-pfm__metric--primary"><span>Future corpus</span><strong>{{ $money($result['corpus']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Total invested</span><strong>{{ $money($result['invested']) }}</strong></div>
        <div class="yk-pfm__metric"><span>Estimated gains</span><strong>{{ $money($result['gains']) }}</strong></div>
        <div class="yk-pfm__metric"><span>FV of contributions</span><strong>{{ $money($result['fv_contributions']) }}</strong></div>
    </div>
@endif
