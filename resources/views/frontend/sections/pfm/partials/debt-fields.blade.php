@php
    $v = fn ($key, $default = '') => old($key, $old[$key] ?? $default);
@endphp

<div class="yk-debt" data-yk-debt-fields>
    {{-- Step 1: Loan details (wizard preserved — Task 41 will address single-page) --}}
    <div class="yk-pfm__step" data-pfm-step="0">
        <section class="yk-debt__section" aria-labelledby="yk-debt-loan-heading">
            <header class="yk-debt__section-head">
                <span class="yk-debt__section-icon" aria-hidden="true">
                    <x-yk-icon name="calculator" class="yk-icon--sm" />
                </span>
                <div>
                    <h2 id="yk-debt-loan-heading" class="yk-debt__section-title">Loan details</h2>
                    <p class="yk-debt__section-lead">Enter the amount borrowed, annual interest rate, and repayment tenure.</p>
                </div>
            </header>

            <div class="yk-pfm__grid yk-debt__grid">
                <div class="yk-pfm__field yk-debt__field">
                    <label for="pfm-principal">
                        <span class="yk-debt__label-row">
                            <x-yk-icon name="wallet" class="yk-icon--sm" />
                            Loan principal
                            <span class="yk-debt__req" aria-hidden="true">*</span>
                        </span>
                    </label>
                    <div class="yk-debt__control yk-debt__control--money">
                        <span class="yk-debt__affix" aria-hidden="true">₹</span>
                        <input
                            id="pfm-principal"
                            type="number"
                            name="principal"
                            min="0.01"
                            step="0.01"
                            inputmode="decimal"
                            autocomplete="off"
                            placeholder="e.g. 2000000"
                            value="{{ $v('principal') }}"
                            required
                            aria-describedby="pfm-principal-help"
                        >
                    </div>
                    <p id="pfm-principal-help" class="yk-debt__help">Total amount you are borrowing.</p>
                </div>

                <div class="yk-pfm__field yk-debt__field">
                    <label for="pfm-rate">
                        <span class="yk-debt__label-row">
                            <x-yk-icon name="chart-pie" class="yk-icon--sm" />
                            Annual interest rate
                            <span class="yk-debt__req" aria-hidden="true">*</span>
                        </span>
                    </label>
                    <div class="yk-debt__control yk-debt__control--percent">
                        <input
                            id="pfm-rate"
                            type="number"
                            name="annual_rate_percent"
                            min="0"
                            max="50"
                            step="0.01"
                            inputmode="decimal"
                            autocomplete="off"
                            placeholder="e.g. 9.00"
                            value="{{ $v('annual_rate_percent') }}"
                            required
                            aria-describedby="pfm-rate-help"
                        >
                        <span class="yk-debt__affix yk-debt__affix--end" aria-hidden="true">%</span>
                    </div>
                    <p id="pfm-rate-help" class="yk-debt__help">Yearly rate from 0% to 50%. Zero interest is supported.</p>
                </div>

                <div class="yk-pfm__field yk-debt__field yk-debt__field--tenure">
                    <label for="pfm-tenure">
                        <span class="yk-debt__label-row">
                            <x-yk-icon name="calendar-days" class="yk-icon--sm" />
                            Loan tenure
                            <span class="yk-debt__req" aria-hidden="true">*</span>
                        </span>
                    </label>
                    <div class="yk-debt__control yk-debt__control--years">
                        <input
                            id="pfm-tenure"
                            type="number"
                            name="tenure_years"
                            min="0.01"
                            max="100"
                            step="0.01"
                            inputmode="decimal"
                            autocomplete="off"
                            placeholder="e.g. 15"
                            value="{{ $v('tenure_years') }}"
                            required
                            aria-describedby="pfm-tenure-help"
                        >
                        <span class="yk-debt__affix yk-debt__affix--end" aria-hidden="true">years</span>
                    </div>
                    <p id="pfm-tenure-help" class="yk-debt__help">Duration in years (0.01–100). EMI months = years × 12.</p>
                </div>
            </div>
        </section>
    </div>

    {{-- Step 2: Optional extra payment --}}
    <div class="yk-pfm__step" data-pfm-step="1" hidden>
        <section class="yk-debt__section" aria-labelledby="yk-debt-extra-heading">
            <header class="yk-debt__section-head">
                <span class="yk-debt__section-icon" aria-hidden="true">
                    <x-yk-icon name="plus" class="yk-icon--sm" />
                </span>
                <div>
                    <h2 id="yk-debt-extra-heading" class="yk-debt__section-title">Extra monthly payment</h2>
                    <p class="yk-debt__section-lead">Optionally pay more each month to compare accelerated payoff against the standard EMI plan.</p>
                </div>
            </header>

            <div class="yk-pfm__grid yk-debt__grid">
                <div class="yk-pfm__field yk-debt__field yk-pfm__field--full">
                    <label for="pfm-extra">
                        <span class="yk-debt__label-row">
                            <x-yk-icon name="wallet" class="yk-icon--sm" />
                            Extra monthly payment
                            <span class="yk-pfm__optional">optional</span>
                        </span>
                    </label>
                    <div class="yk-debt__control yk-debt__control--money">
                        <span class="yk-debt__affix" aria-hidden="true">₹</span>
                        <input
                            id="pfm-extra"
                            type="number"
                            name="extra_payment"
                            min="0"
                            step="0.01"
                            inputmode="decimal"
                            autocomplete="off"
                            placeholder="0"
                            value="{{ $v('extra_payment', '0') }}"
                            aria-describedby="pfm-extra-help"
                        >
                    </div>
                    <p id="pfm-extra-help" class="yk-debt__help">
                        <x-yk-icon name="info" class="yk-icon--sm" />
                        Leave at 0 to calculate the standard EMI only. Extra amounts reduce interest and tenure in the preview.
                    </p>
                </div>
            </div>
        </section>
    </div>
</div>
