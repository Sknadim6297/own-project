@extends('layouts.layout')

@section('title', 'Your Results | BMI & BMR | YuktiOne')
@section('meta_description', 'Your calculated BMI and estimated BMR/TDEE.')
@section('body_class', 'yk-bmi-body')

@section('content')
@php
    $blurbs = \App\Support\BmiBmr\BmiBmrCatalog::RESULT_BLURBS;
@endphp
<main class="yk-bmi yk-tools-ds" data-yk-tools-ds data-yk-bmi>
    <div class="yk-bmi__wrap yk-bmi__wrap--wide">
        <section class="yk-bmi__card yk-bmi__card--results" aria-labelledby="bmi-results-heading">
            <p class="yk-bmi__badge">Results</p>
            <h1 id="bmi-results-heading">Your results</h1>
            <p class="yk-bmi__lead">Estimates only · {{ $result['formula_version'] }}</p>

            @if ($errors->any())
                <p class="yk-bmi__error" role="alert">{{ $errors->first() }}</p>
            @endif
            @if (session('status'))
                <p class="yk-bmi__status" role="status">{{ session('status') }}</p>
            @endif

            <div class="yk-bmi__result-grid">
                <article class="yk-bmi__result-card">
                    <p class="yk-bmi__result-kicker">BMI</p>
                    <p class="yk-bmi__result-value">{{ number_format($result['bmi']['display'], 1) }} <small>{{ $result['bmi']['unit'] }}</small></p>
                    @if ($result['bmi']['is_adult'] && $result['bmi']['category'])
                        <p class="yk-bmi__result-tag">{{ $result['bmi']['category']['label'] }}</p>
                    @elseif ($result['flags']['minor'])
                        <p class="yk-bmi__result-tag">Under 18 — adult category not applied</p>
                    @endif
                    <p class="yk-bmi__result-blurb">{{ $blurbs['bmi'] }}</p>
                </article>

                <article class="yk-bmi__result-card">
                    <p class="yk-bmi__result-kicker">BMR</p>
                    <p class="yk-bmi__result-value">
                        @if ($result['bmr']['eligible'])
                            {{ number_format($result['bmr']['display']) }} <small>{{ $result['bmr']['unit'] }}</small>
                        @else
                            —
                        @endif
                    </p>
                    @if ($result['bmr']['eligible'])
                        <p class="yk-bmi__result-tag">Mifflin–St Jeor</p>
                    @elseif ($result['flags']['sex_unspecified'])
                        <p class="yk-bmi__result-tag">Sex-based equation not used</p>
                    @elseif ($result['flags']['excluded_population'])
                        <p class="yk-bmi__result-tag">Outside adult equation use</p>
                    @elseif ($result['flags']['minor'])
                        <p class="yk-bmi__result-tag">Adult estimate not shown</p>
                    @endif
                    <p class="yk-bmi__result-blurb">{{ $blurbs['bmr'] }}</p>
                </article>

                <article class="yk-bmi__result-card">
                    <p class="yk-bmi__result-kicker">TDEE</p>
                    <p class="yk-bmi__result-value">
                        @if ($result['tdee']['eligible'])
                            {{ number_format($result['tdee']['display']) }} <small>{{ $result['tdee']['unit'] }}</small>
                        @else
                            —
                        @endif
                    </p>
                    @if ($result['tdee']['eligible'])
                        <p class="yk-bmi__result-tag">{{ $result['inputs']['activity_label'] }} ×{{ $result['inputs']['activity_factor'] }}</p>
                    @endif
                    <p class="yk-bmi__result-blurb">{{ $blurbs['tdee'] }}</p>
                </article>
            </div>

            @if ($result['flags']['excluded_population'] && $result['guidance']['excluded_notice'])
                <p class="yk-bmi__note">{{ $result['guidance']['excluded_notice'] }}</p>
            @endif
            @if ($result['flags']['minor'] && $result['guidance']['minor_notice'])
                <p class="yk-bmi__note">{{ $result['guidance']['minor_notice'] }}</p>
            @endif

            <div class="yk-bmi__actions">
                <a class="yk-bmi__btn-secondary" href="{{ route('tools.bmi-bmr.understand') }}">Understand BMI</a>
                <a class="yk-bmi__btn-secondary" href="{{ route('tools.bmi-bmr.guidance') }}">Energy guidance</a>
                <form method="post" action="{{ route('tools.bmi-bmr.save') }}">
                    @csrf
                    <button class="yk-bmi__btn" type="submit">{{ $authenticated ? 'Save to history' : 'Log in to save' }}</button>
                </form>
                <form method="post" action="{{ route('tools.bmi-bmr.recalculate') }}">
                    @csrf
                    <button class="yk-bmi__btn-secondary" type="submit">Recalculate</button>
                </form>
                @auth('user')
                    <a class="yk-bmi__btn-secondary" href="{{ route('tools.bmi-bmr.history') }}">History</a>
                @endauth
            </div>
        </section>
    </div>
</main>
@endsection
