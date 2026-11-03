@extends('layouts.layout')

@section('title', $category['label'].' | Skills & Strengths | YuktiOne')
@section('meta_description', 'Skills & Strengths Assessment — '.$category['label'])
@section('body_class', 'yk-ssa-body')

@section('content')
<main class="yk-ssa">
    <div class="yk-ssa__wrap">
        <form class="yk-ssa__card" method="post" action="{{ route('tools.skills-strengths.category.store', ['category' => $category['key']]) }}" data-ssa-form>
            @csrf
            <div class="yk-ssa__progress">
                <span>{{ $category['range_label'] }}</span>
                <span>{{ $progress }}%</span>
            </div>
            <div class="yk-ssa__bar" aria-hidden="true"><span style="width: {{ $progress }}%"></span></div>
            <h1>{{ $category['label'] }}</h1>
            <p class="yk-ssa__lead">Choose how strongly you agree with each statement.</p>

            @if ($errors->any())
                <p class="yk-ssa__error">Please answer every question on this page.</p>
            @endif

            @foreach ($questions as $question)
                <fieldset class="yk-ssa__question">
                    <legend class="visually-hidden">Question {{ $question['number'] }}</legend>
                    <p>{{ $question['number'] }}. {{ $question['text'] }}</p>
                    <div class="yk-ssa__scale" role="radiogroup" aria-label="Question {{ $question['number'] }}">
                        @foreach ($scale as $value => $label)
                            <label>
                                <input
                                    type="radio"
                                    name="answers[{{ $question['id'] }}]"
                                    value="{{ $value }}"
                                    @checked((string) old('answers.'.$question['id'], $answers[$question['id']] ?? '') === (string) $value)
                                    required
                                >
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach

            <div class="yk-ssa__actions">
                <a class="yk-ssa__btn-secondary" href="{{ $previousUrl }}">Previous</a>
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
