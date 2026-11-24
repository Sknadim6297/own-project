@extends('layouts.layout')

@section('title', 'Daily Mood Check-in | YuktiOne')
@section('meta_description', 'Choose the option that best describes how you feel right now.')
@section('body_class', 'yk-mood-body')

@section('content')
<main class="yk-mood yk-tools-ds yk-tool-shell yk-mood--compact-header @if ($open) yk-mood--home @endif" data-yk-tools-ds data-yk-mood data-yk-tool-shell="1">
    <div class="yk-mood__wrap @if ($open) yk-mood__wrap--home @endif">
        <a class="yk-mood__back" href="{{ route('tools.psychology') }}">
            <span aria-hidden="true">←</span> Back to Tools
        </a>

        @if (!$open)
            <section class="yk-mood__closed" aria-labelledby="mood-closed-heading">
                <p class="yk-mood__badge">Free tool</p>
                <h1 id="mood-closed-heading"><x-yuktione.two-tone text="How are you feeling today?" /></h1>
                <p>This check-in is unavailable right now.</p>
            </section>
        @else
            <header class="yk-mood__hero yk-mood__hero--compact" data-yk-tool-compact-header>
                <div class="yk-mood__hero-copy">
                    <p class="yk-mood__badge">
                        <x-yk-icon name="smile" class="yk-icon--sm" />
                        Mental Wellness
                    </p>
                    <div class="yk-mood__title-row">
                        <span class="yk-mood__title-icon" aria-hidden="true">
                            <x-yk-icon name="smile" class="yk-icon--lg" />
                        </span>
                        <div>
                            <h1><x-yuktione.two-tone text="Daily Mood Check" /></h1>
                            <p class="yk-mood__intro" data-yk-tool-page-lead>{{ \App\Support\Tools\ToolPageLeads::for('daily-mood-check-in') }}</p>
                        </div>
                    </div>
                </div>
            </header>

            @include('frontend.sections.partials.mood-progress', ['current' => 'mood'])

            <div class="yk-mood__layout">
                <form
                    class="yk-mood__form"
                    method="post"
                    action="{{ route('tools.psychology.mood.store') }}"
                    data-mood-form
                    data-mood-initial="{{ $selected ?? '' }}"
                    @if ($hasFollowUpAnswers ?? false) data-mood-has-followup="1" @endif
                >
                    @csrf
                    <div class="yk-mood__panel yk-mood__panel--select">
                        <div class="yk-mood__heading">
                            <span class="yk-mood__heading-icon" aria-hidden="true">
                                <svg viewBox="0 0 36 36"><circle cx="18" cy="18" r="8.6"/><path d="M18 2.2v3.2M18 30.6v3.2M2.2 18h3.2M30.6 18h3.2M6.8 6.8l2.2 2.2M27 27l2.2 2.2M6.8 29.2 9 27M27 9l2.2-2.2"/></svg>
                            </span>
                            <div>
                                <h2><x-yuktione.two-tone text="How are you feeling today?" /></h2>
                            </div>
                        </div>

                        @if ($errors->any())
                            <p class="yk-mood__error" role="alert">{{ $errors->first() }}</p>
                        @endif

                        <fieldset>
                            <legend class="visually-hidden">Primary mood</legend>
                            <ul class="yk-mood__options">
                                @foreach ($moods as $mood)
                                    <li>
                                        <label class="yk-mood__card is-{{ $mood['tone'] }} is-mood-{{ $mood['key'] }} @if ($selected === $mood['key']) is-selected @endif" data-mood-card @if ($selected === $mood['key']) data-selected="true" @endif>
                                            <input class="yk-mood__input" type="radio" name="mood" value="{{ $mood['key'] }}" @checked($selected === $mood['key'])>
                                            <span class="yk-mood__face" aria-hidden="true">
                                                @include('frontend.sections.partials.mood-face', ['mood' => $mood['key']])
                                            </span>
                                            <span class="yk-mood__copy">
                                                <span class="yk-mood__name">{{ $mood['label'] }}</span>
                                                <span class="yk-mood__caption">{{ $mood['caption'] }}</span>
                                            </span>
                                            <span class="yk-mood__radio" aria-hidden="true"></span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </fieldset>
                    </div>

                    <button class="yk-mood__continue" type="submit" data-mood-continue @disabled($selected === null)>Continue</button>
                </form>

                <aside class="yk-mood__aside" aria-label="About this check-in">
                    <section class="yk-mood__side-card">
                        <header class="yk-mood__side-head">
                            <span class="yk-mood__side-icon" aria-hidden="true">
                                <svg viewBox="0 0 28 28"><rect x="5" y="14" width="4.4" height="8.5" rx="1.2"/><rect x="11.8" y="9.5" width="4.4" height="13" rx="1.2"/><rect x="18.6" y="5.5" width="4.4" height="17" rx="1.2"/></svg>
                            </span>
                            <div>
                                <h2><x-yuktione.two-tone text="Your Mood Journey" /></h2>
                                <p class="yk-mood__side-lead">Three short steps for today’s check-in.</p>
                            </div>
                        </header>
                        <ol class="yk-mood__journey">
                            <li class="is-current">
                                <span class="yk-mood__journey-dot" aria-hidden="true"></span>
                                <span>
                                    <strong>Step 1: Mood</strong>
                                    <span>How are you feeling today?</span>
                                </span>
                            </li>
                            <li>
                                <span class="yk-mood__journey-dot" aria-hidden="true"></span>
                                <span>
                                    <strong>Step 2: Emotions</strong>
                                    <span>Pick the emotions you relate to.</span>
                                </span>
                            </li>
                            <li>
                                <span class="yk-mood__journey-dot" aria-hidden="true"></span>
                                <span>
                                    <strong>Step 3: Write</strong>
                                    <span>A short reflection (optional).</span>
                                </span>
                            </li>
                            <li>
                                <span class="yk-mood__journey-dot" aria-hidden="true"></span>
                                <span>
                                    <strong>Step 4: Done</strong>
                                    <span>Get your summary &amp; insights.</span>
                                </span>
                            </li>
                        </ol>
                    </section>

                    <section class="yk-mood__side-card yk-mood__side-card--chart">
                        <header class="yk-mood__side-head">
                            <span class="yk-mood__side-icon" aria-hidden="true">
                                <svg viewBox="0 0 28 28"><rect x="4.5" y="6.5" width="19" height="17" rx="3.2" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="M4.5 9.7a3.2 3.2 0 0 1 3.2-3.2h12.6a3.2 3.2 0 0 1 3.2 3.2v2.3h-19Z"/><path d="M9.5 3.8v4.6M18.5 3.8v4.6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                            </span>
                            <div>
                                <h2><x-yuktione.two-tone text="Track Your Progress" /></h2>
                                <p class="yk-mood__side-lead">Log your mood daily and see patterns over time.</p>
                            </div>
                        </header>
                        <svg class="yk-mood__spark" viewBox="0 0 330 80" aria-hidden="true" focusable="false">
                            <defs>
                                <linearGradient id="yk-mood-spark-fill" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#e8e0d6" stop-opacity="0.65"/>
                                    <stop offset="100%" stop-color="#f5f1eb" stop-opacity="0"/>
                                </linearGradient>
                                <linearGradient id="yk-mood-spark-line" x1="0" x2="1" y1="0" y2="0">
                                    <stop offset="0%" stop-color="#8d1f52"/>
                                    <stop offset="100%" stop-color="#5c1035"/>
                                </linearGradient>
                            </defs>
                            <path class="yk-mood__spark-area" d="M4 73 C11.7 69.7 35 55.2 50 53 C65 50.8 78.7 63 94 60 C109.3 57 126.2 37.2 142 35 C157.8 32.8 173.2 49.3 189 47 C204.8 44.7 221.7 24.3 237 21 C252.3 17.7 266.2 29.8 281 27 C295.8 24.2 318.5 7.8 326 4 L326 80 L4 80 Z"/>
                            <path class="yk-mood__spark-line" d="M4 73 C11.7 69.7 35 55.2 50 53 C65 50.8 78.7 63 94 60 C109.3 57 126.2 37.2 142 35 C157.8 32.8 173.2 49.3 189 47 C204.8 44.7 221.7 24.3 237 21 C252.3 17.7 266.2 29.8 281 27 C295.8 24.2 318.5 7.8 326 4"/>
                            <g class="yk-mood__spark-dots">
                                <circle cx="4" cy="73" r="2.6"/><circle cx="50" cy="53" r="3.2"/><circle cx="94" cy="60" r="3.2"/><circle cx="142" cy="35" r="3.2"/><circle cx="189" cy="47" r="3.2"/><circle cx="237" cy="21" r="3.2"/><circle cx="281" cy="27" r="3.2"/><circle cx="326" cy="4" r="3.2"/>
                            </g>
                        </svg>
                    </section>
                </aside>
            </div>
        @endif
    </div>
</main>
@if ($open)
    <script src="{{ asset('frontend/assets/js/yuktione-mood.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-mood.js')) }}" defer></script>
@endif
@endsection
