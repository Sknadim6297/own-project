@extends('layouts.layout')

@section('title', ($editing ? 'Edit gratitude entry' : 'Gratitude Journal').' | YuktiOne')
@section('meta_description', 'Pause. Reflect. Appreciate. A private three-card gratitude journal.')
@section('body_class', 'yk-gratitude-body')

@section('content')
<main class="yk-gratitude yk-tools-ds" data-yk-tools-ds data-yk-gratitude>
    <div class="yk-gratitude__wrap">
        <a class="yk-gratitude__back" href="{{ $backUrl }}">
            <span aria-hidden="true">←</span> Back
        </a>

        @if (!$open)
            <section class="yk-gratitude__closed yk-gratitude__panel" aria-labelledby="gratitude-closed-heading">
                <p class="yk-gratitude__badge">{{ $editing ? 'Private entry' : 'Free tool' }}</p>
                <h1 id="gratitude-closed-heading">{{ $heading }}</h1>
                <p>{{ $editing ? 'Editing is unavailable right now.' : 'This journal is unavailable right now.' }}</p>
            </section>
        @else
            <form
                class="yk-gratitude__form yk-gratitude__panel"
                method="post"
                action="{{ $formAction }}"
                data-gratitude-form
                data-answer-max="{{ $answerMax }}"
                data-min-answered="{{ $minAnswered }}"
            >
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif
                <p class="yk-gratitude__badge">{{ $editing ? 'Private entry' : 'Free tool' }}</p>
                <h1>{{ $heading }}</h1>
                <p class="yk-gratitude__lead">{{ $lead }}</p>
                @if ($showDate)
                    <p class="yk-gratitude__date"><time datetime="{{ now(\App\Services\GratitudeJournal::TIMEZONE)->toDateString() }}">{{ $today }}</time></p>
                    <h2 class="yk-gratitude__prompt">What are you grateful for today?</h2>
                    <p class="yk-gratitude__support">Fill in whichever cards you like. Skip the rest.</p>
                @endif
                <p class="yk-gratitude__private" role="status">Private · Only you can see this entry</p>

                @if ($errors->any())
                    <p class="yk-gratitude__error" role="alert" data-gratitude-error>{{ $errors->first() }}</p>
                @else
                    <p class="yk-gratitude__error" role="alert" data-gratitude-error hidden></p>
                @endif

                <div
                    class="yk-gratitude-steps"
                    data-gratitude-steps
                    data-gratitude-carousel
                    aria-label="Gratitude cards"
                >
                    <p class="yk-gratitude-carousel__status" data-gratitude-status aria-live="polite">Card 1 of {{ count($cards) }}</p>
                    <div class="yk-gratitude-steps__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ count($cards) > 0 ? (int) round(100 / count($cards)) : 0 }}" aria-label="Card progress">
                        <span data-gratitude-progress style="width: {{ count($cards) > 0 ? (int) round(100 / count($cards)) : 0 }}%"></span>
                    </div>

                    <ol class="yk-gratitude-steps__list">
                        @foreach ($cards as $index => $card)
                            @php
                                $value = old('answers.'.$card['key'], $answers[$card['key']] ?? '');
                            @endphp
                            <li
                                class="yk-gratitude-card @if ($index === 0) is-active @endif"
                                data-gratitude-card
                                data-card-index="{{ $index }}"
                                aria-hidden="{{ $index === 0 ? 'false' : 'true' }}"
                                @if ($index !== 0) hidden @endif
                            >
                                <p class="yk-gratitude-card__eyebrow">Card {{ $index + 1 }} of {{ count($cards) }}</p>
                                <h3 class="yk-gratitude-card__title">{{ $card['label'] }}</h3>
                                @if ($card['hint'])
                                    <p class="yk-gratitude-card__hint">{{ $card['hint'] }}</p>
                                @endif
                                <label class="yk-gratitude-card__label" for="gratitude-answer-{{ $card['key'] }}">
                                    {{ $card['question'] ?? $card['label'] }}
                                </label>
                                <textarea
                                    id="gratitude-answer-{{ $card['key'] }}"
                                    class="yk-gratitude-card__input"
                                    name="answers[{{ $card['key'] }}]"
                                    rows="5"
                                    maxlength="{{ $answerMax }}"
                                    placeholder="Write a few words…"
                                    data-gratitude-answer
                                    data-card-key="{{ $card['key'] }}"
                                >{{ $value }}</textarea>
                                <p class="yk-gratitude-card__count">
                                    <span data-gratitude-count>{{ mb_strlen((string) $value) }}</span> / {{ number_format($answerMax) }}
                                </p>
                            </li>
                        @endforeach
                    </ol>

                    <div class="yk-gratitude-dots" role="tablist" aria-label="Card progress">
                        @foreach ($cards as $index => $card)
                            <button
                                class="yk-gratitude-dot @if ($index === 0) is-active @endif"
                                type="button"
                                role="tab"
                                aria-label="Go to card {{ $index + 1 }}"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                data-gratitude-dot
                                data-card-index="{{ $index }}"
                            ></button>
                        @endforeach
                    </div>

                    <div class="yk-gratitude-nav">
                        <button class="yk-gratitude-nav__prev" type="button" data-gratitude-prev hidden>Previous</button>
                        <button class="yk-gratitude-nav__next" type="button" data-gratitude-next hidden>Next</button>
                        <button class="yk-gratitude-nav__skip" type="button" data-gratitude-skip>Skip</button>
                    </div>
                </div>

                <button class="yk-gratitude__save" type="submit" data-gratitude-save>{{ $submitLabel }}</button>
            </form>
        @endif
    </div>
</main>
@if ($open)
<script src="{{ asset('frontend/assets/js/yuktione-gratitude.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-gratitude.js')) }}" defer></script>
@endif
@endsection
