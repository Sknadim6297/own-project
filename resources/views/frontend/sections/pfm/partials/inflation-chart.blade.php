{{-- Task 46 — Inflation future-cost projection chart (engine-aligned compound series). --}}
@php
    $chart = $chart ?? \App\Support\Pfm\InflationChart::build(
        is_array($inputs ?? null) ? $inputs : [],
        is_array($result ?? null) ? $result : []
    );
    $chartId = $chartId ?? 'pfm-chart-inflation-calculator';
    $money = fn ($value) => '₹'.number_format((float) $value, 2);
@endphp

<figure class="yk-pfm__chart yk-infchart" data-yk-inf-chart aria-labelledby="{{ $chartId }}-title">
    <figcaption id="{{ $chartId }}-title" class="yk-pfm__chart-title">Inflation impact over time</figcaption>

    @if (! ($chart['available'] ?? false))
        <p class="yk-infchart__unavailable" role="status">{{ $chart['reason'] ?? 'Chart values are unavailable for this calculation.' }}</p>
    @else
        <p class="yk-infchart__explain">{{ $chart['explanation'] }}</p>

        <div class="yk-infchart__meta" aria-label="Projection assumptions">
            <span>Current amount <strong>{{ $money($chart['current_amount']) }}</strong></span>
            <span>Inflation <strong>{{ rtrim(rtrim(number_format((float) $chart['inflation_percent'], 4, '.', ''), '0'), '.') }}% / year</strong></span>
            <span>Period <strong>{{ rtrim(rtrim(number_format((float) $chart['years'], 2, '.', ''), '0'), '.') }} years</strong></span>
            <span>Future cost <strong>{{ $money($chart['future_cost']) }}</strong></span>
            <span>Increase <strong>{{ $money($chart['increase']) }}</strong></span>
        </div>

        <svg
            class="yk-infchart__svg"
            viewBox="0 0 480 210"
            role="img"
            aria-label="{{ $chart['aria_summary'] }}"
            preserveAspectRatio="xMidYMid meet"
        >
            @foreach (($chart['grid_y'] ?? []) as $gy)
                <line
                    x1="{{ $chart['axis']['left'] }}"
                    x2="{{ $chart['axis']['right'] }}"
                    y1="{{ $gy }}"
                    y2="{{ $gy }}"
                    class="yk-infchart__grid"
                ></line>
            @endforeach

            <line
                x1="{{ $chart['axis']['left'] }}"
                x2="{{ $chart['axis']['right'] }}"
                y1="{{ $chart['axis']['bottom'] }}"
                y2="{{ $chart['axis']['bottom'] }}"
                class="yk-infchart__axis"
            ></line>
            <line
                x1="{{ $chart['axis']['left'] }}"
                x2="{{ $chart['axis']['left'] }}"
                y1="{{ $chart['axis']['top'] }}"
                y2="{{ $chart['axis']['bottom'] }}"
                class="yk-infchart__axis"
            ></line>

            @foreach (($chart['y_ticks'] ?? []) as $tick)
                <text
                    x="{{ $chart['axis']['left'] - 8 }}"
                    y="{{ $tick['y'] + 4 }}"
                    text-anchor="end"
                    class="yk-infchart__tick"
                >{{ $tick['label'] }}</text>
            @endforeach

            @if (($chart['polyline'] ?? '') !== '')
                <polyline
                    points="{{ $chart['polyline'] }}"
                    class="yk-infchart__line"
                    fill="none"
                ></polyline>
            @endif

            @foreach (($chart['plot_points'] ?? []) as $i => $pt)
                <g class="yk-infchart__point" tabindex="0" data-yk-inf-point="{{ $i }}">
                    <title>{{ $pt['period'] }} — Estimated future cost: {{ $pt['value_full'] }}</title>
                    <circle
                        cx="{{ $pt['cx'] }}"
                        cy="{{ $pt['cy'] }}"
                        r="{{ ($pt['is_start'] || $pt['is_end']) ? 5.5 : 4 }}"
                        class="yk-infchart__dot {{ $pt['is_end'] ? 'yk-infchart__dot--end' : '' }}"
                    ></circle>
                    @if (in_array($i, $chart['show_value_indexes'] ?? [], true))
                        <text
                            x="{{ $pt['cx'] }}"
                            y="{{ $pt['label_y'] }}"
                            text-anchor="middle"
                            class="yk-infchart__value"
                        >{{ $pt['value_short'] }}</text>
                    @endif
                    @if (in_array($i, $chart['show_x_indexes'] ?? [], true))
                        <text
                            x="{{ $pt['cx'] }}"
                            y="{{ $chart['axis']['bottom'] + 18 }}"
                            text-anchor="middle"
                            class="yk-infchart__xlabel {{ ($pt['is_start'] || $pt['is_end']) ? 'yk-infchart__xlabel--strong' : '' }}"
                        >{{ $pt['period'] }}</text>
                    @endif
                </g>
            @endforeach
        </svg>

        <p class="yk-pfm__chart-note">
            Estimated future cost uses annual compounding at the entered inflation rate.
            Hover or focus a point for the full amount. Compact axis labels use ₹K / ₹L / ₹Cr.
        </p>

        <details class="yk-infchart__table-wrap" open>
            <summary class="yk-infchart__table-summary">Year-wise cost projection</summary>
            <div class="yk-infchart__table-scroll" role="region" aria-label="Year-wise cost projection table" tabindex="0">
                <table class="yk-infchart__table">
                    <caption class="visually-hidden">Estimated future cost for each plotted period</caption>
                    <thead>
                        <tr>
                            <th scope="col">Period</th>
                            <th scope="col">Estimated future cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (($chart['table'] ?? []) as $row)
                            <tr>
                                <th scope="row">{{ $row['period'] }}</th>
                                <td>{{ $row['amount'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif
</figure>
