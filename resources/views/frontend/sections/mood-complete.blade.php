@extends('layouts.layout')

@section('title', 'Check-in complete | YuktiOne')
@section('meta_description', 'Thank you for taking a moment for yourself.')
@section('body_class', 'yk-mood-body')

@section('content')
<main class="yk-mood yk-complete yk-tools-ds" data-yk-tools-ds data-yk-mood>
    <div class="yk-mood__wrap yk-complete__wrap">
        @include('frontend.sections.partials.mood-progress', ['current' => 'done'])

        <div class="yk-complete__hero yk-mood__panel">
            <div class="yk-complete__mark" aria-hidden="true">
                <svg viewBox="0 0 48 48" focusable="false">
                    <circle cx="24" cy="24" r="20"></circle>
                    <path d="M15 24.5 21 30.5 33 17.5"></path>
                </svg>
            </div>
            <h1>Check-in complete!</h1>
            <p class="yk-mood__lead">Thank you for taking a moment for yourself.</p>
        </div>

        <section class="yk-complete__summary yk-mood__panel" aria-label="Your check-in">
            <h2>Your mood today</h2>
            <p class="yk-complete__mood">{{ $mood }}</p>

            <h2>Your emotions</h2>
            @if ($emotions === [])
                <p class="yk-complete__empty">No emotions selected.</p>
            @else
                <ul class="yk-complete__chips">
                    @foreach ($emotions as $emotion)
                        <li class="yk-emotion__chip is-{{ $emotion['tone'] }}">{{ $emotion['label'] }}</li>
                    @endforeach
                </ul>
            @endif

            <h2>Your reflection</h2>
            @if (filled($reflection))
                <p class="yk-complete__note">{{ $reflection }}</p>
            @else
                <p class="yk-complete__empty">No reflection added.</p>
            @endif
        </section>

        @include('frontend.sections.partials.psychology-connect', [
            'heading' => 'Would you like to talk to someone?',
            'copy' => 'A supportive conversation with a psychologist can help you explore your feelings and thoughts.',
            'cta' => 'Connect with a Psychologist',
            'url' => $psychologistsUrl,
            'headingId' => 'mood-connect-heading',
        ])

        <div class="yk-mood__actions yk-complete__actions">
            <a class="yk-mood__continue yk-complete__home" href="{{ route('tools.psychology') }}">Go to Psychology Home</a>
            <a class="yk-complete__history" href="{{ route('tools.psychology.mood.history') }}">View Mood History</a>
            <a class="yk-complete__explore" href="{{ route('tools.index') }}">Explore tools</a>
            @include('frontend.sections.partials.tool-back-to-tools', ['class' => 'yk-complete__history'])
        </div>
    </div>
</main>
@endsection
