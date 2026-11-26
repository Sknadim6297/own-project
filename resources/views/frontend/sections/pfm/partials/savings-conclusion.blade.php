{{-- Task 47/51 — Savings & Investment maturity results (presentation only; engine outputs authoritative). --}}
@php
    $view = $presentation ?? \App\Support\Pfm\SavingsInvestmentPresentation::build(
        is_array($inputs ?? null) ? $inputs : [],
        is_array($result ?? null) ? $result : []
    );
    $donut = $view['donut'] ?? ['available' => false];
    $projection = $view['projection'] ?? ['available' => false];
    $chartId = 'pfm-si-results';
@endphp

<section
    class="yk-siconc"
    data-yk-si-conclusion
    data-yk-si-results
    data-yk-si-scenario="{{ $view['scenario'] }}"
    aria-labelledby="siconc-heading"
>
    <header class="yk-siconc__head">
        <h2 id="siconc-heading" class="yk-siconc__title">
            <x-yk-icon name="wallet" class="yk-icon--sm" />
            Your Investment Results
        </h2>
        <p class="yk-siconc__lead">Estimated maturity, contributions, and investment growth under your entered assumptions.</p>
    </header>

    {{-- Primary KPI strip --}}
    <section class="yk-siconc__kpi-grid" aria-label="Key investment results">
        <div class="yk-siconc__kpi yk-siconc__kpi--primary" aria-labelledby="siconc-maturity">
            <h3 id="siconc-maturity" class="yk-siconc__kpi-label">Estimated Maturity Amount</h3>
            <p class="yk-siconc__kpi-value" data-si="maturity">{{ $view['money']['corpus'] }}</p>
            <p class="yk-siconc__kpi-note">
                @if ($view['years'] !== null)
                    Over {{ $view['years_label'] }} · {{ $view['frequency_label'] }}
                @else
                    Duration unavailable for this snapshot.
                @endif
            </p>
        </div>
        <dl class="yk-siconc__kpi-mini">
            <div>
                <dt>Total invested amount</dt>
                <dd data-si="invested">{{ $view['money']['invested'] }}</dd>
            </div>
            <div>
                <dt>Estimated investment returns</dt>
                <dd data-si="gains">{{ $view['money']['gains'] }}</dd>
            </div>
            <div>
                <dt>Investment duration</dt>
                <dd>{{ $view['years_label'] }}</dd>
            </div>
            <div>
                <dt>Contribution frequency</dt>
                <dd data-si="frequency">{{ $view['frequency_label'] }}</dd>
            </div>
            <div>
                <dt>Estimated Total Gain (%)</dt>
                <dd data-si="gain-percent">{{ $view['total_gain_percent_label'] }}</dd>
            </div>
        </dl>
    </section>
    <p class="yk-siconc__gain-note">
        Estimated Total Gain (%) is gains ÷ invested × 100. It is not an annual return, CAGR, or XIRR.
    </p>

    {{-- Investment breakdown --}}
    <section class="yk-siconc__panel" aria-labelledby="siconc-breakdown">
        <h3 id="siconc-breakdown" class="yk-siconc__subtitle">
            <x-yk-icon name="chart-pie" class="yk-icon--sm" />
            Investment breakdown
        </h3>
        <dl class="yk-siconc__metrics" aria-label="Investment breakdown">
            <div>
                <dt>Initial investment (contributed)</dt>
                <dd data-si="initial">{{ $view['money']['initial'] }}</dd>
            </div>
            @if ($view['has_contribution'] ?? false)
                <div>
                    <dt>Total recurring contributions</dt>
                    <dd data-si="recurring-total">{{ $view['money']['recurring_total'] }}</dd>
                </div>
            @endif
            <div>
                <dt>Total invested amount</dt>
                <dd>{{ $view['money']['invested'] }}</dd>
            </div>
            <div>
                <dt>Estimated gains</dt>
                <dd>{{ $view['money']['gains'] }}</dd>
            </div>
            <div>
                <dt>Estimated maturity amount</dt>
                <dd>{{ $view['money']['corpus'] }}</dd>
            </div>
            @if ($view['fv_initial'] !== null)
                <div>
                    <dt>Grown value of initial investment</dt>
                    <dd data-si="fv-initial">{{ $view['money']['fv_initial'] }}</dd>
                </div>
            @endif
            @if (($view['has_contribution'] ?? false) && $view['fv_contributions'] !== null)
                <div>
                    <dt>Grown value of recurring contributions</dt>
                    <dd data-si="fv-contributions">{{ $view['money']['fv_contributions'] }}</dd>
                </div>
            @endif
        </dl>
        <p class="yk-siconc__help">
            Grown values already include investment growth and are not the same as the original contribution amounts.
        </p>
    </section>

    {{-- Donut + growth charts --}}
    <div class="yk-sichart">
        <figure class="yk-sichart__donut-wrap" aria-labelledby="{{ $chartId }}-donut-title">
            <figcaption id="{{ $chartId }}-donut-title" class="yk-siconc__subtitle">
                <x-yk-icon name="chart-pie" class="yk-icon--sm" />
                Invested vs gains
            </figcaption>
            @if ($donut['available'] ?? false)
                <div class="yk-sichart__donut" role="img" aria-label="{{ $donut['aria'] }}">
                    <svg viewBox="0 0 140 140" class="yk-sichart__donut-svg" aria-hidden="true">
                        <circle
                            class="yk-sichart__donut-track"
                            cx="70"
                            cy="70"
                            r="{{ $donut['radius'] }}"
                            fill="none"
                            stroke-width="16"
                        ></circle>
                        <circle
                            class="yk-sichart__donut-invested"
                            cx="70"
                            cy="70"
                            r="{{ $donut['radius'] }}"
                            fill="none"
                            stroke-width="16"
                            stroke-dasharray="{{ $donut['invested_dash'] }}"
                            stroke-dashoffset="0"
                            transform="rotate(-90 70 70)"
                        ></circle>
                        <circle
                            class="yk-sichart__donut-gains"
                            cx="70"
                            cy="70"
                            r="{{ $donut['radius'] }}"
                            fill="none"
                            stroke-width="16"
                            stroke-dasharray="{{ $donut['gains_dash'] }}"
                            stroke-dashoffset="{{ $donut['gains_offset'] }}"
                            transform="rotate(-90 70 70)"
                        ></circle>
                        <text x="70" y="66" text-anchor="middle" class="yk-sichart__donut-center-label">Maturity</text>
                        <text x="70" y="84" text-anchor="middle" class="yk-sichart__donut-center-value">{{ \App\Support\Pfm\InflationChart::compactMoney((float) ($view['corpus'] ?? 0)) }}</text>
                    </svg>
                    <ul class="yk-sichart__legend">
                        <li>
                            <span class="yk-sichart__swatch yk-sichart__swatch--invested" aria-hidden="true"></span>
                            Invested {{ $view['money']['invested'] }}
                            <strong>{{ number_format((float) $donut['invested_percent'], 1) }}%</strong>
                        </li>
                        <li>
                            <span class="yk-sichart__swatch yk-sichart__swatch--gains" aria-hidden="true"></span>
                            Gains {{ $view['money']['gains'] }}
                            <strong>{{ number_format((float) $donut['gains_percent'], 1) }}%</strong>
                        </li>
                    </ul>
                </div>
            @else
                <p class="yk-sichart__unavailable" role="status">{{ $donut['reason'] ?? 'Investment mix chart is unavailable for this calculation snapshot.' }}</p>
            @endif
        </figure>

        <figure class="yk-sichart__growth-wrap" aria-labelledby="{{ $chartId }}-growth-title" data-yk-si-growth-chart>
            <figcaption id="{{ $chartId }}-growth-title" class="yk-siconc__subtitle">
                <x-yk-icon name="chart-no-axes-combined" class="yk-icon--sm" />
                Investment growth over time
            </figcaption>
            @if ($projection['available'] ?? false)
                <p class="yk-sichart__explain">
                    Cumulative invested capital versus estimated investment value. Contributions are added only at completed {{ strtolower($view['frequency_label']) }} intervals; growth compounds monthly.
                </p>
                <svg
                    class="yk-sichart__growth-svg"
                    viewBox="0 0 480 210"
                    role="img"
                    aria-label="{{ $projection['aria_summary'] }}"
                    preserveAspectRatio="xMidYMid meet"
                >
                    @foreach (($projection['grid_y'] ?? []) as $gy)
                        <line
                            x1="{{ $projection['axis']['left'] }}"
                            x2="{{ $projection['axis']['right'] }}"
                            y1="{{ $gy }}"
                            y2="{{ $gy }}"
                            class="yk-sichart__grid"
                        ></line>
                    @endforeach
                    <line
                        x1="{{ $projection['axis']['left'] }}"
                        x2="{{ $projection['axis']['right'] }}"
                        y1="{{ $projection['axis']['bottom'] }}"
                        y2="{{ $projection['axis']['bottom'] }}"
                        class="yk-sichart__axis"
                    ></line>
                    <line
                        x1="{{ $projection['axis']['left'] }}"
                        x2="{{ $projection['axis']['left'] }}"
                        y1="{{ $projection['axis']['top'] }}"
                        y2="{{ $projection['axis']['bottom'] }}"
                        class="yk-sichart__axis"
                    ></line>
                    @foreach (($projection['y_ticks'] ?? []) as $tick)
                        <text
                            x="{{ $projection['axis']['left'] - 8 }}"
                            y="{{ $tick['y'] + 4 }}"
                            text-anchor="end"
                            class="yk-sichart__tick"
                        >{{ $tick['label'] }}</text>
                    @endforeach
                    @if (($projection['polyline_invested'] ?? '') !== '')
                        <polyline points="{{ $projection['polyline_invested'] }}" class="yk-sichart__line yk-sichart__line--invested" fill="none"></polyline>
                    @endif
                    @if (($projection['polyline_value'] ?? '') !== '')
                        <polyline points="{{ $projection['polyline_value'] }}" class="yk-sichart__line yk-sichart__line--value" fill="none"></polyline>
                    @endif
                    @foreach (($projection['plot_points'] ?? []) as $i => $pt)
                        <g class="yk-sichart__point" tabindex="0">
                            <title>{{ $pt['label'] }} — Invested {{ $pt['invested_full'] }}; Value {{ $pt['value_full'] }}</title>
                            <circle cx="{{ $pt['cx'] }}" cy="{{ $pt['cy_invested'] }}" r="3.5" class="yk-sichart__dot yk-sichart__dot--invested"></circle>
                            <circle cx="{{ $pt['cx'] }}" cy="{{ $pt['cy_value'] }}" r="{{ ($pt['is_start'] || $pt['is_end']) ? 5 : 3.5 }}" class="yk-sichart__dot yk-sichart__dot--value"></circle>
                            @if (in_array($i, $projection['show_x_indexes'] ?? [], true))
                                <text
                                    x="{{ $pt['cx'] }}"
                                    y="{{ $projection['axis']['bottom'] + 18 }}"
                                    text-anchor="middle"
                                    class="yk-sichart__xlabel"
                                >{{ $pt['label'] }}</text>
                            @endif
                        </g>
                    @endforeach
                </svg>
                <ul class="yk-sichart__legend">
                    <li>
                        <span class="yk-sichart__swatch yk-sichart__swatch--invested" aria-hidden="true"></span>
                        Cumulative invested (ends {{ $view['money']['invested'] }})
                    </li>
                    <li>
                        <span class="yk-sichart__swatch yk-sichart__swatch--value" aria-hidden="true"></span>
                        Estimated value (ends {{ $view['money']['corpus'] }})
                    </li>
                </ul>
            @else
                <p class="yk-sichart__unavailable" role="status">{{ $projection['reason'] ?? 'Growth chart is unavailable for this calculation snapshot.' }}</p>
            @endif
        </figure>
    </div>

    {{-- Year-wise projection --}}
    @if (($projection['available'] ?? false) && ! empty($projection['year_rows']))
        <details class="yk-siconc__panel yk-sichart__table-wrap" data-yk-si-projection-table>
            <summary class="yk-siconc__subtitle yk-sichart__summary">
                <x-yk-icon name="calendar-days" class="yk-icon--sm" />
                Year-wise investment projection
            </summary>
            <div class="yk-sichart__table-scroll">
                <table class="yk-sichart__table">
                    <caption class="visually-hidden">Year-wise invested capital, estimated value, and gains</caption>
                    <thead>
                        <tr>
                            <th scope="col">Period</th>
                            <th scope="col">Total invested</th>
                            <th scope="col">Estimated value</th>
                            <th scope="col">Estimated gains</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($projection['year_rows'] as $row)
                            <tr @if ($row['is_end'] ?? false) class="is-final" @endif>
                                <th scope="row">{{ $row['label'] }}</th>
                                <td>{{ $row['invested_full'] }}</td>
                                <td>{{ $row['value_full'] }}</td>
                                <td>{{ $row['gains_full'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif

    {{-- Plan details --}}
    <section class="yk-siconc__panel" aria-labelledby="siconc-plan">
        <h3 id="siconc-plan" class="yk-siconc__subtitle">
            <x-yk-icon name="book-open" class="yk-icon--sm" />
            Investment plan details
        </h3>
        <dl class="yk-siconc__assump-grid">
            <div>
                <dt>Investment type</dt>
                <dd data-si="type">{{ $view['investment_type_label'] }}</dd>
            </div>
            <div>
                <dt>Initial investment</dt>
                <dd>{{ $view['money']['initial'] }}</dd>
            </div>
            @if ($view['has_contribution'] ?? false)
                <div>
                    <dt>Amount per contribution</dt>
                    <dd data-si="monthly">{{ $view['money']['contribution'] }}</dd>
                </div>
                <div>
                    <dt>Contribution frequency</dt>
                    <dd>{{ $view['frequency_label'] }}</dd>
                </div>
                <div>
                    <dt>Contributions per year</dt>
                    <dd>{{ $view['contributions_per_year'] }}</dd>
                </div>
                <div>
                    <dt>Scheduled contribution count</dt>
                    <dd data-si="contribution-count">{{ $view['contribution_count'] ?? 'Unavailable' }}</dd>
                </div>
                <div>
                    <dt>Contribution timing</dt>
                    <dd>{{ $view['contribution_timing'] }}</dd>
                </div>
            @endif
            <div>
                <dt>Investment duration</dt>
                <dd>{{ $view['years_label'] }}</dd>
            </div>
            <div>
                <dt>Effective duration (months)</dt>
                <dd>{{ $view['months'] !== null ? $view['months'] : 'Unavailable' }}</dd>
            </div>
            <div>
                <dt>Expected annual return</dt>
                <dd>{{ $view['rate']['return'] }}</dd>
            </div>
            <div>
                <dt>Compounding frequency</dt>
                <dd>{{ $view['compounding_label'] }}</dd>
            </div>
        </dl>
    </section>

    <section class="yk-siconc__panel" aria-labelledby="siconc-conclusion">
        <h3 id="siconc-conclusion" class="yk-siconc__subtitle">
            <x-yk-icon name="lightbulb" class="yk-icon--sm" />
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
            <x-yk-icon name="info" class="yk-icon--sm" />
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
                <dt>Expected annual return</dt>
                <dd>{{ $view['rate']['return'] }}</dd>
            </div>
            <div>
                <dt>Compounding frequency</dt>
                <dd>{{ $view['compounding_label'] }}</dd>
            </div>
            <div>
                <dt>Contribution timing</dt>
                <dd>{{ ($view['has_contribution'] ?? false) ? $view['contribution_timing'] : 'Lump sum (no recurring contributions)' }}</dd>
            </div>
            <div>
                <dt>Effective duration (months)</dt>
                <dd>{{ $view['months'] !== null ? $view['months'] : 'Unavailable' }}</dd>
            </div>
        </dl>
    </section>

    <p class="yk-siconc__disclaimer" role="note">{{ $view['disclaimer'] }}</p>
</section>
