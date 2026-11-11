@extends('layouts.layout')

@section('title', 'Saved BMI/BMR result | YuktiOne')
@section('meta_description', 'A saved BMI and BMR calculation.')
@section('body_class', 'yk-bmi-body')

@section('content')
@php
    $when = optional($calculation->calculated_at)->timezone($timezone)?->format('d M Y, g:i A');
    $activity = \App\Support\BmiBmr\BmiBmrCatalog::ACTIVITIES[$calculation->activity] ?? null;
    $blurbs = \App\Support\BmiBmr\BmiBmrCatalog::RESULT_BLURBS;
@endphp
<main class="yk-bmi yk-tools-ds" data-yk-tools-ds data-yk-bmi>
    <div class="yk-bmi__wrap yk-bmi__wrap--wide">
        <section class="yk-bmi__card yk-bmi__card--results" aria-labelledby="bmi-show-heading">
            <p class="yk-bmi__badge">Saved result</p>
            <h1 id="bmi-show-heading">Saved calculation</h1>
            <p class="yk-bmi__lead">{{ $when }} · {{ $calculation->formula_version }}</p>

            <div class="yk-bmi__result-grid">
                <article class="yk-bmi__result-card">
                    <p class="yk-bmi__result-kicker">BMI</p>
                    <p class="yk-bmi__result-value">{{ number_format((float) $calculation->bmi_display, 1) }} <small>kg/m²</small></p>
                    @if ($calculation->bmi_category_label)
                        <p class="yk-bmi__result-tag">{{ $calculation->bmi_category_label }}</p>
                    @endif
                    <p class="yk-bmi__result-blurb">{{ $blurbs['bmi'] }}</p>
                </article>
                <article class="yk-bmi__result-card">
                    <p class="yk-bmi__result-kicker">BMR</p>
                    <p class="yk-bmi__result-value">
                        @if ($calculation->bmr_kcal)
                            {{ number_format($calculation->bmr_kcal) }} <small>kcal/day</small>
                        @else
                            —
                        @endif
                    </p>
                    <p class="yk-bmi__result-blurb">{{ $blurbs['bmr'] }}</p>
                </article>
                <article class="yk-bmi__result-card">
                    <p class="yk-bmi__result-kicker">TDEE</p>
                    <p class="yk-bmi__result-value">
                        @if ($calculation->tdee_kcal)
                            {{ number_format($calculation->tdee_kcal) }} <small>kcal/day</small>
                        @else
                            —
                        @endif
                    </p>
                    @if ($activity)
                        <p class="yk-bmi__result-tag">
                            <span aria-hidden="true">{{ $activity['emoji'] ?? '' }}</span>
                            {{ $activity['label'] }} ×{{ $calculation->activity_factor }}
                        </p>
                    @endif
                    <p class="yk-bmi__result-blurb">{{ $blurbs['tdee'] }}</p>
                </article>
            </div>

            <p class="yk-bmi__note">
                Stored inputs: age {{ $calculation->age }},
                height {{ number_format((float) $calculation->height_cm, 1) }} cm,
                weight {{ number_format((float) $calculation->weight_kg, 1) }} kg,
                sex {{ $calculation->sex }}
                @if ($calculation->excluded_population) · flagged outside adult equation use @endif
            </p>

            <div class="yk-bmi__actions">
                <a class="yk-bmi__btn-secondary" href="{{ route('tools.bmi-bmr.history') }}">Back to history</a>
                <a class="yk-bmi__btn" href="{{ route('tools.bmi-bmr') }}">New calculation</a>
                <form method="post" action="{{ route('tools.bmi-bmr.history.destroy', $calculation) }}" onsubmit="return confirm('Delete this saved calculation?');">
                    @csrf
                    @method('DELETE')
                    <button class="yk-bmi__btn-secondary" type="submit">Delete</button>
                </form>
            </div>
        </section>
    </div>
</main>
@endsection
