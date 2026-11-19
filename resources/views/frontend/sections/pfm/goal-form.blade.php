@extends('layouts.layout')

@section('title', $tool['name'].' | YuktiOne')
@section('meta_description', $tool['description'])
@section('body_class', 'yk-goal-body')

@push('styles')
    <link href="{{ asset('frontend/assets/css/yuktione-pfm-goal.css') }}?v={{ filemtime(public_path('frontend/assets/css/yuktione-pfm-goal.css')) }}" rel="stylesheet">
@endpush

@section('content')
@php
    $steps = ['Goal type', 'Cost & timeline', 'Assumptions'];
@endphp
<main class="yk-goal" data-yk-goal data-goal-year="{{ now()->year }}">
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
            </nav>
            <p class="yk-goal__eyebrow">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8.6"/><circle cx="12" cy="12" r="4.6"/><circle cx="12" cy="12" r="1.2" fill="currentColor"/></svg>
                Goal planner · 3 quick steps
            </p>
            <h1>{{ $tool['name'] }}</h1>
            <p class="yk-goal__lead">{{ $tool['description'] }} Educational estimates only—not financial advice.</p>
        </div>
    </header>

    <div class="yk-goal__wrap yk-goal__layout">
        <section class="yk-goal__card" aria-labelledby="goal-card-title">
            <h2 class="visually-hidden" id="goal-card-title">Plan your goal</h2>

            @if (! $open)
                <p class="yk-goal__alert" role="alert">This calculator is unavailable right now.</p>
            @else
                @if ($errors->any())
                    <p class="yk-goal__alert" role="alert">{{ $errors->first() }}</p>
                @endif

                @if ($hasResult)
                    <p class="yk-goal__notice">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12.5l4.2 4.2L19 7"/></svg>
                        You have a recent result.
                        <a href="{{ route($tool['route'].'.results') }}">View results</a>
                    </p>
                @endif

                <form method="post" action="{{ route($tool['route'].'.calculate') }}" class="yk-goal__form" data-pfm-form data-pfm-wizard="1" novalidate>
                    @csrf

                    <nav class="yk-goal__steps" aria-label="Calculator steps">
                        <ol>
                            @foreach ($steps as $index => $label)
                                <li class="{{ $index === 0 ? 'is-active' : '' }}" data-pfm-step-label="{{ $index }}">
                                    <span class="yk-goal__node" aria-hidden="true">
                                        <b>{{ $index + 1 }}</b>
                                        <svg viewBox="0 0 24 24" focusable="false"><path d="M5.5 12.5l4.3 4.3L18.5 8"/></svg>
                                    </span>
                                    <span class="yk-goal__step-name">{{ $label }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </nav>

                    @include('frontend.sections.pfm.partials.goal-fields', ['old' => $old, 'goalTypes' => $goalTypes ?? []])

                    <div class="yk-goal__actions">
                        <button type="button" class="yk-goal__btn yk-goal__btn--ghost" data-pfm-prev hidden>
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M19 12H5M10.4 6.4 4.8 12l5.6 5.6"/></svg>
                            Back
                        </button>
                        <button type="button" class="yk-goal__btn yk-goal__btn--primary" data-pfm-next>
                            Continue
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14M13.6 6.4 19.2 12l-5.6 5.6"/></svg>
                        </button>
                        <button type="submit" class="yk-goal__btn yk-goal__btn--gold" data-pfm-submit hidden>
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="5" y="3.5" width="14" height="17" rx="2.6"/><path d="M8.6 8h6.8M9 12.2h.01M12 12.2h.01M15 12.2h.01M9 16h.01M12 16h.01M15 16h.01" stroke-width="2.2"/></svg>
                            Calculate
                        </button>
                        @auth('user')
                            <a class="yk-goal__link" href="{{ route($tool['route'].'.history') }}">View history</a>
                        @endauth
                    </div>
                </form>
            @endif
        </section>

        <aside class="yk-goal__aside" aria-label="Goal summary">
            <section class="yk-goal__summary" aria-labelledby="goal-summary-title">
                <svg class="yk-goal__summary-art" viewBox="0 0 320 110" aria-hidden="true" focusable="false">
                    <path d="M0 96c40-26 74-40 112-40s62 20 96 20 74-26 112-52v86H0Z" fill="#fff" opacity=".07"/>
                    <path d="M0 106c52-20 92-26 140-18 50 8 96-6 180-40v62H0Z" fill="#fff" opacity=".07"/>
                    <path d="M214 78V22" stroke="#e9b955" stroke-width="3" stroke-linecap="round"/>
                    <path d="M214 22l36 11-36 11Z" fill="#e9b955"/>
                    <g fill="#e9b955"><ellipse cx="60" cy="92" rx="22" ry="6.5"/><ellipse cx="60" cy="85" rx="22" ry="6.5" opacity=".8"/><ellipse cx="60" cy="78" rx="22" ry="6.5" opacity=".6"/></g>
                </svg>
                <div class="yk-goal__summary-head">
                    <span class="yk-goal__summary-icon" data-goal-icon aria-hidden="true"></span>
                    <div>
                        <p class="yk-goal__summary-kicker">Your goal</p>
                        <h2 id="goal-summary-title" data-goal-out="name">Custom</h2>
                    </div>
                </div>
                <dl class="yk-goal__rows">
                    <div><dt>Goal type</dt><dd data-goal-out="type">Custom</dd></div>
                    <div><dt>Cost today</dt><dd data-goal-out="cost">—</dd></div>
                    <div><dt>Target year</dt><dd data-goal-out="year">—</dd></div>
                </dl>
                <div class="yk-goal__future">
                    <p>Estimated cost by then</p>
                    <strong data-goal-out="future">—</strong>
                    <span data-goal-out="future-note">Add a cost and timeline to see this.</span>
                </div>
            </section>

            <section class="yk-goal__how">
                <h2>How it works</h2>
                <ol>
                    <li><span aria-hidden="true">1</span>Your goal's cost rises with inflation until the target date.</li>
                    <li><span aria-hidden="true">2</span>Money you've already set aside grows at the return you assume.</li>
                    <li><span aria-hidden="true">3</span>We show the monthly amount needed to cover what's left.</li>
                </ol>
                <p>Educational estimates only—not financial advice.</p>
            </section>
        </aside>
    </div>
</main>
@push('scripts')
<script src="{{ asset('frontend/assets/js/yuktione-pfm-goal.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-pfm-goal.js')) }}" defer></script>
@endpush
@endsection
