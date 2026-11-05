@extends('layouts.layout')

@section('title', 'My Gratitude History | YuktiOne')
@section('meta_description', 'Your little moments of gratitude.')
@section('body_class', 'yk-gratitude-body')

@section('content')
<main class="yk-gratitude yk-gratitude-history">
    <div class="yk-gratitude__wrap yk-gratitude-history__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <a class="yk-gratitude__back" href="{{ route('tools.psychology.gratitude') }}">
            <span aria-hidden="true">←</span> Gratitude home
        </a>

        <p class="yk-gratitude__badge">Private journal</p>
        <h1>My Gratitude History</h1>
        <p class="yk-gratitude__lead">Your little moments of gratitude</p>

        @if (session('status'))
            <p class="yk-gratitude-history__status" role="status">{{ session('status') }}</p>
        @endif

        @if (!$hasAny)
            <section class="yk-gratitude-history__empty">
                <h2>No gratitude entries yet</h2>
                <p>When you save a gratitude entry, it will appear here.</p>
                <a class="yk-gratitude__save" href="{{ route('tools.psychology.gratitude') }}">Write Gratitude</a>
            </section>
        @else
            <div class="yk-gratitude-history__stats" aria-label="Gratitude summary">
                <div>
                    <p class="yk-gratitude-history__stat-value">{{ $totalEntries }}</p>
                    <p class="yk-gratitude-history__stat-label">Total entries</p>
                </div>
                <div>
                    <p class="yk-gratitude-history__stat-value">{{ $daysThisMonth }}</p>
                    <p class="yk-gratitude-history__stat-label">Days this month</p>
                </div>
            </div>

            <section class="yk-gratitude-calendar" aria-labelledby="gratitude-calendar-heading">
                <div class="yk-gratitude-calendar__nav">
                    <a
                        class="yk-gratitude-calendar__prev"
                        href="{{ route('tools.psychology.gratitude.history', ['month' => $calendar['previous']]) }}"
                        aria-label="Previous month"
                    >←</a>
                    <h2 id="gratitude-calendar-heading">{{ $calendar['label'] }}</h2>
                    <a
                        class="yk-gratitude-calendar__next"
                        href="{{ route('tools.psychology.gratitude.history', ['month' => $calendar['next']]) }}"
                        aria-label="Next month"
                    >→</a>
                </div>
                <div class="yk-gratitude-calendar__weekdays" aria-hidden="true">
                    <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
                </div>
                <div class="yk-gratitude-calendar__grid">
                    @foreach ($calendar['weeks'] as $week)
                        @foreach ($week as $cell)
                            @if ($cell['day'] === null)
                                <span class="yk-gratitude-calendar__day is-empty"></span>
                            @else
                                <span
                                    class="yk-gratitude-calendar__day @if ($cell['has_entry']) is-marked @endif @if ($cell['is_today']) is-today @endif"
                                    @if ($cell['has_entry']) title="Entry saved" @endif
                                >{{ $cell['day'] }}</span>
                            @endif
                        @endforeach
                    @endforeach
                </div>
            </section>

            <section class="yk-gratitude-history__recent" aria-labelledby="recent-entries-heading">
                <h2 id="recent-entries-heading">Recent Entries</h2>

                @if ($items === [])
                    <p class="yk-gratitude-history__empty-page">No entries on this page.</p>
                @else
                    <ul class="yk-gratitude-history__list">
                        @foreach ($items as $item)
                            <li class="yk-gratitude-history__card">
                                <div class="yk-gratitude-history__card-top">
                                    <time datetime="{{ $item['datetime'] }}">{{ $item['when'] }}</time>
                                    <p class="yk-gratitude-history__count">{{ $item['answered_count'] }} {{ \Illuminate\Support\Str::plural('response', $item['answered_count']) }}</p>
                                </div>
                                <p class="yk-gratitude-history__categories">{{ implode(' · ', $item['categories']) }}</p>
                                <a class="yk-gratitude-history__open" href="{{ route('tools.psychology.gratitude.show', $item['id']) }}">Open Entry</a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($entries->hasPages())
                    <nav class="yk-gratitude-history__pages" aria-label="Gratitude history pages">
                        <span>Page {{ $entries->currentPage() }} of {{ $entries->lastPage() }}</span>
                        @if ($entries->hasMorePages())
                            <a href="{{ $entries->nextPageUrl() }}">View Older Entries</a>
                        @elseif ($entries->previousPageUrl())
                            <a href="{{ $entries->previousPageUrl() }}">Newer Entries</a>
                        @endif
                    </nav>
                @endif
            </section>
        @endif
    </div>
</main>
@endsection
