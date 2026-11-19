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
    $heroImage = \App\Support\Tools\ToolIllustration::DIRECTORY.'bmi-hero-woman.webp';
    if (! file_exists(public_path($heroImage))) {
        $heroImage = \App\Support\Tools\ToolIllustration::DIRECTORY.'bmi-bmr-3d.webp';
    }
    $heroImageExists = file_exists(public_path($heroImage));
    $sexOptions = [
        'female' => 'Female',
        'male' => 'Male',
        'unspecified' => 'Prefer not to say',
    ];
    $sexIcons = [
        'female' => '<circle cx="12" cy="9" r="5"/><path d="M12 14v8M9 18h6"/>',
        'male' => '<circle cx="10" cy="14" r="5"/><path d="m14 10 6-6M15 4h5v5"/>',
        'unspecified' => '<path d="M5 12h.01M12 12h.01M19 12h.01" stroke-width="3.2"/>',
    ];
    $activityShort = [
        'sedentary' => 'Little or no exercise',
        'lightly-active' => '1–3 days/week',
        'moderately-active' => '3–5 days/week',
        'very-active' => '6–7 days/week',
        'extra-active' => 'Intense exercise & physical job',
    ];
    $activityEmoji = [
        'sedentary' => '🛋️',
        'lightly-active' => '🚶',
        'moderately-active' => '🚴',
        'very-active' => '🏃',
        'extra-active' => '🏋️',
    ];
