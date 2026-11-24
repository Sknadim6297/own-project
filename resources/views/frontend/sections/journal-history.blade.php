@extends('layouts.layout')

@section('title', 'My Journal History | YuktiOne')
@section('meta_description', 'A private record of your journal entries.')
@section('body_class', 'yk-journal-body')

@php
    $promptIcons = [
        'my-thoughts' => 'message-circle',
        'personal-growth' => 'sprout',
        'work-career' => 'briefcase',
        'relationships' => 'heart',
        'difficult-moments' => 'cloud-rain',
        'my-aspirations' => 'sparkles',
    ];
    $heroImage = \App\Support\Tools\ToolIllustration::DIRECTORY.'digital-journal-3d.webp';
    $heroImageExists = file_exists(public_path($heroImage));
    $isFiltered = $filter !== 'all' || ($search ?? '') !== '';
@endphp

@section('content')
<main class="yk-journal yk-journal-history yk-tools-ds" data-yk-tools-ds data-yk-journal>
    <div class="yk-journal__wrap yk-journal__wrap--write yk-journal-history__wrap">
        <div class="yk-journal__topbar">
            <a class="yk-journal__back" href="{{ route('tools.psychology.journal') }}">
                <span aria-hidden="true">←</span> Back to Journal
            </a>
            <a class="yk-journal__history-link yk-journal__history-link--solid" href="{{ route('tools.psychology.journal') }}">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                New entry
            </a>
        </div>

        <div class="yk-journal__panel yk-journal__panel--write">
            <header class="yk-journal__hero yk-journal__hero--compact" data-yk-tool-compact-header>
                <div class="yk-journal__hero-main">
                    <span class="yk-journal__hero-icon" aria-hidden="true">
                        <x-yk-icon name="book-open" class="yk-icon--lg" />
                    </span>
                    <div class="yk-journal__hero-text">
                        <p class="yk-journal__badge">Free tool</p>
                        <h1><x-yuktione.two-tone text="My Journal History" /></h1>
                        <p class="yk-journal__lead">Your private entries, saved only for you.</p>
                    </div>
                </div>
            </header>

            <p class="yk-journal__private" role="status">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2zm-7-2a2 2 0 0 1 4 0v2h-4V7z"/></svg>
                Private · Only you can see these entries
            </p>

            @if (session('status'))
                <p class="yk-journal-history__status" role="status">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.2 4.2L19 7"/></svg>
                    {{ session('status') }}
                </p>
            @endif

            @if (!$hasAny)
                <section class="yk-journal-history__empty">
                    @if ($heroImageExists)
                        <img class="yk-journal-history__empty-art" src="{{ asset($heroImage) }}" alt="" width="140" height="140" loading="lazy" decoding="async">
                    @endif
                    <h2>No journal entries yet</h2>
                    <p>When you save a journal entry, it will appear here.</p>
                    <a class="yk-journal__save" href="{{ route('tools.psychology.journal') }}">Write Journal</a>
                </section>
            @else
                <form class="yk-journal-history__controls" method="get" action="{{ route('tools.psychology.journal.history') }}" role="search">
                    <label class="visually-hidden" for="journal-search">Search journal entries</label>
                    <div class="yk-journal-history__searchrow">
                        <div class="yk-journal-history__searchbox">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                            <input
                                id="journal-search"
                                class="yk-journal-history__search"
                                type="search"
                                name="q"
                                value="{{ $search }}"
                                placeholder="Search journal entries..."
                                maxlength="120"
                            >
                        </div>
                        <button class="yk-journal-history__apply" type="submit">Search</button>
                    </div>

                    <div class="yk-journal-history__filters" role="group" aria-label="Filter journal entries">
                        <a class="yk-journal-history__filter @if ($filter === 'all') is-active @endif" href="{{ route('tools.psychology.journal.history', array_filter(['q' => $search ?: null])) }}">All Entries</a>
                        <a class="yk-journal-history__filter @if ($filter === 'month') is-active @endif" href="{{ route('tools.psychology.journal.history', array_filter(['filter' => 'month', 'q' => $search ?: null, 'month' => $month])) }}">By Month</a>
                        <a class="yk-journal-history__filter @if ($filter === 'prompt') is-active @endif" href="{{ route('tools.psychology.journal.history', array_filter(['filter' => 'prompt', 'q' => $search ?: null, 'prompt' => $prompt])) }}">By Prompt</a>
                    </div>

                    @if ($filter === 'month')
                        <input type="hidden" name="filter" value="month">
                        <label class="visually-hidden" for="journal-month">Month</label>
                        <select id="journal-month" class="yk-journal-history__select" name="month" onchange="this.form.submit()">
                            <option value="">All months</option>
                            @foreach ($months as $monthKey)
                                <option value="{{ $monthKey }}" @selected($month === $monthKey)>
                                    {{ \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->format('F Y') }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    @if ($filter === 'prompt')
                        <input type="hidden" name="filter" value="prompt">
                        <label class="visually-hidden" for="journal-prompt">Prompt</label>
                        <select id="journal-prompt" class="yk-journal-history__select" name="prompt" onchange="this.form.submit()">
                            <option value="">All prompts</option>
                            @foreach ($prompts as $item)
                                <option value="{{ $item['key'] }}" @selected($prompt === $item['key'])>{{ $item['label'] }}</option>
                            @endforeach
                        </select>
                    @endif
                </form>

                <p class="yk-journal-history__count" aria-live="polite">
                    {{ number_format($entries->total()) }} {{ \Illuminate\Support\Str::plural('entry', $entries->total()) }}@if ($isFiltered) found @endif
                </p>

                @if ($groups === [])
                    <section class="yk-journal-history__empty is-filtered">
                        <h2>No matching entries</h2>
                        <p>Try another search or filter, or write a new journal entry.</p>
                        <a class="yk-journal__save" href="{{ route('tools.psychology.journal') }}">Write Journal</a>
                    </section>
                @else
                    @foreach ($groups as $group)
                        <section class="yk-journal-history__month" aria-labelledby="month-{{ $loop->index }}">
                            <h2 id="month-{{ $loop->index }}">
                                <span>{{ $group['label'] }}</span>
                                <small>{{ count($group['items']) }}</small>
                            </h2>
                            <ul class="yk-journal-history__list">
                                @foreach ($group['items'] as $item)
                                    <li class="yk-journal-history__card">
                                        <span class="yk-journal-history__card-icon" aria-hidden="true"><x-yk-icon :name="$promptIcons[$item['prompt_key'] ?? ''] ?? 'pen-line'" class="yk-icon--md" :decorative="true" /></span>
                                        <div class="yk-journal-history__card-body">
                                            <div class="yk-journal-history__card-top">
                                                <h3>{{ $item['title'] }}</h3>
                                                <time datetime="{{ $item['datetime'] }}">{{ $item['when'] }}</time>
                                            </div>
                                            <p class="yk-journal-history__preview">{{ $item['preview'] }}</p>
                                            <a class="yk-journal-history__open" href="{{ route('tools.psychology.journal.show', $item['id']) }}">Open Entry <span aria-hidden="true">→</span></a>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach

                    @if ($entries->hasPages())
                        <nav class="yk-journal-history__pages" aria-label="Journal history pages">
                            @if ($entries->previousPageUrl())
                                <a class="yk-journal-history__page-btn" href="{{ $entries->previousPageUrl() }}"><span aria-hidden="true">←</span> Newer</a>
                            @else
                                <span></span>
                            @endif
                            <span>Page {{ $entries->currentPage() }} of {{ $entries->lastPage() }}</span>
                            @if ($entries->nextPageUrl())
                                <a class="yk-journal-history__page-btn" href="{{ $entries->nextPageUrl() }}">View Older Entries <span aria-hidden="true">→</span></a>
                            @else
                                <span></span>
                            @endif
                        </nav>
                    @endif
                @endif
            @endif
        </div>
    </div>
</main>
@endsection
