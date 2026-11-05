@extends('layouts.layout')

@section('title', 'Weekly Hydration | YuktiOne')
@section('meta_description', 'Seven-day water intake chart from your saved entries.')
@section('body_class', 'yk-water-body')

@section('content')
<main class="yk-water">
    <div class="yk-water__wrap yk-water__wrap--wide">
        <section class="yk-water__card">
            <p class="yk-water__badge">7-day history · Asia/Kolkata</p>
            <h1>Weekly hydration</h1>
            <p class="yk-water__lead">
                @if ($avg_label)
                    Average on days with logs: {{ $avg_label }}.
                @else
                    No logged days in this range yet.
                @endif
            </p>

            <div class="yk-water__bars" role="img" aria-label="Weekly water intake bars">
                @foreach ($days as $day)
                    @php
                        $height = max(4, (int) round(($day['total_ml'] / $max) * 140));
                        $classes = 'yk-water__bar';
                        if ($day['is_today']) $classes .= ' is-today';
                        if (! $day['has_entries']) $classes .= ' is-empty';
                        $pct = $day['goal_ml'] > 0 ? (int) round(($day['total_ml'] / $day['goal_ml']) * 100) : 0;
                    @endphp
                    <a class="{{ $classes }}" href="{{ route('tools.water-intake.today', ['date' => $day['date']]) }}" title="{{ $day['date'] }}">
                        <div class="yk-water__bar-fill" style="height: {{ $day['has_entries'] ? $height : 8 }}px"></div>
                        <small>{{ $day['label'] }}</small>
                        <small>{{ $day['has_entries'] ? number_format($day['total_ml']) : '—' }}</small>
                    </a>
                @endforeach
            </div>

            @if (collect($days)->every(fn ($d) => ! $d['has_entries']))
                <div class="yk-water__empty" role="status">No entries this week. Log water from the dashboard to build history.</div>
            @else
                <ul class="yk-water__history">
                    @foreach ($days as $day)
                        @php
                            $pct = $day['goal_ml'] > 0 && $day['has_entries']
                                ? (int) round(($day['total_ml'] / $day['goal_ml']) * 100)
                                : null;
                        @endphp
                        <li class="yk-water__history-card">
                            <div class="yk-water__history-meta">
                                <strong>{{ \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $day['date'])->format('D, d M') }}</strong>
                                @if ($day['is_today']) <span class="yk-water__pill">Today</span> @endif
                            </div>
                            @if ($day['has_entries'])
                                <div class="yk-water__history-metrics">
                                    <div>
                                        <strong>{{ number_format($day['total_ml']) }}</strong>
                                        <span>Intake ml</span>
                                    </div>
                                    <div>
                                        <strong>{{ number_format($day['goal_ml']) }}</strong>
                                        <span>Goal ml</span>
                                    </div>
                                    <div>
                                        <strong>{{ $pct }}%</strong>
                                        <span>Progress</span>
                                    </div>
                                </div>
                                <div class="yk-water__history-foot">
                                    <div class="yk-water__mini-bar" aria-hidden="true">
                                        <span style="width: {{ min(100, max(0, $pct)) }}%"></span>
                                    </div>
                                    <a class="yk-water__text-link" href="{{ route('tools.water-intake.today', ['date' => $day['date']]) }}">View day</a>
                                </div>
                            @else
                                <p class="yk-water__meta" style="margin:0;">No entries</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="yk-water__actions">
                <a class="yk-water__btn-secondary" href="{{ route('tools.water-intake.week', ['end' => $prevEnd]) }}">Previous week</a>
                @if ($nextEnd <= $today)
                    <a class="yk-water__btn-secondary" href="{{ route('tools.water-intake.week', ['end' => $nextEnd]) }}">Next week</a>
                @endif
                <a class="yk-water__btn" href="{{ route('tools.water-intake') }}">Dashboard</a>
            </div>
        </section>
    </div>
</main>
@endsection
