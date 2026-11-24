@extends('layouts.layout')

@section('title', 'Tarot Reflection | YuktiOne')
@section('meta_description', 'Draw your cards and explore reflective guidance with YuktiOne Tarot Reflection.')
@section('body_class', 'yk-tarot-body')

@section('content')
<main class="yk-tarot yk-tools-ds" data-yk-tools-ds data-yk-tarot>
    <div class="yk-tarot__wrap">
        <div class="yk-tarot__toolbar">
            @include('frontend.sections.partials.tool-back-to-tools')
            <a class="yk-tarot__history-link" href="{{ route('tools.tarot.history') }}">
                @include('frontend.sections.tarot.partials.emoji', ['icon' => 'book-open'])
                My Readings
            </a>
        </div>

        <section class="yk-tarot__hero" aria-labelledby="tarot-intro-heading">
            <div class="yk-tarot__hero-copy">
                <p class="yk-tarot__badge">
                    @include('frontend.sections.tarot.partials.emoji', ['icon' => 'layers'])
                    Reflective tool
                </p>
                <h1 id="tarot-intro-heading">Tarot Reflection</h1>
                <p class="yk-tarot__lead">Draw your cards. Explore what they may help you reflect on — gently, without prediction or certainty.</p>
                <p class="yk-tarot__spread-note" aria-label="Available spreads">
                    <span>One-card clarity</span>
                    <span>Three-card reflection</span>
                </p>

                <ol class="yk-tarot__steps">
                    <li>
                        <span class="yk-tarot__step-ico" aria-hidden="true">1</span>
                        <div>
                            <strong>Choose a focus</strong>
                            <span>Pick the life area you want to explore.</span>
                        </div>
                    </li>
                    <li>
                        <span class="yk-tarot__step-ico" aria-hidden="true">2</span>
                        <div>
                            <strong>Ask &amp; draw</strong>
                            <span>Write one reflective question, then select facedown cards.</span>
                        </div>
                    </li>
                    <li>
                        <span class="yk-tarot__step-ico" aria-hidden="true">3</span>
                        <div>
                            <strong>Reflect</strong>
                            <span>Read grounded insights, save your reading, or talk with an expert.</span>
                        </div>
                    </li>
                </ol>
            </div>

            <div class="yk-tarot__stack" aria-hidden="true">
                <span class="yk-tarot__stack-card"></span>
                <span class="yk-tarot__stack-card"></span>
                <span class="yk-tarot__stack-card"></span>
            </div>
        </section>

        @if ($errors->any())
            <p class="yk-tarot__error" role="alert">{{ $errors->first() }}</p>
        @endif

        <aside class="yk-tarot__disclaimer" aria-label="Disclaimer">
            <p>
                @include('frontend.sections.tarot.partials.emoji', ['icon' => 'moon-star'])
                {{ $disclaimer }}
            </p>
        </aside>

        <div class="yk-tarot__actions @if ($hasDraft) yk-tarot__actions--split @endif">
            @if ($hasDraft)
                <form method="post" action="{{ route('tools.tarot.start') }}" data-tarot-once>
                    @csrf
                    <button class="yk-tarot__btn" type="submit">
                        @include('frontend.sections.tarot.partials.emoji', ['icon' => 'sparkles'])
                        Continue Reading
                    </button>
                </form>
                <form method="post" action="{{ route('tools.tarot.start') }}" data-tarot-once>
                    @csrf
                    <input type="hidden" name="restart" value="1">
                    <button class="yk-tarot__btn yk-tarot__btn--secondary" type="submit">
                        Start a new reading
                    </button>
                </form>
            @else
                <form method="post" action="{{ route('tools.tarot.start') }}" data-tarot-once>
                    @csrf
                    <button class="yk-tarot__btn" type="submit">
                        @include('frontend.sections.tarot.partials.emoji', ['icon' => 'sparkles'])
                        Start a Reading
                    </button>
                </form>
            @endif
        </div>
    </div>
</main>
@endsection
