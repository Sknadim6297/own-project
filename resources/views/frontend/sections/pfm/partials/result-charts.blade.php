@php
    $money = fn ($value) => '₹'.number_format((float) $value, 2);
    $inputs = is_array($inputs ?? null) ? $inputs : [];
    $chartId = 'pfm-chart-'.preg_replace('/[^a-z0-9\-]+/', '-', $toolKey);
@endphp

@if ($toolKey === 'monthly-money-planner')
    @php
        $income = max(0, (float) ($inputs['monthly_income'] ?? 0));
        $essential = max(0, (float) ($inputs['essential_expenses'] ?? 0));
        $lifestyle = max(0, (float) ($inputs['lifestyle_expenses'] ?? 0));
        $emis = max(0, (float) ($inputs['emis'] ?? 0));
        $other = max(0, (float) ($inputs['other_recurring'] ?? 0));
        $investments = max(0, (float) ($inputs['investments'] ?? 0));
        $freeCash = (float) ($result['free_cash'] ?? 0);
        $bars = [
            ['label' => 'Essential', 'value' => $essential, 'color' => '#142B4D'],
            ['label' => 'Lifestyle', 'value' => $lifestyle, 'color' => '#5b6b7c'],
            ['label' => 'EMIs', 'value' => $emis, 'color' => '#c4a35a'],
            ['label' => 'Other', 'value' => $other, 'color' => '#8aa4b8'],
            ['label' => 'Investments', 'value' => $investments, 'color' => '#36877F'],
            ['label' => 'Free cash', 'value' => max(0, $freeCash), 'color' => '#2a6b65'],
        ];
        $max = max(1, $income, ...array_column($bars, 'value'));
    @endphp
    <figure class="yk-pfm__chart" aria-labelledby="{{ $chartId }}-title">
        <figcaption id="{{ $chartId }}-title" class="yk-pfm__chart-title">Monthly money picture</figcaption>
        <svg viewBox="0 0 360 180" role="img" aria-label="Bar chart of monthly money allocation" preserveAspectRatio="xMidYMid meet">
            @foreach ($bars as $i => $bar)
                @php
                    $h = ($bar['value'] / $max) * 120;
                    $x = 24 + ($i * 56);
                    $y = 140 - $h;
                @endphp
                <rect x="{{ $x }}" y="{{ $y }}" width="36" height="{{ max(1, $h) }}" rx="6" fill="{{ $bar['color'] }}"></rect>
                <text x="{{ $x + 18 }}" y="158" text-anchor="middle" font-size="9" fill="#5b6b7c">{{ $bar['label'] }}</text>
            @endforeach
        </svg>
        <p class="yk-pfm__chart-note">Income {{ $money($income) }}. Bars use your entered amounts{{ $freeCash < 0 ? '; free cash is negative so that bar is empty.' : '.' }}</p>
    </figure>

@elseif ($toolKey === 'net-worth-calculator')
    @php
        $assets = max(0, (float) ($result['total_assets'] ?? 0));
        $liabilities = max(0, (float) ($result['total_liabilities'] ?? 0));
        $total = max(1, $assets + $liabilities);
        $assetPct = ($assets / $total) * 100;
        $debtPct = ($liabilities / $total) * 100;
    @endphp
    <figure class="yk-pfm__chart" aria-labelledby="{{ $chartId }}-title">
        <figcaption id="{{ $chartId }}-title" class="yk-pfm__chart-title">Assets vs liabilities</figcaption>
        <div class="yk-pfm__stack" role="img" aria-label="Assets {{ number_format($assetPct, 1) }} percent, liabilities {{ number_format($debtPct, 1) }} percent">
            <span class="yk-pfm__stack-seg yk-pfm__stack-seg--teal" style="width: {{ $assetPct }}%"></span>
            <span class="yk-pfm__stack-seg yk-pfm__stack-seg--navy" style="width: {{ $debtPct }}%"></span>
        </div>
        <p class="yk-pfm__chart-note">Assets {{ $money($assets) }} · Liabilities {{ $money($liabilities) }}</p>
    </figure>

