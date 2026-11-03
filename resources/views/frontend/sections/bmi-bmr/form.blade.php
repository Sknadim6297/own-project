@extends('layouts.layout')

@section('title', 'BMI & BMR Calculator | YuktiOne')
@section('meta_description', 'Estimate adult BMI and Mifflin–St Jeor BMR/TDEE from your measurements. Educational estimates only.')
@section('body_class', 'yk-bmi-body')

@section('content')
@php
    $heightUnit = old('height_unit', $old['height_unit'] ?? 'cm');
    $weightUnit = old('weight_unit', $old['weight_unit'] ?? 'kg');
@endphp
<main class="yk-bmi">
    <div class="yk-bmi__wrap">
        <section class="yk-bmi__card" aria-labelledby="bmi-form-heading">
            <p class="yk-bmi__badge">Nutrition tool</p>
            <h1 id="bmi-form-heading">Enter your details</h1>
            <p class="yk-bmi__lead">Calculate educational BMI and energy estimates. These are screening formulas—not medical measurements or personalised clinical advice.</p>

            @if (! $open)
                <p class="yk-bmi__error">This calculator is unavailable right now.</p>
            @else
                @if ($errors->any())
                    <p class="yk-bmi__error">{{ $errors->first() }}</p>
                @endif

                <form method="post" action="{{ route('tools.bmi-bmr.calculate') }}" data-bmi-form>
                    @csrf
                    <div class="yk-bmi__field">
                        <label for="bmi-age">Age (years)</label>
                        <input id="bmi-age" type="number" name="age" min="1" max="120" step="1" inputmode="numeric" value="{{ old('age', $old['age'] ?? '') }}" required>
                        <p class="yk-bmi__note">Adult BMI categories and calorie estimates apply only at age 18+. Under 18, you will see educational guidance instead.</p>
                    </div>

                    <fieldset class="yk-bmi__field">
                        <legend>Height</legend>
                        <div class="yk-bmi__units" role="radiogroup" aria-label="Height unit">
                            <label><input type="radio" name="height_unit" value="cm" @checked($heightUnit === 'cm') data-bmi-height-unit> cm</label>
                            <label><input type="radio" name="height_unit" value="ft_in" @checked($heightUnit === 'ft_in') data-bmi-height-unit> ft / in</label>
                        </div>
                        <div class="yk-bmi__row" data-bmi-height-cm @style(['display:none' => $heightUnit !== 'cm'])>
                            <input type="number" name="height_cm" min="50" max="250" step="0.1" inputmode="decimal" placeholder="Height in cm" value="{{ old('height_cm', $old['height_cm'] ?? '') }}">
                        </div>
                        <div class="yk-bmi__row is-2" data-bmi-height-ft @style(['display:none' => $heightUnit !== 'ft_in'])>
                            <input type="number" name="height_ft" min="1" max="8" step="1" inputmode="numeric" placeholder="Feet" value="{{ old('height_ft', $old['height_ft'] ?? '') }}">
                            <input type="number" name="height_in" min="0" max="11.99" step="0.01" inputmode="decimal" placeholder="Inches" value="{{ old('height_in', $old['height_in'] ?? '') }}">
                        </div>
                    </fieldset>

                    <fieldset class="yk-bmi__field">
                        <legend>Weight</legend>
                        <div class="yk-bmi__units" role="radiogroup" aria-label="Weight unit">
                            <label><input type="radio" name="weight_unit" value="kg" @checked($weightUnit === 'kg') data-bmi-weight-unit> kg</label>
                            <label><input type="radio" name="weight_unit" value="lb" @checked($weightUnit === 'lb') data-bmi-weight-unit> lb</label>
                        </div>
                        <div data-bmi-weight-kg @style(['display:none' => $weightUnit !== 'kg'])>
                            <input type="number" name="weight_kg" min="10" max="400" step="0.1" inputmode="decimal" placeholder="Weight in kg" value="{{ old('weight_kg', $old['weight_kg'] ?? '') }}">
                        </div>
                        <div data-bmi-weight-lb @style(['display:none' => $weightUnit !== 'lb'])>
                            <input type="number" name="weight_lb" min="22" max="880" step="0.1" inputmode="decimal" placeholder="Weight in lb" value="{{ old('weight_lb', $old['weight_lb'] ?? '') }}">
                        </div>
                    </fieldset>

                    <fieldset class="yk-bmi__field">
                        <legend>Sex for Mifflin–St Jeor equation</legend>
                        <p class="yk-bmi__note">This equation uses binary sex coefficients from research equations. It may not accurately represent every individual. Choose “Prefer not to use” to calculate BMI only without BMR/TDEE.</p>
                        <ul class="yk-bmi__choices">
                            <li><label><input type="radio" name="sex" value="female" @checked(old('sex', $old['sex'] ?? '') === 'female') required> Female equation (base − 161)</label></li>
                            <li><label><input type="radio" name="sex" value="male" @checked(old('sex', $old['sex'] ?? '') === 'male')> Male equation (base + 5)</label></li>
                            <li><label><input type="radio" name="sex" value="unspecified" @checked(old('sex', $old['sex'] ?? '') === 'unspecified')> Prefer not to use a sex-based BMR equation</label></li>
                        </ul>
                    </fieldset>

                    <fieldset class="yk-bmi__field">
                        <legend>Physical activity level</legend>
                        <p class="yk-bmi__note">Activity factors are rough estimates and may overestimate or underestimate real needs.</p>
                        <ul class="yk-bmi__choices">
                            @foreach ($activities as $activity)
                                <li>
                                    <label>
                                        <input type="radio" name="activity" value="{{ $activity['key'] }}" @checked(old('activity', $old['activity'] ?? '') === $activity['key']) required>
                                        <span><strong>{{ $activity['label'] }}</strong> (×{{ $activity['factor'] }}) — {{ $activity['description'] }}</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </fieldset>

                    <label class="yk-bmi__check">
                        <input type="checkbox" name="excluded_population" value="1" @checked(old('excluded_population', $old['excluded_population'] ?? false))>
                        <span>I am pregnant, breastfeeding, or in another situation outside the intended adult equation population.</span>
                    </label>

                    <div class="yk-bmi__actions">
                        <button class="yk-bmi__btn" type="submit">Calculate</button>
                        @if ($hasResult)
                            <a class="yk-bmi__btn-secondary" href="{{ route('tools.bmi-bmr.results') }}">View last result</a>
                        @endif
                        @auth('user')
                            <a class="yk-bmi__btn-secondary" href="{{ route('tools.bmi-bmr.history') }}">My BMI/BMR History</a>
                        @endauth
                    </div>
                </form>
            @endif
        </section>
    </div>
</main>
@if ($open)
<script>
(function () {
    var form = document.querySelector('[data-bmi-form]');
    if (!form) return;
    var sync = function () {
        var hu = form.querySelector('input[name="height_unit"]:checked');
        var wu = form.querySelector('input[name="weight_unit"]:checked');
        var cm = form.querySelector('[data-bmi-height-cm]');
        var ft = form.querySelector('[data-bmi-height-ft]');
        var kg = form.querySelector('[data-bmi-weight-kg]');
        var lb = form.querySelector('[data-bmi-weight-lb]');
        if (cm && ft && hu) {
            cm.style.display = hu.value === 'cm' ? '' : 'none';
            ft.style.display = hu.value === 'ft_in' ? '' : 'none';
        }
        if (kg && lb && wu) {
            kg.style.display = wu.value === 'kg' ? '' : 'none';
            lb.style.display = wu.value === 'lb' ? '' : 'none';
        }
    };
    form.addEventListener('change', sync);
    sync();
})();
</script>
@endif
@endsection
