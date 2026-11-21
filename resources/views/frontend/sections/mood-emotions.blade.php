@extends('layouts.layout')

@section('title', 'Daily Mood Check-in | YuktiOne')
@section('meta_description', 'Choose up to 6 emotions that describe how you feel right now.')
@section('body_class', 'yk-mood-body')

@section('content')
@php
    $groupLabels = [
        'positive' => 'Positive',
        'neutral' => 'Neutral',
        'difficult' => 'Difficult',
    ];
@endphp
<main class="yk-mood yk-tools-ds @if ($open) yk-mood--home yk-mood--emotions @endif" data-yk-tools-ds data-yk-mood>
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
        <a class="yk-mood__back" href="{{ route('tools.psychology.mood') }}">
            <span aria-hidden="true">←</span> Back
        </a>

        @if (!$open)
            <section class="yk-mood__closed" aria-labelledby="emotion-closed-heading">
                <p class="yk-mood__badge">Free tool</p>
                <h1 id="emotion-closed-heading"><x-yuktione.two-tone text="What emotions are you feeling?" /></h1>
                <p>This check-in is unavailable right now.</p>
            </section>
        @else
            <div class="yk-mood__pills yk-emotion__pills">
                <span class="yk-mood__pill yk-mood__pill--accent">Free tool</span>
                <span class="yk-mood__pill">
                    <svg class="yk-mood__pill-leaf" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4.5 19.5C4 11 9.5 4.6 20.5 3.5 20.3 14 14.6 19.7 4.5 19.5Z" fill="#4caf50"/>
                        <path d="M5.5 18.5C9 14 12.5 10.6 17 7.5" fill="none" stroke="#e8f5e9" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    Psychology &amp; Wellness
                </span>
            </div>

            @include('frontend.sections.partials.mood-progress', ['current' => 'emotions'])

            <div class="yk-mood__layout">
                <form class="yk-mood__form yk-emotion__form" method="post" action="{{ route('tools.psychology.mood.emotions.store') }}" data-emotion-form data-emotion-limit="{{ $limit }}">
                    @csrf
                    @include('frontend.sections.partials.emotion-face-defs')

                    <div class="yk-mood__panel yk-mood__panel--select yk-emotion__panel">
                        <div class="yk-mood__heading">
                            <span class="yk-mood__heading-icon" aria-hidden="true">
                                <svg viewBox="0 0 36 36"><path d="M18 30.4S6.4 23.6 6.4 15.4A6.1 6.1 0 0 1 18 12.2a6.1 6.1 0 0 1 11.6 3.2C29.6 23.6 18 30.4 18 30.4Z"/><path class="yk-emotion__heading-spark" d="M27.6 4.6v4.2M25.5 6.7h4.2M8 5.6v3M6.5 7.1h3"/></svg>
                            </span>
                            <div>
                                <h1><x-yuktione.two-tone text="What emotions are you feeling?" /></h1>
                                <p class="yk-mood__lead">Choose up to 6 emotions that describe how you feel right now.</p>
                                <p class="yk-mood__hint">Optional — pick what fits. You can continue with none selected.</p>
                            </div>
                        </div>

                        @if ($errors->any())
                            <p class="yk-mood__error" role="alert">{{ $errors->first() }}</p>
                        @endif

                        @foreach ($groupLabels as $bucket => $groupLabel)
                            <fieldset class="yk-emotion__group is-{{ $bucket }}">
                                <legend>
                                    <span class="yk-emotion__group-icon" aria-hidden="true">
                                        @switch($bucket)
                                            @case('positive')
                                                <svg viewBox="0 0 24 24" focusable="false"><path d="M20.8 3.2C11.2 3.4 4.4 8.2 4.2 16c0 1.4.3 2.8.9 3.9 1.2-4.8 4.6-8.8 9.4-11.1-3.8 2.8-6.6 6.7-7.8 11.5 1 .6 2.3.9 3.6.9 7.7-.2 10.7-8.2 10.5-18Z" fill="#6cb52c"/><path d="M14.5 8.8c-3.8 2.8-6.6 6.7-7.8 11.5" fill="none" stroke="#3f8a14" stroke-width="1.1" stroke-linecap="round"/></svg>
                                                @break
                                            @case('neutral')
                                                <svg viewBox="0 0 24 24" focusable="false"><g fill="none" stroke="#8a929c" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4.2v15.6M7.4 20.2h9.2M4.4 6.6h15.2" stroke-width="1.7"/><path d="M4.4 6.6 2.2 12.6M4.4 6.6l2.2 6M19.6 6.6l-2.2 6M19.6 6.6l2.2 6" stroke-width="1.1"/></g><circle cx="12" cy="4.4" r="1.4" fill="#d4a63a"/><path d="M1.6 12.6h5.6a2.8 2.8 0 0 1-5.6 0ZM16.8 12.6h5.6a2.8 2.8 0 0 1-5.6 0Z" fill="#e9bb4c" stroke="#c99a2e" stroke-width=".8"/></svg>
                                                @break
                                            @default
                                                <svg viewBox="0 0 24 24" focusable="false"><path d="M6.6 15.6h11a3.9 3.9 0 0 0 .4-7.8A5.6 5.6 0 0 0 7.3 6.9a4.4 4.4 0 0 0-.7 8.7Z" fill="#3471b4"/><path d="M8.6 9.2a3.6 3.6 0 0 1 3.4-2" fill="none" stroke="#8fb8e6" stroke-width="1.1" stroke-linecap="round"/><path d="M12.8 13.2 9.8 18h2.7l-1.5 4.4 4.4-5.9h-2.7l1.5-3.3Z" fill="#ffc531"/></svg>
                                        @endswitch
                                    </span>
                                    {{ $groupLabel }}
                                </legend>
                                <div class="yk-emotion__chips">
                                    @foreach ($groups[$bucket] as $emotion)
                                        <label class="yk-emotion__chip is-{{ $bucket }} is-emotion-{{ $emotion['key'] }} @if (in_array($emotion['key'], $selected, true)) is-selected @endif" data-emotion-chip @if (in_array($emotion['key'], $selected, true)) data-selected="true" @endif>
                                            <input class="yk-emotion__input" type="checkbox" name="emotions[]" value="{{ $emotion['key'] }}" @checked(in_array($emotion['key'], $selected, true))>
                                            <span class="yk-emotion__emoji" aria-hidden="true">@include('frontend.sections.partials.emotion-face', ['key' => $emotion['key']])</span>
                                            <span class="yk-emotion__label">{{ $emotion['label'] }}</span>
                                            <span class="yk-emotion__check" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M6.6 12.4l3.6 3.6 7.2-7.6"/></svg></span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach

                        <div class="yk-emotion__footer">
                            <p class="yk-emotion__count" data-emotion-count aria-live="polite">{{ count($selected) }} of {{ $limit }} selected</p>
                            <button class="yk-emotion__clear" type="button" data-emotion-clear @disabled(count($selected) === 0)>
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M19 12a7 7 0 1 1-2.05-4.95"/><path d="M19.2 4.6v3.6h-3.6"/></svg>
                                Clear all
                            </button>
                        </div>
                    </div>

                    <button class="yk-mood__continue" type="submit">Continue <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4.5 12h14.5M13.6 6.4 19.2 12l-5.6 5.6"/></svg></button>
                </form>

                <aside class="yk-mood__aside" aria-label="About this check-in">
                    <section class="yk-mood__side-card yk-emotion__art-card">
                        <div class="yk-emotion__art" aria-hidden="true">
                            <img src="{{ asset('frontend/assets/img/' . rawurlencode('Cozy Lavender Portrait with Mug and Plant.png')) }}" alt="" width="1278" height="1230" decoding="async">
                        </div>
                        <h2><x-yuktione.two-tone text="Name it to tame it" /></h2>
                        <p class="yk-mood__side-lead">Putting feelings into words can make them feel lighter and easier to understand.</p>
                    </section>

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
                            <li class="is-done">
                                <span class="yk-mood__journey-dot" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6.6 12.4l3.6 3.6 7.2-7.6"/></svg></span>
                                <span>
                                    <strong>Step 1: Mood</strong>
                                    <span>Saved — nice start.</span>
                                </span>
                            </li>
                            <li class="is-current" aria-current="step">
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
                                    <span>A short note (optional).</span>
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
                </aside>
            </div>
        @endif
    </div>
</main>
@if ($open)
<script src="{{ asset('frontend/assets/js/yuktione-mood.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-mood.js')) }}" defer></script>
@endif
@endsection
