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
<main class="yk-ssa" data-ssa>
    <div class="yk-ssa__wrap">
        <form class="yk-ssa__card yk-ssa__card--assess" method="post" action="{{ route('tools.skills-strengths.category.store', ['category' => $category['key']]) }}" data-ssa-form>
            @csrf

            <header class="yk-ssa__assess-head">
                <p class="yk-ssa__tool">Skills &amp; Strengths Assessment</p>
                <div class="yk-ssa__progress">
                    <span>{{ $category['range_label'] }} · Step {{ $step }} of {{ $totalSteps }}</span>
                    <span>{{ $progress }}%</span>
                </div>
                <div class="yk-ssa__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}" aria-label="Assessment progress">
                    <span style="width: {{ $progress }}%"></span>
                </div>
                <h1>{{ $category['label'] }}</h1>
                <p class="yk-ssa__lead yk-ssa__lead--tight">How strongly do you agree with each statement?</p>
            </header>

            @if ($errors->any())
                <p class="yk-ssa__error" role="alert">Please answer every question on this page.</p>
            @endif

            @foreach ($questions as $question)
                <fieldset class="yk-ssa__question">
                    <legend class="yk-ssa__q-legend">
                        <span class="yk-ssa__q-num">Q{{ $question['number'] }}</span>
                        <span class="yk-ssa__q-text">{{ $question['text'] }}</span>
                    </legend>
                    <div class="yk-ssa__scale" role="radiogroup" aria-label="{{ $question['text'] }}">
                        @foreach ($scaleOptions as $option)
                            <label class="yk-ssa__option">
                                <input
                                    type="radio"
                                    name="answers[{{ $question['id'] }}]"
                                    value="{{ $option['value'] }}"
                                    @checked((string) old('answers.'.$question['id'], $answers[$question['id']] ?? '') === (string) $option['value'])
                                    required
                                >
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
<script>
(function () {
    var form = document.querySelector('[data-ssa-form]');
    if (!form) return;
    var button = form.querySelector('[data-ssa-continue]');
    var groups = {};
    form.querySelectorAll('input[type="radio"]').forEach(function (input) {
        groups[input.name] = true;
    });
    var sync = function () {
        var ready = Object.keys(groups).every(function (name) {
            return !!form.querySelector('input[name="' + name + '"]:checked');
        });
        if (button) button.disabled = !ready;
    };
    form.addEventListener('change', sync);
    sync();
})();
</script>
@endsection
