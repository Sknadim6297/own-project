@php
    $v = fn ($key, $default = '') => old($key, $old[$key] ?? $default);
@endphp
<div class="yk-pfm__grid">
    <div class="yk-pfm__field">
        <label for="pfm-initial">Initial investment (₹)</label>
        <input id="pfm-initial" type="number" name="initial_amount" min="0" step="0.01" inputmode="decimal" value="{{ $v('initial_amount', '0') }}" required>
    </div>
    <div class="yk-pfm__field">
        <label for="pfm-monthly">Monthly investment (₹)</label>
        <input id="pfm-monthly" type="number" name="monthly_contribution" min="0" step="0.01" inputmode="decimal" value="{{ $v('monthly_contribution', '0') }}" required>
    </div>
    <div class="yk-pfm__field">
        <label for="pfm-si-years">Years</label>
        <input id="pfm-si-years" type="number" name="years" min="0.01" max="100" step="0.01" inputmode="decimal" value="{{ $v('years') }}" required>
    </div>
    <div class="yk-pfm__field">
        <label for="pfm-si-return">Expected annual return (%)</label>
        <input id="pfm-si-return" type="number" name="expected_return_percent" min="0" max="50" step="0.01" inputmode="decimal" value="{{ $v('expected_return_percent', '10') }}" required>
    </div>
</div>
<p class="yk-pfm__note">Estimated gains are illustrative and not guaranteed.</p>
