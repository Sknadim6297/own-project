@extends('layouts.layout')

@section('title', 'BMI & BMR Calculator | YuktiOne')
@section('meta_description', 'Estimate adult BMI and Mifflin–St Jeor BMR/TDEE from your measurements. Educational estimates only.')
@section('body_class', 'yk-bmi-body')

@section('content')
@php
    $heightUnit = old('height_unit', $old['height_unit'] ?? 'cm');
    $weightUnit = old('weight_unit', $old['weight_unit'] ?? 'kg');
    $sex = old('sex', $old['sex'] ?? '');
    $activity = old('activity', $old['activity'] ?? '');
    $heightIsCm = $heightUnit === 'cm';
    $weightIsKg = $weightUnit === 'kg';
@endphp
<main class="yk-bmi yk-tools-ds" data-yk-tools-ds data-yk-bmi>
    <div class="yk-bmi__wrap yk-bmi__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')
        <section class="yk-bmi__card" aria-labelledby="bmi-form-heading">
            <p class="yk-bmi__badge">Nutrition tool</p>
            <h1 id="bmi-form-heading">BMI, BMR &amp; TDEE Calculator</h1>
            <p class="yk-bmi__lead">Educational screening estimates from your measurements—not medical advice.</p>

            @if (! $open)
                <p class="yk-bmi__error" role="alert">This calculator is unavailable right now.</p>
            @else
                @if ($errors->any())
                    <p class="yk-bmi__error" role="alert">{{ $errors->first() }}</p>
                @endif

                <form
                    method="post"
                    action="{{ route('tools.bmi-bmr.calculate') }}"
                    data-bmi-form
                    data-height-mode="{{ $heightIsCm ? 'cm' : 'ft_in' }}"
                    data-weight-mode="{{ $weightIsKg ? 'kg' : 'lb' }}"
                >
                    @csrf

                    <div class="yk-bmi__metrics">
                        <fieldset class="yk-bmi__metric">
                            <legend>Gender</legend>
                            <select id="bmi-sex" name="sex" required aria-describedby="bmi-sex-hint">
                                <option value="" disabled @selected($sex === '')>Select</option>
                                <option value="female" @selected($sex === 'female')>Female</option>
                                <option value="male" @selected($sex === 'male')>Male</option>
                                <option value="unspecified" @selected($sex === 'unspecified')>Prefer not to say</option>
                            </select>
                            <p id="bmi-sex-hint" class="yk-bmi__hint">Used only for Mifflin–St Jeor BMR.</p>
                        </fieldset>

                        <div class="yk-bmi__metric">
                            <label for="bmi-age">Age</label>
                            <div class="yk-bmi__input-unit">
                                <input id="bmi-age" type="number" name="age" min="1" max="120" step="1" inputmode="numeric" value="{{ old('age', $old['age'] ?? '') }}" required>
                                <span>yrs</span>
                            </div>
                            <p class="yk-bmi__hint">Adult categories from age 18+.</p>
                        </div>

                        <fieldset class="yk-bmi__metric">
                            <legend>Weight</legend>
                            <div class="yk-bmi__unit-toggle" role="radiogroup" aria-label="Weight unit">
                                <label class="yk-bmi__unit-option"><input type="radio" name="weight_unit" value="kg" @checked($weightIsKg) data-bmi-weight-unit> kg</label>
                                <label class="yk-bmi__unit-option"><input type="radio" name="weight_unit" value="lb" @checked(! $weightIsKg) data-bmi-weight-unit> lb</label>
                            </div>
                            <div data-bmi-weight-kg @if (! $weightIsKg) hidden aria-hidden="true" @endif>
                                <input type="number" name="weight_kg" min="10" max="400" step="0.1" inputmode="decimal" placeholder="e.g. 70" aria-label="Weight in kilograms" value="{{ old('weight_kg', $old['weight_kg'] ?? '') }}" @disabled(! $weightIsKg)>
                            </div>
                            <div data-bmi-weight-lb @if ($weightIsKg) hidden aria-hidden="true" @endif>
                                <input type="number" name="weight_lb" min="22" max="880" step="0.1" inputmode="decimal" placeholder="e.g. 154" aria-label="Weight in pounds" value="{{ old('weight_lb', $old['weight_lb'] ?? '') }}" @disabled($weightIsKg)>
                            </div>
                        </fieldset>

                        <fieldset class="yk-bmi__metric">
                            <legend>Height</legend>
                            <div class="yk-bmi__unit-toggle" role="radiogroup" aria-label="Height unit">
                                <label class="yk-bmi__unit-option"><input type="radio" name="height_unit" value="cm" @checked($heightIsCm) data-bmi-height-unit> cm</label>
                                <label class="yk-bmi__unit-option"><input type="radio" name="height_unit" value="ft_in" @checked(! $heightIsCm) data-bmi-height-unit> ft/in</label>
                            </div>
                            <div data-bmi-height-cm @if (! $heightIsCm) hidden aria-hidden="true" @endif>
                                <input type="number" name="height_cm" min="50" max="250" step="0.1" inputmode="decimal" placeholder="e.g. 170" aria-label="Height in centimetres" value="{{ old('height_cm', $old['height_cm'] ?? '') }}" @disabled(! $heightIsCm)>
                            </div>
                            <div class="yk-bmi__ftin" data-bmi-height-ft @if ($heightIsCm) hidden aria-hidden="true" @endif>
                                <input type="number" name="height_ft" min="1" max="8" step="1" inputmode="numeric" placeholder="ft" aria-label="Height feet" value="{{ old('height_ft', $old['height_ft'] ?? '') }}" @disabled($heightIsCm)>
                                <input type="number" name="height_in" min="0" max="11.99" step="0.01" inputmode="decimal" placeholder="in" aria-label="Height inches" value="{{ old('height_in', $old['height_in'] ?? '') }}" @disabled($heightIsCm)>
                            </div>
                        </fieldset>
                    </div>

                    <fieldset class="yk-bmi__activity">
                        <legend>Physical activity</legend>
                        <div class="yk-bmi__activity-grid" role="radiogroup" aria-label="Physical activity level">
                            @foreach ($activities as $item)
                                <label class="yk-bmi__activity-card">
                                    <input type="radio" name="activity" value="{{ $item['key'] }}" @checked($activity === $item['key']) required>
                                    <span class="yk-bmi__activity-emoji" aria-hidden="true">{{ $item['emoji'] ?? '•' }}</span>
                                    <span class="yk-bmi__activity-label">{{ $item['label'] }}</span>
                                    <span class="yk-bmi__activity-desc">{{ $item['description'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <label class="yk-bmi__check">
                        <input type="checkbox" name="excluded_population" value="1" @checked(old('excluded_population', $old['excluded_population'] ?? false))>
                        <span>Pregnant, breastfeeding, or outside adult equation use</span>
                    </label>

                    <div class="yk-bmi__actions">
                        <button class="yk-bmi__btn" type="submit">Calculate</button>
                        @if ($hasResult)
                            <a class="yk-bmi__btn-secondary" href="{{ route('tools.bmi-bmr.results') }}">Last result</a>
                        @endif
                        @auth('user')
                            <a class="yk-bmi__btn-secondary" href="{{ route('tools.bmi-bmr.history') }}">History</a>
                        @endauth
                    </div>
                </form>
            @endif
        </section>
    </div>
</main>
@if ($open)
<script src="{{ asset('frontend/assets/js/yuktione-bmi-bmr.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-bmi-bmr.js')) }}" defer></script>
@endif
@endsection
