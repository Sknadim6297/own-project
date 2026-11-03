@extends('layouts.layout')

@section('title', 'Your Results | BMI & BMR | YuktiOne')
@section('meta_description', 'Your calculated BMI and estimated BMR/TDEE.')
@section('body_class', 'yk-bmi-body')

@section('content')
<main class="yk-bmi">
    <div class="yk-bmi__wrap">
        <section class="yk-bmi__card" aria-labelledby="bmi-results-heading">
            <p class="yk-bmi__badge">Results</p>
            <h1 id="bmi-results-heading">Your results</h1>
            <p class="yk-bmi__lead">Estimates only. Formula version {{ $result['formula_version'] }}.</p>

            <div class="yk-bmi__stats">
                <div class="yk-bmi__stat">
                    <strong>{{ number_format($result['bmi']['display'], 1) }}</strong>
                    <span>BMI ({{ $result['bmi']['unit'] }})</span>
                </div>
                <div class="yk-bmi__stat">
                    <strong>{{ $result['bmr']['eligible'] ? number_format($result['bmr']['display']) : '—' }}</strong>
                    <span>Estimated BMR (kcal/day)</span>
                </div>
                <div class="yk-bmi__stat">
                    <strong>{{ $result['tdee']['eligible'] ? number_format($result['tdee']['display']) : '—' }}</strong>
                    <span>Estimated TDEE (kcal/day)</span>
                </div>
            </div>

            @if ($result['bmi']['is_adult'] && $result['bmi']['category'])
                <p class="yk-bmi__lead"><strong>Adult BMI category:</strong> {{ $result['bmi']['category']['label'] }}. BMI is a screening measure based on height and weight—not a diagnosis.</p>
            @elseif ($result['flags']['minor'])
                <p class="yk-bmi__lead">{{ $result['guidance']['minor_notice'] }}</p>
            @endif

            @if ($result['flags']['excluded_population'])
                <p class="yk-bmi__lead">{{ $result['guidance']['excluded_notice'] }}</p>
            @endif

            @if ($result['flags']['sex_unspecified'])
                <p class="yk-bmi__lead">No sex-based BMR equation was selected, so BMR and TDEE were not estimated.</p>
            @elseif ($result['bmr']['eligible'])
                <p class="yk-bmi__note">BMR uses Mifflin–St Jeor. TDEE = BMR × {{ $result['inputs']['activity_factor'] }} ({{ $result['inputs']['activity_label'] }}). Activity factors are approximate.</p>
            @endif

            <div class="yk-bmi__actions">
                <a class="yk-bmi__btn" href="{{ route('tools.bmi-bmr.understand') }}">Understand your BMI</a>
                <a class="yk-bmi__btn-secondary" href="{{ route('tools.bmi-bmr.guidance') }}">Personalized guidance</a>
                <form method="post" action="{{ route('tools.bmi-bmr.recalculate') }}">@csrf<button class="yk-bmi__btn-secondary" type="submit">Recalculate</button></form>
            </div>
        </section>
    </div>
</main>
@endsection
