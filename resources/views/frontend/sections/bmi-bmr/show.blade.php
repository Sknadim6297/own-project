@extends('layouts.layout')

@section('title', 'Saved BMI/BMR result | YuktiOne')
@section('meta_description', 'A saved BMI and BMR calculation.')
@section('body_class', 'yk-bmi-body')

@section('content')
@php $when = optional($calculation->calculated_at)->timezone($timezone)?->format('d M Y, g:i A'); @endphp
<main class="yk-bmi">
    <div class="yk-bmi__wrap">
        <section class="yk-bmi__card" aria-labelledby="bmi-show-heading">
            <p class="yk-bmi__badge">Saved result</p>
            <h1 id="bmi-show-heading">Saved calculation</h1>
            <p class="yk-bmi__lead">Saved {{ $when }} (Asia/Kolkata). Formula {{ $calculation->formula_version }}.</p>

            <div class="yk-bmi__stats">
                <div class="yk-bmi__stat">
                    <strong>{{ number_format((float) $calculation->bmi_display, 1) }}</strong>
                    <span>BMI kg/m²</span>
                </div>
                <div class="yk-bmi__stat">
                    <strong>{{ $calculation->bmr_kcal ? number_format($calculation->bmr_kcal) : '—' }}</strong>
                    <span>BMR kcal/day</span>
                </div>
                <div class="yk-bmi__stat">
                    <strong>{{ $calculation->tdee_kcal ? number_format($calculation->tdee_kcal) : '—' }}</strong>
                    <span>TDEE kcal/day</span>
                </div>
            </div>

            @if ($calculation->bmi_category_label)
                <p class="yk-bmi__lead">Adult category: {{ $calculation->bmi_category_label }}</p>
            @endif

            <p class="yk-bmi__note">Stored inputs (metric): age {{ $calculation->age }}, height {{ number_format((float) $calculation->height_cm, 1) }} cm, weight {{ number_format((float) $calculation->weight_kg, 1) }} kg, activity ×{{ $calculation->activity_factor }}.</p>

            <div class="yk-bmi__actions">
                <a class="yk-bmi__btn-secondary" href="{{ route('tools.bmi-bmr.history') }}">Back to history</a>
                <a class="yk-bmi__btn" href="{{ route('tools.bmi-bmr') }}">Recalculate</a>
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
