@extends('layouts.layout')

@section('title', $category['label'].' | Career Interest Test | YuktiOne')
@section('meta_description', 'Tell us how much you enjoy these '.$category['label'].' activities.')
@section('body_class', 'yk-cit-body')

@section('content')
<main class="yk-cit">
    <div class="yk-cit__wrap">
        <form class="yk-cit__card" method="post" action="{{ route('tools.career-interest.category.store', ['category' => $category['key']]) }}" data-cit-form>
            @csrf
            <div class="yk-cit__progress">
                <span>{{ $step }} of {{ $totalSteps }}</span>
                <span>Career Interest Test</span>
            </div>
            <p class="yk-cit__kicker">{{ $category['kicker'] }}</p>
            <h1>{{ $category['label'] }}</h1>
            <p class="yk-cit__lead">Tell us how much you enjoy these activities.</p>

            @if ($errors->any())
                <p class="yk-cit__error">Please answer every question on this page.</p>
            @endif

            @foreach ($questions as $index => $question)
                <fieldset class="yk-cit__question">
                    <legend class="visually-hidden">Question {{ $index + 1 }}</legend>
                    <p>{{ $index + 1 }}. {{ $question['text'] }}</p>
                    <div class="yk-cit__scale" role="radiogroup" aria-label="Question {{ $index + 1 }}">
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

            <div class="yk-cit__actions">
                <a class="yk-cit__btn-secondary" href="{{ $previousUrl }}">Previous</a>
                <button class="yk-cit__btn" type="submit" data-cit-continue>{{ $nextLabel }}</button>
            </div>
        </form>
    </div>
</main>
<script>
(function () {
    var form = document.querySelector('[data-cit-form]');
    if (!form) return;
    var button = form.querySelector('[data-cit-continue]');
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
