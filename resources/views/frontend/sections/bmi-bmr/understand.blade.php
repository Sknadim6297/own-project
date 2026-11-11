@extends('layouts.layout')

@section('title', 'Understand Your BMI | YuktiOne')
@section('meta_description', 'What adult BMI can and cannot indicate.')
@section('body_class', 'yk-bmi-body')

@section('content')
@php
    $bmi = $result['bmi']['exact'];
    // Map roughly 15–40 BMI onto the track for marker placement when adult.
    $marker = max(0, min(100, (($bmi - 15) / 25) * 100));
@endphp
<main class="yk-bmi yk-tools-ds" data-yk-tools-ds data-yk-bmi>
    <div class="yk-bmi__wrap">
        <section class="yk-bmi__card" aria-labelledby="bmi-understand-heading">
            <p class="yk-bmi__badge">Education</p>
            <h1 id="bmi-understand-heading">Understand your BMI</h1>

            @if ($result['bmi']['is_adult'] && $result['bmi']['category'])
                <p class="yk-bmi__lead">Your BMI is <strong>{{ number_format($result['bmi']['display'], 1) }} {{ $result['bmi']['unit'] }}</strong> (adult category: {{ $result['bmi']['category']['label'] }}).</p>
                <div class="yk-bmi__scale" aria-label="Adult BMI category scale">
                    <div class="yk-bmi__scale-track">
                        <span></span><span></span><span></span><span></span>
                        <i class="yk-bmi__scale-marker" style="left: {{ $marker }}%" aria-hidden="true"></i>
                    </div>
                    <div class="yk-bmi__scale-labels">
                        <span>&lt;18.5</span>
                        <span>18.5–25</span>
                        <span>25–30</span>
                        <span>≥30</span>
                    </div>
                </div>
                <ul class="yk-bmi__list">
                    @foreach ($categories as $band)
                        <li>
                            @if ($band['min'] === null)
                                Below {{ $band['max_exclusive'] }}: {{ $band['label'] }}
                            @elseif ($band['max_exclusive'] === null)
                                {{ $band['min'] }} or above: {{ $band['label'] }}
                            @else
                                {{ $band['min'] }} to below {{ $band['max_exclusive'] }}: {{ $band['label'] }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="yk-bmi__lead">{{ $result['guidance']['minor_notice'] ?? 'Standard adult BMI classification is not shown for this calculation.' }}</p>
                <p class="yk-bmi__note">Your calculated BMI number is {{ number_format($result['bmi']['display'], 1) }} {{ $result['bmi']['unit'] }}, shown for reference only without an adult category label.</p>
            @endif

            <h2 style="font-size:1.05rem;margin:1.1rem 0 0.45rem;">What BMI can indicate</h2>
            <ul class="yk-bmi__list">
                <li>A simple height-and-weight screening ratio used in adult populations.</li>
                <li>A starting point for educational conversation—not a diagnosis.</li>
            </ul>

            <h2 style="font-size:1.05rem;margin:1.1rem 0 0.45rem;">What BMI cannot indicate</h2>
            <ul class="yk-bmi__list">
                <li>Body-fat percentage, muscle mass, metabolic health, or fitness level.</li>
                <li>Individual medical risk without clinical context.</li>
            </ul>

            <p class="yk-bmi__note">Categories use unrounded BMI for classification; the displayed value is rounded to one decimal place.</p>

            <div class="yk-bmi__actions">
                <a class="yk-bmi__btn-secondary" href="{{ route('tools.bmi-bmr.results') }}">Back to results</a>
                <a class="yk-bmi__btn" href="{{ route('tools.bmi-bmr.guidance') }}">Personalized guidance</a>
            </div>
        </section>
    </div>
</main>
@endsection
