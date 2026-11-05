@extends('layouts.layout')

@section('title', 'Mood history | YuktiOne')
@section('meta_description', 'A private record of the moments you checked in.')
@section('body_class', 'yk-mood-body')

@section('content')
<main class="yk-mood yk-history">
    <div class="yk-mood__wrap yk-history__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <a class="yk-mood__back" href="{{ route('tools.psychology') }}">Psychology tools</a>
        <h1>Mood history</h1>
        <p class="yk-mood__lead">A private record of the moments you checked in.</p>

        @if ($items === [])
            <section class="yk-history__empty">
                <h2>No check-ins yet</h2>
                <p>When you finish a check-in, it will appear here.</p>
                <a class="yk-mood__continue" href="{{ route('tools.psychology.mood') }}">Start a check-in</a>
            </section>
        @else
            <ol class="yk-history__list">
                @foreach ($items as $item)
                    <li class="yk-history__card">
                        <time datetime="{{ $item['datetime'] }}">{{ $item['when'] }}</time>
                        <h2>{{ $item['mood'] }}</h2>
                        @if ($item['emotions'] === [])
                            <p class="yk-history__muted">No emotions selected.</p>
                        @else
                            <ul class="yk-history__chips">
                                @foreach ($item['emotions'] as $emotion)
                                    <li class="yk-emotion__chip is-{{ $emotion['tone'] }}">{{ $emotion['label'] }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if (filled($item['reflection']))
                            <p class="yk-history__note">{{ $item['reflection'] }}</p>
                        @else
                            <p class="yk-history__muted">No reflection added.</p>
                        @endif
                    </li>
                @endforeach
            </ol>

            @if ($entries->hasPages())
                <nav class="yk-history__pages" aria-label="Mood history pages">
                    @if ($entries->previousPageUrl())
                        <a href="{{ $entries->previousPageUrl() }}">Newer</a>
                    @else
                        <span></span>
                    @endif
                    <span>Page {{ $entries->currentPage() }} of {{ $entries->lastPage() }}</span>
                    @if ($entries->nextPageUrl())
                        <a href="{{ $entries->nextPageUrl() }}">Older</a>
                    @else
                        <span></span>
                    @endif
                </nav>
            @endif
        @endif
    </div>
</main>
@endsection
