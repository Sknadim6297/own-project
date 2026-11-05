@extends('layouts.layout')

@section('title', 'Gratitude entry | YuktiOne')
@section('meta_description', 'A private gratitude entry.')
@section('body_class', 'yk-gratitude-body')

@section('content')
<main class="yk-gratitude yk-gratitude-entry">
    <div class="yk-gratitude__wrap">
        <a class="yk-gratitude__back" href="{{ route('tools.psychology.gratitude.history') }}">
            <span aria-hidden="true">←</span> Back to History
        </a>

        @if (session('status'))
            <p class="yk-gratitude-history__status" role="status">{{ session('status') }}</p>
        @endif

        <p class="yk-gratitude__badge">Private entry</p>
        <h1>Gratitude entry</h1>
        <p class="yk-gratitude-entry__meta">
            <time datetime="{{ $datetime }}">{{ $when }}</time>
        </p>
        <p class="yk-gratitude__private" role="status">Private · Only you can see this entry</p>

        <ul class="yk-gratitude-complete__list yk-gratitude-entry__list">
            @foreach ($cards as $card)
                <li class="yk-gratitude-complete__card @if ($card['skipped']) is-skipped @endif">
                    <h2>{{ $card['label'] }}</h2>
                    @if ($card['skipped'])
                        <p class="yk-gratitude-complete__skipped">Skipped</p>
                    @else
                        <p class="yk-gratitude-complete__answer">{{ $card['answer'] }}</p>
                    @endif
                </li>
            @endforeach
        </ul>

        <div class="yk-gratitude-entry__actions">
            <a class="yk-gratitude-entry__edit" href="{{ route('tools.psychology.gratitude.edit', $entry->id) }}">Edit</a>
            <button
                class="yk-gratitude-entry__delete"
                type="button"
                data-gratitude-delete-open
                aria-haspopup="dialog"
                aria-controls="gratitude-delete-dialog"
            >
                Delete
            </button>
        </div>
    </div>
</main>

<div class="yk-gratitude-dialog" id="gratitude-delete-dialog" hidden data-gratitude-delete-dialog>
    <div class="yk-gratitude-dialog__backdrop" data-gratitude-delete-cancel></div>
    <div
        class="yk-gratitude-dialog__panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="gratitude-delete-title"
        aria-describedby="gratitude-delete-copy"
    >
        <h2 id="gratitude-delete-title">Delete this entry?</h2>
        <p id="gratitude-delete-copy">This permanently removes your private gratitude entry. This cannot be undone.</p>
        <div class="yk-gratitude-dialog__actions">
            <button class="yk-gratitude-dialog__cancel" type="button" data-gratitude-delete-cancel>Keep entry</button>
            <form method="post" action="{{ route('tools.psychology.gratitude.destroy', $entry->id) }}">
                @csrf
                @method('DELETE')
                <button class="yk-gratitude-dialog__confirm" type="submit" data-gratitude-delete-confirm>Delete entry</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const dialog = document.querySelector('[data-gratitude-delete-dialog]');
    const openBtn = document.querySelector('[data-gratitude-delete-open]');
    if (!dialog || !openBtn) return;

    const confirmBtn = dialog.querySelector('[data-gratitude-delete-confirm]');
    let lastFocus = null;

    const open = () => {
        lastFocus = document.activeElement;
        dialog.hidden = false;
        document.body.classList.add('yk-gratitude-dialog-open');
        confirmBtn?.focus();
    };

    const close = () => {
        dialog.hidden = true;
        document.body.classList.remove('yk-gratitude-dialog-open');
        lastFocus?.focus?.();
    };

    openBtn.addEventListener('click', open);
    dialog.querySelectorAll('[data-gratitude-delete-cancel]').forEach((node) => {
        node.addEventListener('click', close);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !dialog.hidden) close();
    });
})();
</script>
@endpush
