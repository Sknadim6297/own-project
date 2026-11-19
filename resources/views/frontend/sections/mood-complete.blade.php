@extends('layouts.layout')

@section('title', 'Check-in complete | YuktiOne')
@section('meta_description', 'Thank you for taking a moment for yourself.')
@section('body_class', 'yk-mood-body')

@section('content')
<main class="yk-mood yk-complete yk-tools-ds yk-mood--home yk-mood--done" data-yk-tools-ds data-yk-mood>
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
    <div class="yk-mood__wrap yk-mood__wrap--home">
        <div class="yk-mood__pills yk-complete__pills">
            <span class="yk-mood__pill yk-mood__pill--accent">Free tool</span>
            <span class="yk-mood__pill">
                <svg class="yk-mood__pill-leaf" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4.5 19.5C4 11 9.5 4.6 20.5 3.5 20.3 14 14.6 19.7 4.5 19.5Z" fill="#4caf50"/>
                    <path d="M5.5 18.5C9 14 12.5 10.6 17 7.5" fill="none" stroke="#e8f5e9" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                Psychology &amp; Wellness
            </span>
        </div>

        @include('frontend.sections.partials.mood-progress', ['current' => 'done'])

        <div class="yk-mood__layout">
            <div class="yk-complete__main">
                @include('frontend.sections.partials.emotion-face-defs')

                <div class="yk-mood__panel yk-mood__panel--select yk-complete__panel">
                    <div class="yk-complete__hero">
                        <div class="yk-complete__mark" aria-hidden="true">
                            <svg class="yk-complete__confetti" viewBox="0 0 160 120" focusable="false">
                                <circle cx="22" cy="34" r="4" fill="#f7b84b"/>
                                <circle cx="140" cy="28" r="3.5" fill="#7cc576"/>
                                <circle cx="132" cy="92" r="4" fill="#f28aa8"/>
                                <circle cx="30" cy="96" r="3" fill="#a77be0"/>
                                <rect x="40" y="12" width="9" height="4" rx="2" fill="#a77be0" transform="rotate(-30 44 14)"/>
                                <rect x="112" y="10" width="9" height="4" rx="2" fill="#f28aa8" transform="rotate(35 116 12)"/>
                                <rect x="10" y="62" width="9" height="4" rx="2" fill="#7cc576" transform="rotate(20 14 64)"/>
                                <rect x="142" y="60" width="9" height="4" rx="2" fill="#f7b84b" transform="rotate(-25 146 62)"/>
                                <path d="M118 46l2.2 4.6 4.6 2.2-4.6 2.2-2.2 4.6-2.2-4.6-4.6-2.2 4.6-2.2Z" fill="#d88be8"/>
                                <path d="M40 44l1.8 3.6 3.6 1.8-3.6 1.8-1.8 3.6-1.8-3.6-3.6-1.8 3.6-1.8Z" fill="#f7b84b"/>
                            </svg>
                            <span class="yk-complete__badge">
                                <svg viewBox="0 0 48 48" focusable="false"><path d="M14.5 24.8 21 31.2 34 17.4"/></svg>
                            </span>
                        </div>
                        <h1>Check-in complete!</h1>
                        <p class="yk-mood__lead">Thank you for taking a moment for yourself.</p>
                        @if ($checkedAt)
                            <p class="yk-complete__when">
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.6V12l3 2"/></svg>
                                Saved {{ $checkedAt->format('D, j M Y') }} · {{ $checkedAt->format('g:i A') }}
                            </p>
                        @endif
                    </div>

                    <section class="yk-complete__summary" aria-label="Your check-in">
                        <div class="yk-complete__grid">
                            <div class="yk-complete__block">
                                <h2>Your mood today</h2>
                                <div class="yk-complete__mood-row">
                                    <span class="yk-complete__mood-face" aria-hidden="true">@include('frontend.sections.partials.mood-face', ['mood' => $moodKey])</span>
                                    <p class="yk-complete__mood">{{ $mood }}</p>
                                </div>
                            </div>

                            <div class="yk-complete__block">
                                <h2>Your emotions</h2>
                                @if ($emotions === [])
                                    <p class="yk-complete__empty">No emotions selected.</p>
                                @else
                                    <ul class="yk-complete__chips">
                                        @foreach ($emotions as $emotion)
                                            <li class="yk-complete__chip is-{{ $emotion['tone'] }}">
                                                <span class="yk-complete__chip-face" aria-hidden="true">@include('frontend.sections.partials.emotion-face', ['key' => $emotion['key']])</span>
                                                {{ $emotion['label'] }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>

                        <div class="yk-complete__block yk-complete__block--note">
                            <h2>Your reflection</h2>
                            @if (filled($reflection))
                                <div class="yk-complete__quote">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9.6 6.4C6.4 7.6 4.6 10 4.6 13.4v4.2h5.6v-5.4H7.4c.2-1.9 1.2-3.2 3-3.9ZM19 6.4c-3.2 1.2-5 3.6-5 7v4.2h5.6v-5.4h-2.8c.2-1.9 1.2-3.2 3-3.9Z"/></svg>
                                    <p class="yk-complete__note">{{ $reflection }}</p>
                                </div>
                            @else
                                <p class="yk-complete__empty">No reflection added.</p>
                            @endif
                        </div>
                    </section>
                </div>

                <div class="yk-complete__actions">
                    <a class="yk-mood__continue yk-complete__home" href="{{ route('tools.psychology') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4.5 11.2 12 4.8l7.5 6.4"/><path d="M6.6 9.6v9.4h10.8V9.6"/><path d="M10.2 19v-5h3.6v5"/></svg>
                        Go to Psychology Home
                    </a>
                    <a class="yk-complete__history" href="{{ route('tools.psychology.mood.history') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4.5 12a7.5 7.5 0 1 0 2.2-5.3"/><path d="M4.4 4.6v3.6H8"/><path d="M12 8v4.2l2.8 1.8"/></svg>
                        View Mood History
                    </a>
                </div>
                <nav class="yk-complete__links" aria-label="More tools">
                    <a class="yk-complete__explore" href="{{ route('tools.index') }}">Explore tools <span aria-hidden="true">→</span></a>
                    @include('frontend.sections.partials.tool-back-to-tools', ['class' => 'yk-complete__back'])
                </nav>
            </div>

            <aside class="yk-mood__aside" aria-label="What's next">
                <section class="yk-mood__side-card yk-complete__connect">
                    <span class="yk-mood__side-icon" aria-hidden="true">
                        <svg viewBox="0 0 28 28"><path d="M4.5 7.8a3.3 3.3 0 0 1 3.3-3.3h9.4a3.3 3.3 0 0 1 3.3 3.3v5.4a3.3 3.3 0 0 1-3.3 3.3h-5.6l-4.2 3.4v-3.4a3.3 3.3 0 0 1-2.9-3.3Z"/><path d="M22.2 10.4a2.6 2.6 0 0 1 1.6 2.4v4.6a2.6 2.6 0 0 1-2.4 2.6v2.6l-3.4-2.6h-4.4a2.6 2.6 0 0 1-2-1" opacity=".55"/></svg>
                    </span>
                    @include('frontend.sections.partials.psychology-connect', [
                        'heading' => 'Would you like to talk to someone?',
                        'copy' => 'A supportive conversation with a psychologist can help you explore your feelings and thoughts.',
                        'cta' => 'Connect with a Psychologist',
                        'url' => $psychologistsUrl,
                        'headingId' => 'mood-connect-heading',
                    ])
                </section>

                <section class="yk-mood__side-card">
                    <header class="yk-mood__side-head">
                        <span class="yk-mood__side-icon" aria-hidden="true">
                            <svg viewBox="0 0 28 28"><rect x="5" y="14" width="4.4" height="8.5" rx="1.2"/><rect x="11.8" y="9.5" width="4.4" height="13" rx="1.2"/><rect x="18.6" y="5.5" width="4.4" height="17" rx="1.2"/></svg>
                        </span>
                        <div>
                            <h2>Your Mood Journey</h2>
                            <p class="yk-mood__side-lead">All four steps done today.</p>
                        </div>
                    </header>
                    <ol class="yk-mood__journey">
                        <li class="is-done">
                            <span class="yk-mood__journey-dot" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6.6 12.4l3.6 3.6 7.2-7.6"/></svg></span>
                            <span>
                                <strong>Step 1: Mood</strong>
                                <span>Mood chosen.</span>
                            </span>
                        </li>
                        <li class="is-done">
                            <span class="yk-mood__journey-dot" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6.6 12.4l3.6 3.6 7.2-7.6"/></svg></span>
                            <span>
                                <strong>Step 2: Emotions</strong>
                                <span>Emotions noted.</span>
                            </span>
                        </li>
                        <li class="is-done">
                            <span class="yk-mood__journey-dot" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6.6 12.4l3.6 3.6 7.2-7.6"/></svg></span>
                            <span>
                                <strong>Step 3: Write</strong>
                                <span>{{ filled($reflection) ? 'Note added.' : 'Skipped — that\'s okay.' }}</span>
                            </span>
                        </li>
                        <li class="is-done is-current" aria-current="step">
                            <span class="yk-mood__journey-dot" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6.6 12.4l3.6 3.6 7.2-7.6"/></svg></span>
                            <span>
                                <strong>Step 4: Done</strong>
                                <span>Check-in saved.</span>
                            </span>
                        </li>
                    </ol>
                </section>
            </aside>
        </div>
    </div>
</main>
@endsection
