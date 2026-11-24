@php
    $v = fn ($key, $default = '') => old($key, $old[$key] ?? $default);
    $goalType = (string) $v('goal_type', 'custom');
    $icons = [
        'home' => '<path d="M4 11.2 12 4.4l8 6.8"/><path d="M6.2 9.8V19h11.6V9.8"/><path d="M10 19v-5h4v5"/>',
        'education' => '<path d="M2.8 9.4 12 5l9.2 4.4L12 13.8Z"/><path d="M6.4 11.6v4.2c0 1.2 2.6 2.6 5.6 2.6s5.6-1.4 5.6-2.6v-4.2"/><path d="M21.2 9.4v5.2"/>',
        'car' => '<path d="M4.4 15.6V12l1.7-4.4A1.8 1.8 0 0 1 7.8 6.4h8.4a1.8 1.8 0 0 1 1.7 1.2L19.6 12v3.6a.9.9 0 0 1-.9.9H5.3a.9.9 0 0 1-.9-.9Z"/><path d="M4.4 12h15.2"/><circle cx="8" cy="15" r=".6" fill="currentColor"/><circle cx="16" cy="15" r=".6" fill="currentColor"/><path d="M6.4 16.5v2M17.6 16.5v2"/>',
        'marriage' => '<circle cx="9" cy="14.2" r="4.6"/><circle cx="15.4" cy="14.2" r="4.6"/><path d="M7.6 6.2 9 8.4l1.4-2.2-1.4-1.8Z"/>',
        'vacation' => '<path d="M21 4.2 3.6 11.4l5.4 2 2 5.4Z"/><path d="m9 13.4 12-9.2"/>',
        'child' => '<circle cx="12" cy="12.4" r="7.6"/><path d="M9 14.2c.9 1.1 1.8 1.6 3 1.6s2.1-.5 3-1.6"/><path d="M9.4 11h.01M14.6 11h.01" stroke-width="2.6"/><path d="M12 4.8c.2-1.3 1.2-2 2.4-1.8"/>',
        'custom' => '<path d="M6 21V4.2"/><path d="M6 5h11.4l-2.2 3.6 2.2 3.6H6"/>',
    ];
    $yearsRaw = $v('years');
    $yearsNumeric = is_numeric($yearsRaw) ? (float) $yearsRaw : null;
    $yearsSlider = ($yearsNumeric !== null && $yearsNumeric > 0)
        ? max(0.01, min(100, $yearsNumeric))
        : 10;
    $yearsDisplay = ($yearsNumeric !== null && $yearsNumeric > 0)
        ? rtrim(rtrim(number_format($yearsNumeric, 2, '.', ''), '0'), '.')
        : '—';
@endphp

