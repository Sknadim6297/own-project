@extends('layouts.layout')

@section('title', 'Daily Mood Check-in | YuktiOne')
@section('meta_description', 'Choose the option that best describes how you feel right now.')
@section('body_class', 'yk-mood-body')

@section('content')
<main class="yk-mood yk-tools-ds @if ($open) yk-mood--home @endif" data-yk-tools-ds data-yk-mood>
    @if ($open)
        <div class="yk-mood__decor" aria-hidden="true">
            <span class="yk-mood__glow yk-mood__glow--top"></span>
            <span class="yk-mood__glow yk-mood__glow--right"></span>
            <span class="yk-mood__glow yk-mood__glow--left"></span>
            <span class="yk-mood__glow yk-mood__glow--bottom-left"></span>
            <svg class="yk-mood__leaf" viewBox="0 0 120 230" focusable="false">
                <path class="yk-mood__leaf-wave" d="M0 150c26 6 52 24 72 46 14 15 30 26 48 34H0Z"/>
                <path class="yk-mood__leaf-stem" d="M-6 214C8 170 26 120 56 22"/>
                <path d="M54 26c-2-15 6-28 24-34 3 18-4 30-24 34Z"/>
                <path d="M44 62C30 48 14 46 0 52c12 14 26 18 44 10Z"/>
                <path d="M46 66c14-14 34-20 54-14-12 16-30 21-54 14Z"/>
                <path d="M32 104C18 92 4 90-8 98c12 12 24 14 40 6Z"/>
                <path d="M36 108c16-12 36-14 54-6-14 14-32 16-54 6Z"/>
                <path d="M22 148C10 138-2 138-12 146c10 10 22 12 34 2Z"/>
                <path d="M26 150c14-8 30-8 44 0-12 10-28 11-44 0Z"/>
            </svg>
        </div>
    @endif
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
            <header class="yk-mood__hero">
                <div class="yk-mood__hero-copy">
                    <div class="yk-mood__pills">
                        <span class="yk-mood__pill yk-mood__pill--accent">Free tool</span>
                        <span class="yk-mood__pill">
                            <svg class="yk-mood__pill-leaf" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4.5 19.5C4 11 9.5 4.6 20.5 3.5 20.3 14 14.6 19.7 4.5 19.5Z" fill="#4caf50"/>
                                <path d="M5.5 18.5C9 14 12.5 10.6 17 7.5" fill="none" stroke="#e8f5e9" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            Psychology &amp; Wellness
                        </span>
                    </div>
                    <h1><x-yuktione.two-tone text="Daily Mood Check" /></h1>
                    <p class="yk-mood__intro">A simple 4-step check-in to understand your current mood, identify your emotions, and create a short reflection for a healthier, more balanced you.</p>
                    <ul class="yk-mood__perks">
                        <li>
                            <span class="yk-mood__perk-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M13.6 2.6 5.2 13.4h5.9l-1.4 8 8.9-11.3h-6l1-7.5Z"/></svg>
                            </span>
                            <span><span class="yk-mood__perk-more">Just </span>2–3 mins</span>
                        </li>
                        <li>
                            <span class="yk-mood__perk-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M8.2 10.2V7.8a3.8 3.8 0 0 1 7.6 0v2.4" fill="none" stroke="currentColor" stroke-width="2.3"/><rect x="5.2" y="10" width="13.6" height="11" rx="2.6"/><circle cx="12" cy="15" r="1.6" fill="#fff"/><path d="M12 15.5v2.3" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/></svg>
                            </span>
                            <span><span class="yk-mood__perk-more">Private &amp; </span>Secure</span>
                        </li>
                        <li>
                            <span class="yk-mood__perk-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><rect x="4" y="14.5" width="3.6" height="6" rx="1"/><rect x="9.2" y="11" width="3.6" height="9.5" rx="1"/><rect x="14.4" y="7.5" width="3.6" height="13" rx="1"/><path d="M15.2 4.2h4.6v4.6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <span>Track<span class="yk-mood__perk-more"> Your Mood</span></span>
                        </li>
                    </ul>
                </div>
                <div class="yk-mood__hero-visual" aria-hidden="true">
                    <svg class="yk-mood__hero-blob" viewBox="0 0 540 230" preserveAspectRatio="none" focusable="false">
                        <defs>
                            <linearGradient id="yk-mood-hero-blob" x1="0" x2="1" y1="0" y2="1">
                                <stop offset="0" stop-color="#f3ebe6"/>
                                <stop offset="0.55" stop-color="#f6efe9"/>
                                <stop offset="1" stop-color="#f5f1eb"/>
                            </linearGradient>
                        </defs>
                        <path d="M22 132C6 92 30 52 86 40c52-11 92 6 142-14 50-20 96-34 166-22 72 12 112 44 108 96-3 36 22 54 30 82 4 18-6 34-26 46H72C34 222 38 172 22 132Z" fill="url(#yk-mood-hero-blob)"/>
                    </svg>
                    <img
                        class="yk-mood__hero-art"
                        src="{{ asset('frontend/assets/img/' . rawurlencode('Dreamy Woman at a Cozy Desk.png')) }}"
                        width="1536"
                        height="1024"
                        alt=""
                        decoding="async"
                        fetchpriority="high"
                    >
                    <svg class="yk-mood__hero-spark" viewBox="0 0 28 28" focusable="false">
                        <path d="M9.6 10.4 12.4 3.6M13.6 13.2l8.2-5M14.6 18.2l9.4 1.6"/>
                    </svg>
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
                                <p class="yk-mood__lead">Choose the option that best describes how you feel right now.</p>
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
                                <p class="yk-mood__side-lead">Build a healthier and happier you.</p>
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
