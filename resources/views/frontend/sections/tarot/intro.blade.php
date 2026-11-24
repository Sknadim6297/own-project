@extends('layouts.layout')

@section('title', 'Tarot Reflection | YuktiOne')
@section('meta_description', 'Draw your cards and explore reflective guidance with YuktiOne Tarot Reflection.')
@section('body_class', 'yk-tarot-body')

@section('content')
<main class="yk-tarot yk-tools-ds yk-tool-shell" data-yk-tools-ds data-yk-tarot data-yk-tool-shell="1">
    <div class="yk-tarot__wrap">
        <div class="yk-tarot__toolbar">
            @include('frontend.sections.partials.tool-back-to-tools')
            <a class="yk-tarot__history-link" href="{{ route('tools.tarot.history') }}">
                @include('frontend.sections.tarot.partials.emoji', ['icon' => 'book-open'])
                My Readings
            </a>
        </div>

        <section class="yk-tarot__hero yk-tarot__hero--compact" aria-labelledby="tarot-intro-heading" data-yk-tool-compact-header>
            <div class="yk-tarot__hero-copy">
                <p class="yk-tarot__badge">
                    <x-yk-icon name="moon-star" class="yk-icon--sm" />
                    Reflective tool
                </p>
                <div class="yk-tarot__title-row">
                    <span class="yk-tarot__title-icon" aria-hidden="true">
                        <x-yk-icon name="moon-star" class="yk-icon--lg" />
                    </span>
                    <div>
                        <h1 id="tarot-intro-heading">Tarot Reflection</h1>
                        <p class="yk-tarot__lead" data-yk-tool-page-lead>{{ \App\Support\Tools\ToolPageLeads::for('tarot-reflection') }}</p>
                    </div>
                </div>
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
