@php
    $v = fn ($key, $default = '') => old($key, $old[$key] ?? $default);
@endphp
<div class="yk-pfm__grid">
    <div class="yk-pfm__field">
        <label for="pfm-amount">Current amount (₹)</label>
        <input id="pfm-amount" type="number" name="current_amount" min="0" step="0.01" inputmode="decimal" value="{{ $v('current_amount') }}" required>
    </div>
    <div class="yk-pfm__field">
        <label for="pfm-inf-rate">Inflation (% per year)</label>
        <input id="pfm-inf-rate" type="number" name="inflation_percent" min="0" max="50" step="0.01" inputmode="decimal" value="{{ $v('inflation_percent', '6') }}" required>
    </div>
    <div class="yk-pfm__field">
        <label for="pfm-inf-years">Years</label>
        <input id="pfm-inf-years" type="number" name="years" min="0" max="100" step="0.01" inputmode="decimal" value="{{ $v('years') }}" required>
    </div>
</div>
