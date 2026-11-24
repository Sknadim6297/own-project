@php
    $money = fn ($value) => '₹'.number_format((float) $value, 2);
    $inputs = is_array($inputs ?? null) ? $inputs : [];
    $result = is_array($result ?? null) ? $result : [];

    $target = (float) ($result['target_amount'] ?? 0);
    $shortfall = (float) ($result['shortfall'] ?? 0);
    $current = (float) ($inputs['current_savings'] ?? 0);
    $capacity = (float) ($inputs['monthly_saving_capacity'] ?? 0);
    $coverageTarget = (int) ($inputs['coverage_months'] ?? 0);
    $coverageActual = (float) ($result['coverage_months_actual'] ?? 0);
    $monthsToTarget = $result['months_to_target'] ?? null;
    $achieved = $shortfall <= 0.0;
    $pct = $target > 0 ? min(100, max(0, ($current / $target) * 100)) : ($achieved ? 100.0 : 0.0);

    if ($achieved) {
        $statusLabel = $current > $target ? 'Above target' : 'Target achieved';
        $statusTone = 'good';
        $narrative = 'Your current emergency savings meet or exceed the recommended target for the selected coverage.';
    } elseif ($capacity <= 0) {
        $statusLabel = 'Building fund';
        $statusTone = 'warn';
        $narrative = 'You still need '.$money($shortfall).' to reach your emergency fund target. Add a monthly saving capacity to estimate how long that may take. Educational estimate only—not a guarantee.';
    } else {
        $statusLabel = 'Building fund';
        $statusTone = 'warn';
        $approx = number_format((float) $monthsToTarget, 1);
        $narrative = 'Based on your current savings and monthly saving capacity, you could reach your emergency fund target in approximately '.$approx.' months. Educational estimate only—not a guarantee.';
    }
@endphp

<section class="yk-ef__summary" aria-labelledby="yk-ef-summary-heading">
    <div class="yk-ef__hero-metric">
        <p class="yk-ef__hero-label">
            <x-yk-icon name="shield-check" class="yk-icon--sm" />
            Recommended emergency fund target
        </p>
        <p class="yk-ef__hero-value">{{ $money($target) }}</p>
        <p class="yk-ef__hero-meta">
            {{ $coverageTarget > 0 ? $coverageTarget.' months of expenses' : 'Selected coverage' }}
            · Status:
            <span class="yk-pfm__pill yk-pfm__pill--{{ $statusTone }}">{{ $statusLabel }}</span>
        </p>
    </div>

    <div class="yk-ef__progress-block">
        <div class="yk-ef__progress-head">
            <h2 id="yk-ef-summary-heading" class="yk-pfm__section-title">Emergency fund progress</h2>
            <p class="yk-ef__progress-pct" aria-hidden="true">{{ number_format($pct, 1) }}%</p>
        </div>
        <div
            class="yk-pfm__progress yk-ef__progress"
            role="progressbar"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="{{ (int) round($pct) }}"
            aria-valuetext="{{ number_format($pct, 1) }} percent of target funded. Current savings {{ $money($current) }}. Remaining {{ $money($shortfall) }}."
        >
            <span style="width: {{ $pct }}%"></span>
        </div>
        <p class="yk-ef__progress-note">
            Current savings {{ $money($current) }}
            · Remaining {{ $money($shortfall) }}
            · {{ number_format($pct, 1) }}% of target
        </p>
    </div>

    <dl class="yk-ef__metric-grid">
        <div class="yk-ef__metric">
            <dt><x-yk-icon name="wallet" class="yk-icon--sm" /> Current savings</dt>
            <dd>{{ $money($current) }}</dd>
        </div>
        <div class="yk-ef__metric">
            <dt><x-yk-icon name="target" class="yk-icon--sm" /> Remaining requirement</dt>
            <dd>{{ $money($shortfall) }}</dd>
        </div>
        <div class="yk-ef__metric">
            <dt><x-yk-icon name="calendar-days" class="yk-icon--sm" /> Target coverage</dt>
            <dd>{{ $coverageTarget > 0 ? $coverageTarget.' months' : '—' }}</dd>
        </div>
        <div class="yk-ef__metric">
            <dt><x-yk-icon name="history" class="yk-icon--sm" /> Current coverage</dt>
            <dd>{{ number_format($coverageActual, 1) }} months</dd>
        </div>
        <div class="yk-ef__metric">
            <dt><x-yk-icon name="wallet" class="yk-icon--sm" /> Monthly capacity</dt>
            <dd>{{ $money($capacity) }}</dd>
        </div>
        <div class="yk-ef__metric">
            <dt><x-yk-icon name="clock" class="yk-icon--sm" /> Est. time to target</dt>
            <dd>{{ $monthsToTarget === null ? '—' : number_format((float) $monthsToTarget, 1).' months' }}</dd>
        </div>
    </dl>

    <div class="yk-ef__narrative" role="status">
        <p>
            <x-yk-icon name="info" class="yk-icon--sm" />
            {{ $narrative }}
        </p>
    </div>
</section>
