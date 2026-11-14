@php
    $v = fn ($key, $default = '') => old($key, $old[$key] ?? $default);
    $months = (string) $v('coverage_months', '6');
@endphp
<div class="yk-pfm__step" data-pfm-step="0">
    <p class="yk-pfm__note">Enter essential monthly expenses and how many months of cover you want.</p>
    <div class="yk-pfm__grid">
        <div class="yk-pfm__field">
            <label for="pfm-monthly-expenses">Essential monthly expenses (₹)</label>
            <input id="pfm-monthly-expenses" type="number" name="monthly_expenses" min="0.01" step="0.01" inputmode="decimal" value="{{ $v('monthly_expenses') }}" required>
        </div>
        <div class="yk-pfm__field">
            <label for="pfm-coverage">Target months</label>
            <select id="pfm-coverage" name="coverage_months" required>
                @foreach ([3, 6, 9, 12] as $option)
                    <option value="{{ $option }}" @selected($months === (string) $option)>{{ $option }} months</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
<div class="yk-pfm__step" data-pfm-step="1" hidden>
    <p class="yk-pfm__note">Add liquid emergency savings and optional monthly capacity to estimate months to target.</p>
    <div class="yk-pfm__grid">
        <div class="yk-pfm__field">
            <label for="pfm-current-savings">Current emergency savings (₹)</label>
            <input id="pfm-current-savings" type="number" name="current_savings" min="0" step="0.01" inputmode="decimal" value="{{ $v('current_savings', '0') }}" required>
        </div>
        <div class="yk-pfm__field">
            <label for="pfm-capacity">Monthly saving capacity (₹) <span class="yk-pfm__optional">optional</span></label>
            <input id="pfm-capacity" type="number" name="monthly_saving_capacity" min="0" step="0.01" inputmode="decimal" value="{{ $v('monthly_saving_capacity', '0') }}">
        </div>
    </div>
</div>
