{{-- Task 43 — Shared Debt & EMI explanatory result body (preview + saved). Expects $view. --}}
@php
    /** @var array<string, mixed> $view */
    $money = $view['money'];
    $noLoan = (bool) $view['no_loan'];
@endphp

<section class="yk-debtr__headline {{ $noLoan ? 'is-no-loan' : '' }}" aria-labelledby="debtr-emi-label">
    <div class="yk-debtr__headline-top">
        <span class="yk-debtr__chip {{ $noLoan ? 'yk-debtr__chip--ok' : 'yk-debtr__chip--loan' }}">
            @if ($noLoan)
                <x-yk-icon name="circle-check" class="yk-icon--sm" />
                No loan required
            @else
                <x-yk-icon name="calculator" class="yk-icon--sm" />
                Monthly repayment
            @endif
        </span>
        <h2 id="debtr-emi-label" class="yk-debtr__label">
            {{ $noLoan ? 'Monthly EMI' : 'Standard monthly EMI' }}
        </h2>
    </div>
    <p class="yk-debtr__figure" data-debtr="emi">
        <strong>{{ $money['emi'] }}</strong>
        @if (! $noLoan && $view['tenure_label'])
            <span>/ month · {{ $view['tenure_label'] }}</span>
        @elseif ($noLoan)
            <span>No borrowing</span>
        @endif
    </p>
    @unless ($noLoan)
        <dl class="yk-debtr__kpis">
            <div class="yk-debtr__kpi">
                <dt>Annual interest rate</dt>
                <dd>{{ $view['annual_rate_percent'] !== null ? rtrim(rtrim(number_format((float) $view['annual_rate_percent'], 2, '.', ''), '0'), '.').'%' : 'Unavailable' }}</dd>
            </div>
            <div class="yk-debtr__kpi">
                <dt>Loan tenure</dt>
                <dd>{{ $view['tenure_label'] ?? 'Unavailable' }}</dd>
            </div>
            <div class="yk-debtr__kpi">
                <dt>Planned repayment period</dt>
                <dd>{{ $view['standard_duration_label'] ?? 'Unavailable' }}</dd>
            </div>
        </dl>
        @if ($view['has_extra'])
            <div class="yk-debtr__payment-split" role="group" aria-label="Monthly payment breakdown">
                <p><span>Standard EMI</span><strong>{{ $money['emi'] }}</strong></p>
                <p><span>Additional monthly repayment</span><strong>{{ $money['extra'] }}</strong></p>
                <p class="yk-debtr__payment-split-total"><span>Planned combined monthly payment</span><strong>{{ $money['combined_monthly'] }}</strong></p>
                <p class="yk-debtr__hint">The final instalment may differ if the remaining balance is smaller than a full payment.</p>
            </div>
        @endif
    @endunless
</section>

<section class="yk-debtr__panel" aria-labelledby="debtr-breakdown">
    <h2 id="debtr-breakdown">Loan amount breakdown</h2>
    <dl class="yk-debtr__breakdown">
        <div>
            <dt>Total purchase amount</dt>
            <dd data-debtr="purchase">{{ $money['purchase'] }}</dd>
        </div>
        <div>
            <dt>Down payment</dt>
            <dd data-debtr="down-payment">{{ $money['down_payment'] }}</dd>
        </div>
        <div>
            <dt>Actual financed loan amount</dt>
            <dd data-debtr="financed">{{ $money['financed'] }}</dd>
        </div>
        <div>
            <dt>Annual interest rate</dt>
            <dd>{{ $view['annual_rate_percent'] !== null ? rtrim(rtrim(number_format((float) $view['annual_rate_percent'], 2, '.', ''), '0'), '.').'%' : 'Unavailable' }}</dd>
        </div>
        <div>
            <dt>Loan tenure</dt>
            <dd>{{ $view['tenure_label'] ?? 'Unavailable' }}</dd>
        </div>
        @unless ($noLoan)
            <div>
                <dt>Total interest payable (standard)</dt>
                <dd data-debtr="interest">{{ $money['standard_interest'] }}</dd>
            </div>
            <div>
                <dt>Total loan repayment</dt>
                <dd data-debtr="loan-repayment">{{ $money['loan_repayment'] }}</dd>
                <small>Financed principal + loan interest</small>
            </div>
            <div>
                <dt>Total purchase-related outflow</dt>
                <dd data-debtr="outflow">{{ $money['total_outflow'] }}</dd>
                <small>Down payment + total loan repayment</small>
            </div>
        @else
            <div>
                <dt>Total paid upfront</dt>
                <dd>{{ $money['down_payment'] }}</dd>
            </div>
        @endunless
    </dl>
</section>

@if ($view['show_chart'])
    <section class="yk-debtr__panel" aria-labelledby="debtr-chart">
        <h2 id="debtr-chart">Principal versus interest</h2>
        <p class="yk-debtr__panel-lead">Share of total loan repayment (excludes down payment).</p>
        <div
            class="yk-debtr__bar"
            role="img"
            aria-label="Financed principal {{ $view['principal_percent'] }} percent ({{ $money['financed'] }}), interest {{ $view['interest_percent'] }} percent ({{ $money['standard_interest'] }})."
        >
            @if (($view['principal_percent'] ?? 0) > 0)
                <span class="yk-debtr__seg yk-debtr__seg--principal" style="flex-basis: {{ $view['principal_percent'] }}%">
                    {{ $view['principal_percent'] >= 14 ? $view['principal_percent'].'%' : '' }}
                </span>
            @endif
            @if (($view['interest_percent'] ?? 0) > 0)
                <span class="yk-debtr__seg yk-debtr__seg--interest" style="flex-basis: {{ $view['interest_percent'] }}%">
                    {{ $view['interest_percent'] >= 14 ? $view['interest_percent'].'%' : '' }}
                </span>
            @endif
        </div>
        <ul class="yk-debtr__legend">
            <li>
                <i class="yk-debtr__dot yk-debtr__dot--principal" aria-hidden="true"></i>
                <span>Financed principal</span>
                <b>{{ $money['financed'] }} <em>({{ $view['principal_percent'] }}%)</em></b>
            </li>
            <li>
                <i class="yk-debtr__dot yk-debtr__dot--interest" aria-hidden="true"></i>
                <span>Total interest payable</span>
                <b>{{ $money['standard_interest'] }} <em>({{ $view['interest_percent'] }}%)</em></b>
            </li>
        </ul>
        <p class="visually-hidden">
            Financed principal {{ $money['financed'] }} is {{ $view['principal_percent'] }} percent of total loan repayment;
            interest {{ $money['standard_interest'] }} is {{ $view['interest_percent'] }} percent.
        </p>
    </section>
