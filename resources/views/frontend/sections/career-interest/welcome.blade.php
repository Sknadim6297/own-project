@extends('layouts.layout')

@section('title', 'Career Interest Test | YuktiOne')
@section('meta_description', 'Understand your interests, discover your natural preferences and explore career possibilities.')
@section('body_class', 'yk-cit-body')

@php
    $icon = fn (string $name, int $size = 18) => view('frontend.sections.career-interest.partials.icon', ['name' => $name, 'size' => $size])->render();
    $areas = \App\Support\CareerInterest\CareerInterestCatalog::categories();
@endphp

@section('content')
<main class="yk-cit yk-tools-ds yk-tool-shell" data-yk-tools-ds data-yk-cit data-yk-tool-shell="1">
    <div class="yk-cit__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')

        <section class="yk-cit__card yk-cit__card--hero yk-cit__card--compact" aria-labelledby="cit-welcome-heading" data-yk-tool-compact-header>
            <div class="yk-cit__hero-grid yk-cit__hero-grid--compact">
                <div class="yk-cit__hero-copy">
                    <p class="yk-cit__badge"><x-yk-icon name="briefcase-business" class="yk-icon--sm" /> Career tool</p>
                    <div class="yk-cit__title-row">
                        <span class="yk-cit__title-icon" aria-hidden="true">
                            <x-yk-icon name="briefcase-business" class="yk-icon--lg" />
                        </span>
                        <div>
                            <h1 id="cit-welcome-heading"><x-yuktione.two-tone text="Career Interest Test" /></h1>
                            <p class="yk-cit__lead" data-yk-tool-page-lead>{{ \App\Support\Tools\ToolPageLeads::for('career-interest-test') }}</p>
                        </div>
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
                        <p class="yk-cit__note yk-cit__note--inline">{!! $icon('info', 16) !!}<span>This test reflects activities you enjoy. It does not measure ability or guarantee career fit.</span></p>
                    @endif
                </div>
            </div>
        </section>
    </div>
</main>
@endsection
