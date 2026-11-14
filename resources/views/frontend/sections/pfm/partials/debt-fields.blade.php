@php
    $v = fn ($key, $default = '') => old($key, $old[$key] ?? $default);
@endphp
<div class="yk-pfm__step" data-pfm-step="0">
    <p class="yk-pfm__note">Enter the loan principal, annual interest rate, and tenure.</p>
    <div class="yk-pfm__grid">
        <div class="yk-pfm__field">
            <label for="pfm-principal">Loan principal (₹)</label>
            <input id="pfm-principal" type="number" name="principal" min="0.01" step="0.01" inputmode="decimal" value="{{ $v('principal') }}" required>
        </div>
        <div class="yk-pfm__field">
            <label for="pfm-rate">Annual interest rate (%)</label>
            <input id="pfm-rate" type="number" name="annual_rate_percent" min="0" max="50" step="0.01" inputmode="decimal" value="{{ $v('annual_rate_percent') }}" required>
        </div>
        <div class="yk-pfm__field">
            <label for="pfm-tenure">Tenure (years)</label>
            <input id="pfm-tenure" type="number" name="tenure_years" min="0.01" max="100" step="0.01" inputmode="decimal" value="{{ $v('tenure_years') }}" required>
        </div>
    </div>
</div>
<div class="yk-pfm__step" data-pfm-step="1" hidden>
    <p class="yk-pfm__note">Optionally add an extra monthly payment to compare accelerated payoff, then calculate.</p>
    <div class="yk-pfm__grid">
        <div class="yk-pfm__field yk-pfm__field--full">
            <label for="pfm-extra">Extra monthly payment (₹) <span class="yk-pfm__optional">optional</span></label>
            <input id="pfm-extra" type="number" name="extra_payment" min="0" step="0.01" inputmode="decimal" value="{{ $v('extra_payment', '0') }}">
        </div>
    </div>
</div>
