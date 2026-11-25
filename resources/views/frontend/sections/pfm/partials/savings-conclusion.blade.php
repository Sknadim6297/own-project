{{-- Task 47 — Savings & Investment personalized result summary (presentation only). --}}
@php
    $view = $presentation ?? \App\Support\Pfm\SavingsInvestmentPresentation::build(
        is_array($inputs ?? null) ? $inputs : [],
        is_array($result ?? null) ? $result : []
    );
@endphp

<section
    class="yk-siconc"
    data-yk-si-conclusion
    data-yk-si-scenario="{{ $view['scenario'] }}"
    aria-labelledby="siconc-heading"
>
    <header class="yk-siconc__head">
        <h2 id="siconc-heading" class="yk-siconc__title">
            <x-yk-icon name="lightbulb" class="yk-icon--sm" />
            Your Investment Summary
        </h2>
        <p class="yk-siconc__lead">Understand your total contributions and estimated investment growth.</p>
    </header>

    <section class="yk-siconc__kpi" aria-labelledby="siconc-maturity">
        <h3 id="siconc-maturity" class="yk-siconc__kpi-label">Estimated maturity amount</h3>
        <p class="yk-siconc__kpi-value" data-si="maturity">{{ $view['money']['corpus'] }}</p>
        <p class="yk-siconc__kpi-note">
            @if ($view['years'] !== null)
                Over {{ $view['years_label'] }} under the entered return assumptions.
            @else
                Duration unavailable for this snapshot.
            @endif
        </p>
    </section>

    <dl class="yk-siconc__metrics" aria-label="Investment breakdown">
        <div>
            <dt>Initial investment</dt>
            <dd data-si="initial">{{ $view['money']['initial'] }}</dd>
        </div>
        <div>
            <dt>Monthly investment</dt>
            <dd data-si="monthly">{{ $view['money']['monthly'] }}</dd>
        </div>
        <div>
            <dt>Investment frequency</dt>
            <dd>{{ $view['frequency_label'] }}</dd>
        </div>
        <div>
            <dt>Investment duration</dt>
            <dd>{{ $view['years_label'] }}</dd>
        </div>
        <div>
            <dt>Expected annual return</dt>
            <dd>{{ $view['rate']['return'] }}</dd>
        </div>
        <div>
            <dt>Total invested amount</dt>
            <dd data-si="invested">{{ $view['money']['invested'] }}</dd>
        </div>
        <div>
            <dt>Estimated investment returns</dt>
            <dd data-si="gains">{{ $view['money']['gains'] }}</dd>
        </div>
        <div>
            <dt>Estimated maturity amount</dt>
            <dd data-si="corpus">{{ $view['money']['corpus'] }}</dd>
        </div>
    </dl>

    <section class="yk-siconc__panel" aria-labelledby="siconc-conclusion">
        <h3 id="siconc-conclusion" class="yk-siconc__subtitle">
            <x-yk-icon name="info" class="yk-icon--sm" />
            Your Investment Conclusion
        </h3>
        <ul class="yk-siconc__points">
            @foreach ($view['paragraphs'] as $paragraph)
                <li>
                    <span aria-hidden="true"><x-yk-icon name="wallet" class="yk-icon--sm" /></span>
                    <p>{{ $paragraph }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="yk-siconc__panel" aria-labelledby="siconc-terms">
        <h3 id="siconc-terms" class="yk-siconc__subtitle">
            <x-yk-icon name="book-open" class="yk-icon--sm" />
            Understanding the numbers
        </h3>
        <dl class="yk-siconc__terms">
            @foreach ($view['terms'] as $term)
                <div>
                    <dt>{{ $term['term'] }}</dt>
                    <dd>{{ $term['text'] }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <section class="yk-siconc__panel" aria-labelledby="siconc-next">
        <h3 id="siconc-next" class="yk-siconc__subtitle">
            <x-yk-icon name="calendar-days" class="yk-icon--sm" />
            Next Steps
        </h3>
        <ul class="yk-siconc__next">
            @foreach ($view['next_steps'] as $step)
                <li>{{ $step }}</li>
            @endforeach
        </ul>
    </section>

    <section class="yk-siconc__panel yk-siconc__assump" aria-labelledby="siconc-assump">
        <h3 id="siconc-assump" class="yk-siconc__subtitle">
            <x-yk-icon name="chart-no-axes-combined" class="yk-icon--sm" />
            Assumptions used
        </h3>
        <dl class="yk-siconc__assump-grid">
            <div>
                <dt>Initial investment</dt>
                <dd>{{ $view['money']['initial'] }}</dd>
            </div>
            <div>
                <dt>Recurring contribution</dt>
                <dd>{{ $view['money']['monthly'] }}</dd>
            </div>
            <div>
                <dt>Contribution frequency</dt>
                <dd>{{ $view['frequency_label'] }}</dd>
            </div>
            <div>
                <dt>Investment duration</dt>
                <dd>{{ $view['years_label'] }}</dd>
            </div>
            <div>
                <dt>Expected annual return</dt>
                <dd>{{ $view['rate']['return'] }}</dd>
            </div>
            <div>
                <dt>Compounding frequency</dt>
                <dd>{{ $view['compounding_label'] }}</dd>
            </div>
            <div>
                <dt>Contribution timing</dt>
                <dd>{{ $view['contribution_timing'] }}</dd>
            </div>
            <div>
                <dt>Contribution months modelled</dt>
                <dd>{{ $view['months'] !== null ? $view['months'] : 'Unavailable' }}</dd>
            </div>
        </dl>
    </section>

    <p class="yk-siconc__disclaimer" role="note">{{ $view['disclaimer'] }}</p>
</section>
