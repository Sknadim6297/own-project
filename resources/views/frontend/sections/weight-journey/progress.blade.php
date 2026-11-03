@extends('layouts.layout')

@section('title', 'Weight Progress | YuktiOne')
@section('meta_description', 'Charts and history of recorded weights.')
@section('body_class', 'yk-wj-body')

@section('content')
@php
    $svc = app(\App\Services\WeightJourneyService::class);
    $points = $chart['points'];
    $w = 640; $h = 220; $pad = 28;
    $xs = []; $ys = [];
    if (count($points) >= 1) {
        $min = min(array_map(fn ($p) => (float) $p['weight_kg'], $points));
        $max = max(array_map(fn ($p) => (float) $p['weight_kg'], $points));
        if ($min === $max) { $min -= 1; $max += 1; }
        foreach ($points as $i => $p) {
            $t = count($points) === 1 ? 0.5 : $i / (count($points) - 1);
            $xs[] = $pad + $t * ($w - 2 * $pad);
            $ys[] = $pad + (1 - (((float) $p['weight_kg'] - $min) / ($max - $min))) * ($h - 2 * $pad);
        }
    }
@endphp
<main class="yk-wj">
    <div class="yk-wj__wrap">
        <section class="yk-wj__card">
            <p class="yk-wj__badge">Progress</p>
            <h1>Weight progress</h1>
            <p class="yk-wj__lead">Charts use real measurements only. Missing days stay missing—not zero. Multiple same-day readings use the latest for the chart point ({{ $aggregation }}).</p>

            <nav class="yk-wj__tabs" aria-label="Chart period">
                @foreach ($periods as $key => $days)
                    <a href="{{ route('tools.weight-journey.progress', ['period' => $key]) }}" @class(['is-active' => $period === $key])>
                        {{ ucfirst($key) }}
                    </a>
                @endforeach
            </nav>

            <div class="yk-wj__chart">
                @if ($points === [])
                    <p class="yk-wj__note">No measurements in this period yet.</p>
                @else
                    <svg viewBox="0 0 {{ $w }} {{ $h }}" role="img" aria-label="Weight line chart">
                        <rect x="0" y="0" width="{{ $w }}" height="{{ $h }}" fill="#fbfcfe"></rect>
                        @if (count($xs) === 1)
                            <circle cx="{{ $xs[0] }}" cy="{{ $ys[0] }}" r="5" fill="#316d61"></circle>
                        @else
                            <polyline fill="none" stroke="#316d61" stroke-width="3"
                                points="@foreach($xs as $i){{ $xs[$i] }},{{ $ys[$i] }} @endforeach"></polyline>
                            @foreach ($xs as $i)
                                <circle cx="{{ $xs[$i] }}" cy="{{ $ys[$i] }}" r="4" fill="#9d81db"></circle>
                            @endforeach
                        @endif
                    </svg>
                    <ul class="yk-wj__list" style="margin-top:0.75rem;">
                        @foreach ($points as $p)
                            <li><span>{{ $p['date'] }}</span><strong>{{ $p['label'] }}</strong></li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <h2 style="font-size:1.05rem;margin:1rem 0 0.5rem;">All measurements</h2>
            @if ($history->isEmpty())
                <p class="yk-wj__note">No measurements saved.</p>
            @else
                <ul class="yk-wj__list">
                    @foreach ($history as $row)
                        <li>
                            <span>
                                <strong>{{ $svc->formatWeight((string) $row->weight_kg, $unit) }}</strong>
                                <span class="yk-wj__note" style="display:block;margin:0;">{{ $row->recorded_at->timezone($timezone)->format('d M Y, g:i A') }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
                @if ($history->hasPages())
                    <nav class="yk-wj__pages">
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
                <a class="yk-wj__btn-secondary" href="{{ route('tools.weight-journey') }}">Your journey</a>
            </div>
        </section>
    </div>
</main>
@endsection
