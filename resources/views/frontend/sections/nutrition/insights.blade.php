@extends('layouts.layout')

@section('title', 'Nutrition Insights | YuktiOne')
@section('meta_description', 'Seven-day trends from saved meal entries.')
@section('body_class', 'yk-nut-body')

@section('content')
@php
    $fmt = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
    $maxCal = collect($days)->max(fn ($d) => $d['calories'] !== null ? (float) $d['calories'] : 0) ?: 1;
    $mealShare = [];
    foreach ($meals as $key => $label) {
        $mealShare[$key] = '0';
    }
    foreach ($days as $day) {
        foreach ($day['by_meal'] as $key => $cal) {
            if ($cal !== null) {
                $mealShare[$key] = bcadd($mealShare[$key], (string) $cal, 4);
            }
        }
    }
    $shareTotal = '0';
    foreach ($mealShare as $v) {
        $shareTotal = bcadd($shareTotal, $v, 4);
    }
    $hasAny = collect($days)->contains(fn ($d) => $d['has_entries']);
@endphp
<main class="yk-nut">
    <div class="yk-nut__wrap yk-nut__wrap--wide">
        <section class="yk-nut__card">
            <p class="yk-nut__badge">History · ending {{ $end }}</p>
            <h1>Nutrition insights</h1>
            <p class="yk-nut__lead">Saved meal entries only. Tracking data—not medical advice.</p>

            <div class="yk-nut__stats">
                <div><strong>{{ $fmt($today['totals']['calories_kcal']) }}</strong><span>Day calories</span></div>
                <div><strong>{{ $fmt($today['totals']['protein_g']) }}</strong><span>Protein</span></div>
                <div><strong>{{ $fmt($today['totals']['carbs_g']) }}</strong><span>Carbs</span></div>
                <div><strong>{{ $fmt($today['totals']['fat_g']) }}</strong><span>Fat</span></div>
            </div>

            @if ($today['has_incomplete'])
                <p class="yk-nut__note">Selected day includes incomplete nutrient data.</p>
            @endif
            @if (! $goals->track_only && $goals->calories_kcal)
                <p class="yk-nut__note">Optional goal: {{ number_format($goals->calories_kcal) }} kcal/day.</p>
            @endif

            <h2 class="yk-nut__section-title">Seven-day calories</h2>
            @unless ($hasAny)
                <div class="yk-nut__empty" role="status">No logged days in this range yet.</div>
            @endunless
            <div class="yk-nut__bars" role="img" aria-label="Seven day calorie bars">
                @foreach ($days as $day)
                    @php
                        $h = $day['has_entries'] && $day['calories'] !== null
                            ? max(4, (int) round(((float) $day['calories'] / $maxCal) * 120))
                            : 4;
                    @endphp
                    <a class="yk-nut__bar {{ $day['has_entries'] ? '' : 'is-empty' }}"
                       href="{{ route('tools.nutrition', ['date' => $day['date']]) }}"
                       title="{{ $day['date'] }}">
                        <div class="yk-nut__bar-fill" style="height:{{ $h }}px;"></div>
                        <small>{{ $day['label'] }}</small>
                        <small>{{ $day['has_entries'] ? $fmt($day['calories']) : '—' }}</small>
                        @if ($day['incomplete'])<small>partial</small>@endif
                    </a>
                @endforeach
            </div>
            <p class="yk-nut__note">Days without entries show empty—not zero intake.</p>

            <h2 class="yk-nut__section-title">Meal contribution (7 days)</h2>
            <ul class="yk-nut__history">
                @foreach ($meals as $key => $label)
                    @php
                        $share = bccomp($shareTotal, '0', 4) > 0
                            ? bcmul(bcdiv($mealShare[$key], $shareTotal, 6), '100', 1)
                            : null;
                        $displayCal = (! $hasAny && $mealShare[$key] === '0') ? null : $mealShare[$key];
                    @endphp
                    <li class="yk-nut__history-card">
                        <div class="yk-nut__history-meta">
                            <strong>{{ $label }}</strong>
                            <span>{{ $fmt($displayCal) }} kcal @if ($share !== null) · {{ $share }}% @endif</span>
                        </div>
                        @if ($share !== null)
                            <div class="yk-nut__mini-bar" aria-hidden="true"><span style="width: {{ min(100, (float) $share) }}%"></span></div>
                        @endif
                    </li>
                @endforeach
            </ul>

            <div class="yk-nut__actions">
                <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition.insights', ['end' => $prevEnd]) }}">Previous week</a>
                <a class="yk-nut__btn" href="{{ route('tools.nutrition', ['date' => $end]) }}">Dashboard</a>
            </div>
        </section>
    </div>
</main>
@endsection
