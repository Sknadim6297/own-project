@php
    $v = fn ($key, $default = '') => old($key, $old[$key] ?? $default);
@endphp
<div class="yk-pfm__step" data-pfm-step="0">
    <p class="yk-pfm__note">Start with your monthly take-home income. You can review expenses and investments next.</p>
    <div class="yk-pfm__grid">
        <div class="yk-pfm__field yk-pfm__field--full">
            <label for="pfm-monthly-income">Monthly income (₹)</label>
            <input id="pfm-monthly-income" type="number" name="monthly_income" min="0.01" step="0.01" inputmode="decimal" value="{{ $v('monthly_income') }}" required>
            <p class="yk-pfm__note" style="margin-top:0.35rem;margin-bottom:0;">Use take-home income after tax and deductions.</p>
        </div>
    </div>
</div>
<div class="yk-pfm__step" data-pfm-step="1" hidden>
    <p class="yk-pfm__note">Add essential costs, lifestyle spending, and optional EMIs or other recurring amounts.</p>
    <div class="yk-pfm__grid">
        <div class="yk-pfm__field">
            <label for="pfm-essential">Essential expenses (₹)</label>
            <input id="pfm-essential" type="number" name="essential_expenses" min="0" step="0.01" inputmode="decimal" value="{{ $v('essential_expenses', '0') }}" required>
        </div>
        <div class="yk-pfm__field">
            <label for="pfm-lifestyle">Lifestyle / other expenses (₹)</label>
            <input id="pfm-lifestyle" type="number" name="lifestyle_expenses" min="0" step="0.01" inputmode="decimal" value="{{ $v('lifestyle_expenses', '0') }}" required>
        </div>
        <div class="yk-pfm__field">
            <label for="pfm-emis">EMIs (₹) <span class="yk-pfm__optional">optional</span></label>
            <input id="pfm-emis" type="number" name="emis" min="0" step="0.01" inputmode="decimal" value="{{ $v('emis', '0') }}">
        </div>
        <div class="yk-pfm__field">
            <label for="pfm-other">Other recurring (₹) <span class="yk-pfm__optional">optional</span></label>
            <input id="pfm-other" type="number" name="other_recurring" min="0" step="0.01" inputmode="decimal" value="{{ $v('other_recurring', '0') }}">
        </div>
    </div>
</div>
<div class="yk-pfm__step" data-pfm-step="2" hidden>
    <p class="yk-pfm__note">Enter monthly investments or savings contributions, then calculate your money picture.</p>
    <div class="yk-pfm__grid">
        <div class="yk-pfm__field yk-pfm__field--full">
            <label for="pfm-investments">Monthly investments (₹)</label>
            <input id="pfm-investments" type="number" name="investments" min="0" step="0.01" inputmode="decimal" value="{{ $v('investments', '0') }}" required>
        </div>
    </div>
</div>
