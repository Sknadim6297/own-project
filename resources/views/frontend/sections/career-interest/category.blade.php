@extends('layouts.layout')

@section('title', $category['label'].' | Career Interest Test | YuktiOne')
@section('meta_description', 'Tell us how much you enjoy these '.$category['label'].' activities.')
@section('body_class', 'yk-cit-body')

@php
    $scaleOptions = $scaleOptions ?? collect($scale)->map(fn ($label, $value) => [
        'value' => (int) $value,
        'label' => $label,
        'emoji' => \App\Support\CareerInterest\CareerInterestCatalog::SCALE_EMOJI[$value] ?? '',
    ])->all();
    $progress = $progress ?? (int) round(($step / max(1, $totalSteps)) * 100);
    $questionCount = $questionCount ?? 18;
    $perPage = max(1, count($questions));
    $qStart = (($step - 1) * $perPage) + 1;
    $qEnd = min($questionCount, $step * $perPage);
@endphp

@section('content')
<main class="yk-cit yk-tools-ds" data-yk-tools-ds data-yk-cit data-cit>
    <div class="yk-cit__wrap">
        <form class="yk-cit__card yk-cit__card--assess" method="post" action="{{ route('tools.career-interest.category.store', ['category' => $category['key']]) }}" data-cit-form novalidate>
            @csrf

            <header class="yk-cit__assess-head">
                <p class="yk-cit__tool">Career Interest Test</p>
                <div class="yk-cit__progress" aria-hidden="true">
                    <span>
                        Questions {{ $qStart }}–{{ $qEnd }} of {{ $questionCount }}
                        · Step {{ $step }} of {{ $totalSteps }}
                    </span>
                    <span>{{ $progress }}%</span>
                </div>
                <div
                    class="yk-cit__bar-track"
                    role="progressbar"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="{{ $progress }}"
                    aria-label="Assessment progress: step {{ $step }} of {{ $totalSteps }}, questions {{ $qStart }} to {{ $qEnd }} of {{ $questionCount }}"
                >
                    <span style="width: {{ $progress }}%"></span>
                </div>
                <p class="yk-cit__sr-only" aria-live="polite">
                    Step {{ $step }} of {{ $totalSteps }}. {{ $category['label'] }} interests. Answer how much you enjoy each activity.
                </p>
                <p class="yk-cit__kicker">{{ $category['kicker'] }}</p>
                <h1>{{ $category['label'] }}</h1>
                <p class="yk-cit__lead yk-cit__lead--tight">How much do you enjoy these activities?</p>
            </header>

            @if ($errors->any())
                <p class="yk-cit__error" role="alert" tabindex="-1">Please answer every question on this page.</p>
            @endif

            @foreach ($questions as $index => $question)
                @php
                    $globalNumber = (($step - 1) * $perPage) + $index + 1;
                    $legendId = 'cit-q-'.$question['id'];
                @endphp
                <fieldset class="yk-cit__question">
                    <legend class="yk-cit__q-legend" id="{{ $legendId }}">
                        <span class="yk-cit__q-num">Q{{ $globalNumber }}</span>
                        <span class="yk-cit__q-text">{{ $question['text'] }}</span>
                    </legend>
                    <div class="yk-cit__scale" role="radiogroup" aria-labelledby="{{ $legendId }}">
                        @foreach ($scaleOptions as $option)
                            <label class="yk-cit__option">
                                <input
                                    type="radio"
                                    name="answers[{{ $question['id'] }}]"
                                    value="{{ $option['value'] }}"
                                    @checked((string) old('answers.'.$question['id'], $answers[$question['id']] ?? '') === (string) $option['value'])
                                    required
                                >
                                <span class="yk-cit__option-value" aria-hidden="true">{{ $option['value'] }}</span>
                                <span class="yk-cit__emoji" aria-hidden="true">{{ $option['emoji'] }}</span>
                                <span class="yk-cit__option-label">{{ $option['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach

            <div class="yk-cit__actions">
                <a class="yk-cit__btn-secondary" href="{{ $previousUrl }}">Back</a>
                <button class="yk-cit__btn" type="submit" data-cit-continue>{{ $nextLabel }}</button>
            </div>
        </form>
    </div>
</main>
<script src="{{ asset('frontend/assets/js/yuktione-career-interest.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-career-interest.js')) }}" defer></script>
@endsection
