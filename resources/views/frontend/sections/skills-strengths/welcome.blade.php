@extends('layouts.layout')

@section('title', 'Skills & Strengths Assessment | YuktiOne')
@section('meta_description', 'A simple way to understand your current strengths across eight skill areas.')
@section('body_class', 'yk-ssa-body')

@section('content')
<main class="yk-ssa yk-tools-ds" data-yk-tools-ds data-yk-ssa>
    <div class="yk-ssa__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-ssa__card yk-ssa__card--hero" aria-labelledby="ssa-welcome-heading">
            <p class="yk-ssa__badge">Career tool</p>
            <div class="yk-ssa__hero-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false"><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/><circle cx="12" cy="12" r="4"/><path d="m7.5 7.5 2 2M14.5 14.5l2 2M7.5 16.5l2-2M14.5 9.5l2-2"/></svg>
            </div>
            <h1 id="ssa-welcome-heading">Skills &amp; Strengths Assessment</h1>
            <p class="yk-ssa__lead">A clear self-rating snapshot of your current strengths across eight skill areas — not a measure of proven ability or hiring fitness.</p>

            <div class="yk-ssa__stats" role="list">
                <div role="listitem"><strong>24</strong><span>Questions</span></div>
                <div role="listitem"><strong>~7 min</strong><span>Duration</span></div>
                <div role="listitem"><strong>8</strong><span>Skill areas</span></div>
            </div>

            @if (! $open)
                <p class="yk-ssa__error">This assessment is unavailable right now.</p>
            @else
                <div class="yk-ssa__actions">
                    <form method="post" action="{{ route('tools.skills-strengths.start') }}">
                        @csrf
                        <button class="yk-ssa__btn" type="submit">{{ $hasDraft ? 'Continue Assessment' : 'Start Assessment' }}</button>
                    </form>
                    @auth('user')
                        <a class="yk-ssa__btn-secondary" href="{{ route('tools.skills-strengths.history') }}">View history</a>
                    @endauth
                </div>
                <p class="yk-ssa__note">Free · Save results to your YuktiOne profile when you are ready</p>
            @endif
        </section>
    </div>
</main>
@endsection