@endphp
<main class="yk-bmi yk-tools-ds" data-yk-tools-ds data-yk-bmi>
    <svg class="yk-bmi__leaf yk-bmi__leaf--bl" viewBox="0 0 200 260" aria-hidden="true" focusable="false">
        <path d="M100 250C100 160 80 90 20 20c70 20 110 90 80 230z"/>
        <path d="M105 250c0-80 30-140 90-190-10 80-40 150-90 190z"/>
        <path d="M100 250c-20-50-60-90-98-100 50-10 90 30 98 100z"/>
    </svg>
    <svg class="yk-bmi__leaf yk-bmi__leaf--tr" viewBox="0 0 200 260" aria-hidden="true" focusable="false">
        <path d="M100 250C100 160 80 90 20 20c70 20 110 90 80 230z"/>
        <path d="M105 250c0-80 30-140 90-190-10 80-40 150-90 190z"/>
    </svg>

    <div class="yk-bmi__wrap yk-bmi__wrap--wide">
        @include('frontend.sections.partials.tool-back-to-tools')

        {{-- Header: title, illustration, feature pills --}}
        <header class="yk-bmi__hero" aria-labelledby="bmi-form-heading">
            <div class="yk-bmi__hero-copy">
                <p class="yk-bmi__badge">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 19c0-8 5-13 14-14 0 9-5 14-13 14"/><path d="M5 19c3-5 6-8 10-10"/></svg>
                    Nutrition Tool
                </p>
                <h1 id="bmi-form-heading">BMI, BMR &amp; TDEE Calculator</h1>
                <p class="yk-bmi__lead">Educational screening estimates from your measurements—not medical advice.</p>
            </div>

            <div class="yk-bmi__hero-art" aria-hidden="true">
                <span class="yk-bmi__hero-blob"></span>
                <svg class="yk-bmi__heart yk-bmi__heart--a" viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M12 20.5C5 15 3 11.5 3 8.6A4.6 4.6 0 0 1 7.6 4c1.8 0 3.4 1 4.4 2.6C13 5 14.600 4 16.400 4A4.600 4.600 0 0 1 21 8.600c0 2.900-2 6.400-9 11.900z"/></svg>
                <svg class="yk-bmi__heart yk-bmi__heart--b" viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M12 20.5C5 15 3 11.5 3 8.6A4.6 4.6 0 0 1 7.6 4c1.8 0 3.4 1 4.4 2.6C13 5 14.600 4 16.400 4A4.600 4.600 0 0 1 21 8.600c0 2.900-2 6.400-9 11.900z"/></svg>
                @if ($heroImageExists)
                    <img src="{{ asset($heroImage) }}" alt="" width="900" height="600" decoding="async" fetchpriority="high">
                @endif
            </div>

            <ul class="yk-bmi__pills">
                <li>
                    <span class="yk-bmi__pill-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M13 2 4 14h6.500l-1 8L19 10h-6.500z"/></svg></span>
                    <span><strong>Quick &amp; Easy</strong><small>Get results in seconds</small></span>
                </li>
                <li>
                    <span class="yk-bmi__pill-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4.500 6v5.500c0 4.700 3.100 8 7.500 9.500 4.400-1.500 7.500-4.800 7.500-9.500V6z"/><path d="m9 12 2.200 2.200L15.200 10"/></svg></span>
                    <span><strong>Private &amp; Secure</strong><small>Your data stays with you</small></span>
                </li>
                <li>
                    <span class="yk-bmi__pill-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><rect x="4" y="12" width="4" height="8" rx="1.200"/><rect x="10" y="7" width="4" height="13" rx="1.200"/><rect x="16" y="3" width="4" height="17" rx="1.200"/></svg></span>
                    <span><strong>Insightful</strong><small>Understand your health better</small></span>
                </li>
            </ul>
        </header>

        <div class="yk-bmi__layout">
            {{-- Left: live preview --}}
            <aside class="yk-bmi__aside" aria-label="Live preview" data-bmi-preview data-state="empty">
                <svg class="yk-bmi__aside-leaf" viewBox="0 0 200 260" aria-hidden="true" focusable="false">
                    <path d="M100 250C100 160 80 90 20 20c70 20 110 90 80 230z"/>
                    <path d="M105 250c0-80 30-140 90-190-10 80-40 150-90 190z"/>
                    <path d="M100 250c-20-50-60-90-98-100 50-10 90 30 98 100z"/>
                </svg>

                <h2 class="yk-bmi__aside-title">
                    <span class="yk-bmi__aside-icon"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.800 5.600a5 5 0 0 0-7.100 0L12 7.300l-1.700-1.700a5 5 0 0 0-7.100 7.100L12 21l8.800-8.300a5 5 0 0 0 0-7.100z" fill="rgba(255,255,255,.18)"/><path d="M3.500 12h4l2-3 3 6 2-3h6"/></svg></span>
                    Your Health at a Glance
                </h2>

                <div class="yk-bmi__gauge" role="img" aria-label="Live BMI gauge preview">
                    <svg viewBox="0 0 200 112" aria-hidden="true">
                        <g fill="none" stroke-width="17" stroke-linecap="round">
                            <path class="yk-bmi__arc yk-bmi__arc--under" d="M20 100 A80 80 0 0 1 180 100" pathLength="100" stroke-dasharray="20.400 100" stroke-dashoffset="-3.950"/>
                            <path class="yk-bmi__arc yk-bmi__arc--healthy" d="M20 100 A80 80 0 0 1 180 100" pathLength="100" stroke-dasharray="13.800 100" stroke-dashoffset="-32.250"/>
                            <path class="yk-bmi__arc yk-bmi__arc--over" d="M20 100 A80 80 0 0 1 180 100" pathLength="100" stroke-dasharray="8.800 100" stroke-dashoffset="-53.950"/>
                            <path class="yk-bmi__arc yk-bmi__arc--obese" d="M20 100 A80 80 0 0 1 180 100" pathLength="100" stroke-dasharray="25.400 100" stroke-dashoffset="-70.650"/>
                        </g>
                        <g class="yk-bmi__needle" data-bmi-needle style="transform: rotate(-90deg)">
                            <circle cx="100" cy="20" r="7" fill="#fff" stroke="#7a12a0" stroke-width="3"/>
                        </g>
                    </svg>
                    <div class="yk-bmi__gauge-read" aria-live="polite">
                        <em>BMI</em>
                        <strong data-bmi-live-bmi>—</strong>
                        <span data-bmi-live-label>Enter height &amp; weight</span>
                    </div>
                </div>

                <dl class="yk-bmi__kpis">
                    <div class="yk-bmi__kpi">
                        <span class="yk-bmi__kpi-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="4"/><path d="M8.500 9a4 4 0 0 1 7 0M12 9l1.500-1.500"/></svg></span>
                        <dt>BMI</dt>
                        <dd data-bmi-kpi-bmi>—</dd>
                        <small>kg/m²</small>
                    </div>
                    <div class="yk-bmi__kpi">
                        <span class="yk-bmi__kpi-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12 22c4.200 0 7-3 7-7 0-3.200-2-5.200-3.200-7.300-.8 1.600-1.800 2.600-3 2.800.2-3.400-1-6.400-3.800-8.500.2 4.300-4 6.300-4 12.200 0 4.100 3 7.800 7 7.800z"/></svg></span>
                        <dt>BMR</dt>
                        <dd data-bmi-kpi-bmr>—</dd>
                        <small>kcal/day</small>
                    </div>
                    <div class="yk-bmi__kpi">
                        <span class="yk-bmi__kpi-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.200" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="14.500" cy="4.500" r="1.800" fill="currentColor"/><path d="m11 21 2.200-5-3-3 1-5 3 2.200 3 1M8 12.500l3-5.500M15 16l3 5"/></svg></span>
                        <dt>TDEE</dt>
                        <dd data-bmi-kpi-tdee>—</dd>
                        <small>kcal/day</small>
                    </div>
                </dl>

                <p class="yk-bmi__aside-note">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                    <span>Live preview while you type. Your full result and guidance appear after you calculate.</span>
                </p>
            </aside>

            {{-- Right: form --}}
            <section class="yk-bmi__card yk-bmi__card--form" aria-label="Your measurements">
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

                        <h2 class="yk-bmi__section"><i class="yk-bmi__section-icon yk-bmi__section-icon--user" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.200" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4.500 21c0-4 3.400-6 7.500-6s7.500 2 7.500 6"/></svg></i>About You</h2>
                        <div class="yk-bmi__row-about">
                            <fieldset class="yk-bmi__metric yk-bmi__metric--sex">
                                <legend>Gender</legend>
                                <div class="yk-bmi__seg yk-bmi__seg--3" role="radiogroup" aria-label="Gender" aria-describedby="bmi-sex-hint">
                                    @foreach ($sexOptions as $value => $label)
                                        <label class="yk-bmi__seg-option">
                                            <input type="radio" name="sex" value="{{ $value }}" @checked($sex === $value) required>
                                            <span>
                                                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $sexIcons[$value] !!}</svg>
                                                {{ $label }}
                                            </span>
                                            <b class="yk-bmi__tick" aria-hidden="true"><svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3.500" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.500 4.500 4.500L19 7"/></svg></b>
                                        </label>
                                    @endforeach
                                </div>
                                <p id="bmi-sex-hint" class="yk-bmi__hint">Used only for Mifflin–St Jeor BMR.</p>
                            </fieldset>

                            <div class="yk-bmi__metric yk-bmi__metric--age">
                                <label for="bmi-age">Age</label>
                                <div class="yk-bmi__input-unit">
                                    <input id="bmi-age" type="number" name="age" min="1" max="120" step="1" inputmode="numeric" placeholder="e.g. 30" value="{{ old('age', $old['age'] ?? '') }}" required>
                                    <span>yrs</span>
                                </div>
                                <p class="yk-bmi__hint">Adult categories from age 18+.</p>
                            </div>
                        </div>

                        <h2 class="yk-bmi__section"><i class="yk-bmi__section-icon yk-bmi__section-icon--body" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.200" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="12" rx="3"/><path d="M7 7V5M12 7V5M17 7V5"/></svg></i>Body Measurements</h2>
                        <div class="yk-bmi__metrics">
                            <fieldset class="yk-bmi__metric yk-bmi__metric--tile">
                                <legend>
                                    <i class="yk-bmi__tile-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.200" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3.500" width="16" height="17" rx="4"/><path d="M8.500 9.500a3.500 3.500 0 0 1 7 0M12 9.500l1.800-1.800"/></svg></i>
                                    Weight
                                </legend>
                                <div class="yk-bmi__unit-toggle" role="radiogroup" aria-label="Weight unit">
                                    <label class="yk-bmi__unit-option"><input type="radio" name="weight_unit" value="kg" @checked($weightIsKg) data-bmi-weight-unit> kg</label>
                                    <label class="yk-bmi__unit-option"><input type="radio" name="weight_unit" value="lb" @checked(! $weightIsKg) data-bmi-weight-unit> lb</label>
                                </div>
                                <div class="yk-bmi__bigfield" data-bmi-weight-kg @if (! $weightIsKg) hidden aria-hidden="true" @endif>
                                    <input type="number" name="weight_kg" min="10" max="400" step="0.1" inputmode="decimal" placeholder="70" aria-label="Weight in kilograms" value="{{ old('weight_kg', $old['weight_kg'] ?? '') }}" @disabled(! $weightIsKg)>
                                    <span aria-hidden="true">kg</span>
                                </div>
                                <div class="yk-bmi__bigfield" data-bmi-weight-lb @if ($weightIsKg) hidden aria-hidden="true" @endif>
                                    <input type="number" name="weight_lb" min="22" max="880" step="0.1" inputmode="decimal" placeholder="154" aria-label="Weight in pounds" value="{{ old('weight_lb', $old['weight_lb'] ?? '') }}" @disabled($weightIsKg)>
                                    <span aria-hidden="true">lb</span>
                                </div>
                            </fieldset>

                            <fieldset class="yk-bmi__metric yk-bmi__metric--tile">
                                <legend>
                                    <i class="yk-bmi__tile-icon yk-bmi__tile-icon--warm" aria-hidden="true"><svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.200" stroke-linecap="round" stroke-linejoin="round"><path d="m4 16 12-12 4 4L8 20z"/><path d="m8 12 2 2M11 9l2 2M14 6l2 2"/></svg></i>
                                    Height
                                </legend>
                                <div class="yk-bmi__unit-toggle" role="radiogroup" aria-label="Height unit">
                                    <label class="yk-bmi__unit-option"><input type="radio" name="height_unit" value="cm" @checked($heightIsCm) data-bmi-height-unit> cm</label>
                                    <label class="yk-bmi__unit-option"><input type="radio" name="height_unit" value="ft_in" @checked(! $heightIsCm) data-bmi-height-unit> ft/in</label>
                                </div>
                                <div class="yk-bmi__bigfield" data-bmi-height-cm @if (! $heightIsCm) hidden aria-hidden="true" @endif>
                                    <input type="number" name="height_cm" min="50" max="250" step="0.1" inputmode="decimal" placeholder="170" aria-label="Height in centimetres" value="{{ old('height_cm', $old['height_cm'] ?? '') }}" @disabled(! $heightIsCm)>
                                    <span aria-hidden="true">cm</span>
                                </div>
                                <div class="yk-bmi__ftin" data-bmi-height-ft @if ($heightIsCm) hidden aria-hidden="true" @endif>
                                    <div class="yk-bmi__bigfield">
                                        <input type="number" name="height_ft" min="1" max="8" step="1" inputmode="numeric" placeholder="5" aria-label="Height feet" value="{{ old('height_ft', $old['height_ft'] ?? '') }}" @disabled($heightIsCm)>
                                        <span aria-hidden="true">ft</span>
                                    </div>
                                    <div class="yk-bmi__bigfield">
                                        <input type="number" name="height_in" min="0" max="11.99" step="0.01" inputmode="decimal" placeholder="7" aria-label="Height inches" value="{{ old('height_in', $old['height_in'] ?? '') }}" @disabled($heightIsCm)>
                                        <span aria-hidden="true">in</span>
                                    </div>
                                </div>
                            </fieldset>
                        </div>

                        <h2 class="yk-bmi__section"><i class="yk-bmi__section-icon yk-bmi__section-icon--run" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.200" stroke-linecap="round" stroke-linejoin="round"><circle cx="14.500" cy="4.500" r="1.800" fill="currentColor"/><path d="m11 21 2.200-5-3-3 1-5 3 2.200 3 1M8 12.500l3-5.500M15 16l3 5"/></svg></i>Physical Activity</h2>
                        <fieldset class="yk-bmi__activity">
                            <legend class="visually-hidden">Physical activity level</legend>
                            <div class="yk-bmi__activity-grid" role="radiogroup" aria-label="Physical activity level">
                                @foreach ($activities as $item)
                                    <label class="yk-bmi__activity-card" title="{{ $item['description'] }}">
                                        <input type="radio" name="activity" value="{{ $item['key'] }}" data-factor="{{ $item['factor'] }}" @checked($activity === $item['key']) required>
                                        <span class="yk-bmi__activity-emoji" aria-hidden="true">{{ $activityEmoji[$item['key']] ?? ($item['emoji'] ?? '•') }}</span>
                                        <span class="yk-bmi__activity-text">
                                            <span class="yk-bmi__activity-label">{{ $item['label'] }}</span>
                                            <span class="yk-bmi__activity-desc">{{ $activityShort[$item['key']] ?? $item['description'] }}</span>
                                        </span>
                                        <b class="yk-bmi__tick" aria-hidden="true"><svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3.500" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.500 4.500 4.500L19 7"/></svg></b>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <label class="yk-bmi__check">
                            <input type="checkbox" name="excluded_population" value="1" @checked(old('excluded_population', $old['excluded_population'] ?? false))>
                            <span>Pregnant, breastfeeding, or outside adult equation use</span>
                        </label>

                        <div class="yk-bmi__actions">
                            <button class="yk-bmi__btn yk-bmi__btn--lg" type="submit">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="3"/><path d="M8.500 7.500h7M8.500 12h.01M12 12h.01M15.500 12h.01M8.500 16h.01M12 16h.01M15.500 16h.01" stroke-width="2.600"/></svg>
                                Calculate Results
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.400" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                            </button>
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
    </div>
</main>
@if ($open)
<script src="{{ asset('frontend/assets/js/yuktione-bmi-bmr.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-bmi-bmr.js')) }}" defer></script>
@endif
@endsection
