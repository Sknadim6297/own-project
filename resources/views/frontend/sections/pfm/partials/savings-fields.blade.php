@php
    $v = fn ($key, $default = '') => old($key, $old[$key] ?? $default);
    $initialRaw = (string) $v('initial_amount', '0');
    $monthlyRaw = (string) $v('monthly_contribution', '0');
    $yearsRaw = (string) $v('years');
    $initialVal = is_numeric($initialRaw) ? (float) $initialRaw : 0.0;
    $monthlyVal = is_numeric($monthlyRaw) ? (float) $monthlyRaw : 0.0;
    $yearsVal = is_numeric($yearsRaw) ? (float) $yearsRaw : 0.0;
    $hasInitial = $initialVal > 0.005;
    $hasMonthly = $monthlyVal > 0.005;
    if ($hasInitial && $hasMonthly) {
        $inferredType = 'combined';
    } elseif ($hasMonthly && ! $hasInitial) {
        $inferredType = 'monthly';
    } else {
        $inferredType = 'lump';
    }
    $previewMonths = $yearsVal > 0 ? (int) round($yearsVal * 12) : 0;
    $previewTotal = $initialVal + ($monthlyVal * $previewMonths);
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
{{-- Task 48 — Investment amount UI (presentation only; same backend field names). --}}
<div
    class="yk-siamt"
    data-yk-si-amount
    data-yk-si-type="{{ $inferredType }}"
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
                        <span class="yk-siamt__type-label">Monthly Investment</span>
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
                        <span class="yk-siamt__type-hint">Initial plus monthly</span>
                    </span>
                </label>
            </div>
            <p class="yk-siamt__help" id="si-type-help">
                At least one contribution must be greater than zero. Switching types keeps your previous amounts available if you switch back.
            </p>
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
                <label for="pfm-monthly">Monthly investment</label>
                <div class="yk-siamt__control">
                    <span class="yk-siamt__affix" aria-hidden="true">₹</span>
                    <input
                        id="pfm-monthly"
                        type="number"
                        name="monthly_contribution"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        autocomplete="off"
                        placeholder="0"
                        value="{{ $v('monthly_contribution', '0') }}"
                        required
                        data-yk-si-monthly
                        aria-describedby="pfm-monthly-help si-amount-errors"
                    >
                </div>
                <p id="pfm-monthly-help" class="yk-siamt__help">Amount contributed at the end of each month under this model.</p>
                <div class="yk-siamt__presets" role="group" aria-label="Quick monthly amounts">
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
            <dl class="yk-siamt__preview-grid">
                <div>
                    <dt>Initial investment</dt>
                    <dd data-yk-si-preview-initial>{{ $fmtInr($initialVal) }}</dd>
                </div>
                <div>
                    <dt>Monthly contribution</dt>
                    <dd data-yk-si-preview-monthly>{{ $fmtInr($monthlyVal) }}</dd>
                </div>
                <div>
                    <dt>Contribution months (from years)</dt>
                    <dd data-yk-si-preview-months>{{ $previewMonths }}</dd>
                </div>
                <div class="yk-siamt__preview-total">
                    <dt>Planned total contributions</dt>
                    <dd data-yk-si-preview-total>{{ $fmtInr($previewTotal) }}</dd>
                </div>
            </dl>
            <p class="yk-siamt__preview-note">
                This preview shows planned contributions only. It excludes estimated investment returns. Maturity and gains are calculated on the results screen using the server engine.
            </p>
        </aside>
    </section>

    <section class="yk-siamt__section yk-siamt__section--meta" aria-labelledby="si-meta-heading">
        <header class="yk-siamt__head">
            <h2 id="si-meta-heading" class="yk-siamt__title">
                <x-yk-icon name="calendar-days" class="yk-icon--sm" />
                Duration and return
            </h2>
            <p class="yk-siamt__lead">Existing duration and expected return inputs are unchanged in this task.</p>
        </header>
        <div class="yk-pfm__grid yk-siamt__grid">
            <div class="yk-pfm__field">
                <label for="pfm-si-years">Years</label>
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
                    aria-describedby="pfm-si-years-help"
                >
                <p id="pfm-si-years-help" class="yk-siamt__help">Investment duration in years. Contribution months = round(years × 12).</p>
            </div>
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
        </div>
    </section>

    <p class="yk-pfm__note">Estimated gains are illustrative and not guaranteed.</p>
</div>
