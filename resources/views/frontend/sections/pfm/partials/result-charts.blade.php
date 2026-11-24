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
            ['label' => 'Income', 'value' => $income, 'color' => '#1f1f1f'],
            ['label' => 'Essential', 'value' => $essential, 'color' => '#6e0b3c'],
            ['label' => 'Lifestyle', 'value' => $lifestyle, 'color' => '#c3196b'],
            ['label' => 'EMIs', 'value' => $emis, 'color' => '#e0a73f'],
            ['label' => 'Other', 'value' => $other, 'color' => '#efe0e8'],
            ['label' => 'Invested', 'value' => $investments, 'color' => '#8a0f4d'],
            ['label' => 'Free cash', 'value' => max(0, $freeCash), 'color' => '#2fa37c'],
        ];
        $max = max(1, $income, ...array_column($bars, 'value'));
        // Compact Indian labels for the values above the bars (₹50K, ₹1.2L).
        $short = function (float $v): string {
            $trim = fn (float $x, int $d) => rtrim(rtrim(number_format($x, $d, '.', ''), '0'), '.');
            if ($v >= 1e7) {
                return '₹'.$trim($v / 1e7, 2).'Cr';
            }
            if ($v >= 1e5) {
                return '₹'.$trim($v / 1e5, 2).'L';
            }
            if ($v >= 1e3) {
                return '₹'.$trim($v / 1e3, 1).'K';
            }

            return '₹'.number_format($v);
        };
    @endphp
    <figure class="yk-pfm__chart" aria-labelledby="{{ $chartId }}-title">
        <figcaption id="{{ $chartId }}-title" class="yk-pfm__chart-title">Monthly money picture</figcaption>
        <svg viewBox="0 0 360 190" role="img" aria-label="Bar chart of monthly money allocation" preserveAspectRatio="xMidYMid meet">
            <line x1="4" x2="356" y1="150" y2="150" stroke="#efe0e8" stroke-width="1.5"></line>
            @foreach ($bars as $i => $bar)
                @php
                    $h = ($bar['value'] / $max) * 118;
                    $x = 10 + ($i * 48);
                    $y = 150 - $h;
                @endphp
                <rect x="{{ $x }}" y="{{ $y }}" width="34" height="{{ max(1, $h) }}" rx="6" fill="{{ $bar['color'] }}"></rect>
                <text x="{{ $x + 17 }}" y="{{ max(12, $y - 6) }}" text-anchor="middle" font-size="10" font-weight="700" fill="#1f1f1f">{{ $short($bar['value']) }}</text>
                <text x="{{ $x + 17 }}" y="168" text-anchor="middle" font-size="10" fill="#6b6b7a">{{ $bar['label'] }}</text>
            @endforeach
        </svg>
        <p class="yk-pfm__chart-note">Monthly income {{ $money($income) }}. Bars use your entered amounts{{ $freeCash < 0 ? '; free cash is negative so that bar is empty.' : '.' }}</p>
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
    {{-- Progress + metrics are rendered in emergency-summary (Task 30). --}}

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
            <rect x="40" y="{{ 140 - $cH }}" width="50" height="{{ max(1, $cH) }}" rx="8" fill="#8a0f4d"></rect>
            <rect x="130" y="{{ 140 - $fH }}" width="50" height="{{ max(1, $fH) }}" rx="8" fill="#e0a73f"></rect>
            <text x="65" y="158" text-anchor="middle" font-size="11" fill="#6b6b7a">Today</text>
            <text x="155" y="158" text-anchor="middle" font-size="11" fill="#6b6b7a">Future</text>
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
