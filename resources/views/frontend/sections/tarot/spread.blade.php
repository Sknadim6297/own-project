@extends('layouts.layout')

@section('title', 'Choose Spread | Tarot Reflection')
@section('meta_description', 'Choose a one-card or three-card Tarot spread.')
@section('body_class', 'yk-tarot-body')

@section('content')
<main class="yk-tarot yk-tools-ds" data-yk-tools-ds data-yk-tarot>
    <div class="yk-tarot__wrap">
        <a class="yk-tarot__back" href="{{ route('tools.tarot.question') }}"><span aria-hidden="true">←</span> Back</a>

        <p class="yk-tarot__step">Step 3 of 5</p>
        <h1>Choose your reading</h1>
        <p class="yk-tarot__lead">Only two spreads are available in this version.</p>

        @if ($errors->any())
            <p class="yk-tarot__error" role="alert">{{ $errors->first() }}</p>
        @endif

        <form method="post" action="{{ route('tools.tarot.spread.store') }}" class="yk-tarot__form" data-tarot-select-form>
            @csrf
            <ul class="yk-tarot__options yk-tarot__options--spread">
                @foreach ($spreads as $key => $spread)
                    <li>
                        <label class="yk-tarot__option yk-tarot__option--tall @if ($selected === $key) is-selected @endif">
                            <input type="radio" name="spread_type" value="{{ $key }}" @checked($selected === $key) required>
                            <span class="yk-tarot__option-copy">
                                <span class="yk-tarot__option-label">
                                    @include('frontend.sections.tarot.partials.emoji', ['emoji' => $key === 'one' ? '🃏' : '🔮'])
                                    {{ $spread['label'] }} · {{ $spread['subtitle'] }}
                                </span>
                                <div class="yk-tarot__spread-preview" aria-hidden="true">
                                    @if ($key === 'one')
                                        <span class="yk-tarot__mini-card"></span>
                                    @else
                                        <span class="yk-tarot__mini-card"></span>
                                        <span class="yk-tarot__mini-card yk-tarot__mini-card--stack"></span>
                                        <span class="yk-tarot__mini-card yk-tarot__mini-card--stack"></span>
                                    @endif
                                </div>
                                <span class="yk-tarot__option-meta">
                                    @foreach ($spread['positions'] as $position)
                                        {{ $position['label'] }}@if (!$loop->last) · @endif
                                    @endforeach
                                </span>
                            </span>
                            <span class="yk-tarot__option-mark" aria-hidden="true"></span>
                        </label>
                    </li>
                @endforeach
            </ul>

            <div class="yk-tarot__toggle">
                <label class="yk-tarot__switch" for="reversed_enabled">
                    <input id="reversed_enabled" type="checkbox" name="reversed_enabled" value="1" @checked(old('reversed_enabled', $reversedEnabled))>
                    <span class="yk-tarot__switch-ui" aria-hidden="true"></span>
                    <span class="yk-tarot__switch-copy">
                        <strong>Include reversed cards</strong>
                        <span>Optional. Off by default.</span>
                    </span>
                </label>
            </div>

            <button class="yk-tarot__btn" type="submit" data-tarot-continue @disabled($selected === null)>Continue</button>
        </form>
    </div>
</main>
@endsection
