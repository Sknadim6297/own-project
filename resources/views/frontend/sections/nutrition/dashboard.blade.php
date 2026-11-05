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
        $pct = (int) round(((float) $t['calories_kcal'] / $calGoal) * 100);
    }
    $ringPct = $pct !== null ? min(100, max(0, $pct)) : 0;
    $catalogueReady = ($catalogueCount ?? 0) > 0;
    $mealsLogged = collect($summary['by_meal'])->sum(fn ($m) => $m['entries']->count());
@endphp
<main class="yk-nut">
    <div class="yk-nut__wrap yk-nut__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-nut__card" aria-labelledby="nut-dash-heading">
            <p class="yk-nut__badge">{{ $catalogueReady ? ($summary['date'].' · Asia/Kolkata') : 'Food Catalogue Pending' }}</p>
            <h1 id="nut-dash-heading">Calorie &amp; Nutrition Tracker</h1>
            <p class="yk-nut__lead">
                @if ($catalogueReady)
                    Totals from saved meal entries only—not sample data.
                @else
                    Food logging unlocks after the verified catalogue is published. Totals below never use sample data.
                @endif
            </p>

            @if ($status ?? session('status'))
                <p class="yk-nut__ok" role="status">{{ $status ?? session('status') }}</p>
            @endif

            <form method="get" action="{{ route('tools.nutrition') }}" class="yk-nut__date-row">
                <label for="nut-date">Date</label>
                <input id="nut-date" type="date" name="date" value="{{ $summary['date'] }}" onchange="this.form.submit()">
            </form>

            @include('frontend.sections.nutrition.partials.catalogue-gate', [
                'catalogueCount' => $catalogueCount ?? 0,
                'importWorkbooksMissing' => $importWorkbooksMissing ?? false,
            ])

            <div class="yk-nut__dash-grid">
                <div
                    class="yk-nut__progress {{ ($pct !== null && $pct > 100) ? 'is-over' : '' }}"
                    role="img"
                    aria-label="{{ $calGoal ? ($fmt($t['calories_kcal']).' of '.$calGoal.' kcal, '.$pct.' percent') : ($fmt($t['calories_kcal']).' kcal logged') }}"
                >
                    @if ($calGoal)
                        @php
                            $radius = 54;
                            $circ = 2 * M_PI * $radius;
                            $offset = $circ * (1 - ($ringPct / 100));
                        @endphp
                        <svg viewBox="0 0 140 140" aria-hidden="true">
                            <circle class="yk-nut__ring-track" cx="70" cy="70" r="{{ $radius }}" fill="none" stroke-width="12"></circle>
                            <circle class="yk-nut__ring-fill" cx="70" cy="70" r="{{ $radius }}" fill="none" stroke-width="12" stroke-linecap="round"
                                stroke-dasharray="{{ number_format($circ, 4, '.', '') }}"
                                stroke-dashoffset="{{ number_format($offset, 4, '.', '') }}"
                                transform="rotate(-90 70 70)"></circle>
                        </svg>
                        <div class="yk-nut__progress-center">
                            <strong>{{ $pct }}%</strong>
                            <span>{{ $fmt($t['calories_kcal']) }} kcal</span>
                            <span class="yk-nut__progress-goal">of {{ number_format($calGoal) }}</span>
                        </div>
                    @else
                        <div class="yk-nut__progress-plain">
                            <strong>{{ $fmt($t['calories_kcal']) }}</strong>
                            <span>kcal today</span>
                            @if ($goals->track_only)
                                <span class="yk-nut__progress-goal">Tracking only</span>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="yk-nut__stats" aria-label="Daily macros">
                    <div>
                        <strong>{{ $fmt($t['calories_kcal']) }}</strong>
                        <span>Calories</span>
                    </div>
                    <div>
                        <strong>{{ $fmt($t['protein_g']) }}</strong>
                        <span>Protein</span>
                    </div>
                    <div>
                        <strong>{{ $fmt($t['carbs_g']) }}</strong>
                        <span>Carbs</span>
                    </div>
                    <div>
                        <strong>{{ $fmt($t['fat_g']) }}</strong>
                        <span>Fat</span>
                    </div>
                </div>
            </div>

            @if ($goals->track_only)
                <p class="yk-nut__note">Tracking-only mode: no calorie or macro goals are applied.</p>
            @endif
            @if (! $summary['has_entries'])
                <p class="yk-nut__note">No meals logged for this date. Empty does not mean nothing was eaten.</p>
            @endif
            @if ($summary['has_incomplete'])
                <p class="yk-nut__note">Some entries have incomplete nutrient data.</p>
            @endif

            <div class="yk-nut__meta-row">
                <span class="yk-nut__pill">{{ $mealsLogged }} food{{ $mealsLogged === 1 ? '' : 's' }} logged</span>
                @unless ($goals->track_only)
                    @if ($goals->protein_g)<span class="yk-nut__meta">Protein goal {{ rtrim(rtrim(number_format((float) $goals->protein_g, 1, '.', ''), '0'), '.') }} g</span>@endif
                    @if ($goals->carbs_g)<span class="yk-nut__meta">Carbs goal {{ rtrim(rtrim(number_format((float) $goals->carbs_g, 1, '.', ''), '0'), '.') }} g</span>@endif
                    @if ($goals->fat_g)<span class="yk-nut__meta">Fat goal {{ rtrim(rtrim(number_format((float) $goals->fat_g, 1, '.', ''), '0'), '.') }} g</span>@endif
                @endunless
            </div>

            <h2 class="yk-nut__section-title">Meals</h2>
            <div class="yk-nut__meals">
                @foreach ($summary['by_meal'] as $key => $meal)
                    <article class="yk-nut__meal">
                        <div class="yk-nut__meal-head">
                            <strong>{{ $meal['label'] }}</strong>
                            <span>{{ $fmt($meal['totals']['calories_kcal']) }} kcal</span>
                        </div>
                        <p class="yk-nut__meta">{{ $meal['entries']->count() }} item{{ $meal['entries']->count() === 1 ? '' : 's' }}</p>
                        <div class="yk-nut__meal-actions">
                            <a class="yk-nut__text-link" href="{{ route('tools.nutrition.meal', ['date' => $summary['date'], 'meal' => $key]) }}">View</a>
                            @if ($catalogueReady)
                                <a class="yk-nut__text-link" href="{{ route('tools.nutrition.search', ['meal' => $key]) }}">Add food</a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="yk-nut__actions">
                @if ($catalogueReady)
                    <a class="yk-nut__btn" href="{{ route('tools.nutrition.search') }}">Add food</a>
                @else
                    <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition.search') }}">Check catalogue</a>
                @endif
                <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition.insights', ['end' => $summary['date']]) }}">History</a>
                <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition.goals') }}">Goals</a>
                <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition', ['date' => $prev]) }}">Previous day</a>
                @unless ($isToday)
                    <a class="yk-nut__btn-secondary" href="{{ route('tools.nutrition', ['date' => $next]) }}">Next day</a>
                @endunless
            </div>

            @include('frontend.sections.partials.experts-connect', [
                'discovery' => app(\App\Services\ExpertsDiscovery::class)->nutrition(),
                'heading' => 'Optional expert support',
                'copy' => 'Nutrition tools are educational trackers. Browse Nutrition & Fitness experts only if you want professional guidance.',
                'headingId' => 'nut-experts-heading',
            ])
        </section>
    </div>
</main>
@endsection
