@extends('layouts.layout')

@section('title', 'Skills & Strengths Assessment | YuktiOne')
@section('meta_description', 'A simple way to understand your current strengths across eight skill areas.')
@section('body_class', 'yk-ssa-body')

@section('content')
<main class="yk-ssa yk-tools-ds yk-tool-shell" data-yk-tools-ds data-yk-ssa data-yk-tool-shell="1">
    <div class="yk-ssa__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-ssa__card yk-ssa__card--hero yk-ssa__card--compact" aria-labelledby="ssa-welcome-heading" data-yk-tool-compact-header>
            <p class="yk-ssa__badge"><x-yk-icon name="lightbulb" class="yk-icon--sm" /> Career tool</p>
            <div class="yk-ssa__title-row">
                <span class="yk-ssa__title-icon" aria-hidden="true">
                    <x-yk-icon name="lightbulb" class="yk-icon--lg" />
                </span>
                <div>
                    <h1 id="ssa-welcome-heading"><x-yuktione.two-tone text="Skills & Strengths Assessment" /></h1>
                    <p class="yk-ssa__lead" data-yk-tool-page-lead>{{ \App\Support\Tools\ToolPageLeads::for('skills-strengths-assessment') }}</p>
                </div>
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
                <p class="yk-ssa__note">Self-rating only—not a measure of proven ability or hiring fitness.</p>
            @endif
        </section>
    </div>
</main>
@endsection
