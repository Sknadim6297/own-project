@extends('layouts.layout')

@section('title', 'My Journal History | YuktiOne')
@section('meta_description', 'A private record of your journal entries.')
@section('body_class', 'yk-journal-body')

@section('content')
<main class="yk-journal yk-journal-history">
    <div class="yk-journal__wrap yk-journal-history__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <a class="yk-journal__back" href="{{ route('tools.psychology') }}">
            <span aria-hidden="true">←</span> Psychology tools
        </a>

        <p class="yk-journal__badge">Free tool</p>
        <h1>My Journal History</h1>
        <p class="yk-journal__lead">Your private entries, saved only for you.</p>

        @if (session('status'))
            <p class="yk-journal-history__status" role="status">{{ session('status') }}</p>
        @endif

        @if (!$hasAny)
            <section class="yk-journal-history__empty">
                <h2>No journal entries yet</h2>
                <p>When you save a journal entry, it will appear here.</p>
                <a class="yk-journal__save" href="{{ route('tools.psychology.journal') }}">Write Journal</a>
            </section>
        @else
            <form class="yk-journal-history__controls" method="get" action="{{ route('tools.psychology.journal.history') }}" role="search">
                <label class="visually-hidden" for="journal-search">Search journal entries</label>
                <input
                    id="journal-search"
                    class="yk-journal-history__search"
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Search journal entries..."
                    maxlength="120"
                >

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

                <button class="yk-journal-history__apply" type="submit">Search</button>
            </form>

            @if ($groups === [])
                <section class="yk-journal-history__empty is-filtered">
                    <h2>No matching entries</h2>
                    <p>Try another search or filter, or write a new journal entry.</p>
                    <a class="yk-journal__save" href="{{ route('tools.psychology.journal') }}">Write Journal</a>
                </section>
            @else
                @foreach ($groups as $group)
                    <section class="yk-journal-history__month" aria-labelledby="month-{{ $loop->index }}">
                        <h2 id="month-{{ $loop->index }}">{{ $group['label'] }}</h2>
                        <ul class="yk-journal-history__list">
                            @foreach ($group['items'] as $item)
                                <li class="yk-journal-history__card">
                                    <div class="yk-journal-history__card-top">
                                        <h3>{{ $item['title'] }}</h3>
                                        <time datetime="{{ $item['datetime'] }}">{{ $item['when'] }}</time>
                                    </div>
                                    <p class="yk-journal-history__prompt">{{ $item['prompt'] }}</p>
                                    <p class="yk-journal-history__preview">{{ $item['preview'] }}</p>
                                    <a class="yk-journal-history__open" href="{{ route('tools.psychology.journal.show', $item['id']) }}">Open Entry</a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach

                @if ($entries->hasPages())
                    <nav class="yk-journal-history__pages" aria-label="Journal history pages">
                        @if ($entries->previousPageUrl())
                            <a href="{{ $entries->previousPageUrl() }}">Newer</a>
                        @else
                            <span></span>
                        @endif
                        <span>Page {{ $entries->currentPage() }} of {{ $entries->lastPage() }}</span>
                        @if ($entries->nextPageUrl())
                            <a href="{{ $entries->nextPageUrl() }}">View Older Entries</a>
                        @else
                            <span></span>
                        @endif
                    </nav>
                @endif
            @endif
        @endif
    </div>
</main>
@endsection
