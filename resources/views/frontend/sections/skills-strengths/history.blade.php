@extends('layouts.layout')

@section('title', 'Skills & Strengths history | YuktiOne')
@section('meta_description', 'A private record of your Skills & Strengths Assessment results.')
@section('body_class', 'yk-ssa-body')

@section('content')
<main class="yk-ssa">
    <div class="yk-ssa__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-ssa__card" aria-labelledby="ssa-history-heading">
            <p class="yk-ssa__badge">Private</p>
            <h1 id="ssa-history-heading">Assessment history</h1>
            <p class="yk-ssa__lead">A private record of Skills &amp; Strengths results saved to your profile.</p>

            @if (session('status'))
                <p class="yk-ssa__lead">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <p class="yk-ssa__error">{{ $errors->first() }}</p>
            @endif

            <div class="yk-ssa__actions" style="margin-top:0;">
                <form method="post" action="{{ route('tools.skills-strengths.retake') }}">
                    @csrf
                    <button class="yk-ssa__btn" type="submit">Retake assessment</button>
                </form>
                <a class="yk-ssa__btn-secondary" href="{{ route('tools.skills-strengths') }}">Assessment home</a>
            </div>

            @if ($items->isEmpty())
                <p class="yk-ssa__lead" style="margin-top:1.2rem;">No saved assessments yet.</p>
            @else
                <ul class="yk-ssa__history">
                    @foreach ($items as $item)
                        @php
                            $tops = collect($item->strengths ?? [])
                                ->take(3)
                                ->map(fn ($row) => $row['label'] ?? '')
                                ->filter()
                                ->implode(', ');
                            $when = optional($item->completed_at)->timezone($timezone)?->format('d M Y, g:i A');
                        @endphp
                        <li>
                            <a href="{{ route('tools.skills-strengths.result', $item) }}">
                                <strong>{{ $tops !== '' ? $tops : 'Skills profile' }}</strong>
                                <br>
                                <small>{{ $when }} · Asia/Kolkata</small>
                            </a>
                        </li>
                    @endforeach
                </ul>

                @if ($items->hasPages())
                    <nav class="yk-ssa__pages" aria-label="Assessment history pages">
                        @if ($items->previousPageUrl())
                            <a class="yk-ssa__btn-secondary" href="{{ $items->previousPageUrl() }}">Newer</a>
                        @endif
                        <span>Page {{ $items->currentPage() }} of {{ $items->lastPage() }}</span>
                        @if ($items->nextPageUrl())
                            <a class="yk-ssa__btn-secondary" href="{{ $items->nextPageUrl() }}">Older</a>
                        @endif
                    </nav>
                @endif
            @endif
        </section>
    </div>
</main>
@endsection
