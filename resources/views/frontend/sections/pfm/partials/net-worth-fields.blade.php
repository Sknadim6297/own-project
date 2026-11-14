@php
    $v = fn ($key, $default = '0') => old($key, $old[$key] ?? $default);
@endphp
<div class="yk-pfm__step" data-pfm-step="0">
    <p class="yk-pfm__note">Estimate each asset category. Approximate values are fine.</p>
    <fieldset class="yk-pfm__fieldset">
        <legend>Assets</legend>
        <div class="yk-pfm__grid">
            @foreach (['cash' => 'Cash', 'bank' => 'Bank', 'fd' => 'Fixed deposits', 'mf' => 'Mutual funds', 'stocks' => 'Stocks', 'gold' => 'Gold', 'property' => 'Property', 'epf_nps' => 'EPF / NPS'] as $name => $label)
                <div class="yk-pfm__field">
                    <label for="pfm-{{ $name }}">{{ $label }} (₹)</label>
                    <input id="pfm-{{ $name }}" type="number" name="{{ $name }}" min="0" step="0.01" inputmode="decimal" value="{{ $v($name) }}">
                </div>
            @endforeach
        </div>
    </fieldset>
</div>
<div class="yk-pfm__step" data-pfm-step="1" hidden>
    <p class="yk-pfm__note">Add outstanding loans and other liabilities, then calculate net worth.</p>
    <fieldset class="yk-pfm__fieldset">
        <legend>Liabilities</legend>
        <div class="yk-pfm__grid">
            @foreach (['loans' => 'Loans', 'credit_cards' => 'Credit cards', 'other_debt' => 'Other debt'] as $name => $label)
                <div class="yk-pfm__field">
                    <label for="pfm-{{ $name }}">{{ $label }} (₹)</label>
                    <input id="pfm-{{ $name }}" type="number" name="{{ $name }}" min="0" step="0.01" inputmode="decimal" value="{{ $v($name) }}">
                </div>
            @endforeach
        </div>
    </fieldset>
</div>
