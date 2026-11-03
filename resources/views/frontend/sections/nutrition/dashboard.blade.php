@extends('layouts.layout')

@section('title', 'Nutrition Dashboard | YuktiOne')
@section('meta_description', 'Daily calorie and macronutrient totals from saved meal entries.')
@section('body_class', 'yk-nut-body')

@section('content')
@php
    $t = $summary['totals'];
    $fmt = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
    $calGoal = (! $goals->track_only && $goals->calories_kcal) ? (int) $goals->calories_kcal : null;
    $pct = null;
    if ($calGoal && $t['calories_kcal'] !== null) {
        $pct = (int) min(100, round(((float) $t['calories_kcal'] / $calGoal) * 100));
    }
@endphp
<main class="yk-nut">
    <div class="yk-nut__wrap">
        <section class="yk-nut__card">
            <p class="yk-nut__badge">{{ $summary['date'] }} · Asia/Kolkata</p>
            <h1>Nutrition dashboard</h1>
            <p class="yk-nut__lead">Totals reflect saved meal entries only.</p>

            @if (session('status'))
                <p class="yk-nut__ok">{{ session('status') }}</p>
            @endif

            <form method="get" action="{{ route('tools.nutrition') }}" class="yk-nut__field">
                <label for="nut-date">Date</label>
                <input id="nut-date" type="date" name="date" value="{{ $summary['date'] }}" onchange="this.form.submit()">
            </form>

            @if (! $summary['has_entries'])
                <p class="yk-nut__note">No meals logged for this date. An empty diary does not mean no food was consumed.</p>
            @endif
            @if ($summary['has_incomplete'])
                <p class="yk-nut__note">Some entries have incomplete nutrient data and are marked accordingly.</p>
            @endif

            <div class="yk-nut__stats">
                <div><strong>{{ $fmt($t['calories_kcal']) }}</strong><span>Calories</span></div>
                <div><strong>{{ $fmt($t['protein_g']) }}</strong><span>Protein (g)</span></div>
                <div><strong>{{ $fmt($t['carbs_g']) }}</strong><span>Carbs (g)</span></div>
                <div><strong>{{ $fmt($t['fat_g']) }}</strong><span>Fat (g)</span></div>
            </div>

            @if ($goals->track_only)
                <p class="yk-nut__note">Tracking-only mode: no calorie or macro goals are applied.</p>
            @elseif ($calGoal)
                <p class="yk-nut__note">Optional calorie goal: {{ $calGoal }} kcal
                    @if ($pct !== null) · logged {{ $pct }}% of goal @endif
                </p>
                <div style="height:10px;background:#edf1f6;border-radius:999px;overflow:hidden;margin-bottom:1rem;" role="progressbar" aria-valuenow="{{ $pct ?? 0 }}" aria-valuemin="0" aria-valuemax="100">
                    <div style="height:100%;width:{{ $pct ?? 0 }}%;background:#316d61;"></div>
                </div>
            @endif

            <div class="yk-nut__meals">
                @foreach ($summary['by_meal'] as $key => $meal)
                    <div class="yk-nut__meal">
                        <strong>{{ $meal['label'] }}</strong>
                        <span class="yk-nut__note" style="display:block;margin:0.2rem 0 0;">
                            {{ $meal['entries']->count() }} item(s) · {{ $fmt($meal['totals']['calories_kcal']) }} kcal
                        </span>
                        <div class="yk-nut__actions" style="margin-top:0.55rem;">
                            <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition.meal', ['date' => $summary['date'], 'meal' => $key]) }}">View meal</a>
                            <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition.search', ['meal' => $key]) }}">Add food</a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="yk-nut__actions">
                <a class="yk-nut__btn" href="{{ route('tools.nutrition.search') }}">Add food</a>
                <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition.insights', ['end' => $summary['date']]) }}">Insights</a>
                <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition.goals') }}">Goals</a>
                <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition', ['date' => $prev]) }}">Previous day</a>
                @unless ($isToday)
                    <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition', ['date' => $next]) }}">Next day</a>
                @endunless
            </div>
        </section>
    </div>
</main>
@endsection