{{-- Task 35: all seven inputs on one continuous form (no wizard steps) --}}
<div class="yk-goal__panel">
    <header class="yk-goal__step-head">
        <h3>Plan your financial goal</h3>
        <p>Enter your goal details, timeline, and assumptions below. Everything is on this page.</p>
    </header>

    <section class="yk-goal__section" aria-labelledby="goal-section-details">
        <h4 id="goal-section-details" class="yk-goal__section-title">
            <x-yk-icon name="target" class="yk-icon--sm" />
            Goal details
        </h4>

        <fieldset class="yk-goal__types">
            <legend class="visually-hidden">Goal type</legend>
            @foreach ($goalTypes as $key => $label)
                <label class="yk-goal__type" data-goal-label="{{ $label }}">
                    <input type="radio" name="goal_type" value="{{ $key }}" @checked($goalType === $key) required>
                    <span class="yk-goal__type-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">{!! $icons[$key] ?? $icons['custom'] !!}</svg>
                    </span>
                    <span class="yk-goal__type-name">{{ $label }}</span>
                    <span class="yk-goal__type-tick" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M6.4 12.4l3.6 3.6 7.6-7.8"/></svg></span>
                </label>
            @endforeach
        </fieldset>

        <div class="yk-goal__field">
            <label for="pfm-goal-name">Goal name <span class="yk-goal__optional">optional</span></label>
            <div class="yk-goal__control">
                <input id="pfm-goal-name" type="text" name="goal_name" maxlength="80" value="{{ $v('goal_name') }}" placeholder="e.g. Dream home" autocomplete="off">
            </div>
            @error('goal_name')
                <p class="yk-goal__error" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </section>

    <section class="yk-goal__section" aria-labelledby="goal-section-cost">
        <h4 id="goal-section-cost" class="yk-goal__section-title">
            <x-yk-icon name="wallet" class="yk-icon--sm" />
            Goal cost
        </h4>
        <div class="yk-goal__field yk-goal__field--flush">
            <label for="pfm-current-cost">Current goal cost</label>
            <div class="yk-goal__control yk-goal__control--money @error('current_cost') is-invalid @enderror">
                <span class="yk-goal__affix" aria-hidden="true">₹</span>
                <input id="pfm-current-cost" type="number" name="current_cost" min="0.01" step="0.01" inputmode="decimal" value="{{ $v('current_cost') }}" placeholder="0" required aria-describedby="pfm-current-cost-help{{ $errors->has('current_cost') ? ' pfm-current-cost-error' : '' }}">
            </div>
            <p id="pfm-current-cost-help" class="yk-goal__help" data-goal-out="cost-words">Enter the price as it is today.</p>
            @error('current_cost')
                <p id="pfm-current-cost-error" class="yk-goal__error" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </section>

    <section class="yk-goal__section yk-goal__years" data-yk-goal-years data-years-min="0.01" data-years-max="100" data-years-step="0.01" data-years-bump="1" aria-labelledby="goal-section-duration">
        <h4 id="goal-section-duration" class="yk-goal__section-title">
            <x-yk-icon name="calendar-days" class="yk-icon--sm" />
            Investment duration
        </h4>

        <div class="yk-goal__field yk-goal__field--flush">
            <label for="pfm-years" class="visually-hidden">Years to prepare</label>
            <div class="yk-goal__years-head">
                <p class="yk-goal__years-readout" aria-live="polite">
                    <span class="yk-goal__years-figure" data-goal-years-display>{{ $yearsDisplay }}</span>
                    <span class="yk-goal__years-unit">years</span>
                </p>
                <div class="yk-goal__control yk-goal__control--years @error('years') is-invalid @enderror">
                    <input id="pfm-years" type="number" name="years" min="0.01" max="100" step="0.01" inputmode="decimal" value="{{ $yearsRaw }}" placeholder="0" required data-goal-years-input aria-describedby="pfm-years-help{{ $errors->has('years') ? ' pfm-years-error' : '' }}">
                    <span class="yk-goal__affix yk-goal__affix--end" aria-hidden="true">years</span>
                </div>
            </div>

            <div class="yk-goal__years-slider">
                <button type="button" class="yk-goal__years-step" data-goal-years-dec aria-label="Decrease investment years by 1">
                    <x-yk-icon name="minus" class="yk-icon--sm" />
                </button>
                <div class="yk-goal__years-track">
                    <input
                        id="pfm-years-slider"
                        class="yk-goal__years-range"
                        type="range"
                        min="0.01"
                        max="100"
                        step="0.01"
                        value="{{ $yearsSlider }}"
                        data-goal-years-slider
                        aria-valuemin="0.01"
                        aria-valuemax="100"
                        aria-valuenow="{{ $yearsSlider }}"
                        aria-label="Investment duration in years"
                    >
                    <div class="yk-goal__years-scale" aria-hidden="true">
                        <span>Min</span>
                        <span>25</span>
                        <span>50</span>
                        <span>75</span>
                        <span>Max</span>
                    </div>
                    <div class="yk-goal__years-bounds">
                        <span>0.01 yr</span>
                        <span>100 yr</span>
                    </div>
                </div>
                <button type="button" class="yk-goal__years-step" data-goal-years-inc aria-label="Increase investment years by 1">
                    <x-yk-icon name="plus" class="yk-icon--sm" />
                </button>
            </div>

            <div class="yk-goal__quick" role="group" aria-label="Quick year choices">
                @foreach ([3, 5, 10, 15, 20] as $quick)
                    <button type="button" data-goal-years="{{ $quick }}">{{ $quick }}y</button>
                @endforeach
            </div>
            <p id="pfm-years-help" class="yk-goal__help">Drag the slider or type a duration between 0.01 and 100 years.</p>
            @error('years')
                <p id="pfm-years-error" class="yk-goal__error" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </section>

    <section class="yk-goal__section" aria-labelledby="goal-section-savings">
        <h4 id="goal-section-savings" class="yk-goal__section-title">
            <x-yk-icon name="wallet" class="yk-icon--sm" />
            Existing savings
        </h4>
        <div class="yk-goal__field yk-goal__field--flush">
            <label for="pfm-existing">Savings already set aside</label>
            <div class="yk-goal__control yk-goal__control--money @error('existing_savings') is-invalid @enderror">
                <span class="yk-goal__affix" aria-hidden="true">₹</span>
                <input id="pfm-existing" type="number" name="existing_savings" min="0" step="0.01" inputmode="decimal" value="{{ $v('existing_savings', '0') }}" required aria-describedby="pfm-existing-help{{ $errors->has('existing_savings') ? ' pfm-existing-error' : '' }}">
            </div>
            <p id="pfm-existing-help" class="yk-goal__help">Money already earmarked for this goal. Use 0 if none.</p>
            @error('existing_savings')
                <p id="pfm-existing-error" class="yk-goal__error" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </section>

    <section class="yk-goal__section" aria-labelledby="goal-section-assumptions">
        <h4 id="goal-section-assumptions" class="yk-goal__section-title">
            <x-yk-icon name="activity" class="yk-icon--sm" />
            Investment assumptions
        </h4>

        <div class="yk-goal__two yk-goal__two--flush">
            <div class="yk-goal__field yk-goal__field--flush">
                <label for="pfm-inflation"><x-yk-icon name="chart-no-axes-combined" class="yk-icon--sm yk-goal__label-icon" /> Inflation per year</label>
                <div class="yk-goal__control yk-goal__control--percent @error('inflation_percent') is-invalid @enderror">
                    <input id="pfm-inflation" type="number" name="inflation_percent" min="0" max="50" step="0.01" inputmode="decimal" value="{{ $v('inflation_percent', '6') }}" required aria-describedby="pfm-inflation-help{{ $errors->has('inflation_percent') ? ' pfm-inflation-error' : '' }}">
                    <span class="yk-goal__affix yk-goal__affix--end" aria-hidden="true">%</span>
                </div>
                <p id="pfm-inflation-help" class="yk-goal__help">How fast you expect this goal's cost to rise.</p>
                @error('inflation_percent')
                    <p id="pfm-inflation-error" class="yk-goal__error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="yk-goal__field yk-goal__field--flush">
                <label for="pfm-return"><x-yk-icon name="activity" class="yk-icon--sm yk-goal__label-icon" /> Expected return per year</label>
                <div class="yk-goal__control yk-goal__control--percent @error('expected_return_percent') is-invalid @enderror">
                    <input id="pfm-return" type="number" name="expected_return_percent" min="0" max="50" step="0.01" inputmode="decimal" value="{{ $v('expected_return_percent', '10') }}" required aria-describedby="pfm-return-help{{ $errors->has('expected_return_percent') ? ' pfm-return-error' : '' }}">
                    <span class="yk-goal__affix yk-goal__affix--end" aria-hidden="true">%</span>
                </div>
                <p id="pfm-return-help" class="yk-goal__help">Growth you assume on money set aside.</p>
                @error('expected_return_percent')
                    <p id="pfm-return-error" class="yk-goal__error" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <p class="yk-goal__callout">
        <x-yk-icon name="info" class="yk-icon--sm" />
        <span>Returns are illustrative estimates, not guarantees. Actual results can be higher or lower.</span>
    </p>
</div>