@elseif ($toolKey === 'emergency-fund-calculator')
    @php
        $target = max(0, (float) ($result['target_amount'] ?? 0));
        $current = max(0, (float) ($inputs['current_savings'] ?? 0));
        $pct = $target > 0 ? min(100, ($current / $target) * 100) : 0;
    @endphp
    <figure class="yk-pfm__chart" aria-labelledby="{{ $chartId }}-title">
        <figcaption id="{{ $chartId }}-title" class="yk-pfm__chart-title">Emergency fund progress</figcaption>
        <div class="yk-pfm__progress" role="img" aria-label="{{ number_format($pct, 1) }} percent of target funded">
            <span style="width: {{ $pct }}%"></span>
        </div>
        <p class="yk-pfm__chart-note">{{ $money($current) }} of {{ $money($target) }} ({{ number_format($pct, 1) }}%)</p>
    </figure>

@elseif ($toolKey === 'financial-goal-calculator')
    @php
        $corpus = max(0, (float) ($result['required_corpus'] ?? 0));
        $existingFv = max(0, (float) ($result['future_value_existing'] ?? 0));
        $gap = max(0, (float) ($result['funding_gap'] ?? 0));
        $total = max(1, $corpus);
        $fundedPct = min(100, ($existingFv / $total) * 100);
        $gapPct = min(100, ($gap / $total) * 100);
    @endphp
    <figure class="yk-pfm__chart" aria-labelledby="{{ $chartId }}-title">
        <figcaption id="{{ $chartId }}-title" class="yk-pfm__chart-title">Goal funding mix</figcaption>
        <div class="yk-pfm__stack" role="img" aria-label="Existing savings cover {{ number_format($fundedPct, 1) }} percent; gap {{ number_format($gapPct, 1) }} percent">
            <span class="yk-pfm__stack-seg yk-pfm__stack-seg--teal" style="width: {{ $fundedPct }}%"></span>
            <span class="yk-pfm__stack-seg yk-pfm__stack-seg--warn" style="width: {{ $gapPct }}%"></span>
        </div>
        <p class="yk-pfm__chart-note">Covered {{ $money($existingFv) }} · Gap {{ $money($gap) }}</p>
    </figure>

@elseif ($toolKey === 'debt-emi-calculator')
    @php
        $principal = max(0, (float) ($inputs['principal'] ?? 0));
        $interest = max(0, (float) ($result['standard_interest'] ?? 0));
        $total = max(1, $principal + $interest);
        $pPct = ($principal / $total) * 100;
        $iPct = ($interest / $total) * 100;
        $saved = max(0, (float) ($result['interest_saved'] ?? 0));
    @endphp
    <figure class="yk-pfm__chart" aria-labelledby="{{ $chartId }}-title">
        <figcaption id="{{ $chartId }}-title" class="yk-pfm__chart-title">EMI repayment breakdown</figcaption>
        <div class="yk-pfm__stack" role="img" aria-label="Principal {{ number_format($pPct, 1) }} percent, interest {{ number_format($iPct, 1) }} percent">
            <span class="yk-pfm__stack-seg yk-pfm__stack-seg--teal" style="width: {{ $pPct }}%"></span>
            <span class="yk-pfm__stack-seg yk-pfm__stack-seg--navy" style="width: {{ $iPct }}%"></span>
        </div>
        <p class="yk-pfm__chart-note">Principal {{ $money($principal) }} · Interest {{ $money($interest) }}@if ($saved > 0) · Interest saved with extra payments {{ $money($saved) }}@endif</p>
    </figure>

