{{-- Task 45 — Monthly investment sufficiency assessment (uses engine outputs only). --}}
@php
    $view = $presentation ?? \App\Support\Pfm\RetirementPresentation::build(
        is_array($inputs ?? null) ? $inputs : [],
        is_array($result ?? null) ? $result : []
    );
    $suf = $view['sufficiency'] ?? [];
    $status = (string) ($suf['status'] ?? 'unavailable');
    $statusLabels = [
        'insufficient' => 'Additional investment required',
        'meets' => 'Meets estimated monthly target',
        'above' => 'Above estimated monthly target',
        'zero_required' => 'No additional monthly contribution estimated',
        'unavailable' => 'Monthly investment assessment unavailable',
    ];
    $statusClass = [
        'insufficient' => 'yk-rtsuf__chip--gap',
        'meets' => 'yk-rtsuf__chip--ok',
        'above' => 'yk-rtsuf__chip--ok',
        'zero_required' => 'yk-rtsuf__chip--ok',
        'unavailable' => 'yk-rtsuf__chip--muted',
    ];
    $barPct = (float) ($suf['bar_percent'] ?? 0);
    $coverageLabel = $suf['coverage_label'] ?? 'Unavailable';
@endphp

<section
    class="yk-rtsuf"
    data-yk-rt-sufficiency
    data-yk-rt-suf-status="{{ $status }}"
    aria-labelledby="rtsuf-heading"
>
    <header class="yk-rtsuf__head">
        <span class="yk-rtsuf__chip {{ $statusClass[$status] ?? $statusClass['unavailable'] }}">
            @if (in_array($status, ['meets', 'above', 'zero_required'], true))
                <x-yk-icon name="circle-check" class="yk-icon--sm" />
            @elseif ($status === 'insufficient')
                <x-yk-icon name="triangle-alert" class="yk-icon--sm" />
            @else
                <x-yk-icon name="info" class="yk-icon--sm" />
            @endif
            {{ $statusLabels[$status] ?? $statusLabels['unavailable'] }}
        </span>
        <h2 id="rtsuf-heading" class="yk-rtsuf__title">Is Your Monthly Investment Sufficient?</h2>
        <p class="yk-rtsuf__lead">Compare your planned monthly contribution with the estimated amount required under your entered assumptions.</p>
    </header>

    @if ($status === 'unavailable')
        <p class="yk-rtsuf__note" role="status">
            Enter your planned monthly investment to compare it with your estimated retirement contribution requirement.
        </p>
    @else
        <div class="yk-rtsuf__groups">
            <section class="yk-rtsuf__group" aria-labelledby="rtsuf-monthly">
                <h3 id="rtsuf-monthly">Monthly contribution</h3>
                <dl class="yk-rtsuf__metrics">
                    <div>
                        <dt>Required monthly investment</dt>
                        <dd data-rtsuf="required-monthly">{{ $view['money']['monthly'] }}</dd>
                    </div>
                    <div>
                        <dt>Planned monthly investment</dt>
                        <dd data-rtsuf="planned-monthly">{{ $suf['money']['planned'] ?? 'Unavailable' }}</dd>
                    </div>
                    @if (($suf['monthly_shortfall'] ?? 0) > 0.005)
                        <div>
                            <dt>Additional monthly investment needed</dt>
                            <dd data-rtsuf="monthly-shortfall">{{ $suf['money']['monthly_shortfall'] }}</dd>
                        </div>
                    @endif
                    @if (($suf['monthly_surplus'] ?? 0) > 0.005)
                        <div>
                            <dt>Monthly contribution surplus</dt>
                            <dd data-rtsuf="monthly-surplus">{{ $suf['money']['monthly_surplus'] }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="yk-rtsuf__coverage">
                    <p class="yk-rtsuf__coverage-label">
                        Monthly contribution target coverage:
                        <strong>{{ $coverageLabel }}</strong>
                    </p>
                    <div
                        class="yk-rtsuf__bar"
                        role="img"
                        aria-label="Monthly contribution target coverage {{ $coverageLabel }}. Visual bar shows {{ number_format($barPct, 1) }} percent of the estimated requirement."
                    >
                        <span class="yk-rtsuf__bar-fill" style="width: {{ $barPct }}%"></span>
                    </div>
                    <p class="visually-hidden">
                        Planned monthly investment covers {{ $coverageLabel }} of the estimated required monthly investment. The progress bar is capped at 100 percent for display.
                    </p>
                </div>
            </section>

            <section class="yk-rtsuf__group" aria-labelledby="rtsuf-corpus">
                <h3 id="rtsuf-corpus">Retirement corpus at target age</h3>
                <dl class="yk-rtsuf__metrics">
                    <div>
                        <dt>Required retirement corpus</dt>
                        <dd data-rtsuf="required-corpus">{{ $view['money']['corpus'] }}</dd>
                    </div>
                    <div>
                        <dt>Projected retirement corpus</dt>
                        <dd data-rtsuf="projected-corpus">{{ $suf['money']['projected_corpus'] ?? 'Unavailable' }}</dd>
                    </div>
                    @if (($suf['corpus_shortfall'] ?? 0) > 0.005)
                        <div>
                            <dt>Estimated retirement corpus shortfall</dt>
                            <dd data-rtsuf="corpus-shortfall">{{ $suf['money']['corpus_shortfall'] }}</dd>
                        </div>
                    @endif
                    @if (($suf['corpus_surplus'] ?? 0) > 0.005)
                        <div>
                            <dt>Estimated retirement corpus surplus</dt>
                            <dd data-rtsuf="corpus-surplus">{{ $suf['money']['corpus_surplus'] }}</dd>
                        </div>
                    @endif
                </dl>
            </section>
        </div>

        <ul class="yk-rtsuf__explain">
            @foreach (($suf['paragraphs'] ?? []) as $paragraph)
                <li>{{ $paragraph }}</li>
            @endforeach
        </ul>
    @endif
</section>
