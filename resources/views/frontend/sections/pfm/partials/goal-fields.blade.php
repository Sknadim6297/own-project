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
@endphp

<div class="yk-goal__step" data-pfm-step="0">
    <header class="yk-goal__step-head">
        <h3>What are you saving for?</h3>
        <p>Pick the closest match. You can name it anything you like.</p>
    </header>

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
    </div>
</div>

<div class="yk-goal__step" data-pfm-step="1" hidden>
    <header class="yk-goal__step-head">
        <h3>What will it cost, and by when?</h3>
        <p>Use today's estimated price. We'll account for rising costs.</p>
    </header>

    <div class="yk-goal__field">
        <label for="pfm-current-cost">Current goal cost</label>
        <div class="yk-goal__control">
            <span class="yk-goal__affix" aria-hidden="true">₹</span>
            <input id="pfm-current-cost" type="number" name="current_cost" min="0.01" step="0.01" inputmode="decimal" value="{{ $v('current_cost') }}" placeholder="0" required>
        </div>
        <p class="yk-goal__help" data-goal-out="cost-words">Enter the price as it is today.</p>
    </div>

    <div class="yk-goal__two">
        <div class="yk-goal__field">
            <label for="pfm-years">Years to prepare</label>
            <div class="yk-goal__control">
                <input id="pfm-years" type="number" name="years" min="0.01" max="100" step="0.01" inputmode="decimal" value="{{ $v('years') }}" placeholder="0" required>
                <span class="yk-goal__affix yk-goal__affix--end" aria-hidden="true">years</span>
            </div>
            <div class="yk-goal__quick" role="group" aria-label="Quick year choices">
                @foreach ([3, 5, 10, 15, 20] as $quick)
                    <button type="button" data-goal-years="{{ $quick }}">{{ $quick }}y</button>
                @endforeach
            </div>
        </div>

        <div class="yk-goal__field">
            <label for="pfm-existing">Savings already set aside</label>
            <div class="yk-goal__control">
                <span class="yk-goal__affix" aria-hidden="true">₹</span>
                <input id="pfm-existing" type="number" name="existing_savings" min="0" step="0.01" inputmode="decimal" value="{{ $v('existing_savings', '0') }}" required>
            </div>
            <p class="yk-goal__help">Money already earmarked for this goal. Use 0 if none.</p>
        </div>
    </div>
</div>

<div class="yk-goal__step" data-pfm-step="2" hidden>
    <header class="yk-goal__step-head">
        <h3>Set your assumptions</h3>
        <p>These are illustrative. Change them to match your own view.</p>
    </header>

    <div class="yk-goal__two">
        <div class="yk-goal__field">
            <label for="pfm-inflation">Inflation per year</label>
            <div class="yk-goal__control">
                <input id="pfm-inflation" type="number" name="inflation_percent" min="0" max="50" step="0.01" inputmode="decimal" value="{{ $v('inflation_percent', '6') }}" required>
                <span class="yk-goal__affix yk-goal__affix--end" aria-hidden="true">%</span>
            </div>
            <p class="yk-goal__help">How fast you expect this goal's cost to rise.</p>
        </div>

        <div class="yk-goal__field">
            <label for="pfm-return">Expected return per year</label>
            <div class="yk-goal__control">
                <input id="pfm-return" type="number" name="expected_return_percent" min="0" max="50" step="0.01" inputmode="decimal" value="{{ $v('expected_return_percent', '10') }}" required>
                <span class="yk-goal__affix yk-goal__affix--end" aria-hidden="true">%</span>
            </div>
            <p class="yk-goal__help">Growth you assume on money set aside.</p>
        </div>
    </div>

    <p class="yk-goal__callout">
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8.8"/><path d="M12 11v5M12 7.8v.01" stroke-width="2.4"/></svg>
        <span>Returns are illustrative estimates, not guarantees. Actual results can be higher or lower.</span>
    </p>

    <dl class="yk-goal__review" aria-label="Your inputs">
        <div><dt>Goal</dt><dd data-goal-out="name">—</dd></div>
        <div><dt>Cost today</dt><dd data-goal-out="cost">—</dd></div>
        <div><dt>Timeline</dt><dd data-goal-out="years">—</dd></div>
    </dl>
</div>
