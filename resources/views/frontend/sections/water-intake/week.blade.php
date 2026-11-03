@extends('layouts.layout')

@section('title', 'Weekly Hydration | YuktiOne')
@section('meta_description', 'Seven-day water intake chart from your saved entries.')
@section('body_class', 'yk-water-body')

@section('content')
<main class="yk-water">
    <div class="yk-water__wrap">
        <section class="yk-water__card">
            <p class="yk-water__badge">7-day view · Asia/Kolkata</p>
            <h1>Weekly hydration</h1>
            <p class="yk-water__lead">
                @if ($avg_label)
                    Average on days with logs: {{ $avg_label }}.
                @else
                    No logged days in this range yet.
                @endif
                Days without entries are shown empty—not interpreted as zero intake proof.
            </p>

            <div class="yk-water__bars" role="img" aria-label="Weekly water intake bars">
                @foreach ($days as $day)
                    @php
                        $height = max(4, (int) round(($day['total_ml'] / $max) * 140));
                        $classes = 'yk-water__bar';
                        if ($day['is_today']) $classes .= ' is-today';
                        if (! $day['has_entries']) $classes .= ' is-empty';
                    @endphp
                    <div class="{{ $classes }}">
                        <div class="yk-water__bar-fill" style="height: {{ $day['has_entries'] ? $height : 8 }}px" title="{{ $day['date'] }}: {{ $day['has_entries'] ? $day['total_ml'].' ml' : 'no entries' }}"></div>
                        <small>{{ $day['label'] }}</small>
                        <small>{{ $day['has_entries'] ? number_format($day['total_ml']) : '—' }}</small>
                    </div>
                @endforeach
            </div>

            <ul class="yk-water__list">
                @foreach ($days as $day)
                    <li>
                        <span>
                            <strong>{{ $day['date'] }}</strong>
                            @if ($day['is_today']) · today @endif
                        </span>
                        <span>
                            @if ($day['has_entries'])
                                {{ number_format($day['total_ml']) }} ml / goal {{ number_format($day['goal_ml']) }} ml
                            @else
                                No entries
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>

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
