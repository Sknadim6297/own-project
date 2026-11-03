@extends('layouts.layout')

@section('title', 'Personalized Guidance | BMI & BMR | YuktiOne')
@section('meta_description', 'Educational guidance about BMR, TDEE and activity estimates.')
@section('body_class', 'yk-bmi-body')

@section('content')
<main class="yk-bmi">
    <div class="yk-bmi__wrap">
        <section class="yk-bmi__card" aria-labelledby="bmi-guidance-heading">
            <p class="yk-bmi__badge">Guidance</p>
            <h1 id="bmi-guidance-heading">Personalized guidance</h1>
            <p class="yk-bmi__lead">Educational information based on your calculation. This does not replace advice from a qualified healthcare professional.</p>

            @if ($errors->any())
                <p class="yk-bmi__error">{{ $errors->first() }}</p>
            @endif

            @if ($result['guidance']['energy_applicable'])
                <p class="yk-bmi__lead">Estimated maintenance energy expenditure (TDEE): <strong>{{ number_format($result['tdee']['display']) }} kcal/day</strong>.</p>
                <p class="yk-bmi__note">BMR is an estimate of resting energy use. TDEE multiplies BMR by an activity factor to approximate daily needs. Neither is a laboratory measurement.</p>

                @if ($result['guidance']['examples'])
                    <div class="yk-bmi__examples" aria-label="Illustrative energy examples">
                        <div><strong>Maintain (estimate):</strong> {{ number_format($result['guidance']['examples']['maintain']) }} kcal/day</div>
                        <div><strong>Gentle deficit example:</strong> {{ number_format($result['guidance']['examples']['gentle_deficit_example']) }} kcal/day</div>
                        <div><strong>Gentle surplus example:</strong> {{ number_format($result['guidance']['examples']['gentle_surplus_example']) }} kcal/day</div>
                    </div>
                    <p class="yk-bmi__note">{{ $result['guidance']['examples']['disclaimer'] }}</p>
                @endif
            @else
                @if ($result['flags']['minor'])
                    <p class="yk-bmi__lead">{{ $result['guidance']['minor_notice'] }}</p>
                @endif
                @if ($result['flags']['excluded_population'])
                    <p class="yk-bmi__lead">{{ $result['guidance']['excluded_notice'] }}</p>
                @endif
                @if ($result['flags']['sex_unspecified'])
                    <p class="yk-bmi__lead">Without a sex-based equation input, adult calorie illustrations are not shown.</p>
                @endif
            @endif

            <h2 style="font-size:1.05rem;margin:1rem 0 0.45rem;">General tips</h2>
            <ul class="yk-bmi__list">
                <li>Regular movement and balanced eating patterns support wellbeing for many people.</li>
                <li>Hydration, sleep and stress also influence how energy needs feel day to day.</li>
                <li>If you have health conditions or special dietary needs, seek professional guidance.</li>
            </ul>

            <ul class="yk-bmi__list">
                @foreach ($result['guidance']['notes'] as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ul>

            <div class="yk-bmi__actions">
                <a class="yk-bmi__btn-secondary" href="{{ route('tools.bmi-bmr.understand') }}">Back</a>
                <a class="yk-bmi__btn-secondary" href="{{ route('professionals') }}">Talk to an expert</a>
                <form method="post" action="{{ route('tools.bmi-bmr.save') }}">
                    @csrf
                    <button class="yk-bmi__btn" type="submit">{{ $authenticated ? 'Save result' : 'Log in to save' }}</button>
                </form>
                <form method="post" action="{{ route('tools.bmi-bmr.recalculate') }}">@csrf<button class="yk-bmi__btn-secondary" type="submit">Recalculate</button></form>
            </div>
        </section>
    </div>
</main>
@endsection
