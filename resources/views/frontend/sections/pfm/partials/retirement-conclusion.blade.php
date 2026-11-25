{{-- Task 44 — Retirement Calculator personalized conclusion (presentation only). --}}
@php
    $view = $presentation ?? \App\Support\Pfm\RetirementPresentation::build(
        is_array($inputs ?? null) ? $inputs : [],
        is_array($result ?? null) ? $result : []
    );
@endphp

<section class="yk-rtconc" data-yk-rt-conclusion data-yk-rt-scenario="{{ $view['scenario'] }}" aria-labelledby="rtconc-heading">
    <header class="yk-rtconc__head">
        <h2 id="rtconc-heading" class="yk-rtconc__title">
            <x-yk-icon name="lightbulb" class="yk-icon--sm" />
            Your Retirement Summary
        </h2>
        <p class="yk-rtconc__lead">What these estimates mean for your retirement plan under the assumptions you entered.</p>
    </header>

    <ul class="yk-rtconc__points">
        @foreach ($view['paragraphs'] as $paragraph)
            <li>
                <span aria-hidden="true"><x-yk-icon name="info" class="yk-icon--sm" /></span>
                <p>{{ $paragraph }}</p>
            </li>
        @endforeach
    </ul>

    <section class="yk-rtconc__panel" aria-labelledby="rtconc-terms">
        <h3 id="rtconc-terms" class="yk-rtconc__subtitle">
            <x-yk-icon name="book-open" class="yk-icon--sm" />
            Understanding the numbers
        </h3>
        <dl class="yk-rtconc__terms">
            @foreach ($view['terms'] as $term)
                <div>
                    <dt>{{ $term['term'] }}</dt>
                    <dd>{{ $term['text'] }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <section class="yk-rtconc__panel" aria-labelledby="rtconc-next">
        <h3 id="rtconc-next" class="yk-rtconc__subtitle">
            <x-yk-icon name="calendar-days" class="yk-icon--sm" />
            Next Steps
        </h3>
        <ul class="yk-rtconc__next">
            @foreach ($view['next_steps'] as $step)
                <li>{{ $step }}</li>
            @endforeach
        </ul>
    </section>

    <section class="yk-rtconc__panel yk-rtconc__assump" aria-labelledby="rtconc-assump">
        <h3 id="rtconc-assump" class="yk-rtconc__subtitle">
            <x-yk-icon name="chart-no-axes-combined" class="yk-icon--sm" />
            Assumptions used
        </h3>
        <dl class="yk-rtconc__assump-grid">
            <div>
                <dt>Current age</dt>
                <dd>{{ $view['current_age'] !== null ? $view['current_age'] : 'Unavailable' }}</dd>
            </div>
            <div>
                <dt>Retirement age</dt>
                <dd>{{ $view['retirement_age'] !== null ? $view['retirement_age'] : 'Unavailable' }}</dd>
            </div>
            <div>
                <dt>Years until retirement</dt>
                <dd>{{ $view['years_to_retire'] !== null ? $view['years_to_retire'] : 'Unavailable' }}</dd>
            </div>
            <div>
                <dt>Life expectancy</dt>
                <dd>{{ $view['life_expectancy'] !== null ? $view['life_expectancy'] : 'Unavailable' }}</dd>
            </div>
            <div>
                <dt>Retirement duration</dt>
                <dd>{{ $view['retirement_years'] !== null ? $view['retirement_years'].' years' : 'Unavailable' }}</dd>
            </div>
            <div>
                <dt>Current monthly expenses</dt>
                <dd>{{ $view['money']['expenses'] }}</dd>
            </div>
            <div>
                <dt>Estimated expenses at retirement</dt>
                <dd>{{ $view['money']['future_monthly'] }}</dd>
            </div>
            <div>
                <dt>Existing retirement savings</dt>
                <dd>{{ $view['money']['assets'] }}</dd>
            </div>
            <div>
                <dt>Inflation</dt>
                <dd>{{ $view['rate']['inflation'] }} a year</dd>
            </div>
            <div>
                <dt>Pre-retirement return</dt>
                <dd>{{ $view['rate']['pre'] }} a year</dd>
            </div>
            <div>
                <dt>Post-retirement return</dt>
                <dd>{{ $view['rate']['post'] }} a year</dd>
            </div>
            <div>
                <dt>Contribution frequency</dt>
                <dd>Monthly (estimated)</dd>
            </div>
        </dl>
    </section>

    <p class="yk-rtconc__disclaimer" role="note">{{ $view['disclaimer'] }}</p>
</section>
