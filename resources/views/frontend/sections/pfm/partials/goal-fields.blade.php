@php
    $v = fn ($key, $default = '') => old($key, $old[$key] ?? $default);
    $goalType = (string) $v('goal_type', 'custom');
@endphp
<div class="yk-pfm__step" data-pfm-step="0">
    <p class="yk-pfm__note">Choose a goal type, then continue to cost and timeline.</p>
    <div class="yk-pfm__goal-types" role="radiogroup" aria-label="Goal type">
        @foreach ($goalTypes as $key => $label)
            <label class="yk-pfm__chip">
                <input type="radio" name="goal_type" value="{{ $key }}" @checked($goalType === $key) required>
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </div>
    <div class="yk-pfm__grid">
        <div class="yk-pfm__field yk-pfm__field--full">
            <label for="pfm-goal-name">Goal name <span class="yk-pfm__optional">optional</span></label>
            <input id="pfm-goal-name" type="text" name="goal_name" maxlength="80" value="{{ $v('goal_name') }}" placeholder="e.g. Dream home">
        </div>
    </div>
</div>
<div class="yk-pfm__step" data-pfm-step="1" hidden>
    <p class="yk-pfm__note">Enter today’s estimated cost and how many years you have to prepare.</p>
    <div class="yk-pfm__grid">
        <div class="yk-pfm__field">
            <label for="pfm-current-cost">Current goal cost (₹)</label>
            <input id="pfm-current-cost" type="number" name="current_cost" min="0.01" step="0.01" inputmode="decimal" value="{{ $v('current_cost') }}" required>
        </div>
        <div class="yk-pfm__field">
            <label for="pfm-years">Years</label>
            <input id="pfm-years" type="number" name="years" min="0.01" max="100" step="0.01" inputmode="decimal" value="{{ $v('years') }}" required>
        </div>
        <div class="yk-pfm__field">
            <label for="pfm-existing">Current earmarked savings (₹)</label>
            <input id="pfm-existing" type="number" name="existing_savings" min="0" step="0.01" inputmode="decimal" value="{{ $v('existing_savings', '0') }}" required>
        </div>
    </div>
</div>
<div class="yk-pfm__step" data-pfm-step="2" hidden>
    <p class="yk-pfm__note">Set illustrative inflation and return assumptions, then calculate.</p>
    <div class="yk-pfm__grid">
        <div class="yk-pfm__field">
            <label for="pfm-inflation">Inflation (% per year)</label>
            <input id="pfm-inflation" type="number" name="inflation_percent" min="0" max="50" step="0.01" inputmode="decimal" value="{{ $v('inflation_percent', '6') }}" required>
        </div>
        <div class="yk-pfm__field">
            <label for="pfm-return">Expected return (% per year)</label>
            <input id="pfm-return" type="number" name="expected_return_percent" min="0" max="50" step="0.01" inputmode="decimal" value="{{ $v('expected_return_percent', '10') }}" required>
        </div>
    </div>
    <p class="yk-pfm__note">Returns are illustrative estimates, not guarantees.</p>
</div>