@elseif ($toolKey === 'retirement-calculator')
    @php
        $corpus = max(0, (float) ($result['required_corpus'] ?? 0));
        $assetsFv = max(0, (float) ($result['future_value_assets'] ?? 0));
        $gap = max(0, (float) ($result['funding_gap'] ?? 0));
        $readiness = (int) ($result['readiness_score'] ?? 0);
        $total = max(1, $corpus);
        $assetPct = min(100, ($assetsFv / $total) * 100);
        $gapPct = min(100, ($gap / $total) * 100);
    @endphp
    <figure class="yk-pfm__chart" aria-labelledby="{{ $chartId }}-title">
        <figcaption id="{{ $chartId }}-title" class="yk-pfm__chart-title">Retirement readiness</figcaption>
        <div class="yk-pfm__readiness" role="img" aria-label="Readiness score {{ $readiness }} percent">
            <strong>{{ $readiness }}%</strong>
            <span>readiness</span>
        </div>
        <div class="yk-pfm__stack" aria-hidden="true">
            <span class="yk-pfm__stack-seg yk-pfm__stack-seg--teal" style="width: {{ $assetPct }}%"></span>
            <span class="yk-pfm__stack-seg yk-pfm__stack-seg--warn" style="width: {{ $gapPct }}%"></span>
        </div>
        <p class="yk-pfm__chart-note">Projected assets {{ $money($assetsFv) }} · Gap {{ $money($gap) }}</p>
    </figure>

@elseif ($toolKey === 'inflation-calculator')
    @php
        $current = max(0, (float) ($inputs['current_amount'] ?? 0));
        $future = max(0, (float) ($result['future_cost'] ?? 0));
        $max = max(1, $future, $current);
        $cH = ($current / $max) * 120;
        $fH = ($future / $max) * 120;
    @endphp
    <figure class="yk-pfm__chart" aria-labelledby="{{ $chartId }}-title">
        <figcaption id="{{ $chartId }}-title" class="yk-pfm__chart-title">Inflation impact</figcaption>
        <svg viewBox="0 0 220 180" role="img" aria-label="Current amount versus future cost" preserveAspectRatio="xMidYMid meet">
            <rect x="40" y="{{ 140 - $cH }}" width="50" height="{{ max(1, $cH) }}" rx="8" fill="#36877F"></rect>
            <rect x="130" y="{{ 140 - $fH }}" width="50" height="{{ max(1, $fH) }}" rx="8" fill="#142B4D"></rect>
            <text x="65" y="158" text-anchor="middle" font-size="11" fill="#5b6b7c">Today</text>
            <text x="155" y="158" text-anchor="middle" font-size="11" fill="#5b6b7c">Future</text>
        </svg>
        <p class="yk-pfm__chart-note">Today {{ $money($current) }} · Future {{ $money($future) }}</p>
    </figure>

@elseif ($toolKey === 'savings-investment-calculator')
    @php
        $invested = max(0, (float) ($result['invested'] ?? 0));
        $gains = max(0, (float) ($result['gains'] ?? 0));
        $corpus = max(0, (float) ($result['corpus'] ?? 0));
        $total = max(1, $corpus);
        $invPct = min(100, ($invested / $total) * 100);
        $gainPct = min(100, ($gains / $total) * 100);
    @endphp
    <figure class="yk-pfm__chart" aria-labelledby="{{ $chartId }}-title">
        <figcaption id="{{ $chartId }}-title" class="yk-pfm__chart-title">Investment growth mix</figcaption>
        <div class="yk-pfm__stack" role="img" aria-label="Invested {{ number_format($invPct, 1) }} percent, gains {{ number_format($gainPct, 1) }} percent">
            <span class="yk-pfm__stack-seg yk-pfm__stack-seg--navy" style="width: {{ $invPct }}%"></span>
            <span class="yk-pfm__stack-seg yk-pfm__stack-seg--teal" style="width: {{ $gainPct }}%"></span>
        </div>
        <p class="yk-pfm__chart-note">Invested {{ $money($invested) }} · Estimated gains {{ $money($gains) }} · Corpus {{ $money($corpus) }}</p>
    </figure>
@endif
