@extends('layouts.layout')

@section('title', 'Mood history | YuktiOne')
@section('meta_description', 'A private record of the moments you checked in.')
@section('body_class', 'yk-mood-body')

@section('content')
<main class="yk-mood yk-history yk-tools-ds yk-mood--home yk-mood--log" data-yk-tools-ds data-yk-mood>
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
        <a class="yk-mood__back" href="{{ route('tools.psychology') }}">
            <span aria-hidden="true">←</span> Psychology tools
        </a>

        <div class="yk-mood__pills yk-history__pills">
            <span class="yk-mood__pill yk-mood__pill--accent">Private to you</span>
            <span class="yk-mood__pill">
                <svg class="yk-mood__pill-leaf" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4.5 19.5C4 11 9.5 4.6 20.5 3.5 20.3 14 14.6 19.7 4.5 19.5Z" fill="#4caf50"/>
                    <path d="M5.5 18.5C9 14 12.5 10.6 17 7.5" fill="none" stroke="#e8f5e9" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                Psychology &amp; Wellness
            </span>
        </div>

        <div class="yk-mood__layout yk-history__layout">
            <div class="yk-history__main">
                @include('frontend.sections.partials.emotion-face-defs')

                <section class="yk-mood__panel yk-mood__panel--select yk-history__panel" aria-labelledby="mood-history-heading">
                    <div class="yk-mood__heading">
                        <span class="yk-mood__heading-icon" aria-hidden="true">
                            <svg viewBox="0 0 40 40" focusable="false"><rect x="6" y="8.5" width="28" height="25" rx="5"/><path d="M6 16h28M13.5 5.5v6M26.5 5.5v6"/><circle cx="14" cy="23" r="1.8"/><circle cx="20" cy="23" r="1.8"/><circle cx="26" cy="23" r="1.8"/><circle cx="14" cy="28.4" r="1.8"/></svg>
                        </span>
                        <div>
                            <h1 id="mood-history-heading"><x-yuktione.two-tone text="Mood history" /></h1>
                            <p class="yk-mood__lead">A private record of the moments you checked in.</p>
                            @if ($items !== [])
                                <p class="yk-mood__hint">Newest first · {{ $entries->total() }} {{ \Illuminate\Support\Str::plural('check-in', $entries->total()) }} saved</p>
                            @endif
                        </div>
                    </div>

                    @if ($items === [])
                        <div class="yk-history__empty">
                            <img src="{{ asset('frontend/assets/img/' . rawurlencode('Cozy Lavender Portrait with Mug and Plant.png')) }}" alt="" width="1278" height="1230" decoding="async">
                            <h2>No check-ins yet</h2>
                            <p>When you finish a check-in, it will appear here.</p>
                            <a class="yk-mood__continue" href="{{ route('tools.psychology.mood') }}">Start a check-in <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4.5 12h14.5M13.6 6.4 19.2 12l-5.6 5.6"/></svg></a>
                        </div>
                    @else
                        <ol class="yk-history__list">
                            @foreach ($items as $item)
                                <li class="yk-history__card is-mood-{{ $item['moodKey'] }}">
                                    <span class="yk-history__face" aria-hidden="true">@include('frontend.sections.partials.mood-face', ['mood' => $item['moodKey']])</span>
                                    <div class="yk-history__body">
                                        <div class="yk-history__top">
                                            <h2>{{ $item['mood'] }}</h2>
                                            <time datetime="{{ $item['datetime'] }}">
                                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.6V12l3 2"/></svg>
                                                {{ $item['when'] }}
                                            </time>
                                        </div>
                                        @if ($item['emotions'] === [])
                                            <p class="yk-history__muted">No emotions selected.</p>
                                        @else
                                            <ul class="yk-history__chips">
                                                @foreach ($item['emotions'] as $emotion)
                                                    <li class="yk-history__chip is-{{ $emotion['tone'] }}">
                                                        <span class="yk-history__chip-face" aria-hidden="true">@include('frontend.sections.partials.emotion-face', ['key' => $emotion['key']])</span>
                                                        {{ $emotion['label'] }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        @if (filled($item['reflection']))
                                            <p class="yk-history__note">{{ $item['reflection'] }}</p>
                                        @else
                                            <p class="yk-history__muted">No reflection added.</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>

                        @if ($entries->hasPages())
                            <nav class="yk-history__pages" aria-label="Mood history pages">
                                @if ($entries->previousPageUrl())
                                    <a href="{{ $entries->previousPageUrl() }}"><span aria-hidden="true">←</span> Newer</a>
                                @else
                                    <span></span>
                                @endif
                                <span class="yk-history__page-label">Page {{ $entries->currentPage() }} of {{ $entries->lastPage() }}</span>
                                @if ($entries->nextPageUrl())
                                    <a href="{{ $entries->nextPageUrl() }}">Older <span aria-hidden="true">→</span></a>
                                @else
                                    <span></span>
                                @endif
                            </nav>
                        @endif
                    @endif
                </section>

                <nav class="yk-complete__links" aria-label="More tools">
                    <a class="yk-complete__explore" href="{{ route('tools.psychology') }}">Psychology Home <span aria-hidden="true">→</span></a>
                    @include('frontend.sections.partials.tool-back-to-tools', ['class' => 'yk-complete__back'])
                </nav>
            </div>

            <aside class="yk-mood__aside" aria-label="Your check-ins at a glance">
                @if ($stats)
                    <section class="yk-mood__side-card yk-history__stats">
                        <header class="yk-mood__side-head">
                            <span class="yk-mood__side-icon" aria-hidden="true">
                                <svg viewBox="0 0 28 28"><rect x="5" y="14" width="4.4" height="8.5" rx="1.2"/><rect x="11.8" y="9.5" width="4.4" height="13" rx="1.2"/><rect x="18.6" y="5.5" width="4.4" height="17" rx="1.2"/></svg>
                            </span>
                            <div>
                                <h2><x-yuktione.two-tone text="At a glance" /></h2>
                                <p class="yk-mood__side-lead">Based on all your saved check-ins.</p>
                            </div>
                        </header>

                        <dl class="yk-history__numbers">
                            <div>
                                <dt>Total</dt>
                                <dd>{{ $stats['total'] }}</dd>
                            </div>
                            <div>
                                <dt>Last 7 days</dt>
                                <dd>{{ $stats['week'] }}</dd>
                            </div>
                        </dl>

                        @if ($stats['top'])
                            <div class="yk-history__top-mood">
                                <span class="yk-history__top-face" aria-hidden="true">@include('frontend.sections.partials.mood-face', ['mood' => $stats['top']['key']])</span>
                                <span>
                                    <small>Most frequent mood</small>
                                    <strong>{{ $stats['top']['label'] }}</strong>
                                </span>
                                <span class="yk-history__top-count">{{ $stats['top']['count'] }}×</span>
                            </div>
                        @endif

                        @if ($stats['mix'] !== [])
                            <div class="yk-history__mix">
                                <p class="yk-history__mix-title">Mood mix</p>
                                <div class="yk-history__mix-bar" role="img" aria-label="Mood mix: @foreach ($stats['mix'] as $part){{ $part['label'] }} {{ $part['count'] }}{{ $loop->last ? '' : ', ' }}@endforeach">
                                    @foreach ($stats['mix'] as $part)
                                        <span class="is-mood-{{ $part['key'] }}" style="flex-grow: {{ $part['count'] }}"></span>
                                    @endforeach
                                </div>
                                <ul class="yk-history__legend">
                                    @foreach ($stats['mix'] as $part)
                                        <li class="is-mood-{{ $part['key'] }}"><span aria-hidden="true"></span>{{ $part['label'] }} <b>{{ $part['count'] }}</b></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </section>
                @endif

                <section class="yk-mood__side-card yk-history__cta">
                    <span class="yk-mood__side-icon" aria-hidden="true">
                        <svg viewBox="0 0 28 28"><path d="M14 23.4s-8.6-5.1-8.6-11.4a4.6 4.6 0 0 1 8.6-2.4 4.6 4.6 0 0 1 8.6 2.4c0 6.3-8.6 11.4-8.6 11.4Z"/></svg>
                    </span>
                    <h2><x-yuktione.two-tone text="How are you feeling today?" /></h2>
                    <p class="yk-mood__side-lead">Pick a mood, name your emotions and add a note if you like.</p>
                    <a class="yk-mood__continue" href="{{ route('tools.psychology.mood') }}">New check-in <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4.5 12h14.5M13.6 6.4 19.2 12l-5.6 5.6"/></svg></a>
                </section>
            </aside>
        </div>
    </div>
</main>
@endsection
