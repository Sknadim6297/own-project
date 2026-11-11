@extends('layouts.layout')

@section('title', 'Ask Your Question | Tarot Reflection')
@section('meta_description', 'Enter the question you would like to explore in your Tarot Reflection.')
@section('body_class', 'yk-tarot-body')

@section('content')
@php
    $ui = \App\Support\Tarot\TarotUi::categoryPresentation($categoryKey ?? 'general-guidance');
@endphp
<main class="yk-tarot yk-tools-ds" data-yk-tools-ds data-yk-tarot>
    <div class="yk-tarot__wrap">
        <a class="yk-tarot__back" href="{{ route('tools.tarot.category') }}"><span aria-hidden="true">←</span> Back</a>

        <p class="yk-tarot__step">
            Step 2 of 5 ·
            @include('frontend.sections.tarot.partials.emoji', ['emoji' => $ui['emoji']])
            {{ $categoryLabel }}
        </p>
        <h1>What would you like to explore?</h1>
        <p class="yk-tarot__lead">Write one clear question. Keep it open and reflective rather than yes/no about another person.</p>

        @if ($errors->any())
            <p class="yk-tarot__error" role="alert">{{ $errors->first() }}</p>
        @endif

        <div class="yk-tarot__panel">
            <form method="post" action="{{ route('tools.tarot.question.store') }}" class="yk-tarot__form yk-tarot__form--flush">
                @csrf
                <label class="yk-tarot__label" for="tarot-question">Your question</label>
                <textarea
                    id="tarot-question"
                    class="yk-tarot__textarea"
                    name="question"
                    rows="4"
                    maxlength="{{ $max }}"
                    minlength="{{ $min }}"
                    required
                    placeholder="{{ $examples[0] ?? 'What would I like clarity on?' }}"
                >{{ old('question', $question) }}</textarea>
                <p class="yk-tarot__hint"><span data-tarot-count>0</span> / {{ $max }} characters</p>

                @if (!empty($examples))
                    <div class="yk-tarot__examples">
                        <p class="yk-tarot__examples-label">Examples for {{ $categoryLabel }}</p>
                        <ul>
                            @foreach ($examples as $example)
                                <li>
                                    <button type="button" class="yk-tarot__example" data-tarot-example="{{ $example }}">{{ $example }}</button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="yk-tarot__actions">
                    <button class="yk-tarot__btn" type="submit">Continue</button>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
