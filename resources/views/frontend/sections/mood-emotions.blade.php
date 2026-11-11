@extends('layouts.layout')

@section('title', 'Daily Mood Check-in | YuktiOne')
@section('meta_description', 'Choose up to 6 emotions that describe how you feel right now.')
@section('body_class', 'yk-mood-body')

@section('content')
@php
    $groupLabels = [
        'positive' => 'Positive',
        'neutral' => 'Neutral',
        'difficult' => 'Difficult',
    ];
@endphp
<main class="yk-mood yk-tools-ds" data-yk-tools-ds data-yk-mood>
    <div class="yk-mood__wrap">
        <a class="yk-mood__back" href="{{ route('tools.psychology.mood') }}">
            <span aria-hidden="true">←</span> Back
        </a>

        @if (!$open)
            <section class="yk-mood__closed" aria-labelledby="emotion-closed-heading">
                <p class="yk-mood__badge">Free tool</p>
                <h1 id="emotion-closed-heading">What emotions are you feeling?</h1>
                <p>This check-in is unavailable right now.</p>
            </section>
        @else
            @include('frontend.sections.partials.mood-progress', ['current' => 'emotions'])

            <form class="yk-mood__form yk-mood__panel" method="post" action="{{ route('tools.psychology.mood.emotions.store') }}" data-emotion-form data-emotion-limit="{{ $limit }}">
                @csrf
                <p class="yk-mood__badge">Free tool</p>
                <h1>What emotions are you feeling?</h1>
                <p class="yk-mood__lead">Choose up to 6 emotions that describe how you feel right now.</p>
                <p class="yk-mood__hint">Optional — pick what fits. You can continue with none selected.</p>

                @if ($errors->any())
                    <p class="yk-mood__error" role="alert">{{ $errors->first() }}</p>
                @endif

                @foreach ($groupLabels as $bucket => $groupLabel)
                    <fieldset class="yk-emotion__group">
                        <legend>{{ $groupLabel }}</legend>
                        <div class="yk-emotion__chips">
                            @foreach ($groups[$bucket] as $emotion)
                                <label class="yk-emotion__chip is-{{ $bucket }} @if (in_array($emotion['key'], $selected, true)) is-selected @endif" data-emotion-chip @if (in_array($emotion['key'], $selected, true)) data-selected="true" @endif>
                                    <input class="yk-emotion__input" type="checkbox" name="emotions[]" value="{{ $emotion['key'] }}" @checked(in_array($emotion['key'], $selected, true))>
                                    <span>{{ $emotion['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                <p class="yk-emotion__count" data-emotion-count aria-live="polite">{{ count($selected) }} of {{ $limit }} selected</p>
                <button class="yk-mood__continue" type="submit">Continue</button>
            </form>
        @endif
    </div>
</main>
@if ($open)
<script src="{{ asset('frontend/assets/js/yuktione-mood.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-mood.js')) }}" defer></script>
@endif
@endsection
