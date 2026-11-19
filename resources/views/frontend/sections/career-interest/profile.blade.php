@extends('layouts.layout')

@section('title', 'Your Interest Profile | YuktiOne')
@section('meta_description', 'Your responses show the types of activities you may naturally enjoy.')
@section('body_class', 'yk-cit-body')

@section('content')
@php
    $icon = fn (string $name, int $size = 18) => view('frontend.sections.career-interest.partials.icon', ['name' => $name, 'size' => $size])->render();
    $ranked = $scored['ranked'];
    $primary = array_values(array_filter($ranked, fn ($row) => in_array($row['key'], $scored['top'], true)));
    $others = array_values(array_filter($ranked, fn ($row) => ! in_array($row['key'], $scored['top'], true)));
@endphp
<main class="yk-cit yk-tools-ds" data-yk-tools-ds data-yk-cit>
    <div class="yk-cit__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')

        <section class="yk-cit__card" aria-labelledby="cit-profile-heading">
            <p class="yk-cit__badge">{!! $icon('sparkle', 14) !!} Results</p>
            <h1 id="cit-profile-heading">Your Interest Profile</h1>
            <p class="yk-cit__lead">Your responses show the types of activities you may naturally enjoy.</p>

            <h2 class="yk-cit__section-title" style="margin-top:0.4rem">{!! $icon('sparkle', 18) !!} Primary interests</h2>
            <ol class="yk-cit__primary">
                @foreach ($primary as $i => $row)
                    <li class="yk-cit__top yk-cit__top--{{ min($i + 1, 3) }}">
                        <div class="yk-cit__top-head">
                            <div class="yk-cit__top-name">
                                <span class="yk-cit__top-icon">{!! $icon($categories[$row['key']]['icon'] ?? 'compass', 20) !!}</span>
                                <div>
                                    <span class="yk-cit__top-rank">Top {{ $i + 1 }}</span>
                                    <strong class="yk-cit__top-label">{{ $row['label'] }}</strong>
                                </div>
                            </div>
                            <span class="yk-cit__top-pct">{{ $row['percent'] }}%</span>
                        </div>
                        <div class="yk-cit__bar" aria-hidden="true"><span style="width: {{ max(0, min(100, (int) $row['percent'])) }}%"></span></div>
                        @if (! empty($row['description']))
                            <p class="yk-cit__top-desc">{{ $row['description'] }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>

            @if ($others !== [])
                <h2 class="yk-cit__section-title">{!! $icon('grid', 18) !!} Other interests</h2>
                <div class="yk-cit__bars">
                    @foreach ($others as $row)
                        <div class="yk-cit__bar-row">
                            <strong><span>{!! $icon($categories[$row['key']]['icon'] ?? 'compass', 16) !!} {{ $row['label'] }}</span><span>{{ $row['percent'] }}%</span></strong>
                            <div class="yk-cit__bar" aria-hidden="true"><span style="width: {{ max(0, min(100, (int) $row['percent'])) }}%"></span></div>
                            <p class="yk-cit__bar-desc">{{ $row['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <p class="yk-cit__lead" style="margin-top:1.2rem">{{ $scored['interpretation'] }}</p>
            <p class="yk-cit__note">{!! $icon('info', 16) !!}<span>Score display uses an implementation convention (sum of three answers ÷ 15 × 100). It is not a scientifically validated psychometric score.</span></p>

            <div class="yk-cit__actions">
                <a class="yk-cit__btn" href="{{ route('tools.career-interest.suggestions') }}">View Career Suggestions {!! $icon('arrow', 18) !!}</a>
                @php $careerExperts = app(\App\Services\ExpertsDiscovery::class)->career(); @endphp
                <a class="yk-cit__btn-secondary" href="{{ $careerExperts['url'] }}">{!! $icon('briefcase', 18) !!} {{ $careerExperts['label'] }}</a>
            </div>
        </section>
    </div>
</main>
@endsection
