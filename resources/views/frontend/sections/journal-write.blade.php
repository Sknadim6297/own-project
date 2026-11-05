@extends('layouts.layout')

@section('title', ($editing ? 'Edit journal entry' : 'Digital Journal').' | YuktiOne')
@section('meta_description', 'Express your thoughts in your own words with a private journal entry.')
@section('body_class', 'yk-journal-body')

@section('content')
<main class="yk-journal">
    <div class="yk-journal__wrap">
        <a class="yk-journal__back" href="{{ $backUrl }}">
            <span aria-hidden="true">←</span> Back
        </a>

        @if (!$open)
            <section class="yk-journal__closed" aria-labelledby="journal-closed-heading">
                <p class="yk-journal__badge">{{ $editing ? 'Private entry' : 'Free tool' }}</p>
                <h1 id="journal-closed-heading">{{ $heading }}</h1>
                <p>{{ $editing ? 'Editing is unavailable right now.' : 'This journal is unavailable right now.' }}</p>
            </section>
        @else
            <form
                class="yk-journal__form"
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
                <p class="yk-journal__badge">{{ $editing ? 'Private entry' : 'Free tool' }}</p>
                <h1>{{ $heading }}</h1>
                <p class="yk-journal__lead">{{ $lead }}</p>
                <p class="yk-journal__private" role="status">Private · Only you can see this entry</p>

                @if ($errors->any())
                    <p class="yk-journal__error">{{ $errors->first() }}</p>
                @endif

                <fieldset class="yk-journal__prompts">
                    <legend>Choose a prompt</legend>
                    <ul>
                        @foreach ($prompts as $prompt)
                            <li>
                                <label class="yk-journal__prompt @if ($selected === $prompt['key']) is-selected @endif" data-journal-prompt data-prompt-label="{{ $prompt['label'] }}">
                                    <input class="yk-journal__prompt-input" type="radio" name="prompt_key" value="{{ $prompt['key'] }}" @checked($selected === $prompt['key']) required>
                                    <span class="yk-journal__prompt-label">{{ $prompt['label'] }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </fieldset>

                <div class="yk-journal__field">
                    <label class="yk-journal__label" for="journal-title">
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

                <div class="yk-journal__field">
                    <label class="yk-journal__label" for="journal-body">
                        Your thoughts
                        <span class="yk-journal__required">Required</span>
                    </label>
                    <textarea
                        id="journal-body"
                        class="yk-journal__body"
                        name="body"
                        rows="8"
                        maxlength="{{ $bodyMax }}"
                        required
                        placeholder="Write freely. This space is yours."
                        data-journal-body
                        aria-describedby="journal-body-count"
                    >{{ old('body', $body) }}</textarea>
                    <div class="yk-journal__meta" id="journal-body-count">
                        <span>Up to {{ number_format($bodyMax) }} characters</span>
                        <span data-journal-count>{{ mb_strlen(old('body', $body)) }} / {{ number_format($bodyMax) }}</span>
                    </div>
                </div>

                <button class="yk-journal__save" type="submit">{{ $submitLabel }}</button>
            </form>
        @endif
    </div>
</main>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.querySelector('[data-journal-form]');
    if (!form) return;

    const title = form.querySelector('[data-journal-title]');
    const body = form.querySelector('[data-journal-body]');
    const count = form.querySelector('[data-journal-count]');
    const bodyMax = Number(form.dataset.bodyMax || 5000);
    let titleTouched = form.dataset.preserveTitle === '1'
        || Boolean(title && title.value.trim() !== '' && !form.querySelector('[data-journal-prompt].is-selected'));

    const syncCount = () => {
        if (!body || !count) return;
        count.textContent = `${body.value.length} / ${bodyMax.toLocaleString()}`;
    };

    body?.addEventListener('input', syncCount);
    title?.addEventListener('input', () => { titleTouched = true; });

    form.querySelectorAll('[data-journal-prompt]').forEach((label) => {
        const input = label.querySelector('input');
        input?.addEventListener('change', () => {
            form.querySelectorAll('[data-journal-prompt]').forEach((node) => node.classList.remove('is-selected'));
            label.classList.add('is-selected');
            if (title && !titleTouched) {
                title.value = label.dataset.promptLabel || '';
            }
        });
    });

    syncCount();
})();
</script>
@endpush
