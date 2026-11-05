@extends('layouts.layout')

@section('title', 'Weight Progress | YuktiOne')
@section('meta_description', 'Charts and history of recorded weights.')
@section('body_class', 'yk-wj-body')

@section('content')
@php $svc = app(\App\Services\WeightJourneyService::class); @endphp
<main class="yk-wj">
    <div class="yk-wj__wrap yk-wj__wrap--wide">
        <section class="yk-wj__card">
            <p class="yk-wj__badge">Progress · Asia/Kolkata</p>
            <h1>Weight progress</h1>
            <p class="yk-wj__lead">Real measurements only. Missing days stay blank. Same-day chart points use the latest reading.</p>

            <nav class="yk-wj__tabs" aria-label="Chart period">
                @foreach ($periods as $key => $days)
                    <a href="{{ route('tools.weight-journey.progress', ['period' => $key]) }}" @class(['is-active' => $period === $key])>
                        {{ ucfirst($key) }}
                    </a>
                @endforeach
            </nav>

            @include('frontend.sections.weight-journey.partials.chart', ['chart' => $chart])

            <h2 class="yk-wj__section-title" style="margin-top:1.15rem;">All measurements</h2>
            @if ($history->isEmpty())
                <div class="yk-wj__empty" role="status">No measurements saved.</div>
            @else
                <ul class="yk-wj__list yk-wj__list--history">
                    @foreach ($history as $index => $row)
                        @php
                            // Newest-first list; delta vs the next older item when present on this page.
                            $older = $history->get($index + 1);
                            $delta = $older
                                ? $svc->formatNetChange(
                                    bcsub((string) $row->weight_kg, (string) $older->weight_kg, 3),
                                    $unit
                                )
                                : null;
                        @endphp
                        <li>
                            <div>
                                <strong>{{ $svc->formatWeight((string) $row->weight_kg, $unit) }}</strong>
                                @if ($delta)
                                    <span class="yk-wj__delta">{{ $delta }}</span>
                                @endif
                                <div class="yk-wj__meta">{{ $row->recorded_at->timezone($timezone)->format('d M Y, g:i A') }}</div>
                                @if ($row->note)
                                    <div class="yk-wj__meta">{{ $row->note }}</div>
                                @endif
                            </div>
                            <a class="yk-wj__text-link" href="{{ route('tools.weight-journey.log') }}">Edit on log</a>
                        </li>
                    @endforeach
                </ul>
                @if ($history->hasPages())
                    <nav class="yk-wj__pages" aria-label="Measurement pages">
                        @if ($history->previousPageUrl())
                            <a class="yk-wj__btn-secondary" href="{{ $history->previousPageUrl() }}">Newer</a>
                        @endif
                        <span>Page {{ $history->currentPage() }} of {{ $history->lastPage() }}</span>
                        @if ($history->nextPageUrl())
                            <a class="yk-wj__btn-secondary" href="{{ $history->nextPageUrl() }}">Older</a>
                        @endif
                    </nav>
                @endif
            @endif

            <div class="yk-wj__actions">
                <a class="yk-wj__btn" href="{{ route('tools.weight-journey.log') }}">Log weight</a>
                <a class="yk-wj__btn-secondary" href="{{ route('tools.weight-journey') }}">Dashboard</a>
            </div>
        </section>
    </div>
</main>
@endsection
