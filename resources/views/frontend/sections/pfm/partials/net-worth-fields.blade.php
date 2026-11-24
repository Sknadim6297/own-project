@php
    // Fresh Net Worth inputs stay blank (Task 27). Draft/old values, including explicit 0, still render.
    $v = fn ($key) => old($key, array_key_exists($key, $old ?? []) ? $old[$key] : '');
@endphp
<div class="yk-pfm__step" data-pfm-step="0">
    <p class="yk-pfm__note">Estimate each asset category. Approximate values are fine. Leave unused fields blank.</p>
    <fieldset class="yk-pfm__fieldset">
        <legend>Assets</legend>
        <div class="yk-pfm__grid">
            @foreach (['cash' => 'Cash', 'bank' => 'Bank', 'fd' => 'Fixed deposits', 'mf' => 'Mutual funds', 'stocks' => 'Stocks', 'gold' => 'Gold', 'property' => 'Property', 'epf_nps' => 'EPF / NPS'] as $name => $label)
                <div class="yk-pfm__field">
                    <label for="pfm-{{ $name }}">{{ $label }} (₹)</label>
                    <input
                        id="pfm-{{ $name }}"
                        type="number"
                        name="{{ $name }}"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        placeholder="Enter amount"
                        value="{{ $v($name) }}"
                    >
                </div>
            @endforeach
        </div>
    </fieldset>
</div>
<div class="yk-pfm__step" data-pfm-step="1" hidden>
    <p class="yk-pfm__note">Add outstanding loans and other liabilities, then calculate net worth. Leave unused fields blank.</p>
    <fieldset class="yk-pfm__fieldset">
        <legend>Liabilities</legend>
        <div class="yk-pfm__grid">
            @foreach (['loans' => 'Loans', 'credit_cards' => 'Credit cards', 'other_debt' => 'Other debt'] as $name => $label)
                <div class="yk-pfm__field">
                    <label for="pfm-{{ $name }}">{{ $label }} (₹)</label>
                    <input
                        id="pfm-{{ $name }}"
                        type="number"
                        name="{{ $name }}"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        placeholder="Enter amount"
                        value="{{ $v($name) }}"
                    >
                </div>
            @endforeach
        </div>
    </fieldset>
</div>
