@extends('layouts.layout')

@section('title', $title.' | YuktiOne')
@section('meta_description', 'A private journal entry.')
@section('body_class', 'yk-journal-body')

@section('content')
<main class="yk-journal yk-journal-entry yk-tools-ds" data-yk-tools-ds data-yk-journal>
    <div class="yk-journal__wrap">
        <a class="yk-journal__back" href="{{ route('tools.psychology.journal.history') }}">
            <span aria-hidden="true">←</span> Back to history
        </a>

        <div class="yk-journal__panel">
        @if (session('status'))
            <p class="yk-journal-history__status" role="status">{{ session('status') }}</p>
        @endif

        <p class="yk-journal__badge">Private entry</p>
        <h1>{{ $title }}</h1>
        <p class="yk-journal-entry__meta">
            <span>{{ $prompt }}</span>
            <span aria-hidden="true">·</span>
            <time>{{ $when }}</time>
        </p>
        @if ($question)
            <p class="yk-journal-entry__question">{{ $question }}</p>
        @endif
        <p class="yk-journal__private" role="status">Private · Only you can see this entry</p>
        <div class="yk-journal-entry__body">{{ $body }}</div>

        @include('frontend.sections.partials.psychology-connect', [
            'heading' => 'Would you like to talk to someone?',
            'copy' => 'A supportive conversation with a psychologist can help you explore your feelings and thoughts.',
            'cta' => 'Connect with a Psychologist',
            'url' => $psychologistsUrl,
            'headingId' => 'journal-connect-heading',
        ])

        <div class="yk-journal-entry__actions">
            <a class="yk-journal-entry__edit" href="{{ route('tools.psychology.journal.edit', $entry->id) }}">Edit</a>
            <button
                class="yk-journal-entry__delete"
                type="button"
                data-journal-delete-open
                aria-haspopup="dialog"
                aria-controls="journal-delete-dialog"
            >
                Delete
            </button>
        </div>
        </div>
    </div>
</main>

<div class="yk-journal-dialog" id="journal-delete-dialog" hidden data-journal-delete-dialog>
    <div class="yk-journal-dialog__backdrop" data-journal-delete-cancel></div>
    <div
        class="yk-journal-dialog__panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="journal-delete-title"
        aria-describedby="journal-delete-copy"
    >
        <h2 id="journal-delete-title">Delete this entry?</h2>
        <p id="journal-delete-copy">This permanently removes your private journal entry. This cannot be undone.</p>
        <div class="yk-journal-dialog__actions">
            <button class="yk-journal-dialog__cancel" type="button" data-journal-delete-cancel>Keep entry</button>
            <form method="post" action="{{ route('tools.psychology.journal.destroy', $entry->id) }}">
                @csrf
                @method('DELETE')
                <button class="yk-journal-dialog__confirm" type="submit" data-journal-delete-confirm>Delete entry</button>
            </form>
        </div>
    </div>
</div>
<script src="{{ asset('frontend/assets/js/yuktione-journal.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-journal.js')) }}" defer></script>
@endsection
