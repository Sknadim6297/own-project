@extends('layouts.layout')

@section('title', 'Your Card | Tarot Reflection')
@section('meta_description', 'Reflect on the cards drawn in your Tarot reading.')
@section('body_class', 'yk-tarot-body')

@section('content')
@php
    $cards = $reading['cards'] ?? [];
    $isThree = ($reading['spread_type'] ?? '') === 'three';
@endphp
<main class="yk-tarot yk-tools-ds" data-yk-tools-ds data-yk-tarot data-tarot-reveal>
    <div class="yk-tarot__wrap yk-tarot__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')

        <p class="yk-tarot__step">
            @include('frontend.sections.tarot.partials.emoji', ['emoji' => '✨'])
            Reveal
        </p>
        <h1>{{ $isThree ? 'Your cards' : 'Your Card' }}</h1>
        <p class="yk-tarot__lead">{{ $reading['category_label'] ?? '' }} · reflective insight, not a prediction.</p>
        <p class="yk-tarot__sr-status" aria-live="polite" data-tarot-reveal-status></p>

        <blockquote class="yk-tarot__question-chip">
            <span>Your question</span>
            <p>{{ $reading['question'] ?? '' }}</p>
        </blockquote>

        <div class="yk-tarot__reveal-list @if ($isThree) yk-tarot__reveal-list--three @endif">
            @foreach ($cards as $card)
                <article class="yk-tarot__reveal-card @if (!empty($card['is_reversed'])) is-reversed @endif" data-tarot-reveal-card>
                    <div class="yk-tarot__flip @if ($isThree) @else yk-tarot__flip--lg @endif" data-tarot-flip>
                        <div class="yk-tarot__flip-inner">
                            <div class="yk-tarot__flip-back" aria-hidden="true"></div>
                            <div class="yk-tarot__flip-front">
                                @include('frontend.sections.tarot.partials.card-face', ['card' => $card, 'size' => $isThree ? 'md' : 'lg'])
                            </div>
                        </div>
                    </div>
                    <div class="yk-tarot__reveal-copy">
                        <p class="yk-tarot__position">{{ $card['position_label'] }}</p>
                        <h2>{{ $card['card_name'] }} · {{ $card['orientation_label'] }}</h2>
                        <p class="yk-tarot__meaning">{{ $card['core_meaning'] }}</p>
                        <p class="yk-tarot__insight">{{ $card['insight'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="yk-tarot__actions">
            <a class="yk-tarot__btn" href="{{ route('tools.tarot.reading') }}">See Full Reading</a>
        </div>
    </div>
</main>
@endsection
