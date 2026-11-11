@extends('layouts.layout')

@section('title', 'Saved Tarot Reading | YuktiOne')
@section('meta_description', 'Your saved Tarot Reflection reading.')
@section('body_class', 'yk-tarot-body')

@section('content')
@php
    $cards = $reading['cards'] ?? [];
    $isThree = ($reading['spread_type'] ?? '') === 'three';
    $categoryKey = $reading['category'] ?? 'general-guidance';
    $ui = \App\Support\Tarot\TarotUi::categoryPresentation($categoryKey);
@endphp
<main class="yk-tarot yk-tools-ds" data-yk-tools-ds data-yk-tarot>
    <div class="yk-tarot__wrap yk-tarot__wrap--wide">
        <div class="yk-tarot__toolbar">
            <a class="yk-tarot__back" href="{{ route('tools.tarot.history') }}"><span aria-hidden="true">←</span> My Readings</a>
            <a class="yk-tarot__history-link" href="{{ route('tools.tarot.intro') }}">New reading</a>
        </div>

        <p class="yk-tarot__step">
            @include('frontend.sections.tarot.partials.emoji', ['emoji' => '💾'])
            Saved reading
        </p>
        <h1>Your Tarot Reading</h1>
        <p class="yk-tarot__lead">
            @include('frontend.sections.tarot.partials.emoji', ['emoji' => $ui['emoji']])
            {{ $reading['category_label'] ?? '' }}
            @if (!empty($reading['saved_at']))
                · saved {{ $reading['saved_at']->timezone('Asia/Kolkata')->format('d M Y · g:i A') }}
            @endif
        </p>

        @if (session('status'))
            <p class="yk-tarot__success" role="status">{{ session('status') }}</p>
        @endif

        <blockquote class="yk-tarot__question-chip">
            <span>Your question</span>
            <p>{{ $reading['question'] ?? '' }}</p>
        </blockquote>

        <div class="yk-tarot__reveal-list">
            @foreach ($cards as $card)
                <article class="yk-tarot__reveal-card @if (!empty($card['is_reversed'])) is-reversed @endif">
                    @include('frontend.sections.tarot.partials.card-face', ['card' => $card, 'size' => $isThree ? 'md' : 'lg'])
                    <div class="yk-tarot__reveal-copy">
                        <p class="yk-tarot__position">{{ $card['position_label'] }}</p>
                        <h2>{{ $card['card_name'] }} · {{ $card['orientation_label'] }}</h2>
                        <p class="yk-tarot__meaning">{{ $card['core_meaning'] }}</p>
                        <p class="yk-tarot__insight">{{ $card['insight'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>

        @if (!empty($reading['overall_reflection']))
            <section class="yk-tarot__overall" aria-labelledby="tarot-saved-overall">
                <h2 id="tarot-saved-overall">
                    @include('frontend.sections.tarot.partials.emoji', ['emoji' => '🌙'])
                    {{ $isThree ? 'Overall reflection' : 'Reflection' }}
                </h2>
                <p>{{ $reading['overall_reflection'] }}</p>
            </section>
        @endif

        @if (!empty($reflectionPrompt))
            <p class="yk-tarot__prompt"><strong>Sit with this:</strong> {{ $reflectionPrompt }}</p>
        @endif

        <aside class="yk-tarot__disclaimer" aria-label="Disclaimer">
            <p>{{ $disclaimer }}</p>
        </aside>

        <div class="yk-tarot__final-ctas" role="group" aria-label="Reading actions">
            <a class="yk-tarot__btn yk-tarot__btn--secondary" data-tarot-cta="expert" href="{{ $discovery['url'] ?? route('professionals', ['category' => 'astrology-tarot']) }}">
                @include('frontend.sections.tarot.partials.emoji', ['emoji' => '👤'])
                Talk to an Expert
            </a>
        </div>
        <p class="yk-tarot__hint">This is your saved snapshot. Cards and insights are not redrawn.</p>
    </div>
</main>
@endsection