@elseif ($noLoan)
    <section class="yk-debtr__panel" aria-labelledby="debtr-noloan-note">
        <h2 id="debtr-noloan-note">Principal versus interest</h2>
        <p class="yk-debtr__panel-lead">There is no financed principal or loan interest to chart because the purchase is covered by the down payment.</p>
    </section>
@endif

@unless ($noLoan)
    <section class="yk-debtr__panel" aria-labelledby="debtr-standard">
        <h2 id="debtr-standard">Standard repayment summary</h2>
        <p class="yk-debtr__panel-lead">Assumes no additional monthly repayments beyond the standard EMI.</p>
        <dl class="yk-debtr__breakdown yk-debtr__breakdown--compact">
            <div>
                <dt>Monthly EMI</dt>
                <dd>{{ $money['emi'] }}</dd>
            </div>
            <div>
                <dt>Repayment period</dt>
                <dd>{{ $view['standard_duration_label'] ?? 'Unavailable' }}</dd>
            </div>
            <div>
                <dt>Total interest</dt>
                <dd>{{ $money['standard_interest'] }}</dd>
            </div>
            <div>
                <dt>Total loan repayment</dt>
                <dd>{{ $money['loan_repayment'] }}</dd>
            </div>
            <div>
                <dt>Total purchase-related outflow</dt>
                <dd>{{ $money['total_outflow'] }}</dd>
            </div>
        </dl>
    </section>
@endunless

@if ($view['has_extra'] && ! $noLoan)
    <section class="yk-debtr__panel" aria-labelledby="debtr-compare">
        <h2 id="debtr-compare">Additional repayment comparison</h2>
        <div class="yk-debtr__table-wrap">
            <table class="yk-debtr__table">
                <caption class="visually-hidden">Standard repayment versus repayment with extra monthly payments</caption>
                <thead>
                    <tr>
                        <th scope="col">Metric</th>
                        <th scope="col">Standard repayment</th>
                        <th scope="col">With extra payment</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th scope="row">Monthly payment</th>
                        <td>{{ $money['emi'] }}</td>
                        <td>{{ $money['combined_monthly'] }}</td>
                    </tr>
                    <tr>
                        <th scope="row">Repayment duration</th>
                        <td>{{ $view['standard_duration_label'] ?? 'Unavailable' }}</td>
                        <td>{{ $view['accelerated_duration_label'] ?? 'Unavailable' }}</td>
                    </tr>
                    <tr>
                        <th scope="row">Total interest</th>
                        <td>{{ $money['standard_interest'] }}</td>
                        <td>{{ $money['accelerated_interest'] }}</td>
                    </tr>
                    <tr>
                        <th scope="row">Total loan repayment</th>
                        <td>{{ $money['standard_total'] }}</td>
                        <td>{{ $money['accelerated_total'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @if ($view['extra_benefit'])
            <ul class="yk-debtr__savings">
                @if (($view['interest_saved'] ?? 0) > 0.005)
                    <li>
                        <x-yk-icon name="wallet" class="yk-icon--sm" />
                        <span>Interest saved: <strong>{{ $money['interest_saved'] }}</strong></span>
                    </li>
                @endif
                @if (($view['months_saved'] ?? 0) > 0)
                    <li>
                        <x-yk-icon name="calendar-days" class="yk-icon--sm" />
                        <span>Earlier completion by: <strong>{{ $view['months_saved_label'] }}</strong></span>
                    </li>
                @endif
            </ul>
        @elseif ($view['extra_no_benefit'])
            <p class="yk-debtr__panel-lead" role="status">
                The entered additional repayment does not produce measurable savings under the current calculation assumptions.
            </p>
        @endif
    </section>
@endif

<section class="yk-debtr__panel" aria-labelledby="debtr-meaning" data-debtr-scenario="{{ $view['scenario'] }}">
    <h2 id="debtr-meaning">What this means</h2>
    <ul class="yk-debtr__points">
        @foreach ($view['explanation'] as $paragraph)
            <li>
                <span aria-hidden="true"><x-yk-icon name="info" class="yk-icon--sm" /></span>
                <p>{{ $paragraph }}</p>
            </li>
        @endforeach
    </ul>
</section>

<details class="yk-debtr__terms">
    <summary>
        <x-yk-icon name="book-open" class="yk-icon--sm" />
        Key terms
    </summary>
    <dl>
        @foreach ($view['terms'] as $term)
            <div>
                <dt>{{ $term['term'] }}</dt>
                <dd>{{ $term['text'] }}</dd>
            </div>
        @endforeach
    </dl>
</details>

<p class="yk-debtr__disclaimer" role="note">{{ $view['disclaimer'] }}</p>
