@extends('layouts.layout')

@section('title', 'Career Interest Test | YuktiOne')
@section('meta_description', 'Understand your interests, discover your natural preferences and explore career possibilities.')
@section('body_class', 'yk-cit-body')

@php
    $icon = fn (string $name, int $size = 18) => view('frontend.sections.career-interest.partials.icon', ['name' => $name, 'size' => $size])->render();
    $areas = \App\Support\CareerInterest\CareerInterestCatalog::categories();
@endphp

@section('content')
<main class="yk-cit yk-tools-ds" data-yk-tools-ds data-yk-cit>
    <div class="yk-cit__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')

        <section class="yk-cit__card yk-cit__card--hero" aria-labelledby="cit-welcome-heading">
            <div class="yk-cit__hero-grid">
                <div class="yk-cit__hero-copy">
                    <p class="yk-cit__badge">{!! $icon('compass', 14) !!} Career tool</p>
                    <h1 id="cit-welcome-heading">Career Interest Test</h1>
                    <p class="yk-cit__lead">Discover which activities you naturally enjoy across six interest areas — a self-rating of preferences, not aptitude or guaranteed career fit.</p>

                    <div class="yk-cit__stats" role="list">
                        <div class="yk-cit__stat" role="listitem">{!! $icon('list', 18) !!}<strong>{{ \App\Support\CareerInterest\CareerInterestCatalog::questionCount() }}</strong><span>Questions</span></div>
                        <div class="yk-cit__stat" role="listitem">{!! $icon('clock', 18) !!}<strong>~5 min</strong><span>Duration</span></div>
                        <div class="yk-cit__stat" role="listitem">{!! $icon('grid', 18) !!}<strong>6</strong><span>Interest areas</span></div>
                    </div>

                    @if (! $open)
                        <p class="yk-cit__error">This assessment is unavailable right now.</p>
                    @else
                        <div class="yk-cit__actions yk-cit__actions--flush">
                            <form method="post" action="{{ route('tools.career-interest.start') }}">
                                @csrf
                                <button class="yk-cit__btn" type="submit">{{ $hasDraft ? 'Continue Assessment' : 'Start Assessment' }} {!! $icon('arrow', 18) !!}</button>
                            </form>
                            @auth('user')
                                <a class="yk-cit__btn-secondary" href="{{ route('tools.career-interest.history') }}">{!! $icon('history', 18) !!} View History</a>
                            @endauth
                        </div>
                        <p class="yk-cit__meta">Free · Save results to your YuktiOne profile when you are ready</p>
                    @endif
                </div>
                <div class="yk-cit__hero-art" aria-hidden="true">
                    <img src="{{ asset('frontend/assets/img/tools-illustrations/career-interest-3d.webp') }}" alt="" width="330" height="330" loading="eager" decoding="async">
                </div>
            </div>
        </section>

        <section class="yk-cit__card" aria-labelledby="cit-how-heading">
            <h2 class="yk-cit__section-title" id="cit-how-heading" style="margin-top:0">How it works</h2>
            <ol class="yk-cit__how">
                <li>
                    <span class="yk-cit__how-num">1</span>
                    <div><strong>Tell us about you</strong><p>Age group, education and what you are looking for — it takes under a minute.</p></div>
                </li>
                <li>
                    <span class="yk-cit__how-num">2</span>
                    <div><strong>Rate six interest areas</strong><p>Three short statements per area. Choose how much you enjoy each activity.</p></div>
                </li>
                <li>
                    <span class="yk-cit__how-num">3</span>
                    <div><strong>See your profile</strong><p>Get your interest profile and career areas worth exploring. Save it whenever you like.</p></div>
                </li>
            </ol>
        </section>

        <section class="yk-cit__card" aria-labelledby="cit-areas-heading">
            <h2 class="yk-cit__section-title" id="cit-areas-heading" style="margin-top:0">The six interest areas</h2>
            <ul class="yk-cit__areas">
                @foreach ($areas as $area)
                    <li class="yk-cit__area">
                        <span class="yk-cit__area-icon">{!! $icon($area['icon'], 20) !!}</span>
                        <div><strong>{{ $area['label'] }}</strong><span>{{ $area['description'] }}</span></div>
                    </li>
                @endforeach
            </ul>
            <p class="yk-cit__note">{!! $icon('info', 16) !!}<span>This test reflects the activities you enjoy. It does not measure ability and is not a guarantee of career fit.</span></p>
        </section>
    </div>
</main>
@endsection
