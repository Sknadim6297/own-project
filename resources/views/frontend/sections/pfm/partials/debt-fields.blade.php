@php
    $v = fn ($key, $default = '') => old($key, $old[$key] ?? $default);
    $purchaseRaw = (string) $v('principal');
    $downRaw = (string) $v('down_payment', '0');
    $purchasePreview = is_numeric($purchaseRaw) ? (float) $purchaseRaw : 0.0;
    $downPreview = is_numeric($downRaw) ? (float) $downRaw : 0.0;
    $financedPreview = max(0, $purchasePreview - $downPreview);
    $fmtInr = function (float $value): string {
        $n = (string) (int) round(abs($value));
        if (strlen($n) > 3) {
            $head = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($n, 0, -3));
            $n = $head.','.substr($n, -3);
        }

        return '₹'.$n;
    };
@endphp
{{-- Task 41/42: continuous form; principal = purchase amount; down_payment optional. --}}
<div class="yk-debt" data-yk-debt-fields data-yk-debt-single="1" data-yk-debt-down-payment="1">
    <section class="yk-debt__section" aria-labelledby="debt-section-loan">
        <header class="yk-debt__section-head">
            <h2 id="debt-section-loan" class="yk-debt__section-title">
                <x-yk-icon name="wallet" class="yk-icon--sm" />
                Loan details
            </h2>
            <p class="yk-debt__section-lead">Enter the total purchase amount, optional down payment, interest rate, and tenure.</p>
        </header>

        <div class="yk-pfm__grid yk-debt__grid">
            <div class="yk-pfm__field yk-debt__field">
                <label for="pfm-principal">Total purchase amount</label>
                <div class="yk-debt__control">
                    <span class="yk-debt__affix yk-debt__affix--prefix" aria-hidden="true">₹</span>
                    <input
                        id="pfm-principal"
                        type="number"
                        name="principal"
                        min="0.01"
                        step="0.01"
                        inputmode="decimal"
                        autocomplete="off"
                        placeholder="0"
                        value="{{ $v('principal') }}"
                        required
                        data-yk-debt-purchase
                        aria-describedby="pfm-principal-help"
                    >
                </div>
                <p id="pfm-principal-help" class="yk-debt__help">Full price of what you are buying before any down payment.</p>
            </div>

            <div class="yk-pfm__field yk-debt__field">
                <label for="pfm-down-payment">
                    Down payment
                    <span class="yk-pfm__optional">optional</span>
                </label>
                <div class="yk-debt__control">
                    <span class="yk-debt__affix yk-debt__affix--prefix" aria-hidden="true">₹</span>
                    <input
                        id="pfm-down-payment"
                        type="number"
                        name="down_payment"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        autocomplete="off"
                        placeholder="0"
                        value="{{ $v('down_payment', '0') }}"
                        data-yk-debt-down
                        aria-describedby="pfm-down-payment-help debt-financed-preview"
                    >
                </div>
                <p id="pfm-down-payment-help" class="yk-debt__help">Enter the amount you plan to pay upfront. This reduces the amount you need to borrow.</p>
                <p
                    id="debt-financed-preview"
                    class="yk-debt__financed"
                    data-yk-debt-financed-preview
                    role="status"
                    aria-live="polite"
                >
                    Actual loan amount:
                    <strong data-yk-debt-financed-value>{{ $fmtInr($financedPreview) }}</strong>
                </p>
            </div>

            <div class="yk-pfm__field yk-debt__field">
                <label for="pfm-rate">Annual interest rate</label>
                <div class="yk-debt__control">
                    <input
                        id="pfm-rate"
                        type="number"
                        name="annual_rate_percent"
                        min="0"
                        max="50"
                        step="0.01"
                        inputmode="decimal"
                        autocomplete="off"
                        placeholder="0"
                        value="{{ $v('annual_rate_percent') }}"
                        required
                        aria-describedby="pfm-rate-help"
                    >
                    <span class="yk-debt__affix yk-debt__affix--suffix" aria-hidden="true">%</span>
                </div>
                <p id="pfm-rate-help" class="yk-debt__help">Yearly rate charged on the loan (0–50%).</p>
            </div>

            <div class="yk-pfm__field yk-debt__field">
                <label for="pfm-tenure">Loan tenure</label>
                <div class="yk-debt__control">
                    <input
                        id="pfm-tenure"
                        type="number"
                        name="tenure_years"
                        min="0.01"
                        max="100"
                        step="0.01"
                        inputmode="decimal"
                        autocomplete="off"
                        placeholder="0"
                        value="{{ $v('tenure_years') }}"
                        required
                        aria-describedby="pfm-tenure-help"
                    >
                    <span class="yk-debt__affix yk-debt__affix--suffix" aria-hidden="true">years</span>
                </div>
                <p id="pfm-tenure-help" class="yk-debt__help">Duration from 0.01 to 100 years (decimals allowed).</p>
            </div>
        </div>
    </section>

    <section class="yk-debt__section" aria-labelledby="debt-section-extra">
        <header class="yk-debt__section-head">
            <h2 id="debt-section-extra" class="yk-debt__section-title">
                <x-yk-icon name="plus" class="yk-icon--sm" />
                Additional repayment
            </h2>
            <p class="yk-debt__section-lead">Optionally add an extra monthly payment to compare accelerated payoff against the standard EMI plan.</p>
        </header>

        <div class="yk-pfm__grid yk-debt__grid">
            <div class="yk-pfm__field yk-debt__field yk-pfm__field--full">
                <label for="pfm-extra">
                    Extra monthly payment
                    <span class="yk-pfm__optional">optional</span>
                </label>
                <div class="yk-debt__control">
                    <span class="yk-debt__affix yk-debt__affix--prefix" aria-hidden="true">₹</span>
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
                <p id="pfm-extra-help" class="yk-debt__help">Amount paid each month on top of EMI. Use 0 to see the standard schedule only. Not the same as a down payment.</p>
            </div>
        </div>
    </section>
</div>
