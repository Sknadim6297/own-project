@extends('layouts.layout')

@section('title', 'Focus & Draw | Tarot Reflection')
@section('meta_description', 'Focus on your question and choose a facedown card.')
@section('body_class', 'yk-tarot-body')

@section('content')
<main class="yk-tarot yk-tools-ds" data-yk-tools-ds data-yk-tarot>
    <div class="yk-tarot__wrap yk-tarot__wrap--wide">
        <a class="yk-tarot__back" href="{{ route('tools.tarot.spread') }}"><span aria-hidden="true">←</span> Back</a>

        <p class="yk-tarot__step">Step 4 of 5 · {{ $spreadLabel }}</p>
        <h1>Focus &amp; Draw</h1>
        <p class="yk-tarot__lead">Take a breath. Focus on your question, then choose a facedown card.</p>

        <blockquote class="yk-tarot__question-chip">
            <span>{{ $categoryLabel }}</span>
            <p>{{ $question }}</p>
        </blockquote>

        <div class="yk-tarot__progress" aria-live="polite">
            <p>Drawing <strong>{{ $nextPosition }}</strong> · {{ $fan['picked'] }} of {{ $fan['needed'] }} selected</p>
            <ol class="yk-tarot__progress-list">
                @foreach ($fan['positions'] as $index => $position)
                    <li class="@if ($index < $fan['picked']) is-done @elseif ($index === $fan['picked']) is-current @endif">
                        {{ $position['label'] }}
                    </li>
                @endforeach
            </ol>
        </div>

        @if ($errors->any())
            <p class="yk-tarot__error" role="alert">{{ $errors->first() }}</p>
        @endif

        <ul class="yk-tarot__fan" role="list" aria-label="Facedown tarot cards. Identities stay hidden until the draw is complete.">
            @foreach ($fan['tokens'] as $index => $token)
                @php $already = in_array($token, $fan['selected_tokens'], true); @endphp
                <li>
                    @if ($already)
                        <span class="yk-tarot__facedown is-picked" aria-disabled="true">Selected</span>
                    @else
                        <form method="post" action="{{ route('tools.tarot.draw.pick') }}" data-tarot-once>
                            @csrf
                            <input type="hidden" name="slot_token" value="{{ $token }}">
                            <input type="hidden" name="draw_nonce" value="{{ $fan['nonce'] }}">
                            <button
                                class="yk-tarot__facedown"
                                type="submit"
                                aria-label="Draw facedown card {{ $index + 1 }} for {{ $nextPosition }}"
                            >
                                <span class="yk-tarot__facedown-mark" aria-hidden="true"></span>
                                <span class="visually-hidden">Draw card {{ $index + 1 }}</span>
                            </button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
        <p class="yk-tarot__hint">Card identities stay hidden until you finish drawing. Selections cannot be changed after reveal.</p>
    </div>
</main>
@endsection
