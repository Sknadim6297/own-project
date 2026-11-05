@extends('layouts.layout')

@section('title', 'Career Interest Test | YuktiOne')
@section('meta_description', 'Understand your interests, discover your natural preferences and explore career possibilities.')
@section('body_class', 'yk-cit-body')

@section('content')
<main class="yk-cit">
    <div class="yk-cit__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-cit__card yk-cit__card--hero" aria-labelledby="cit-welcome-heading">
            <p class="yk-cit__badge">Career tool</p>
            <div class="yk-cit__hero-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="3"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M5.6 18.4 7 17M17 7l1.4-1.4"/></svg>
            </div>
            <h1 id="cit-welcome-heading">Career Interest Test</h1>
            <p class="yk-cit__lead">Discover which activities you naturally enjoy across six interest areas — a self-rating of preferences, not aptitude or guaranteed career fit.</p>

            <div class="yk-cit__stats" role="list">
                <div role="listitem"><strong>{{ \App\Support\CareerInterest\CareerInterestCatalog::questionCount() }}</strong><span>Questions</span></div>
                <div role="listitem"><strong>~5 min</strong><span>Duration</span></div>
                <div role="listitem"><strong>6</strong><span>Interest areas</span></div>
            </div>

            @if (! $open)
                <p class="yk-cit__error">This assessment is unavailable right now.</p>
            @else
                <div class="yk-cit__actions">
                    <form method="post" action="{{ route('tools.career-interest.start') }}">
                        @csrf
                        <button class="yk-cit__btn" type="submit">{{ $hasDraft ? 'Continue Assessment' : 'Start Assessment' }}</button>
                    </form>
                    @auth('user')
                        <a class="yk-cit__btn-secondary" href="{{ route('tools.career-interest.history') }}">View history</a>
                    @endauth
                </div>
                <p class="yk-cit__meta">Free · Save results to your YuktiOne profile when you are ready</p>
            @endif
        </section>
    </div>
</main>
@endsection
