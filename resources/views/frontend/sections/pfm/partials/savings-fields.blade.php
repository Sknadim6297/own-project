@php
    $v = fn ($key, $default = '') => old($key, $old[$key] ?? $default);
    $initialRaw = (string) $v('initial_amount', '0');
    $contributionRaw = (string) $v('contribution_amount', $v('monthly_contribution', '0'));
    $yearsRaw = (string) $v('years');
    $frequencyRaw = strtolower(trim((string) $v('investment_frequency', 'monthly')));
    $allowedFrequencies = ['monthly', 'quarterly', 'half_yearly', 'yearly'];
    $frequency = in_array($frequencyRaw, $allowedFrequencies, true) ? $frequencyRaw : 'monthly';
    $frequencyMeta = \App\Support\Pfm\SavingsInvestmentPresentation::frequencyMeta($frequency);
    $initialVal = is_numeric($initialRaw) ? (float) $initialRaw : 0.0;
    $contributionVal = is_numeric($contributionRaw) ? (float) $contributionRaw : 0.0;
    $yearsVal = is_numeric($yearsRaw) ? (float) $yearsRaw : 0.0;
    $hasInitial = $initialVal > 0.005;
    $hasContribution = $contributionVal > 0.005;
    if ($hasInitial && $hasContribution) {
        $inferredType = 'combined';
    } elseif ($hasContribution && ! $hasInitial) {
        $inferredType = 'monthly';
    } else {
        $inferredType = 'lump';
    }
    $previewMonths = $yearsVal > 0 ? (int) round($yearsVal * 12) : 0;
    $intervalMonths = (int) $frequencyMeta['interval_months'];
    $previewCount = $inferredType === 'lump' ? 0 : ($previewMonths > 0 ? intdiv($previewMonths, $intervalMonths) : 0);
    $previewRecurring = $inferredType === 'lump' ? 0.0 : ($contributionVal * $previewCount);
    $previewTotal = ($inferredType === 'monthly' ? 0.0 : $initialVal) + $previewRecurring;
    $fmtInr = function (float $value): string {
        $rounded = round($value, 2);
        $sign = $rounded < 0 ? '-' : '';
        $abs = abs($rounded);
        $whole = (string) (int) floor($abs);
        $frac = sprintf('%02d', (int) round(($abs - (int) floor($abs)) * 100));
        if (strlen($whole) > 3) {
            $head = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($whole, 0, -3));
            $whole = $head.','.substr($whole, -3);
        }

        return $sign.'₹'.$whole.'.'.$frac;
    };
@endphp
{{-- Task 48/50 — Investment amount + frequency UI (presentation; engine field contracts preserved). --}}
<div
    class="yk-siamt"
    data-yk-si-amount
    data-yk-si-type="{{ $inferredType }}"
    data-yk-si-frequency="{{ $frequency }}"
