@extends('layouts.layout')

@section('title', 'Your Interest Profile | YuktiOne')
@section('meta_description', 'Your responses show the types of activities you may naturally enjoy.')
@section('body_class', 'yk-cit-body')

@section('content')
@php
    $ranked = $scored['ranked'];
    $primary = array_values(array_filter($ranked, fn ($row) => in_array($row['key'], $scored['top'], true)));
    $others = array_values(array_filter($ranked, fn ($row) => ! in_array($row['key'], $scored['top'], true)));
@endphp
<main class="yk-cit">
    <div class="yk-cit__wrap">
        <section class="yk-cit__card" aria-labelledby="cit-profile-heading">
            <p class="yk-cit__badge">Results</p>
            <h1 id="cit-profile-heading">Your Interest Profile</h1>
            <p class="yk-cit__lead">Your responses show the types of activities you may naturally enjoy.</p>

            <div class="yk-cit__primary">
                <h2>Primary interests</h2>
                <div class="yk-cit__bars">
                    @foreach ($primary as $row)
                        <div class="yk-cit__bar-row">
                            <strong><span>{{ $row['label'] }}</span><span>{{ $row['percent'] }}%</span></strong>
                            <div class="yk-cit__bar" aria-hidden="true"><span style="width: {{ max(0, min(100, (int) $row['percent'])) }}%"></span></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <h2 class="yk-cit__section-title">Other interests</h2>
            <div class="yk-cit__bars">
                @foreach ($others as $row)
                    <div class="yk-cit__bar-row">
                        <strong><span>{{ $row['label'] }}</span><span>{{ $row['percent'] }}%</span></strong>
                        <div class="yk-cit__bar" aria-hidden="true"><span style="width: {{ max(0, min(100, (int) $row['percent'])) }}%"></span></div>
                        <p class="yk-cit__bar-desc">{{ $row['description'] }}</p>
                    </div>
                @endforeach
            </div>

            <p class="yk-cit__lead">{{ $scored['interpretation'] }}</p>
            <p class="yk-cit__note">Score display uses an implementation convention (sum of three answers ÷ 15 × 100). It is not a scientifically validated psychometric score.</p>

            <div class="yk-cit__actions">
                <a class="yk-cit__btn" href="{{ route('tools.career-interest.suggestions') }}">View Career Suggestions</a>
                @php $careerExperts = app(\App\Services\ExpertsDiscovery::class)->career(); @endphp
                <a class="yk-cit__btn-secondary" href="{{ $careerExperts['url'] }}">{{ $careerExperts['label'] }}</a>
            </div>
        </section>
    </div>
</main>
@endsection
