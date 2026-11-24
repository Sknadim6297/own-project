@php
    $v = fn ($key, $default = '') => old($key, $old[$key] ?? $default);
    $monthsRaw = (string) $v('coverage_months', '6');
    $monthsInt = (int) $monthsRaw;
    if ($monthsInt < 1 || $monthsInt > 36) {
        $monthsInt = 6;
    }
    $standardMonths = [3, 6, 9, 12];
    $isCustomCoverage = ! in_array($monthsInt, $standardMonths, true);
@endphp

<div class="yk-ef" data-yk-ef-fields>
    <div class="yk-pfm__step" data-pfm-step="0">
        <p class="yk-pfm__note">Enter essential monthly expenses and choose how many months of cover you want.</p>

        <div class="yk-pfm__grid yk-ef__grid">
            <div class="yk-pfm__field yk-ef__field">
                <label for="pfm-monthly-expenses">
                    <span class="yk-ef__label-row">
                        <x-yk-icon name="wallet" class="yk-icon--sm" />
                        Essential monthly expenses
                    </span>
                </label>
                <div class="yk-ef__input">
                    <span class="yk-ef__prefix" aria-hidden="true">₹</span>
                    <input
                        id="pfm-monthly-expenses"
                        type="number"
                        name="monthly_expenses"
                        min="0.01"
                        step="0.01"
                        inputmode="decimal"
                        autocomplete="off"
                        placeholder="e.g. 25000"
                        value="{{ $v('monthly_expenses') }}"
                        required
                        aria-describedby="pfm-monthly-expenses-help"
                    >
                </div>
                <p id="pfm-monthly-expenses-help" class="yk-ef__help">Rent, food, utilities, and other must-pay costs.</p>
            </div>
        </div>

        <fieldset class="yk-ef__durations" aria-describedby="pfm-coverage-help">
            <legend class="yk-ef__legend">
                <span class="yk-ef__label-row">
                    <x-yk-icon name="calendar-days" class="yk-icon--sm" />
                    Target coverage
                </span>
            </legend>
            <p id="pfm-coverage-help" class="yk-ef__help">Common targets are 3–12 months. Backend still accepts 1–36 months for saved values.</p>
            <div class="yk-ef__duration-grid" role="radiogroup" aria-label="Target coverage months">
                @foreach ($standardMonths as $option)
                    <label class="yk-ef__duration">
                        <input
                            type="radio"
                            name="coverage_months"
                            value="{{ $option }}"
                            @checked($monthsInt === $option)
                            required
                        >
                        <span class="yk-ef__duration-card">
                            <strong>{{ $option }}</strong>
                            <span>months</span>
                        </span>
                    </label>
                @endforeach
                @if ($isCustomCoverage)
                    <label class="yk-ef__duration">
                        <input
                            type="radio"
                            name="coverage_months"
                            value="{{ $monthsInt }}"
                            checked
                            required
                        >
                        <span class="yk-ef__duration-card">
                            <strong>{{ $monthsInt }}</strong>
                            <span>months (saved)</span>
                        </span>
                    </label>
                @endif
            </div>
        </fieldset>
    </div>

    <div class="yk-pfm__step" data-pfm-step="1" hidden>
        <p class="yk-pfm__note">Add liquid emergency savings and optional monthly capacity to estimate time to target.</p>
        <div class="yk-pfm__grid yk-ef__grid">
            <div class="yk-pfm__field yk-ef__field">
                <label for="pfm-current-savings">
                    <span class="yk-ef__label-row">
                        <x-yk-icon name="shield-check" class="yk-icon--sm" />
                        Current emergency savings
                    </span>
                </label>
                <div class="yk-ef__input">
                    <span class="yk-ef__prefix" aria-hidden="true">₹</span>
                    <input
                        id="pfm-current-savings"
                        type="number"
                        name="current_savings"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        autocomplete="off"
                        placeholder="e.g. 50000"
                        value="{{ $v('current_savings', '0') }}"
                        required
                        aria-describedby="pfm-current-savings-help"
                    >
                </div>
                <p id="pfm-current-savings-help" class="yk-ef__help">Cash or liquid savings set aside for emergencies.</p>
            </div>
            <div class="yk-pfm__field yk-ef__field">
                <label for="pfm-capacity">
                    <span class="yk-ef__label-row">
                        <x-yk-icon name="target" class="yk-icon--sm" />
                        Monthly saving capacity
                        <span class="yk-pfm__optional">optional</span>
                    </span>
                </label>
                <div class="yk-ef__input">
                    <span class="yk-ef__prefix" aria-hidden="true">₹</span>
                    <input
                        id="pfm-capacity"
                        type="number"
                        name="monthly_saving_capacity"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        autocomplete="off"
                        placeholder="e.g. 10000"
                        value="{{ $v('monthly_saving_capacity', '0') }}"
                        aria-describedby="pfm-capacity-help"
                    >
                </div>
                <p id="pfm-capacity-help" class="yk-ef__help">Used only to estimate months to reach the target.</p>
            </div>
        </div>
    </div>
</div>
