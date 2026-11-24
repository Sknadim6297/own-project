@php
    $inputs = is_array($inputs ?? null) ? $inputs : [];
    $result = is_array($result ?? null) ? $result : [];
    $money = fn ($value) => '₹'.number_format((float) $value, 2);
    $moneySigned = function ($value) use ($money) {
        $n = (float) $value;

        return ($n < 0 ? '−' : '').$money(abs($n));
    };
    $income = (float) ($inputs['monthly_income'] ?? 0);
    $essential = (float) ($inputs['essential_expenses'] ?? 0);
    $lifestyle = (float) ($inputs['lifestyle_expenses'] ?? 0);
    $emis = (float) ($inputs['emis'] ?? 0);
    $other = (float) ($inputs['other_recurring'] ?? 0);
    $investments = (float) ($inputs['investments'] ?? 0);
    $totalExpenses = array_key_exists('total_expenses', $result)
        ? (float) $result['total_expenses']
        : ($essential + $lifestyle + $emis + $other);
    $freeCash = (float) ($result['free_cash'] ?? 0);
    $outflow = (float) ($result['total_outflow'] ?? ($totalExpenses + $investments));
@endphp

@if ($toolKey === 'monthly-money-planner')
    <section class="yk-pfm__saved-summary" aria-labelledby="pfm-saved-summary-heading">
        <h2 id="pfm-saved-summary-heading" class="yk-pfm__section-title">Financial allocation breakdown</h2>
        <dl class="yk-pfm__input-grid">
            <div>
                <dt>Total income</dt>
                <dd>{{ $money($income) }}</dd>
            </div>
            <div>
                <dt>Total expenses</dt>
                <dd>{{ $money($totalExpenses) }}</dd>
            </div>
            <div>
                <dt>EMI obligations</dt>
                <dd>{{ $money($emis) }}</dd>
            </div>
            <div>
                <dt>Other recurring commitments</dt>
                <dd>{{ $money($other) }}</dd>
            </div>
            <div>
                <dt>Monthly investments</dt>
                <dd>{{ $money($investments) }}</dd>
            </div>
            <div>
                <dt>Total outflow</dt>
                <dd>{{ $money($outflow) }}</dd>
            </div>
            <div class="{{ $freeCash < 0 ? 'yk-pfm__input-grid-item--bad' : '' }}">
                <dt>Available cash (free cash)</dt>
                <dd>{{ $moneySigned($freeCash) }}</dd>
            </div>
            <div>
                <dt>Savings rate</dt>
                <dd>{{ number_format((float) ($result['savings_rate_percent'] ?? 0), 2) }}%</dd>
            </div>
        </dl>
    </section>
@endif
