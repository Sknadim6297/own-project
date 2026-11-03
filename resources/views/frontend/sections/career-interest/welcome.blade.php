@extends('layouts.layout')

@section('title', 'Career Interest Test | YuktiOne')
@section('meta_description', 'Understand your interests, discover your natural preferences and explore career possibilities.')
@section('body_class', 'yk-cit-body')

@section('content')
<main class="yk-cit">
    <div class="yk-cit__wrap">
        <section class="yk-cit__card" aria-labelledby="cit-welcome-heading">
            <p class="yk-cit__badge">Free tool</p>
            <h1 id="cit-welcome-heading">Discover what drives you.</h1>
            <p class="yk-cit__lead">Understand your interests, discover your natural preferences and explore career possibilities. This assessment identifies interests—not aptitude or guaranteed career suitability.</p>

            <div class="yk-cit__stats" role="list">
                <div role="listitem"><strong>18</strong><span>Questions</span></div>
                <div role="listitem"><strong>5 min</strong><span>Duration</span></div>
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
                <p class="yk-cit__lead" style="margin-top:0.85rem;margin-bottom:0;">Free · Save your results to your YuktiOne profile</p>
            @endif
        </section>
    </div>
</main>
@endsection
