@extends('layouts.layout')

@section('title', 'Career Interest history | YuktiOne')
@section('meta_description', 'A private record of your Career Interest Test results.')
@section('body_class', 'yk-cit-body')

@section('content')
<main class="yk-cit yk-tools-ds" data-yk-tools-ds data-yk-cit>
    <div class="yk-cit__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-cit__card" aria-labelledby="cit-history-heading">
            <p class="yk-cit__badge">Private</p>
            <h1 id="cit-history-heading">Assessment history</h1>
            <p class="yk-cit__lead">A private record of Career Interest Test results saved to your profile.</p>

            @if (session('status'))
                <p class="yk-cit__lead">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <p class="yk-cit__error">{{ $errors->first() }}</p>
            @endif

            <div class="yk-cit__actions yk-cit__actions--flush" aria-label="History actions">
                <form method="post" action="{{ route('tools.career-interest.retake') }}">
                    @csrf
                    <button class="yk-cit__btn" type="submit">Retake Assessment</button>
                </form>
                <a class="yk-cit__btn-secondary" href="{{ route('tools.career-interest') }}">Assessment home</a>
            </div>

            @if ($items->isEmpty())
                <div class="yk-cit__empty" role="status">
                    <p class="yk-cit__lead">No saved assessments yet.</p>
                    <p class="yk-cit__note">Complete the Career Interest Test and save it to your profile to build your history.</p>
                </div>
            @else
                <ul class="yk-cit__list yk-cit__list-block">
                    @foreach ($items as $item)
                        @php
                            $tops = collect($item->top_categories ?? [])
                                ->map(fn ($key) => $categories[$key]['label'] ?? $key)
                                ->implode(', ');
                            $when = optional($item->completed_at)->timezone($timezone)?->format('d M Y, g:i A');
                        @endphp
                        <li>
                            <a href="{{ route('tools.career-interest.result', $item) }}">
                                <strong>{{ $tops !== '' ? $tops : 'Interest profile' }}</strong>
                                <br>
                                <small>{{ $when }} · Asia/Kolkata</small>
                                <span class="yk-cit__list-cta">View Result</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                @if ($items->hasPages())
                    <nav class="yk-cit__pages" aria-label="Assessment history pages">
                        @if ($items->previousPageUrl())
                            <a class="yk-cit__btn-secondary" href="{{ $items->previousPageUrl() }}">Newer</a>
                        @endif
                        <span>Page {{ $items->currentPage() }} of {{ $items->lastPage() }}</span>
                        @if ($items->nextPageUrl())
                            <a class="yk-cit__btn-secondary" href="{{ $items->nextPageUrl() }}">Older</a>
                        @endif
                    </nav>
                @endif
            @endif
        </section>
    </div>
</main>
@endsection
