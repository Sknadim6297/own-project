@extends('layouts.layout')

@section('title', 'Daily Mood Check-in | YuktiOne')
@section('meta_description', 'Choose the option that best describes how you feel right now.')
@section('body_class', 'yk-mood-body')

@section('content')
<main class="yk-mood yk-tools-ds" data-yk-tools-ds data-yk-mood>
    <div class="yk-mood__wrap">
        <a class="yk-mood__back" href="{{ route('tools.psychology') }}">
            <span aria-hidden="true">←</span> Back
        </a>

        @if (!$open)
            <section class="yk-mood__closed" aria-labelledby="mood-closed-heading">
                <p class="yk-mood__badge">Free tool</p>
                <h1 id="mood-closed-heading">How are you feeling today?</h1>
                <p>This check-in is unavailable right now.</p>
            </section>
        @else
            @include('frontend.sections.partials.mood-progress', ['current' => 'mood'])

            <form class="yk-mood__form yk-mood__panel" method="post" action="{{ route('tools.psychology.mood.store') }}" data-mood-form>
                @csrf
                <p class="yk-mood__badge">Free tool</p>
                <h1>How are you feeling today?</h1>
                <p class="yk-mood__lead">Choose the option that best describes how you feel right now.</p>

                @if ($errors->any())
                    <p class="yk-mood__error" role="alert">{{ $errors->first() }}</p>
                @endif

                <fieldset>
                    <legend class="visually-hidden">Primary mood</legend>
                    <ul class="yk-mood__options">
                        @foreach ($moods as $mood)
                            <li>
                                <label class="yk-mood__card is-{{ $mood['tone'] }} @if ($selected === $mood['key']) is-selected @endif" data-mood-card @if ($selected === $mood['key']) data-selected="true" @endif>
                                    <input class="yk-mood__input" type="radio" name="mood" value="{{ $mood['key'] }}" @checked($selected === $mood['key'])>
                                    <span class="yk-mood__face" aria-hidden="true">
                                        @include('frontend.sections.partials.mood-face', ['mood' => $mood['key']])
                                    </span>
                                    <span class="yk-mood__copy">
                                        <span class="yk-mood__name">{{ $mood['label'] }}</span>
                                        <span class="yk-mood__caption">{{ $mood['caption'] }}</span>
                                    </span>
                                    <span class="yk-mood__radio" aria-hidden="true"></span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </fieldset>

                <button class="yk-mood__continue" type="submit" data-mood-continue @disabled($selected === null)>Continue</button>
            </form>
        @endif
    </div>
</main>
@if ($open)
<script src="{{ asset('frontend/assets/js/yuktione-mood.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-mood.js')) }}" defer></script>
@endif
@endsection
