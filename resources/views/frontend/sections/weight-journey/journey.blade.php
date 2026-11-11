@extends('layouts.layout')

@section('title', 'Your Journey | Weight Journey | YuktiOne')
@section('meta_description', 'Overview of your weight tracking journey.')
@section('body_class', 'yk-wj-body')

@section('content')
@php
    $goal = $summary['goal'];
    $directionLabel = $goal
        ? (\App\Support\WeightJourney\WeightJourneyCatalog::DIRECTIONS[$goal->direction] ?? $goal->direction)
        : null;
    $svc = app(\App\Services\WeightJourneyService::class);
@endphp
<main class="yk-wj yk-tools-ds" data-yk-tools-ds data-yk-wj>
    <div class="yk-wj__wrap yk-wj__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-wj__card" aria-labelledby="wj-journey-heading">
            <p class="yk-wj__badge">Your journey · Asia/Kolkata</p>
            <h1 id="wj-journey-heading">Weight Journey</h1>
            <p class="yk-wj__lead">Personal tracking only—changes are not labeled as success or failure.</p>

            @if ($status)
                <p class="yk-wj__ok" role="status">{{ $status }}</p>
            @endif

            <div class="yk-wj__stats yk-wj__stats--4" aria-label="Journey summary">
                <div>
                    <strong>{{ $summary['goal_starting_label'] ?? $summary['starting_label'] ?? '—' }}</strong>
                    <span>Starting</span>
                </div>
                <div>
                    <strong>{{ $summary['latest_label'] ?? '—' }}</strong>
                    <span>Current</span>
                </div>
                <div>
                    <strong>{{ $summary['target_label'] ?? '—' }}</strong>
                    <span>Target</span>
                </div>
                <div>
                    <strong>{{ $summary['net_label'] ?? '—' }}</strong>
                    <span>Net change</span>
                </div>
            </div>

            @if ($goal)
                <div class="yk-wj__goal-panel">
                    <div class="yk-wj__goal-meta">
                        <span class="yk-wj__pill">{{ $directionLabel }}</span>
                        @if ($summary['distance_label'] !== null)
                            <span class="yk-wj__meta">From target {{ $summary['distance_label'] }}</span>
                        @endif
                    </div>
                    @if (($summary['progress']['meaningful'] ?? false) && isset($summary['progress']['percent']))
                        <div class="yk-wj__progress-bar" role="img" aria-label="{{ $summary['progress']['percent'] }} percent of directional distance toward target">
                            <span style="width: {{ $summary['progress']['percent'] }}%"></span>
                        </div>
                        <p class="yk-wj__note" style="margin:0.45rem 0 0;">{{ $summary['progress']['percent'] }}% of distance toward target (geometric only).</p>
                    @elseif (in_array($summary['progress']['reason'] ?? null, ['maintain', 'track'], true))
                        <p class="yk-wj__note" style="margin:0.35rem 0 0;">Maintain / track goals do not use a percentage-complete indicator.</p>
                    @endif
                </div>
            @else
                <div class="yk-wj__empty" role="status">No active goal. Track anytime or set an optional goal.</div>
            @endif

            @if ($latestChange)
                <p class="yk-wj__change">Latest change vs previous log: <strong>{{ $latestChange['signed'] }}</strong></p>
            @endif

            <div class="yk-wj__chart-head">
                <h2 class="yk-wj__section-title">Progress (30 days)</h2>
                <a class="yk-wj__text-link" href="{{ route('tools.weight-journey.progress') }}">Full chart &amp; history</a>
            </div>
            @include('frontend.sections.weight-journey.partials.chart', ['chart' => $chart])

            <div class="yk-wj__logs-head">
                <h2 class="yk-wj__section-title">Recent measurements</h2>
                <a class="yk-wj__text-link" href="{{ route('tools.weight-journey.log') }}">Manage</a>
            </div>
            @if ($recent->isEmpty())
                <div class="yk-wj__empty" role="status">No measurements yet. Log a weight to begin.</div>
            @else
                <ul class="yk-wj__list">
                    @foreach ($recent as $index => $row)
                        @php
                            $prev = $recent->get($index + 1);
                            $delta = $prev
                                ? $svc->formatNetChange(
                                    bcsub((string) $row->weight_kg, (string) $prev->weight_kg, 3),
                                    $summary['unit']
                                )
                                : null;
                        @endphp
                        <li>
                            <div>
                                <strong>{{ $svc->formatWeight((string) $row->weight_kg, $summary['unit']) }}</strong>
                                @if ($delta)
                                    <span class="yk-wj__delta">{{ $delta }}</span>
                                @endif
                                <div class="yk-wj__meta">{{ $row->recorded_at->timezone($timezone)->format('d M Y, g:i A') }}</div>
                                @if ($row->note)
                                    <div class="yk-wj__meta">{{ $row->note }}</div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="yk-wj__actions">
                <a class="yk-wj__btn" href="{{ route('tools.weight-journey.log') }}">Log weight</a>
                <a class="yk-wj__btn-secondary" href="{{ route('tools.weight-journey.goal') }}">Edit goal</a>
                <a class="yk-wj__btn-secondary" href="{{ route('tools.weight-journey.progress') }}">History</a>
            </div>
        </section>
    </div>
</main>
@endsection
