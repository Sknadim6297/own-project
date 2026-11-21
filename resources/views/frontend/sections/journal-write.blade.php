@extends('layouts.layout')

@section('title', ($editing ? 'Edit journal entry' : 'Digital Journal').' | YuktiOne')
@section('meta_description', 'Express your thoughts in your own words with a private journal entry.')
@section('body_class', 'yk-journal-body')

@php
    $promptIcons = [
        'my-thoughts' => '💭',
        'personal-growth' => '🌱',
        'work-career' => '💼',
        'relationships' => '❤️',
        'difficult-moments' => '🌧️',
        'my-aspirations' => '✨',
    ];
    $currentBody = old('body', $body);
    $selectedQuestion = null;
    foreach ($prompts as $p) {
        if ($selected === $p['key']) {
            $selectedQuestion = $p['question'] ?? null;
        }
    }
    $heroImage = \App\Support\Tools\ToolIllustration::DIRECTORY.'digital-journal-3d.webp';
    $heroImageExists = file_exists(public_path($heroImage));
    $lockSvg = '<svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2zm-7-2a2 2 0 0 1 4 0v2h-4V7z"/></svg>';
@endphp

@section('content')
<main class="yk-journal yk-tools-ds" data-yk-tools-ds data-yk-journal>
    <div class="yk-journal__wrap yk-journal__wrap--write">
        <div class="yk-journal__topbar">
            @if ($editing)
                <a class="yk-journal__back" href="{{ $backUrl }}">
                    <span aria-hidden="true">←</span> Back
                </a>
            @else
                <a class="yk-journal__back" href="{{ route('tools.index') }}">
                    <span aria-hidden="true">←</span> Back to Tools
                </a>
            @endif
            @if (!$editing && $open)
                <a class="yk-journal__history-link" href="{{ route('tools.psychology.journal.history') }}">
                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 5.5A1.5 1.5 0 0 1 3.5 4H10a2 2 0 0 1 2 2v14a2 2 0 0 0-2-2H3.5A1.5 1.5 0 0 1 2 16.5v-11z"/><path d="M22 5.5A1.5 1.5 0 0 0 20.5 4H14a2 2 0 0 0-2 2v14a2 2 0 0 1 2-2h6.5a1.5 1.5 0 0 0 1.5-1.5v-11z"/></svg>
                    My entries
                </a>
            @endif
        </div>

        @if (!$open)
            <section class="yk-journal__closed yk-journal__panel" aria-labelledby="journal-closed-heading">
                <p class="yk-journal__badge">{{ $editing ? 'Private entry' : 'Free tool' }}</p>
                <h1 id="journal-closed-heading"><x-yuktione.two-tone :text="$heading" /></h1>
                <p>{{ $editing ? 'Editing is unavailable right now.' : 'This journal is unavailable right now.' }}</p>
            </section>
        @else
            <form
                class="yk-journal__form yk-journal__panel yk-journal__panel--write"
                method="post"
                action="{{ $formAction }}"
                data-journal-form
                data-title-max="{{ $titleMax }}"
                data-body-max="{{ $bodyMax }}"
                @if ($preserveTitle) data-preserve-title="1" @endif
            >
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <header class="yk-journal__hero">
                    <div class="yk-journal__hero-main">
                        <span class="yk-journal__hero-icon" aria-hidden="true">
                            <svg viewBox="0 0 48 48" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 8h20a4 4 0 0 1 4 4v28H16a4 4 0 0 1-4-4V8z"/>
                                <path d="M12 36a4 4 0 0 1 4-4h20"/>
                                <path d="M20 16h10M20 22h6"/>
                                <path d="M38 14l4 4-10 10-5 1 1-5 10-10z" fill="currentColor" fill-opacity=".2"/>
                            </svg>
                        </span>
                        <div class="yk-journal__hero-text">
                            <p class="yk-journal__badge">{{ $editing ? 'Private entry' : 'Free tool' }}</p>
                            <h1><x-yuktione.two-tone :text="$heading" /></h1>
                            <p class="yk-journal__lead">{{ $lead }}</p>
                        </div>
                    </div>
                    @if ($heroImageExists)
                        <div class="yk-journal__hero-art" aria-hidden="true">
                            <img src="{{ asset($heroImage) }}" alt="" width="180" height="180" loading="eager" decoding="async">
                        </div>
                    @endif
                </header>

                <p class="yk-journal__private" role="status">{!! $lockSvg !!} Private · Only you can see this entry</p>

                @if ($errors->any())
                    <p class="yk-journal__error" role="alert">{{ $errors->first() }}</p>
                @endif

                <fieldset class="yk-journal__prompts">
                    <legend><span class="yk-journal__step" aria-hidden="true">1</span> Choose a prompt</legend>
                    <ul>
                        @foreach ($prompts as $prompt)
                            <li>
                                <label
                                    class="yk-journal__prompt @if ($selected === $prompt['key']) is-selected @endif"
                                    data-journal-prompt
                                    data-prompt-label="{{ $prompt['label'] }}"
                                    data-prompt-question="{{ $prompt['question'] ?? '' }}"
                                >
                                    <input class="yk-journal__prompt-input" type="radio" name="prompt_key" value="{{ $prompt['key'] }}" @checked($selected === $prompt['key']) required>
                                    <span class="yk-journal__prompt-icon" aria-hidden="true">{{ $promptIcons[$prompt['key']] ?? '✍️' }}</span>
                                    <span class="yk-journal__prompt-label">{{ $prompt['label'] }}</span>
                                    <span class="yk-journal__prompt-check" aria-hidden="true">
                                        <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8.5l3.2 3L13 4.5"/></svg>
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </fieldset>

                <div class="yk-journal__field">
                    <label class="yk-journal__label" for="journal-title">
                        <span class="yk-journal__step" aria-hidden="true">2</span>
                        Title
                        <span class="yk-journal__optional">Optional</span>
                    </label>
                    <input
                        id="journal-title"
                        class="yk-journal__title"
                        type="text"
                        name="title"
                        value="{{ old('title', $title) }}"
                        maxlength="{{ $titleMax }}"
                        placeholder="Add a title, or keep the prompt suggestion"
                        autocomplete="off"
                        data-journal-title
                    >
                </div>

                <div class="yk-journal__field yk-journal__write">
                    <label class="yk-journal__label" for="journal-body">
                        <span class="yk-journal__step" aria-hidden="true">3</span>
                        Your thoughts
                        <span class="yk-journal__required">Required</span>
                    </label>

                    <p class="yk-journal__guide" data-journal-guide @if (!$selectedQuestion) hidden @endif>
                        <span aria-hidden="true">💡</span>
                        <span data-journal-guide-text>{{ $selectedQuestion }}</span>
                    </p>

                    <textarea
                        id="journal-body"
                        class="yk-journal__body"
                        name="body"
                        rows="8"
                        maxlength="{{ $bodyMax }}"
                        required
                        placeholder="Start writing here…"
                        data-journal-body
                        aria-describedby="journal-body-count"
                    >{{ $currentBody }}</textarea>
                    <div class="yk-journal__progress" aria-hidden="true">
                        <span data-journal-progress style="width: {{ min(100, round(mb_strlen($currentBody) / max(1, $bodyMax) * 100, 1)) }}%"></span>
                    </div>
                    <div class="yk-journal__meta" id="journal-body-count">
                        <span>Up to {{ number_format($bodyMax) }} characters</span>
                        <span data-journal-count>{{ mb_strlen($currentBody) }} / {{ number_format($bodyMax) }}</span>
                    </div>
                </div>

                <button class="yk-journal__save" type="submit">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.2 4.2L19 7"/></svg>
                    {{ $submitLabel }}
                </button>
                <p class="yk-journal__foot">{!! $lockSvg !!} Your writing is saved privately to your account. It is never shared automatically.</p>
            </form>
        @endif
    </div>
</main>
@if ($open)
<script src="{{ asset('frontend/assets/js/yuktione-journal.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-journal.js')) }}" defer></script>
@endif
@endsection
