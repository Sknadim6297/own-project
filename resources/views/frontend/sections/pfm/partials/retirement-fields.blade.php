@php
    $v = fn ($key, $default = '') => old($key, $old[$key] ?? $default);
@endphp
<div class="yk-pfm__step" data-pfm-step="0">
    <p class="yk-pfm__note">Capture your age profile, monthly expenses, and current retirement assets.</p>
    <div class="yk-pfm__grid">
        <div class="yk-pfm__field">
            <label for="pfm-current-age">Current age</label>
            <input id="pfm-current-age" type="number" name="current_age" min="18" max="80" step="1" inputmode="numeric" value="{{ $v('current_age') }}" required>
        </div>
        <div class="yk-pfm__field">
            <label for="pfm-retire-age">Retirement age</label>
            <input id="pfm-retire-age" type="number" name="retirement_age" min="19" max="90" step="1" inputmode="numeric" value="{{ $v('retirement_age') }}" required>
        </div>
        <div class="yk-pfm__field">
            <label for="pfm-life">Life expectancy</label>
            <input id="pfm-life" type="number" name="life_expectancy" min="40" max="110" step="1" inputmode="numeric" value="{{ $v('life_expectancy', '85') }}" required>
        </div>
        <div class="yk-pfm__field">
            <label for="pfm-expenses">Current monthly expenses (₹)</label>
            <input id="pfm-expenses" type="number" name="current_monthly_expenses" min="0.01" step="0.01" inputmode="decimal" value="{{ $v('current_monthly_expenses') }}" required>
        </div>
        <div class="yk-pfm__field">
            <label for="pfm-assets">Current retirement assets (₹)</label>
            <input id="pfm-assets" type="number" name="current_assets" min="0" step="0.01" inputmode="decimal" value="{{ $v('current_assets', '0') }}" required>
        </div>
    </div>
</div>
<div class="yk-pfm__step" data-pfm-step="1" hidden>
    <p class="yk-pfm__note">Set inflation and return assumptions, then calculate corpus and readiness.</p>
    <div class="yk-pfm__grid">
        <div class="yk-pfm__field">
            <label for="pfm-inf">Inflation (% per year)</label>
            <input id="pfm-inf" type="number" name="inflation_percent" min="0" max="50" step="0.01" inputmode="decimal" value="{{ $v('inflation_percent', '6') }}" required>
        </div>
        <div class="yk-pfm__field">
            <label for="pfm-pre">Pre-retirement return (% per year)</label>
            <input id="pfm-pre" type="number" name="pre_retirement_return_percent" min="0" max="50" step="0.01" inputmode="decimal" value="{{ $v('pre_retirement_return_percent', '10') }}" required>
        </div>
        <div class="yk-pfm__field">
            <label for="pfm-post">Post-retirement return (% per year)</label>
            <input id="pfm-post" type="number" name="post_retirement_return_percent" min="0" max="50" step="0.01" inputmode="decimal" value="{{ $v('post_retirement_return_percent', '6') }}" required>
        </div>
    </div>
    <p class="yk-pfm__note">Assumptions are illustrative. Scenario testing is recommended.</p>
</div>