>
    <section class="yk-siamt__section" aria-labelledby="si-amount-heading">
        <header class="yk-siamt__head">
            <h2 id="si-amount-heading" class="yk-siamt__title">
                <x-yk-icon name="wallet" class="yk-icon--sm" />
                Investment amount
            </h2>
            <p class="yk-siamt__lead">
                Choose how you plan to invest, then enter amounts. Inactive contributions are sent as ₹0 so they are not included in the calculation.
            </p>
        </header>

        <fieldset class="yk-siamt__types" data-yk-si-type-group>
            <legend class="yk-siamt__legend">Investment type</legend>
            <div class="yk-siamt__type-row" role="radiogroup" aria-label="Investment type">
                <label class="yk-siamt__type {{ $inferredType === 'lump' ? 'is-active' : '' }}">
                    <input
                        type="radio"
                        name="si_investment_type_ui"
                        value="lump"
                        data-yk-si-type-option
                        {{ $inferredType === 'lump' ? 'checked' : '' }}
                    >
                    <span class="yk-siamt__type-card">
                        <span class="yk-siamt__type-icon" aria-hidden="true"><x-yk-icon name="wallet" class="yk-icon--sm" /></span>
                        <span class="yk-siamt__type-label">Lump Sum</span>
                        <span class="yk-siamt__type-hint">Initial investment only</span>
                    </span>
                </label>
                <label class="yk-siamt__type {{ $inferredType === 'monthly' ? 'is-active' : '' }}">
                    <input
                        type="radio"
                        name="si_investment_type_ui"
                        value="monthly"
                        data-yk-si-type-option
                        {{ $inferredType === 'monthly' ? 'checked' : '' }}
                    >
                    <span class="yk-siamt__type-card">
                        <span class="yk-siamt__type-icon" aria-hidden="true"><x-yk-icon name="calendar-days" class="yk-icon--sm" /></span>
                        <span class="yk-siamt__type-label">Recurring Investment</span>
                        <span class="yk-siamt__type-hint">Recurring contribution only</span>
                    </span>
                </label>
                <label class="yk-siamt__type {{ $inferredType === 'combined' ? 'is-active' : '' }}">
                    <input
                        type="radio"
                        name="si_investment_type_ui"
                        value="combined"
                        data-yk-si-type-option
                        {{ $inferredType === 'combined' ? 'checked' : '' }}
                    >
                    <span class="yk-siamt__type-card">
                        <span class="yk-siamt__type-icon" aria-hidden="true"><x-yk-icon name="chart-no-axes-combined" class="yk-icon--sm" /></span>
                        <span class="yk-siamt__type-label">Combined Investment</span>
                        <span class="yk-siamt__type-hint">Initial plus recurring</span>
                    </span>
                </label>
            </div>
            <p class="yk-siamt__help" id="si-type-help">
                At least one contribution must be greater than zero. Switching types keeps your previous amounts available if you switch back.
            </p>
        </fieldset>

        {{-- Task 50 — Investment frequency (hidden for lump sum). --}}
        <fieldset
            class="yk-sifreq"
            data-yk-si-frequency-group
            @if ($inferredType === 'lump') hidden @endif
            aria-labelledby="si-frequency-legend"
        >
            <legend id="si-frequency-legend" class="yk-siamt__legend">Investment frequency</legend>
            <p class="yk-siamt__help" id="si-frequency-help">
                Choose how often each recurring contribution is invested. The amount you enter is one contribution per selected period — not automatically converted between frequencies.
            </p>
            <div class="yk-sifreq__row" role="radiogroup" aria-label="Investment frequency">
                @foreach ([
                    'monthly' => ['label' => 'Monthly', 'hint' => 'Every 1 month · 12 / year'],
                    'quarterly' => ['label' => 'Quarterly', 'hint' => 'Every 3 months · 4 / year'],
                    'half_yearly' => ['label' => 'Half-Yearly', 'hint' => 'Every 6 months · 2 / year'],
                    'yearly' => ['label' => 'Yearly', 'hint' => 'Every 12 months · 1 / year'],
                ] as $freqKey => $freqCopy)
                    <label class="yk-sifreq__option {{ $frequency === $freqKey ? 'is-active' : '' }}">
                        <input
                            type="radio"
                            name="investment_frequency"
                            value="{{ $freqKey }}"
                            data-yk-si-frequency-option
                            {{ $frequency === $freqKey ? 'checked' : '' }}
                        >
                        <span class="yk-sifreq__card">
                            <span class="yk-sifreq__label">{{ $freqCopy['label'] }}</span>
                            <span class="yk-sifreq__hint">{{ $freqCopy['hint'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="yk-siamt__grid">
            <div
                class="yk-pfm__field yk-siamt__field"
                data-yk-si-field="initial"
                @if ($inferredType === 'monthly') hidden @endif
            >
                <label for="pfm-initial">Initial investment</label>
                <div class="yk-siamt__control">
                    <span class="yk-siamt__affix" aria-hidden="true">₹</span>
                    <input
                        id="pfm-initial"
                        type="number"
                        name="initial_amount"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        autocomplete="off"
                        placeholder="0"
                        value="{{ $v('initial_amount', '0') }}"
                        required
                        data-yk-si-initial
                        aria-describedby="pfm-initial-help si-amount-errors"
                    >
                </div>
                <p id="pfm-initial-help" class="yk-siamt__help">One-time amount invested at the start of the plan.</p>
                <div class="yk-siamt__presets" role="group" aria-label="Quick initial amounts">
                    @foreach ([10000, 50000, 100000, 500000] as $preset)
                        <button
                            type="button"
                            class="yk-siamt__preset"
                            data-yk-si-preset="initial"
                            data-yk-si-preset-value="{{ $preset }}"
                        >
                            @if ($preset >= 100000)
                                ₹{{ number_format($preset / 100000, $preset % 100000 === 0 ? 0 : 2) }}L
                            @else
                                ₹{{ number_format($preset) }}
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            <div
                class="yk-pfm__field yk-siamt__field"
                data-yk-si-field="monthly"
                @if ($inferredType === 'lump') hidden @endif
            >
                <label for="pfm-monthly" data-yk-si-contribution-label>{{ $frequencyMeta['amount_label'] }}</label>
                <div class="yk-siamt__control">
                    <span class="yk-siamt__affix" aria-hidden="true">₹</span>
                    <input
                        id="pfm-monthly"
                        type="number"
                        name="contribution_amount"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        autocomplete="off"
                        placeholder="0"
                        value="{{ $contributionRaw }}"
                        required
                        data-yk-si-monthly
                        data-yk-si-contribution
                        aria-describedby="pfm-monthly-help si-amount-errors"
                    >
                    {{-- Legacy alias kept in sync for API/tests that still post monthly_contribution. --}}
                    <input type="hidden" name="monthly_contribution" value="{{ $contributionRaw }}" data-yk-si-monthly-alias>
                </div>
                <p id="pfm-monthly-help" class="yk-siamt__help" data-yk-si-contribution-help>
                    Amount contributed once per {{ $frequencyMeta['period'] }} ({{ $frequencyMeta['adverb'] }}). Switching frequency does not convert this amount.
                </p>
                <div class="yk-siamt__presets" role="group" aria-label="Quick contribution amounts" data-yk-si-contribution-presets>
                    @foreach ([1000, 2000, 5000, 10000] as $preset)
                        <button
                            type="button"
                            class="yk-siamt__preset"
                            data-yk-si-preset="monthly"
                            data-yk-si-preset-value="{{ $preset }}"
                        >
                            ₹{{ number_format($preset) }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <p id="si-amount-errors" class="yk-siamt__error" data-yk-si-amount-error role="alert" hidden></p>

        <aside
            class="yk-siamt__preview"
            data-yk-si-preview
            aria-live="polite"
            aria-labelledby="si-preview-heading"
        >
            <h3 id="si-preview-heading" class="yk-siamt__preview-title">
                <x-yk-icon name="info" class="yk-icon--sm" />
                Contribution preview
            </h3>
            <dl class="yk-siamt__preview-grid yk-siamt__preview-grid--freq">
                <div>
                    <dt>Initial investment</dt>
                    <dd data-yk-si-preview-initial>{{ $fmtInr($inferredType === 'monthly' ? 0.0 : $initialVal) }}</dd>
                </div>
                <div>
                    <dt>Selected frequency</dt>
                    <dd data-yk-si-preview-frequency>{{ $frequencyMeta['label'] }}</dd>
                </div>
                <div>
                    <dt>Amount per contribution</dt>
                    <dd data-yk-si-preview-monthly>{{ $fmtInr($inferredType === 'lump' ? 0.0 : $contributionVal) }}</dd>
                </div>
                <div>
                    <dt>Contributions per year</dt>
                    <dd data-yk-si-preview-per-year>{{ $inferredType === 'lump' ? 0 : $frequencyMeta['per_year'] }}</dd>
                </div>
                <div>
                    <dt>Effective duration</dt>
                    <dd data-yk-si-preview-months>{{ $previewMonths }} months</dd>
                </div>
                <div>
                    <dt>Scheduled contributions</dt>
                    <dd data-yk-si-preview-count>{{ $previewCount }}</dd>
                </div>
                <div>
                    <dt>Total recurring contributions</dt>
                    <dd data-yk-si-preview-recurring>{{ $fmtInr($previewRecurring) }}</dd>
                </div>
                <div class="yk-siamt__preview-total">
                    <dt>Total invested (planned)</dt>
                    <dd data-yk-si-preview-total>{{ $fmtInr($previewTotal) }}</dd>
                </div>
            </dl>
            <p class="yk-siamt__preview-note">
                This preview shows planned contributions only. It excludes estimated investment returns. Partial periods do not add prorated payments. Maturity and gains are calculated on the results screen using the server engine.
            </p>
        </aside>
    </section>

    @php
        $yearsSlider = ($yearsVal > 0) ? max(0.01, min(100, $yearsVal)) : 10;
        $yearsDisplay = ($yearsVal > 0)
            ? rtrim(rtrim(number_format($yearsVal, 2, '.', ''), '0'), '.')
            : '—';
        $timelineMonths = $previewMonths;
        $midLabel = $yearsVal > 0
            ? rtrim(rtrim(number_format($yearsVal / 2, 2, '.', ''), '0'), '.').' yr'
            : 'Midpoint';
    @endphp

    {{-- Task 49 — Investment time period (presentation; same `years` field). --}}
    <section
        class="yk-siamt__section yk-sidur"
        data-yk-si-duration
        data-years-min="0.01"
        data-years-max="100"
        data-years-step="0.01"
        data-years-bump="1"
        aria-labelledby="si-duration-heading"
    >
        <header class="yk-siamt__head">
            <h2 id="si-duration-heading" class="yk-siamt__title">
                <x-yk-icon name="calendar-days" class="yk-icon--sm" />
                Investment Time Period
            </h2>
            <p class="yk-siamt__lead">Choose how long you want to stay invested.</p>
        </header>

        <div class="yk-sidur__head">
            <p class="yk-sidur__readout" aria-live="polite">
                <span class="yk-sidur__figure" data-yk-si-years-display>{{ $yearsDisplay }}</span>
                <span class="yk-sidur__unit">years</span>
            </p>
            <div class="yk-siamt__control yk-sidur__control">
                <label for="pfm-si-years" class="visually-hidden">Investment duration in years</label>
                <input
                    id="pfm-si-years"
                    type="number"
                    name="years"
                    min="0.01"
                    max="100"
                    step="0.01"
                    inputmode="decimal"
                    autocomplete="off"
                    value="{{ $v('years') }}"
                    required
                    data-yk-si-years
                    data-yk-si-years-input
                    aria-describedby="pfm-si-years-help si-duration-months"
                >
                <span class="yk-siamt__affix yk-siamt__affix--suffix" aria-hidden="true">years</span>
            </div>
        </div>

        <div class="yk-sidur__slider">
            <button type="button" class="yk-sidur__step" data-yk-si-years-dec aria-label="Decrease investment duration by 1 year">
                <x-yk-icon name="minus" class="yk-icon--sm" />
            </button>
            <div class="yk-sidur__track">
                <input
                    id="pfm-si-years-slider"
                    class="yk-sidur__range"
                    type="range"
                    min="0.01"
                    max="100"
                    step="0.01"
                    value="{{ $yearsSlider }}"
                    data-yk-si-years-slider
                    aria-valuemin="0.01"
                    aria-valuemax="100"
                    aria-valuenow="{{ $yearsSlider }}"
                    aria-label="Investment duration slider in years"
                >
                <div class="yk-sidur__scale" aria-hidden="true">
                    <span>Min</span>
                    <span>25</span>
                    <span>50</span>
                    <span>75</span>
                    <span>Max</span>
                </div>
                <div class="yk-sidur__bounds">
                    <span>0.01 yr</span>
                    <span>100 yr</span>
                </div>
            </div>
            <button type="button" class="yk-sidur__step" data-yk-si-years-inc aria-label="Increase investment duration by 1 year">
                <x-yk-icon name="plus" class="yk-icon--sm" />
            </button>
        </div>

        <div class="yk-sidur__presets" role="group" aria-label="Quick duration choices">
            @foreach ([1, 3, 5, 10, 15, 20, 30] as $quick)
                <button type="button" class="yk-sidur__preset" data-yk-si-years-preset="{{ $quick }}">{{ $quick }} {{ $quick === 1 ? 'year' : 'years' }}</button>
            @endforeach
        </div>

        <p id="si-duration-months" class="yk-sidur__months" data-yk-si-duration-months role="status" aria-live="polite">
            Equivalent duration:
            <strong data-yk-si-months-label>{{ $timelineMonths }} months</strong>
            <span data-yk-si-contrib-label>
                @if ($inferredType === 'lump')
                    · Lump sum — no recurring contribution periods
                @elseif ($previewCount === 0 && $previewMonths > 0)
                    · No complete {{ strtolower($frequencyMeta['label']) }} contribution period in this duration
                @else
                    · {{ $previewCount }} {{ strtolower($frequencyMeta['label']) }} contribution {{ $previewCount === 1 ? 'period' : 'periods' }}
                @endif
            </span>
        </p>

        <ol class="yk-sidur__timeline" data-yk-si-timeline aria-label="Investment timeline">
            <li>
                <span class="yk-sidur__dot" aria-hidden="true"></span>
                <span class="yk-sidur__tl-label">Start</span>
                <span class="yk-sidur__tl-note">Investment begins</span>
            </li>
            <li>
                <span class="yk-sidur__dot" aria-hidden="true"></span>
                <span class="yk-sidur__tl-label">Midpoint</span>
                <span class="yk-sidur__tl-note" data-yk-si-timeline-mid>{{ $midLabel }}</span>
            </li>
            <li>
                <span class="yk-sidur__dot" aria-hidden="true"></span>
                <span class="yk-sidur__tl-label">Completion</span>
                <span class="yk-sidur__tl-note" data-yk-si-timeline-end>
                    @if ($yearsVal > 0)
                        After {{ $yearsDisplay }} {{ abs($yearsVal - 1.0) < 1e-9 ? 'year' : 'years' }}
                    @else
                        Select a duration
                    @endif
                </span>
            </li>
        </ol>

        <p id="pfm-si-years-help" class="yk-siamt__help">
            Enter any duration from 0.01 to 100 years (2 decimal places). Effective contribution months follow the engine: round(years × 12). Invalid values are not replaced automatically — the server validates on Calculate.
        </p>
    </section>

    <section class="yk-siamt__section yk-siamt__section--meta" aria-labelledby="si-return-heading">
        <header class="yk-siamt__head">
            <h2 id="si-return-heading" class="yk-siamt__title">
                <x-yk-icon name="chart-no-axes-combined" class="yk-icon--sm" />
                Expected return
            </h2>
            <p class="yk-siamt__lead">Assumed annual growth rate used by the existing calculation model.</p>
        </header>
        <div class="yk-pfm__field">
            <label for="pfm-si-return">Expected annual return (%)</label>
            <div class="yk-siamt__control">
                <input
                    id="pfm-si-return"
                    type="number"
                    name="expected_return_percent"
                    min="0"
                    max="50"
                    step="0.01"
                    inputmode="decimal"
                    autocomplete="off"
                    value="{{ $v('expected_return_percent', '10') }}"
                    required
                    aria-describedby="pfm-si-return-help"
                >
                <span class="yk-siamt__affix yk-siamt__affix--suffix" aria-hidden="true">%</span>
            </div>
            <p id="pfm-si-return-help" class="yk-siamt__help">Assumed annual growth rate (0–50). Converted to a monthly rate by the engine.</p>
        </div>
    </section>

    <p class="yk-pfm__note">Estimated gains are illustrative and not guaranteed.</p>
</div>
