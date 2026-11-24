@php
    $inputs = is_array($inputs ?? null) ? $inputs : [];
    $money = fn ($value) => '₹'.number_format((float) $value, 2);
    $has = static fn (string $key): bool => array_key_exists($key, $inputs) && $inputs[$key] !== null && $inputs[$key] !== '';
@endphp

@if ($toolKey === 'monthly-money-planner')
    <section class="yk-pfm__saved-inputs" aria-labelledby="pfm-saved-inputs-heading">
        <h2 id="pfm-saved-inputs-heading" class="yk-pfm__section-title">Saved financial inputs</h2>
        <p class="yk-pfm__note">These values are from this saved calculation and are shown read-only.</p>
        <dl class="yk-pfm__input-grid">
            <div>
                <dt>Monthly income</dt>
                <dd>{{ $has('monthly_income') ? $money($inputs['monthly_income']) : '—' }}</dd>
            </div>
            <div>
                <dt>Essential expenses</dt>
                <dd>{{ $has('essential_expenses') ? $money($inputs['essential_expenses']) : '—' }}</dd>
            </div>
            <div>
                <dt>Lifestyle expenses</dt>
                <dd>{{ $has('lifestyle_expenses') ? $money($inputs['lifestyle_expenses']) : '—' }}</dd>
            </div>
            <div>
                <dt>Monthly EMIs</dt>
                <dd>{{ $has('emis') ? $money($inputs['emis']) : '—' }}</dd>
            </div>
            <div>
                <dt>Other recurring expenses</dt>
                <dd>{{ $has('other_recurring') ? $money($inputs['other_recurring']) : '—' }}</dd>
            </div>
            <div>
                <dt>Monthly investments</dt>
                <dd>{{ $has('investments') ? $money($inputs['investments']) : '—' }}</dd>
            </div>
        </dl>
    </section>
@elseif ($toolKey === 'emergency-fund-calculator')
    <section class="yk-pfm__saved-inputs yk-ef__saved-inputs" aria-labelledby="pfm-saved-inputs-heading">
        <h2 id="pfm-saved-inputs-heading" class="yk-pfm__section-title">Saved financial inputs</h2>
        <p class="yk-pfm__note">These values are from this saved calculation and are shown read-only.</p>
        <dl class="yk-pfm__input-grid">
            <div>
                <dt>Essential monthly expenses</dt>
                <dd>{{ $has('monthly_expenses') ? $money($inputs['monthly_expenses']) : '—' }}</dd>
            </div>
            <div>
                <dt>Target coverage</dt>
                <dd>{{ $has('coverage_months') ? ((int) $inputs['coverage_months']).' months' : '—' }}</dd>
            </div>
            <div>
                <dt>Current emergency savings</dt>
                <dd>{{ $has('current_savings') ? $money($inputs['current_savings']) : '—' }}</dd>
            </div>
            <div>
                <dt>Monthly saving capacity</dt>
                <dd>{{ $has('monthly_saving_capacity') ? $money($inputs['monthly_saving_capacity']) : '—' }}</dd>
            </div>
        </dl>
    </section>
@endif
