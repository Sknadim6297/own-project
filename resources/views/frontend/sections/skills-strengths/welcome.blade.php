@extends('layouts.layout')

@section('title', 'Skills & Strengths Assessment | YuktiOne')
@section('meta_description', 'A simple way to understand your current strengths across eight skill areas.')
@section('body_class', 'yk-ssa-body')

@section('content')
<main class="yk-ssa">
    <div class="yk-ssa__wrap">
        <section class="yk-ssa__card" aria-labelledby="ssa-welcome-heading">
            <p class="yk-ssa__badge">Career tool</p>
            <h1 id="ssa-welcome-heading">Skills &amp; Strengths Assessment</h1>
            <p class="yk-ssa__lead">A simple way to understand your current strengths. This is a self-rating snapshot—not a measure of proven ability or hiring fitness.</p>

            <ul class="yk-ssa__bullets">
                <li>Know your key strengths</li>
                <li>Understand your skill profile</li>
            </ul>
            <p class="yk-ssa__meta">8 skill areas · 24 questions · About 7 minutes</p>

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
            @endif
        </section>
    </div>
</main>
@endsection
