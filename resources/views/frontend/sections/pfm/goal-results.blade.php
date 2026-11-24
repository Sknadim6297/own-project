@extends('layouts.layout')

@section('title', $tool['name'].' Results | YuktiOne')
@section('meta_description', $tool['description'])
@section('body_class', 'yk-goal-body')

@push('styles')
    <link href="{{ asset('frontend/assets/css/yuktione-pfm-goal.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-pfm-goal.css')) }}" rel="stylesheet">
@endpush

@section('content')
@php
    // Whole rupees with Indian digit grouping, e.g. ₹39,84,620.
    $inr = function ($value): string {
        $n = (string) (int) round(abs((float) $value));
        if (strlen($n) > 3) {
            $head = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($n, 0, -3));
            $n = $head.','.substr($n, -3);
        }

        return ((float) $value < 0 ? '-' : '').'₹'.$n;
    };
    $num = fn ($v, int $d = 1) => rtrim(rtrim(number_format((float) $v, $d, '.', ''), '0'), '.');

    $corpus = (float) ($result['required_corpus'] ?? 0);
    $fvExisting = (float) ($result['future_value_existing'] ?? 0);
    $gap = (float) ($result['funding_gap'] ?? 0);
    $monthly = (float) ($result['monthly_investment'] ?? 0);

    $years = (float) ($inputs['years'] ?? 0);
    $months = (int) round($years * 12);
    $yearsLabel = $num($years).' '.($years == 1.0 ? 'year' : 'years');
    $targetYear = now()->addMonths($months)->year;

    $cost = (float) ($inputs['current_cost'] ?? 0);
    $existing = (float) ($inputs['existing_savings'] ?? 0);
    $infl = (float) ($inputs['inflation_percent'] ?? 0);
    $ret = (float) ($inputs['expected_return_percent'] ?? 0);
    $typeKey = (string) ($inputs['goal_type'] ?? 'custom');
    $typeLabel = $goalTypes[$typeKey] ?? ucfirst($typeKey);
    $customName = trim((string) ($inputs['goal_name'] ?? ''));
    // Prefer custom name; otherwise goal type — never empty tool-name placeholders.
    $displayName = $customName !== '' ? $customName : $typeLabel;

    $onTrack = $gap <= 0.5;
    $coveredPct = $corpus > 0 ? max(0, min(100, ($fvExisting / $corpus) * 100)) : 100;
    $gapPct = 100 - $coveredPct;
    $coveredLabel = $num($coveredPct, 0);
    $gapLabel = $num($gapPct, 0);
    if ($coveredPct > 0 && $coveredPct < 1) {
        $coveredLabel = '<1';
    }
    if ($gapPct > 0 && $gapPct < 1) {
        $gapLabel = '<1';
    }

    $inflationExtra = max(0, $corpus - $cost);
    $inflationPct = $cost > 0 ? ($inflationExtra / $cost) * 100 : 0;
    $inflationClause = $inflationExtra > 0
        ? ' — <b>'.e($inr($inflationExtra)).'</b> ('.e($num($inflationPct, 0)).'%) more'
        : '';
    $deposits = $monthly * $months;
    $growthShare = max(0, $gap - $deposits);

    $factChips = [];
    $factChips[] = e($typeLabel);
    if ($customName !== '' && strcasecmp($customName, $typeLabel) !== 0) {
        $factChips[] = e($customName);
    }
    $factChips[] = e($inr($cost)).' today';
    $factChips[] = e($yearsLabel);
    $factChips[] = 'Target '.e((string) $targetYear);
    $factsHtml = '<p class="yk-goalr__facts">'.implode('', array_map(fn ($c) => '<span>'.$c.'</span>', $factChips)).'</p>';
