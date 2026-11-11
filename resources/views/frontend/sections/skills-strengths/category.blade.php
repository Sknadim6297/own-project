@extends('layouts.layout')

@section('title', $category['label'].' | Skills & Strengths | YuktiOne')
@section('meta_description', 'Skills & Strengths Assessment — '.$category['label'])
@section('body_class', 'yk-ssa-body')

@php
    $scaleOptions = $scaleOptions ?? collect($scale)->map(fn ($label, $value) => [
        'value' => (int) $value,
        'label' => $label,
        'emoji' => \App\Support\SkillsStrengths\SkillsStrengthsCatalog::SCALE_EMOJI[$value] ?? '',
    ])->all();
@endphp

@section('content')
<main class="yk-ssa yk-tools-ds" data-yk-tools-ds data-yk-ssa data-ssa>
    <div class="yk-ssa__wrap">
        <form class="yk-ssa__card yk-ssa__card--assess" method="post" action="{{ route('tools.skills-strengths.category.store', ['category' => $category['key']]) }}" data-ssa-form novalidate>
            @csrf

            <header class="yk-ssa__assess-head">
                <p class="yk-ssa__tool">Skills &amp; Strengths Assessment</p>
                <div class="yk-ssa__progress" aria-hidden="true">
                    <span>{{ $category['range_label'] }} · Step {{ $step }} of {{ $totalSteps }}</span>
                    <span>{{ $progress }}%</span>
                </div>
                <div
                    class="yk-ssa__bar"
                    role="progressbar"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="{{ $progress }}"
                    aria-label="Assessment progress: step {{ $step }} of {{ $totalSteps }}. {{ $category['range_label'] }}"
                >
                    <span style="width: {{ $progress }}%"></span>
                </div>
                <p class="yk-ssa__sr-only" aria-live="polite">
                    Step {{ $step }} of {{ $totalSteps }}. {{ $category['label'] }}. Rate how strongly you agree with each statement.
                </p>
                <h1>{{ $category['label'] }}</h1>
                <p class="yk-ssa__lead yk-ssa__lead--tight">How strongly do you agree with each statement?</p>
            </header>

            @if ($errors->any())
                <p class="yk-ssa__error" role="alert" tabindex="-1">Please answer every question on this page.</p>
            @endif

            @foreach ($questions as $question)
                @php $legendId = 'ssa-q-'.$question['id']; @endphp
                <fieldset class="yk-ssa__question">
                    <legend class="yk-ssa__q-legend" id="{{ $legendId }}">
                        <span class="yk-ssa__q-num">Q{{ $question['number'] }}</span>
                        <span class="yk-ssa__q-text">{{ $question['text'] }}</span>
                    </legend>
                    <div class="yk-ssa__scale" role="radiogroup" aria-labelledby="{{ $legendId }}">
                        @foreach ($scaleOptions as $option)
                            <label class="yk-ssa__option">
                                <input
                                    type="radio"
                                    name="answers[{{ $question['id'] }}]"
                                    value="{{ $option['value'] }}"
                                    @checked((string) old('answers.'.$question['id'], $answers[$question['id']] ?? '') === (string) $option['value'])
                                    required
                                >
                                <span class="yk-ssa__option-value" aria-hidden="true">{{ $option['value'] }}</span>
                                <span class="yk-ssa__emoji" aria-hidden="true">{{ $option['emoji'] }}</span>
                                <span class="yk-ssa__option-label">{{ $option['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach

            <div class="yk-ssa__actions">
                <a class="yk-ssa__btn-secondary" href="{{ $previousUrl }}">Back</a>
                <button class="yk-ssa__btn" type="submit" data-ssa-continue>{{ $nextLabel }}</button>
            </div>
        </form>
    </div>
</main>
<script src="{{ asset('frontend/assets/js/yuktione-skills-strengths.js') }}?v={{ filemtime(public_path('frontend/assets/js/yuktione-skills-strengths.js')) }}" defer></script>
@endsection
