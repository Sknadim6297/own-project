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
@endphp
<main class="yk-nut">
    <div class="yk-nut__wrap">
        <section class="yk-nut__card">
            <p class="yk-nut__badge">Insights · ending {{ $end }}</p>
            <h1>Nutrition insights</h1>
            <p class="yk-nut__lead">Charts use saved meal entries only. This is tracking data, not medical advice.</p>

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
                <p class="yk-nut__note">Optional goal comparison: {{ $goals->calories_kcal }} kcal/day (tracking preference only).</p>
            @endif

            <h2 style="font-size:1.05rem;margin:0.5rem 0;">Seven-day calories</h2>
            <div class="yk-nut__bars" role="img" aria-label="Seven day calorie bars">
                @foreach ($days as $day)
                    @php
                        $h = $day['has_entries'] && $day['calories'] !== null
                            ? max(4, (int) round(((float) $day['calories'] / $maxCal) * 120))
                            : 4;
                    @endphp
                    <div @class(['yk-nut__bar', 'is-empty' => ! $day['has_entries']])>
                        <div class="yk-nut__bar-fill" style="height:{{ $h }}px;"></div>
                        <small>{{ $day['label'] }}</small>
                        <small>{{ $day['has_entries'] ? $fmt($day['calories']) : '—' }}</small>
                        @if ($day['incomplete'])<small>partial</small>@endif
                    </div>
                @endforeach
            </div>
            <p class="yk-nut__note">Days without entries show as empty—not as zero intake.</p>

            <h2 style="font-size:1.05rem;margin:1rem 0 0.5rem;">Meal contribution (7 days)</h2>
            <ul class="yk-nut__list">
                @foreach ($meals as $key => $label)
                    @php
                        $share = bccomp($shareTotal, '0', 4) > 0
                            ? bcmul(bcdiv($mealShare[$key], $shareTotal, 6), '100', 1)
                            : null;
                    @endphp
                    <li>
                        <span><strong>{{ $label }}</strong></span>
                        <span>{{ $fmt($mealShare[$key] === '0' && ! collect($days)->contains(fn ($d) => $d['has_entries']) ? null : $mealShare[$key]) }} kcal
                            @if ($share !== null) ({{ $share }}%) @endif
                        </span>
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
