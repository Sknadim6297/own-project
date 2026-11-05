@extends('layouts.layout')

@section('title', 'Beautifully done! | YuktiOne')
@section('meta_description', 'You took a moment to appreciate the good in your day.')
@section('body_class', 'yk-gratitude-body')

@section('content')
<main class="yk-gratitude yk-gratitude-complete">
    <div class="yk-gratitude__wrap yk-gratitude-complete__wrap">
        <div class="yk-gratitude-complete__mark" aria-hidden="true">
            <svg viewBox="0 0 48 48" focusable="false">
                <circle cx="24" cy="24" r="20"></circle>
                <path d="M15 24.5 21 30.5 33 17.5"></path>
            </svg>
        </div>

        <h1>Beautifully done!</h1>
        <p class="yk-gratitude__lead">You took a moment to appreciate the good in your day.</p>

        <section class="yk-gratitude-complete__summary" aria-labelledby="todays-gratitude-heading">
            <h2 id="todays-gratitude-heading">Today's Gratitude</h2>
            <p class="yk-gratitude-complete__date">
                <time datetime="{{ $datetime }}">{{ $when }}</time>
            </p>

            <ul class="yk-gratitude-complete__list">
                @foreach ($cards as $card)
                    <li class="yk-gratitude-complete__card @if ($card['skipped']) is-skipped @endif">
                        <h3>{{ $card['label'] }}</h3>
                        @if ($card['skipped'])
                            <p class="yk-gratitude-complete__skipped">Skipped</p>
                        @else
                            <p class="yk-gratitude-complete__answer">{{ $card['answer'] }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="yk-gratitude-complete__encourage" aria-label="Encouragement">
            <h2>Keep growing, one day at a time.</h2>
            <p>Every little moment of gratitude counts.</p>
        </section>

        @include('frontend.sections.partials.psychology-connect', [
            'heading' => 'Would you like to talk to someone?',
            'copy' => 'A supportive conversation with a psychologist can help you explore your feelings and thoughts.',
            'cta' => 'Connect with a Psychologist',
            'url' => $psychologistsUrl,
            'headingId' => 'gratitude-connect-heading',
        ])

        <a class="yk-gratitude__save yk-gratitude-complete__home" href="{{ route('tools.psychology.gratitude') }}">
            Go to Gratitude Home
        </a>

        <a class="yk-gratitude-complete__history" href="{{ route('tools.psychology.gratitude.history') }}">
            View My Gratitude History
        </a>
        @include('frontend.sections.partials.tool-back-to-tools', ['class' => 'yk-gratitude-complete__history'])
    </div>
</main>
@endsection
