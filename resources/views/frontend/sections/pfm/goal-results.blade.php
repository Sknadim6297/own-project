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
    $goalName = (string) ($inputs['goal_name'] ?? $tool['name']);
    $typeKey = (string) ($inputs['goal_type'] ?? 'custom');
    $typeLabel = $goalTypes[$typeKey] ?? ucfirst($typeKey);

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
@endphp
<main class="yk-goal yk-goalr">
    <header class="yk-goal__hero">
        <svg class="yk-goal__hero-art" viewBox="0 0 520 220" aria-hidden="true" focusable="false">
            <g fill="none" stroke="#e9b955" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" opacity=".55">
                <path d="M20 190h480"/>
                <path d="M70 190v-34M130 190v-62M190 190v-92M250 190v-124"/>
                <path d="M70 156l60-28 60-30 60-34 80-22"/>
                <path d="M318 72l-14-2M318 72l-6 13"/>
            </g>
            <g fill="#e9b955" opacity=".7">
                <circle cx="70" cy="156" r="5"/><circle cx="130" cy="128" r="5"/><circle cx="190" cy="98" r="5"/><circle cx="250" cy="64" r="5"/><circle cx="330" cy="42" r="7"/>
            </g>
            <g opacity=".22" fill="#fff">
                <circle cx="420" cy="150" r="44"/><circle cx="468" cy="112" r="26"/>
            </g>
        </svg>
        <div class="yk-goal__wrap">
            <nav class="yk-goal__crumbs" aria-label="Breadcrumb">
                <a href="{{ route('tools.index') }}"><span aria-hidden="true">←</span> Back to Tools</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('tools.pfm') }}">Money &amp; Finance</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route($tool['route']) }}">{{ $tool['name'] }}</a>
            </nav>
            <p class="yk-goal__eyebrow">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8.6"/><circle cx="12" cy="12" r="4.6"/><circle cx="12" cy="12" r="1.2" fill="currentColor"/></svg>
                Your goal plan
            </p>
            <h1 id="pfm-results-heading">{{ $goalName }}</h1>
            <p class="yk-goal__lead yk-goalr__facts">
                <span>{{ $typeLabel }}</span>
                <span>{{ $inr($cost) }} today</span>
                <span>{{ $yearsLabel }}</span>
                <span>Target {{ $targetYear }}</span>
            </p>
        </div>
    </header>

    <div class="yk-goal__wrap yk-goal__layout">
        <div class="yk-goalr__main">
            @if ($errors->any())
                <p class="yk-goal__alert yk-goalr__alert" role="alert">{{ $errors->first() }}</p>
            @endif

            <section class="yk-goal__card yk-goalr__headline {{ $onTrack ? 'is-on-track' : '' }}" aria-labelledby="goalr-headline">
                <div class="yk-goalr__headline-top">
                    <span class="yk-goalr__chip {{ $onTrack ? 'yk-goalr__chip--ok' : 'yk-goalr__chip--gap' }}">
                        @if ($onTrack)
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12.5l4.2 4.2L19 7"/></svg>
                            On track
                        @else
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5v9M12 18.5v.01"/></svg>
                            Gap to fund
                        @endif
                    </span>
                    <h2 id="goalr-headline" class="yk-goalr__label">{{ $onTrack ? 'Extra to invest each month' : 'Invest every month' }}</h2>
                </div>
                <p class="yk-goalr__figure" data-goalr="monthly">
                    <strong>{{ $inr($monthly) }}</strong>
                    @unless ($onTrack)
                        <span>/ month</span>
                    @endunless
                </p>
                <p class="yk-goalr__sentence">
                    @if ($onTrack)
                        Your current savings are projected to cover this goal on their own. No additional monthly investment is needed.
                    @else
                        For {{ $yearsLabel }} at an assumed {{ $num($ret, 2) }}% yearly return, to fund the {{ $inr($gap) }} your savings won’t cover.
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
                            <li><i class="yk-goalr__swatch yk-goalr__swatch--savings"></i>Your savings</li>
                            <li><i class="yk-goalr__swatch yk-goalr__swatch--cost"></i>Goal cost</li>
                        </ul>
                    </div>
                    <div class="yk-goalr__chart">
                        @include('frontend.sections.pfm.partials.goal-chart', ['projection' => $projection, 'compact' => false])
                        @include('frontend.sections.pfm.partials.goal-chart', ['projection' => $projection, 'compact' => true])
                    </div>
                    <p class="yk-goalr__caption">Assumes {{ $num($ret, 2) }}% yearly return on your savings and {{ $num($infl, 2) }}% yearly inflation on the goal’s cost, with deposits at the end of each month.</p>
                </section>
            @endif

            <section class="yk-goal__card yk-goalr__panel" aria-labelledby="goalr-meaning">
                <h2 id="goalr-meaning">What this means</h2>
                <ul class="yk-goalr__points">
                    <li>
                        <span aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M4 17l6-6 4 4 6-8M15 7h5v5"/></svg></span>
                        <p>At {{ $num($infl, 2) }}% inflation, a goal that costs <b>{{ $inr($cost) }}</b> today is expected to cost <b>{{ $inr($corpus) }}</b> in {{ $yearsLabel }}{!! $inflationClause !!}.</p>
                    </li>
                    <li>
                        <span aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M5 19V9M12 19V5M19 19v-7"/></svg></span>
                        <p>
                            @if ($existing > 0)
                                Your <b>{{ $inr($existing) }}</b> in savings can grow to <b>{{ $inr($fvExisting) }}</b>, covering about <b>{{ $coveredLabel }}%</b> of the goal.
                            @else
                                You’re starting from zero, so the whole goal has to be built through monthly investing.
                            @endif
                        </p>
                    </li>
                    <li>
                        <span aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><rect x="4" y="6" width="16" height="12" rx="2.5"/><circle cx="12" cy="12" r="2.6"/></svg></span>
                        <p>
                            @if ($onTrack)
                                Your savings alone are projected to finish <b>{{ $inr(max(0, $fvExisting - $corpus)) }}</b> ahead of the goal, so you can keep this plan as is.
                            @else
                                Investing <b>{{ $inr($monthly) }}</b> a month puts in about <b>{{ $inr($deposits) }}</b> of your own money; market growth at the assumed return supplies roughly <b>{{ $inr($growthShare) }}</b> more.
                            @endif
                        </p>
                    </li>
                </ul>
                <p class="yk-goalr__note">These figures are educational estimates only and are not financial advice. Real returns and inflation will vary.</p>
            </section>
        </div>

        <aside class="yk-goal__aside yk-goalr__aside" aria-label="Plan actions">
            <section class="yk-goal__summary yk-goalr__keep" aria-labelledby="goalr-keep">
                <svg class="yk-goal__summary-art" viewBox="0 0 320 110" aria-hidden="true" focusable="false">
                    <path d="M0 96c40-26 74-40 112-40s62 20 96 20 74-26 112-52v86H0Z" fill="#fff" opacity=".07"/>
                    <path d="M0 106c52-20 92-26 140-18 50 8 96-6 180-40v62H0Z" fill="#fff" opacity=".07"/>
                    <path d="M214 78V22" stroke="#e9b955" stroke-width="3" stroke-linecap="round"/>
                    <path d="M214 22l36 11-36 11Z" fill="#e9b955"/>
                    <g fill="#e9b955"><ellipse cx="60" cy="92" rx="22" ry="6.5"/><ellipse cx="60" cy="85" rx="22" ry="6.5" opacity=".8"/><ellipse cx="60" cy="78" rx="22" ry="6.5" opacity=".6"/></g>
                </svg>
                <p class="yk-goal__summary-kicker">Next steps</p>
                <h2 id="goalr-keep">Keep this plan</h2>
                <p class="yk-goalr__keep-text">{{ $authenticated ? 'Save it to your history, or adjust the numbers and see how the plan changes.' : 'Sign in to save it to your history, or adjust the numbers and see how the plan changes.' }}</p>

                <div class="yk-goalr__acts">
                    @if ($authenticated)
                        <form method="post" action="{{ route($tool['route'].'.save') }}">
                            @csrf
                            <button type="submit" class="yk-goal__btn yk-goal__btn--gold">Save result</button>
                        </form>
                        <form method="post" action="{{ route($tool['route'].'.save') }}">
                            @csrf
                            <input type="hidden" name="as_draft" value="1">
                            <button type="submit" class="yk-goal__btn yk-goalr__btn--dark">Save as draft</button>
                        </form>
                    @else
                        <form method="post" action="{{ route($tool['route'].'.save') }}">
                            @csrf
                            <button type="submit" class="yk-goal__btn yk-goal__btn--gold">Save result (sign in)</button>
                        </form>
                    @endif
                    <form method="post" action="{{ route($tool['route'].'.recalculate') }}">
                        @csrf
                        <button type="submit" class="yk-goal__btn yk-goalr__btn--dark">Edit &amp; recalculate</button>
                    </form>
                    @if ($authenticated)
                        <a class="yk-goalr__hist" href="{{ route($tool['route'].'.history') }}">View history</a>
                    @endif
                </div>
            </section>

            <section class="yk-goal__how yk-goalr__assump" aria-labelledby="goalr-assump">
                <h2 id="goalr-assump">Assumptions you used</h2>
                <dl>
                    <div><dt>Goal type</dt><dd>{{ $typeLabel }}</dd></div>
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
