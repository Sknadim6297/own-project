@extends('layouts.layout')

@section('title', 'Daily Mood Check-in | YuktiOne')
@section('meta_description', 'Sometimes writing down what is on your mind can help.')
@section('body_class', 'yk-mood-body')

@section('content')
<main class="yk-mood yk-tools-ds @if ($open) yk-mood--home yk-mood--write @endif" data-yk-tools-ds data-yk-mood>
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
        <a class="yk-mood__back" href="{{ route('tools.psychology.mood.emotions') }}">
            <span aria-hidden="true">←</span> Back
        </a>

        @if (!$open)
            <section class="yk-mood__closed" aria-labelledby="reflection-closed-heading">
                <p class="yk-mood__badge">Free tool</p>
                <h1 id="reflection-closed-heading">Would you like to share more?</h1>
                <p>This check-in is unavailable right now.</p>
            </section>
        @else
            <div class="yk-mood__pills yk-reflect__pills">
                <span class="yk-mood__pill yk-mood__pill--accent">Free tool</span>
                <span class="yk-mood__pill">
                    <svg class="yk-mood__pill-leaf" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4.5 19.5C4 11 9.5 4.6 20.5 3.5 20.3 14 14.6 19.7 4.5 19.5Z" fill="#4caf50"/>
                        <path d="M5.5 18.5C9 14 12.5 10.6 17 7.5" fill="none" stroke="#e8f5e9" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    Psychology &amp; Wellness
                </span>
            </div>

            @include('frontend.sections.partials.mood-progress', ['current' => 'reflection'])

            <div class="yk-mood__layout">
                <form class="yk-mood__form yk-reflect__form" method="post" action="{{ route('tools.psychology.mood.reflection.store') }}" data-reflection-form data-reflection-limit="{{ $limit }}">
                    @csrf
                    @include('frontend.sections.partials.emotion-face-defs')

                    <div class="yk-mood__panel yk-mood__panel--select yk-reflect__panel">
                        <div class="yk-mood__heading">
                            <span class="yk-mood__heading-icon" aria-hidden="true">
                                <svg viewBox="0 0 40 40" focusable="false"><rect x="7" y="6" width="20" height="28" rx="4"/><path d="M12.5 14h9M12.5 19.5h9M12.5 25h5"/><path class="yk-reflect__heading-pen" d="M34.6 15.6 25 25.2l-1.2 4.6 4.6-1.2 9.6-9.6a2.4 2.4 0 0 0-3.4-3.4Z"/></svg>
                            </span>
                            <div>
                                <h1>Would you like to share more?</h1>
                                <p class="yk-mood__lead">Sometimes writing down what's on your mind can help.</p>
                                <p class="yk-mood__hint">There are no right or wrong answers — write as much or as little as you like.</p>
                            </div>
                        </div>

                        @if ($held)
                            <p class="yk-reflect__progress" role="status">This check-in is still in progress.</p>
                        @endif

                        @if ($errors->any())
                            <p class="yk-mood__error" role="alert">{{ $errors->first() }}</p>
                        @endif

                        <label class="yk-reflect__label" for="mood-reflection">
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 19h3.6L18.4 9.2a2.3 2.3 0 0 0-3.6-3.6L5 15.4V19Z"/><path d="M13.4 7l3.6 3.6"/></svg>
                            What made you feel this way today?
                        </label>
                        <div class="yk-reflect__field">
                            <textarea id="mood-reflection" class="yk-reflect__input" name="reflection" rows="5" maxlength="{{ $limit }}" placeholder="Write anything you would like to share..." data-reflection-input aria-describedby="reflection-meta reflection-count">{{ $reflection }}</textarea>
                            <div class="yk-reflect__meta" id="reflection-meta">
                                <span>Optional · Up to 1,000 characters</span>
                                <span class="yk-reflect__count" id="reflection-count" data-reflection-count>{{ mb_strlen($reflection) }} / 1,000</span>
                            </div>
                        </div>

                        <h2 class="yk-reflect__prompts-title">
                            <span class="yk-reflect__prompts-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false"><path d="M12 3.4a6.2 6.2 0 0 0-3.6 11.3c.6.4 1 1.1 1 1.8v.9h5.2v-.9c0-.7.4-1.4 1-1.8A6.2 6.2 0 0 0 12 3.4Z" fill="#ffd54f" stroke="#e0a21c" stroke-width="1.2"/><path d="M9.8 19.6h4.4M10.6 21.6h2.8" stroke="#8a7a9a" stroke-width="1.5" stroke-linecap="round"/></svg>
                            </span>
                            Need a little inspiration?
                        </h2>
                        <div class="yk-reflect__prompts">
                            @foreach ($prompts as $prompt)
                                <button class="yk-reflect__prompt is-tone-{{ $loop->index % 3 }}" type="button" data-reflection-prompt="{{ $prompt }}">
                                    <span class="yk-reflect__prompt-icon" aria-hidden="true">
                                        @switch($loop->index % 3)
                                            @case(0)
                                                <svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="4.6" fill="#ffc531"/><path d="M12 2.8v2.4M12 18.8v2.4M2.8 12h2.4M18.8 12h2.4M5.5 5.5l1.7 1.7M16.8 16.8l1.7 1.7M5.5 18.5l1.7-1.7M16.8 7.2l1.7-1.7" stroke="#f4a522" stroke-width="1.6" stroke-linecap="round"/></svg>
                                                @break
                                            @case(1)
                                                <svg viewBox="0 0 24 24" focusable="false"><path d="M7 18h10.4a4 4 0 0 0 .5-8A6 6 0 0 0 6.5 9a4.5 4.5 0 0 0 .5 9Z" fill="#c7cfe0"/><path d="M9 21.2l.6-1.4M12.4 21.2l.6-1.4M15.8 21.2l.6-1.4" stroke="#6f95d6" stroke-width="1.5" stroke-linecap="round"/></svg>
                                                @break
                                            @default
                                                <svg viewBox="0 0 24 24" focusable="false"><path d="M12 3.2l2.3 5.6 6 .5-4.6 3.9 1.4 5.9L12 16l-5.1 3.1 1.4-5.9-4.6-3.9 6-.5Z" fill="#b57ad9"/></svg>
                                        @endswitch
                                    </span>
                                    <span class="yk-reflect__prompt-text">{{ $prompt }}</span>
                                    <span class="yk-reflect__prompt-add" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M12 6v12M6 12h12"/></svg></span>
                                </button>
                            @endforeach
                        </div>
                        <div class="yk-reflect__confirm" data-prompt-confirm hidden>
                            <p>Add this prompt below what you have written?</p>
                            <div class="yk-reflect__confirm-actions">
                                <button class="yk-reflect__add" type="button" data-prompt-add>Add prompt</button>
                                <button class="yk-reflect__keep" type="button" data-prompt-cancel>Keep my writing</button>
                            </div>
                        </div>
                    </div>

                    <div class="yk-mood__actions yk-reflect__actions">
                        <button class="yk-mood__continue" type="submit" name="action" value="save">Save Check-in <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 12.6l4 3.9 8-8.6"/></svg></button>
                        <button class="yk-reflect__skip" type="submit" name="action" value="skip">Skip</button>
                    </div>
                </form>

                <aside class="yk-mood__aside" aria-label="About this check-in">
                    <section class="yk-mood__side-card yk-reflect__summary">
                        <header class="yk-mood__side-head">
                            <span class="yk-mood__side-icon" aria-hidden="true">
                                <svg viewBox="0 0 28 28"><path d="M14 23.4s-8.6-5.1-8.6-11.4a4.6 4.6 0 0 1 8.6-2.4 4.6 4.6 0 0 1 8.6 2.4c0 6.3-8.6 11.4-8.6 11.4Z"/></svg>
                            </span>
                            <div>
                                <h2>Your check-in so far</h2>
                                <p class="yk-mood__side-lead">A quick look at what you picked.</p>
                            </div>
                        </header>

                        <div class="yk-reflect__picked">
                            <div class="yk-reflect__picked-row">
                                <span class="yk-reflect__picked-face" aria-hidden="true">@include('frontend.sections.partials.mood-face', ['mood' => $moodKey])</span>
                                <span class="yk-reflect__picked-text">
                                    <small>Mood</small>
                                    <strong>{{ $moodLabel ?? '—' }}</strong>
                                </span>
                                <a class="yk-reflect__edit" href="{{ route('tools.psychology.mood') }}" aria-label="Change mood">Change</a>
                            </div>

                            <div class="yk-reflect__picked-block">
                                <div class="yk-reflect__picked-head">
                                    <small>Emotions</small>
                                    <a class="yk-reflect__edit" href="{{ route('tools.psychology.mood.emotions') }}" aria-label="Change emotions">Change</a>
                                </div>
                                @if (count($emotionChips))
                                    <ul class="yk-reflect__tags">
                                        @foreach ($emotionChips as $chip)
                                            <li class="yk-reflect__tag is-{{ $chip['tone'] }}">
                                                <span class="yk-reflect__tag-face" aria-hidden="true">@include('frontend.sections.partials.emotion-face', ['key' => $chip['key']])</span>
                                                {{ $chip['label'] }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="yk-reflect__none">None picked — that's okay.</p>
                                @endif
                            </div>
                        </div>
                    </section>

                    <section class="yk-mood__side-card">
                        <header class="yk-mood__side-head">
                            <span class="yk-mood__side-icon" aria-hidden="true">
                                <svg viewBox="0 0 28 28"><rect x="5" y="14" width="4.4" height="8.5" rx="1.2"/><rect x="11.8" y="9.5" width="4.4" height="13" rx="1.2"/><rect x="18.6" y="5.5" width="4.4" height="17" rx="1.2"/></svg>
                            </span>
                            <div>
                                <h2>Your Mood Journey</h2>
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
                            <li class="is-done">
                                <span class="yk-mood__journey-dot" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6.6 12.4l3.6 3.6 7.2-7.6"/></svg></span>
                                <span>
                                    <strong>Step 2: Emotions</strong>
                                    <span>Emotions noted.</span>
                                </span>
                            </li>
                            <li class="is-current" aria-current="step">
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