@endphp
<main class="yk-goal yk-goalr yk-goal--compact-header" data-yk-goal-results>
    @include('frontend.sections.pfm.partials.goal-compact-header', [
        'title' => 'Your Financial Goal Plan',
        'titleId' => 'pfm-results-heading',
        'lead' => $displayName,
        'eyebrow' => 'Dedicated result',
        'showToolCrumb' => true,
        'tool' => $tool,
        'facts' => $factsHtml,
    ])

    <div class="yk-goal__wrap yk-goal__layout">
        <div class="yk-goalr__main">
            @if ($errors->any())
                <p class="yk-goal__alert yk-goalr__alert" role="alert">{{ $errors->first() }}</p>
            @endif

            <section class="yk-goal__card yk-goalr__headline {{ $onTrack ? 'is-on-track' : '' }}" aria-labelledby="goalr-headline">
                <div class="yk-goalr__headline-top">
                    <span class="yk-goalr__chip {{ $onTrack ? 'yk-goalr__chip--ok' : 'yk-goalr__chip--gap' }}">
                        @if ($onTrack)
                            <x-yk-icon name="circle-check" class="yk-icon--sm" />
                            On track
                        @else
                            <x-yk-icon name="triangle-alert" class="yk-icon--sm" />
                            Gap to fund
                        @endif
                    </span>
                    <h2 id="goalr-headline" class="yk-goalr__label">{{ $onTrack ? 'Extra to invest each month' : 'Required monthly investment' }}</h2>
                </div>
                <p class="yk-goalr__figure" data-goalr="monthly">
                    <strong>{{ $inr($monthly) }}</strong>
                    <span>/ month · {{ $yearsLabel }}</span>
                </p>
                <p class="yk-goalr__sentence">
                    @if ($onTrack)
                        Your current savings are projected to cover this goal on their own. No additional monthly investment is needed.
                    @else
                        For {{ $yearsLabel }} at an assumed {{ $num($ret, 2) }}% yearly return, invest this amount each month to fund the {{ $inr($gap) }} your savings won’t cover.
                    @endif
                </p>

                <dl class="yk-goalr__kpis">
                    <div class="yk-goalr__kpi">
                        <dt>Goal cost in {{ $targetYear }}</dt>
                        <dd data-goalr="corpus">{{ $inr($corpus) }}</dd>
                        <small>{{ $inr($cost) }} today</small>
                    </div>
                    <div class="yk-goalr__kpi">
                        <dt>Your savings grow to</dt>
                        <dd data-goalr="future-existing">{{ $inr($fvExisting) }}</dd>
                        <small>{{ $existing > 0 ? 'from '.$inr($existing).' today' : 'Starting from zero' }}</small>
                    </div>
                    <div class="yk-goalr__kpi {{ $onTrack ? 'is-ok' : 'is-gap' }}">
                        <dt>{{ $onTrack ? 'Surplus' : 'Remaining gap' }}</dt>
                        <dd data-goalr="gap">{{ $onTrack ? $inr(max(0, $fvExisting - $corpus)) : $inr($gap) }}</dd>
                        <small>{{ $onTrack ? 'Above the goal cost' : 'Funded by monthly investing' }}</small>
                    </div>
                </dl>
            </section>

            <section class="yk-goal__card yk-goalr__panel" aria-labelledby="goalr-breakdown">
                <h2 id="goalr-breakdown">Financial breakdown</h2>
                <dl class="yk-goalr__breakdown">
                    <div>
                        <dt>Current goal cost</dt>
                        <dd>{{ $inr($cost) }}</dd>
                    </div>
                    <div>
                        <dt>Inflation-adjusted goal cost</dt>
                        <dd>{{ $inr($corpus) }}</dd>
                    </div>
                    <div>
                        <dt>Existing savings today</dt>
                        <dd>{{ $inr($existing) }}</dd>
                    </div>
                    <div>
                        <dt>Future value of savings</dt>
                        <dd>{{ $inr($fvExisting) }}</dd>
                    </div>
                    <div>
                        <dt>Remaining funding need</dt>
                        <dd>{{ $inr($gap) }}</dd>
                    </div>
                    <div>
                        <dt>Required monthly investment</dt>
                        <dd>{{ $inr($monthly) }}</dd>
                    </div>
                    @unless ($onTrack)
                        <div>
                            <dt>Total projected contributions</dt>
                            <dd>{{ $inr($deposits) }}</dd>
                        </div>
                        <div>
                            <dt>Projected investment growth</dt>
                            <dd>{{ $inr($growthShare) }}</dd>
                        </div>
                    @endunless
                    <div>
                        <dt>Expected annual return</dt>
                        <dd>{{ $num($ret, 2) }}%</dd>
                    </div>
                    <div>
                        <dt>Annual inflation rate</dt>
                        <dd>{{ $num($infl, 2) }}%</dd>
                    </div>
                    <div>
                        <dt>Investment duration</dt>
                        <dd>{{ $yearsLabel }}</dd>
                    </div>
                </dl>
            </section>

            <section class="yk-goal__card yk-goalr__panel" aria-labelledby="goalr-funding">
                <h2 id="goalr-funding">How your goal gets funded</h2>
                <div class="yk-goalr__bar" role="img" aria-label="{{ $coveredLabel }}% of the goal is covered by your savings and {{ $gapLabel }}% needs monthly investing.">
                    @if ($coveredPct > 0)
                        <span class="yk-goalr__seg yk-goalr__seg--saved" style="flex-basis: {{ round($coveredPct, 2) }}%">{{ $coveredPct >= 14 ? $coveredLabel.'%' : '' }}</span>
                    @endif
                    @if ($gapPct > 0)
                        <span class="yk-goalr__seg yk-goalr__seg--gap" style="flex-basis: {{ round($gapPct, 2) }}%">{{ $gapPct >= 14 ? $gapLabel.'%' : '' }}</span>
                    @endif
                </div>
                <ul class="yk-goalr__legend">
                    <li>
                        <i class="yk-goalr__dot yk-goalr__dot--saved" aria-hidden="true"></i>
                        <span>Covered by your savings</span>
                        <b>{{ $inr(min($fvExisting, $corpus)) }} <em>({{ $coveredLabel }}%)</em></b>
                    </li>
                    <li>
                        <i class="yk-goalr__dot yk-goalr__dot--gap" aria-hidden="true"></i>
                        <span>To fund with monthly investing</span>
                        <b>{{ $inr($gap) }} <em>({{ $gapLabel }}%)</em></b>
                    </li>
                </ul>
            </section>

            @if ($projection)
                <section class="yk-goal__card yk-goalr__panel" aria-labelledby="goalr-growth">
                    <div class="yk-goalr__panel-head">
                        <h2 id="goalr-growth">Savings growth vs. goal cost</h2>
                        <ul class="yk-goalr__key" aria-hidden="true">
                            <li><i class="yk-goalr__swatch yk-goalr__swatch--savings"></i>Projected savings value</li>
                            <li><i class="yk-goalr__swatch yk-goalr__swatch--cost"></i>Goal target cost</li>
                        </ul>
                    </div>
                    <div class="yk-goalr__chart">
                        @include('frontend.sections.pfm.partials.goal-chart', ['projection' => $projection, 'compact' => false])
                        @include('frontend.sections.pfm.partials.goal-chart', ['projection' => $projection, 'compact' => true])
                    </div>
                    <p class="yk-goalr__caption">
                        Over {{ $yearsLabel }}, projected savings (contributions plus assumed growth at {{ $num($ret, 2) }}%) are compared with the inflation-adjusted goal cost at {{ $num($infl, 2) }}% a year. Deposits are assumed at the end of each month.
                    </p>
                    @php
                        $last = is_array($projection['points'] ?? null) ? end($projection['points']) : null;
                    @endphp
                    @if (is_array($last))
                        <p class="yk-goalr__chart-summary visually-hidden">
                            At the end of the timeline, projected savings reach {{ $inr($last['savings'] ?? 0) }} while the goal cost is {{ $inr($last['cost'] ?? 0) }}.
                        </p>
                    @endif
                </section>
            @endif

            <section class="yk-goal__card yk-goalr__panel" aria-labelledby="goalr-meaning">
                <h2 id="goalr-meaning">What this means</h2>
                <ul class="yk-goalr__points">
                    <li>
                        <span aria-hidden="true"><x-yk-icon name="target" class="yk-icon--sm" /></span>
                        <p>
                            Your plan for <b>{{ e($displayName) }}</b>
                            @if ($customName !== '' && strcasecmp($customName, $typeLabel) !== 0)
                                ({{ e($typeLabel) }})
                            @endif
                            spans <b>{{ $yearsLabel }}</b>, targeting {{ $targetYear }}.
                        </p>
                    </li>
                    <li>
                        <span aria-hidden="true"><x-yk-icon name="chart-no-axes-combined" class="yk-icon--sm" /></span>
                        <p>
                            @if ($infl > 0)
                                At {{ $num($infl, 2) }}% inflation, a goal that costs <b>{{ $inr($cost) }}</b> today is expected to cost <b>{{ $inr($corpus) }}</b> in {{ $yearsLabel }}{!! $inflationClause !!}.
                            @else
                                With <b>0%</b> inflation, the goal cost stays <b>{{ $inr($cost) }}</b> over {{ $yearsLabel }}.
                            @endif
                        </p>
                    </li>
                    <li>
                        <span aria-hidden="true"><x-yk-icon name="wallet" class="yk-icon--sm" /></span>
                        <p>
                            @if ($existing > 0)
                                Your <b>{{ $inr($existing) }}</b> in savings can grow to <b>{{ $inr($fvExisting) }}</b>
                                @if ($ret > 0)
                                    at an assumed {{ $num($ret, 2) }}% yearly return,
                                @else
                                    (with 0% assumed return growth is from time alone on contributions already set aside),
                                @endif
                                covering about <b>{{ $coveredLabel }}%</b> of the goal.
                            @else
                                You’re starting from zero existing savings, so the whole goal has to be built through monthly investing.
                            @endif
                        </p>
                    </li>
                    <li>
                        <span aria-hidden="true"><x-yk-icon name="calendar-days" class="yk-icon--sm" /></span>
                        <p>
                            @if ($onTrack)
                                Your savings alone are projected to finish <b>{{ $inr(max(0, $fvExisting - $corpus)) }}</b> ahead of the goal, so the required monthly investment is <b>{{ $inr($monthly) }}</b>.
                            @else
                                Investing <b>{{ $inr($monthly) }}</b> a month puts in about <b>{{ $inr($deposits) }}</b> of your own money; market growth at the assumed return supplies roughly <b>{{ $inr($growthShare) }}</b> more. These returns are illustrative estimates, not guarantees.
                            @endif
                        </p>
                    </li>
                </ul>
                <p class="yk-goalr__note">These figures are educational estimates only and are not financial advice. Real returns and inflation will vary.</p>
            </section>
        </div>

        <aside class="yk-goal__aside yk-goalr__aside" aria-label="Plan actions">
            <section class="yk-goal__summary yk-goalr__keep" aria-labelledby="goalr-keep">
                <p class="yk-goal__summary-kicker">Next steps</p>
                <h2 id="goalr-keep">Keep this plan</h2>
                <p class="yk-goalr__keep-text">{{ $authenticated ? 'Save it to your history, or adjust the numbers and see how the plan changes.' : 'Sign in to save it to your history, or adjust the numbers and see how the plan changes.' }}</p>

                <div class="yk-goalr__acts">
                    @if ($authenticated)
                        <form method="post" action="{{ route($tool['route'].'.save') }}">
                            @csrf
                            <button type="submit" class="yk-goal__btn yk-goal__btn--gold">
                                <x-yk-icon name="save" class="yk-icon--sm" />
                                Save result
                            </button>
                        </form>
                        <form method="post" action="{{ route($tool['route'].'.save') }}">
                            @csrf
                            <input type="hidden" name="as_draft" value="1">
                            <button type="submit" class="yk-goal__btn yk-goalr__btn--dark">
                                <x-yk-icon name="pencil" class="yk-icon--sm" />
                                Save as draft
                            </button>
                        </form>
                    @else
                        <form method="post" action="{{ route($tool['route'].'.save') }}">
                            @csrf
                            <button type="submit" class="yk-goal__btn yk-goal__btn--gold">
                                <x-yk-icon name="save" class="yk-icon--sm" />
                                Save result (sign in)
                            </button>
                        </form>
                    @endif
                    <form method="post" action="{{ route($tool['route'].'.recalculate') }}">
                        @csrf
                        <button type="submit" class="yk-goal__btn yk-goalr__btn--dark">
                            <x-yk-icon name="refresh-cw" class="yk-icon--sm" />
                            Edit &amp; recalculate
                        </button>
                    </form>
                    @include('frontend.sections.pfm.partials.new-calculation-action', [
                        'tool' => $tool,
                        'toolKey' => 'financial-goal-calculator',
                        'confirm' => true,
                        'buttonClass' => 'yk-goal__btn yk-goalr__btn--dark',
                    ])
                    <a class="yk-goal__btn yk-goalr__btn--dark" href="{{ route($tool['route']) }}">
                        <x-yk-icon name="arrow-left" class="yk-icon--sm" />
                        Back to calculator
                    </a>
                    @if ($authenticated)
                        <a class="yk-goalr__hist" href="{{ route($tool['route'].'.history') }}">
                            <x-yk-icon name="history" class="yk-icon--sm" />
                            View history
                        </a>
                    @endif
                </div>
            </section>

            <section class="yk-goal__how yk-goalr__assump" aria-labelledby="goalr-assump">
                <h2 id="goalr-assump">Assumptions you used</h2>
                <dl>
                    <div><dt>Goal type</dt><dd>{{ $typeLabel }}</dd></div>
                    @if ($customName !== '')
                        <div><dt>Goal name</dt><dd>{{ $customName }}</dd></div>
                    @endif
                    <div><dt>Cost today</dt><dd>{{ $inr($cost) }}</dd></div>
                    <div><dt>Timeline</dt><dd>{{ $yearsLabel }} · {{ $targetYear }}</dd></div>
                    <div><dt>Inflation</dt><dd>{{ $num($infl, 2) }}% a year</dd></div>
                    <div><dt>Expected return</dt><dd>{{ $num($ret, 2) }}% a year</dd></div>
                    <div><dt>Existing savings</dt><dd>{{ $inr($existing) }}</dd></div>
                </dl>
            </section>
        </aside>
    </div>
</main>
@endsection
